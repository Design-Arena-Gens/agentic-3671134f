<?php
namespace CardFlipGame;

if (!defined('ABSPATH')) {
    exit;
}

class Shortcodes {

    public function __construct() {
        add_shortcode('card_flip_game', array($this, 'game_shortcode'));
        add_shortcode('card_flip_wallet', array($this, 'wallet_shortcode'));
        add_shortcode('card_flip_dashboard', array($this, 'dashboard_shortcode'));
    }

    public function game_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="cfg-login-required"><p>Please login to play</p></div>';
        }

        ob_start();
        include CFG_PLUGIN_DIR . 'templates/game-page.php';
        return ob_get_clean();
    }

    public function wallet_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="cfg-login-required"><p>Please login to view wallet</p></div>';
        }

        $user_id = get_current_user_id();
        $balance = Database::get_user_balance($user_id);

        ob_start();
        echo '<div class="cfg-wallet-display">';
        echo '<span class="cfg-wallet-label">Wallet Balance:</span> ';
        echo '<span class="cfg-wallet-amount">₹' . number_format($balance, 2) . '</span>';
        echo '</div>';
        return ob_get_clean();
    }

    public function dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="cfg-login-required"><p>Please login to view dashboard</p></div>';
        }

        ob_start();
        include CFG_PLUGIN_DIR . 'templates/dashboard-page.php';
        return ob_get_clean();
    }
}
