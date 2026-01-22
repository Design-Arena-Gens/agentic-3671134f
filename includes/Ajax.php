<?php
namespace CardFlipGame;

if (!defined('ABSPATH')) {
    exit;
}

class Ajax {

    public function __construct() {
        add_action('wp_ajax_cfg_get_game_state', array($this, 'get_game_state'));
        add_action('wp_ajax_nopriv_cfg_get_game_state', array($this, 'get_game_state'));

        add_action('wp_ajax_cfg_book_card', array($this, 'book_card'));
        add_action('wp_ajax_cfg_request_withdrawal', array($this, 'request_withdrawal'));
        add_action('wp_ajax_cfg_get_user_data', array($this, 'get_user_data'));
    }

    public function get_game_state() {
        check_ajax_referer('cfg_nonce', 'nonce');

        $round_data = Game::get_current_round_data();
        $cards = Game::get_cards();
        $recent_winners = Database::get_recent_winners(3);

        $user_id = get_current_user_id();
        $user_balance = 0;
        if ($user_id) {
            $user_balance = Database::get_user_balance($user_id);
        }

        wp_send_json_success(array(
            'round' => $round_data,
            'cards' => $cards,
            'recent_winners' => $recent_winners,
            'user_balance' => $user_balance,
            'card_cost' => get_option('cfg_card_cost', 10)
        ));
    }

    public function book_card() {
        check_ajax_referer('cfg_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to book cards'));
            return;
        }

        $user_id = get_current_user_id();
        $card_name = isset($_POST['card_name']) ? sanitize_text_field($_POST['card_name']) : '';
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

        if (!in_array($card_name, Game::get_cards())) {
            wp_send_json_error(array('message' => 'Invalid card'));
            return;
        }

        if ($quantity < 1) {
            wp_send_json_error(array('message' => 'Invalid quantity'));
            return;
        }

        $round = Database::get_active_round();
        if (!$round) {
            wp_send_json_error(array('message' => 'No active round'));
            return;
        }

        // Check if round still active
        $now = current_time('timestamp');
        $end_time = strtotime($round->end_time);
        if ($now >= $end_time) {
            wp_send_json_error(array('message' => 'Round has ended'));
            return;
        }

        $card_cost = get_option('cfg_card_cost', 10);
        $total_cost = $card_cost * $quantity;
        $balance = Database::get_user_balance($user_id);

        if ($balance < $total_cost) {
            wp_send_json_error(array('message' => 'Insufficient balance'));
            return;
        }

        $booking_id = Database::create_booking($user_id, $round->id, $card_name, $quantity);

        if ($booking_id) {
            wp_send_json_success(array(
                'message' => 'Booking successful',
                'new_balance' => Database::get_user_balance($user_id)
            ));
        } else {
            wp_send_json_error(array('message' => 'Booking failed'));
        }
    }

    public function request_withdrawal() {
        check_ajax_referer('cfg_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login'));
            return;
        }

        $user_id = get_current_user_id();
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $upi_id = isset($_POST['upi_id']) ? sanitize_text_field($_POST['upi_id']) : '';

        if (empty($upi_id)) {
            wp_send_json_error(array('message' => 'UPI ID is required'));
            return;
        }

        $result = Database::create_withdrawal_request($user_id, $amount, $upi_id);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    public function get_user_data() {
        check_ajax_referer('cfg_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Not logged in'));
            return;
        }

        $user_id = get_current_user_id();

        wp_send_json_success(array(
            'balance' => Database::get_user_balance($user_id),
            'transactions' => Database::get_wallet_transactions($user_id, 10),
            'bookings' => Database::get_user_bookings($user_id, 10),
            'withdrawals' => Database::get_withdrawal_requests($user_id)
        ));
    }
}
