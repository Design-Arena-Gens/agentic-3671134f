<?php
namespace CardFlipGame;

if (!defined('ABSPATH')) {
    exit;
}

class Game {

    private static $cards = array('Maruti', 'Ganpati', 'Superman', 'Spiderman');

    public function __construct() {
        add_action('init', array($this, 'check_round_status'));
    }

    public function check_round_status() {
        $active_round = Database::get_active_round();

        if (!$active_round) {
            // No active round, check if we should start new one
            $this->start_new_round_if_needed();
            return;
        }

        // Check if round has ended
        $now = current_time('timestamp');
        $end_time = strtotime($active_round->end_time);

        if ($now >= $end_time) {
            // Round ended, process it
            $this->process_round_end($active_round->id);
        }
    }

    private function start_new_round_if_needed() {
        global $wpdb;
        $table = $wpdb->prefix . 'cfg_game_rounds';

        // Get last completed round
        $last_round = $wpdb->get_row(
            "SELECT * FROM $table WHERE status = 'completed' ORDER BY id DESC LIMIT 1"
        );

        if (!$last_round) {
            // First round ever
            Database::create_new_round();
            return;
        }

        // Check pause duration
        $pause_duration = get_option('cfg_pause_duration', 5);
        $now = current_time('timestamp');
        $last_end = strtotime($last_round->end_time);

        if ($now >= ($last_end + $pause_duration)) {
            Database::create_new_round();
        }
    }

    private function process_round_end($round_id) {
        // Check if already processed
        global $wpdb;
        $table = $wpdb->prefix . 'cfg_game_rounds';

        $round = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $round_id
        ));

        if ($round->status === 'completed') {
            return;
        }

        // Determine winner
        $winner = $this->determine_winner($round_id);

        // Complete round and process payouts
        Database::complete_round($round_id, $winner);
    }

    private function determine_winner($round_id) {
        $bookings = Database::get_round_bookings($round_id);

        if (empty($bookings)) {
            // No bookings, random winner
            return self::$cards[array_rand(self::$cards)];
        }

        // Find card with least bookings
        $card_totals = array();
        foreach (self::$cards as $card) {
            $card_totals[$card] = 0;
        }

        foreach ($bookings as $booking) {
            if (isset($card_totals[$booking->card_name])) {
                $card_totals[$booking->card_name] = intval($booking->total_quantity);
            }
        }

        // Get minimum value
        $min_bookings = min($card_totals);

        // Get all cards with minimum bookings
        $candidates = array();
        foreach ($card_totals as $card => $total) {
            if ($total === $min_bookings) {
                $candidates[] = $card;
            }
        }

        // If multiple cards have same minimum, pick random
        return $candidates[array_rand($candidates)];
    }

    public static function get_current_round_data() {
        $round = Database::get_active_round();

        if (!$round) {
            return array(
                'status' => 'waiting',
                'message' => 'Next game starting soon...',
                'time_remaining' => 0
            );
        }

        $now = current_time('timestamp');
        $end_time = strtotime($round->end_time);
        $time_remaining = max(0, $end_time - $now);

        if ($time_remaining === 0) {
            return array(
                'status' => 'ended',
                'round_id' => $round->id,
                'winner_card' => $round->winner_card,
                'time_remaining' => 0
            );
        }

        return array(
            'status' => 'active',
            'round_id' => $round->id,
            'time_remaining' => $time_remaining,
            'end_time' => $round->end_time,
            'bookings' => Database::get_round_bookings($round->id)
        );
    }

    public static function get_cards() {
        return self::$cards;
    }

    public static function get_card_images($card_name) {
        $card_key = strtolower($card_name);
        return array(
            'normal' => get_option('cfg_card_' . $card_key . '_normal', ''),
            'winner' => get_option('cfg_card_' . $card_key . '_winner', ''),
            'loss' => get_option('cfg_card_' . $card_key . '_loss', '')
        );
    }
}
