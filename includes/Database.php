<?php
namespace CardFlipGame;

if (!defined('ABSPATH')) {
    exit;
}

class Database {

    public function __construct() {
        // Database methods are called directly when needed
    }

    public static function get_user_balance($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'cfg_user_balances';

        $balance = $wpdb->get_var($wpdb->prepare(
            "SELECT balance FROM $table WHERE user_id = %d",
            $user_id
        ));

        if ($balance === null) {
            // Initialize balance for new user
            $wpdb->insert($table, array(
                'user_id' => $user_id,
                'balance' => 0
            ));
            return 0;
        }

        return floatval($balance);
    }

    public static function update_user_balance($user_id, $amount, $type, $reason) {
        global $wpdb;
        $table_balance = $wpdb->prefix . 'cfg_user_balances';
        $table_trans = $wpdb->prefix . 'cfg_wallet_transactions';

        // Get current balance
        $current_balance = self::get_user_balance($user_id);

        // Calculate new balance
        if ($type === 'credit') {
            $new_balance = $current_balance + abs($amount);
        } else {
            $new_balance = $current_balance - abs($amount);
        }

        // Never allow negative balance
        if ($new_balance < 0) {
            return false;
        }

        // Update balance
        $wpdb->replace($table_balance, array(
            'user_id' => $user_id,
            'balance' => $new_balance
        ));

        // Record transaction
        $wpdb->insert($table_trans, array(
            'user_id' => $user_id,
            'type' => $type,
            'amount' => abs($amount),
            'reason' => sanitize_text_field($reason),
            'timestamp' => current_time('mysql')
        ));

        return true;
    }

