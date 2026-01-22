<?php
namespace CardFlipGame;

if (!defined('ABSPATH')) {
    exit;
}

class Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_cfg_manual_wallet_action', array($this, 'handle_manual_wallet_action'));
        add_action('admin_post_cfg_process_withdrawal', array($this, 'handle_withdrawal_action'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Card Flip Game',
            'Card Flip Game',
            'manage_options',
            'card-flip-game',
            array($this, 'settings_page'),
            'dashicons-games',
            30
        );

        add_submenu_page(
            'card-flip-game',
            'Game Settings',
            'Settings',
            'manage_options',
            'card-flip-game',
            array($this, 'settings_page')
        );

        add_submenu_page(
            'card-flip-game',
            'Card Images',
            'Card Images',
            'manage_options',
            'card-flip-game-images',
            array($this, 'images_page')
        );

        add_submenu_page(
            'card-flip-game',
            'Wallet Management',
            'Wallet Management',
            'manage_options',
            'card-flip-game-wallet',
            array($this, 'wallet_page')
        );

        add_submenu_page(
            'card-flip-game',
            'Withdrawals',
            'Withdrawals',
            'manage_options',
            'card-flip-game-withdrawals',
            array($this, 'withdrawals_page')
        );

        add_submenu_page(
            'card-flip-game',
            'Game History',
            'Game History',
            'manage_options',
            'card-flip-game-history',
            array($this, 'history_page')
        );
    }

    public function register_settings() {
        register_setting('cfg_settings_group', 'cfg_round_duration');
        register_setting('cfg_settings_group', 'cfg_pause_duration');
        register_setting('cfg_settings_group', 'cfg_card_cost');
        register_setting('cfg_settings_group', 'cfg_winner_payout');
        register_setting('cfg_settings_group', 'cfg_min_recharge');
        register_setting('cfg_settings_group', 'cfg_min_withdrawal');

        // Card images
        $cards = array('maruti', 'ganpati', 'superman', 'spiderman');
        $types = array('normal', 'winner', 'loss');

        foreach ($cards as $card) {
            foreach ($types as $type) {
                register_setting('cfg_images_group', 'cfg_card_' . $card . '_' . $type);
            }
        }
    }

    public function settings_page() {
        include CFG_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    public function images_page() {
        include CFG_PLUGIN_DIR . 'templates/admin-images.php';
    }

    public function wallet_page() {
        include CFG_PLUGIN_DIR . 'templates/admin-wallet.php';
    }

    public function withdrawals_page() {
        include CFG_PLUGIN_DIR . 'templates/admin-withdrawals.php';
    }

    public function history_page() {
        include CFG_PLUGIN_DIR . 'templates/admin-history.php';
    }

    public function handle_manual_wallet_action() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('cfg_manual_wallet_action');

        $user_identifier = sanitize_text_field($_POST['user_identifier']);
        $amount = floatval($_POST['amount']);
        $action_type = sanitize_text_field($_POST['action_type']);
        $reason = sanitize_text_field($_POST['reason']);

        // Get user by email or ID
        if (is_numeric($user_identifier)) {
            $user = get_user_by('id', $user_identifier);
        } else {
            $user = get_user_by('email', $user_identifier);
        }

        if (!$user) {
            wp_redirect(add_query_arg(array('page' => 'card-flip-game-wallet', 'error' => 'user_not_found'), admin_url('admin.php')));
            exit;
        }

        $result = Database::update_user_balance($user->ID, $amount, $action_type, $reason);

        if ($result) {
            wp_redirect(add_query_arg(array('page' => 'card-flip-game-wallet', 'success' => '1'), admin_url('admin.php')));
        } else {
            wp_redirect(add_query_arg(array('page' => 'card-flip-game-wallet', 'error' => 'insufficient_balance'), admin_url('admin.php')));
        }
        exit;
    }

    public function handle_withdrawal_action() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('cfg_process_withdrawal');

        $withdrawal_id = intval($_POST['withdrawal_id']);
        $action = sanitize_text_field($_POST['withdrawal_action']);

        $status = ($action === 'approve') ? 'approved' : 'rejected';

        Database::process_withdrawal($withdrawal_id, $status);

        wp_redirect(add_query_arg(array('page' => 'card-flip-game-withdrawals', 'success' => '1'), admin_url('admin.php')));
        exit;
    }
}