    public static function get_wallet_transactions($user_id, $limit = 50) {
        global $wpdb;
        $table = $wpdb->prefix . 'cfg_wallet_transactions';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY timestamp DESC LIMIT %d",
            $user_id,
            $limit
        ));

        return $results ? $results : array();
    }

    public static function create_withdrawal_request($user_id, $amount, $upi_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'cfg_withdrawals';

        $min_withdrawal = get_option('cfg_min_withdrawal', 200);

        if ($amount < $min_withdrawal) {
            return array('success' => false, 'message' => 'Minimum withdrawal is ₹' . $min_withdrawal);
        }

        $balance = self::get_user_balance($user_id);
        if ($balance < $amount) {
            return array('success' => false, 'message' => 'Insufficient balance');
        }

        // Create withdrawal request
        $inserted = $wpdb->insert($table, array(
            'user_id' => $user_id,
            'amount' => $amount,
            'upi_id' => sanitize_text_field($upi_id),
            'status' => 'pending',
            'request_date' => current_time('mysql')
        ));

        if ($inserted) {
            return array('success' => true, 'message' => 'Withdrawal request submitted');
        }

        return array('success' => false, 'message' => 'Failed to create request');
    }

    public static function get_withdrawal_requests($user_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'cfg_withdrawals';

        if ($user_id) {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d ORDER BY request_date DESC",
                $user_id
            ));
        } else {
            $results = $wpdb->get_results(
                "SELECT w.*, u.user_email, u.display_name
                FROM $table w
                LEFT JOIN {$wpdb->users} u ON w.user_id = u.ID
                ORDER BY request_date DESC"
            );
        }

        return $results ? $results : array();
    }

    public static function process_withdrawal($withdrawal_id, $status) {
        global $wpdb;
        $table = $wpdb->prefix . 'cfg_withdrawals';

        $withdrawal = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $withdrawal_id
        ));

        if (!$withdrawal || $withdrawal->status !== 'pending') {
            return false;
        }

        // Update withdrawal status
        $wpdb->update(
            $table,
            array(
                'status' => $status,
                'processed_date' => current_time('mysql')
            ),
            array('id' => $withdrawal_id)
        );

        // If approved, deduct from wallet
        if ($status === 'approved') {
            self::update_user_balance(
                $withdrawal->user_id,
                $withdrawal->amount,
                'debit',
                'Withdrawal approved - UPI: ' . $withdrawal->upi_id
            );
        }

        return true;
    }

    public static function get_active_round() {
        global $wpdb;
        $table = $wpdb->prefix . 'cfg_game_rounds';

        $now = current_time('mysql');

        $round = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE status = 'active' AND end_time > %s ORDER BY id DESC LIMIT 1",
            $now
        ));

        return $round;
    }

    public static function create_new_round() {
        global $wpdb;
        $table = $wpdb->prefix . 'cfg_game_rounds';

        $duration = get_option('cfg_round_duration', 60);
        $start_time = current_time('mysql');
        $end_time = date('Y-m-d H:i:s', strtotime($start_time) + $duration);

        $wpdb->insert($table, array(
            'start_time' => $start_time,
            'end_time' => $end_time,
            'status' => 'active',
            'created_at' => $start_time
        ));

        return $wpdb->insert_id;
    }

    public static function complete_round($round_id, $winner_card) {
        global $wpdb;
        $table_rounds = $wpdb->prefix . 'cfg_game_rounds';
        $table_bookings = $wpdb->prefix . 'cfg_bookings';

        // Update round
        $wpdb->update(
            $table_rounds,
            array(
                'status' => 'completed',
                'winner_card' => $winner_card
            ),
            array('id' => $round_id)
        );

        // Update bookings and process payouts
        $winner_payout = get_option('cfg_winner_payout', 40);

        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_bookings WHERE round_id = %d AND card_name = %s",
            $round_id,
            $winner_card
        ));

        foreach ($bookings as $booking) {
            $payout = $booking->quantity * $winner_payout;

            // Update booking
            $wpdb->update(
                $table_bookings,
                array(
                    'is_winner' => 1,
                    'payout' => $payout
                ),
                array('id' => $booking->id)
            );

            // Credit user wallet
            self::update_user_balance(
                $booking->user_id,
                $payout,
                'credit',
                "Round #{$round_id} win - {$booking->card_name} x{$booking->quantity}"
            );
        }

        return true;
    }

    public static function create_booking($user_id, $round_id, $card_name, $quantity) {
        global $wpdb;
        $table = $wpdb->prefix . 'cfg_bookings';

        $card_cost = get_option('cfg_card_cost', 10);
        $total_cost = $card_cost * $quantity;

        // Deduct from wallet
        $deducted = self::update_user_balance(
            $user_id,
            $total_cost,
            'debit',
            "Booked {$card_name} x{$quantity} - Round #{$round_id}"
        );

        if (!$deducted) {
            return false;
        }

        // Create booking
        $wpdb->insert($table, array(
            'round_id' => $round_id,
            'user_id' => $user_id,
            'card_name' => $card_name,
            'quantity' => $quantity,
            'cost' => $total_cost,
            'created_at' => current_time('mysql')
        ));

        return $wpdb->insert_id;
    }

    public static function get_round_bookings($round_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'cfg_bookings';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT card_name, SUM(quantity) as total_quantity
            FROM $table
            WHERE round_id = %d
            GROUP BY card_name",
            $round_id
        ));

        return $results ? $results : array();
    }

    public static function get_user_bookings($user_id, $limit = 50) {
        global $wpdb;
        $table_bookings = $wpdb->prefix . 'cfg_bookings';
        $table_rounds = $wpdb->prefix . 'cfg_game_rounds';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, r.winner_card, r.status as round_status
            FROM $table_bookings b
            LEFT JOIN $table_rounds r ON b.round_id = r.id
            WHERE b.user_id = %d
            ORDER BY b.created_at DESC
            LIMIT %d",
            $user_id,
            $limit
        ));

        return $results ? $results : array();
    }

    public static function get_recent_winners($limit = 3) {
        global $wpdb;
        $table = $wpdb->prefix . 'cfg_game_rounds';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT id, winner_card, end_time
            FROM $table
            WHERE status = 'completed' AND winner_card IS NOT NULL
            ORDER BY end_time DESC
            LIMIT %d",
            $limit
        ));

        return $results ? $results : array();
    }
}
