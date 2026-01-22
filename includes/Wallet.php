<?php
namespace CardFlipGame;

if (!defined('ABSPATH')) {
    exit;
}

class Wallet {

    public function __construct() {
        add_action('woocommerce_thankyou', array($this, 'process_woo_payment'), 10, 1);
        add_filter('woocommerce_account_menu_items', array($this, 'add_wallet_menu_item'));
        add_action('init', array($this, 'add_wallet_endpoint'));
        add_action('woocommerce_account_wallet_endpoint', array($this, 'wallet_endpoint_content'));
    }

    public function add_wallet_endpoint() {
        add_rewrite_endpoint('wallet', EP_ROOT | EP_PAGES);
    }

    public function add_wallet_menu_item($items) {
        $items['wallet'] = __('Wallet', 'card-flip-game');
        return $items;
    }

    public function wallet_endpoint_content() {
        if (!is_user_logged_in()) {
            echo '<p>Please login to view your wallet.</p>';
            return;
        }

        $user_id = get_current_user_id();
        $balance = Database::get_user_balance($user_id);
        $transactions = Database::get_wallet_transactions($user_id, 20);

        include CFG_PLUGIN_DIR . 'templates/wallet-page.php';
    }

    public function process_woo_payment($order_id) {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Check if already processed
        if ($order->get_meta('_cfg_wallet_credited')) {
            return;
        }

        // Only process completed orders
        if ($order->get_status() !== 'completed') {
            return;
        }

        $user_id = $order->get_user_id();
        if (!$user_id) {
            return;
        }

        // Check if order contains wallet recharge product
        // This requires a specific product to be created for wallet recharge
        $total = $order->get_total();
        $min_recharge = get_option('cfg_min_recharge', 50);

        if ($total >= $min_recharge) {
            Database::update_user_balance(
                $user_id,
                $total,
                'credit',
                'Wallet recharge - Order #' . $order_id
            );

            $order->update_meta_data('_cfg_wallet_credited', true);
            $order->save();
        }
    }

    public static function get_balance($user_id) {
        return Database::get_user_balance($user_id);
    }

    public static function credit($user_id, $amount, $reason) {
        return Database::update_user_balance($user_id, abs($amount), 'credit', $reason);
    }

    public static function debit($user_id, $amount, $reason) {
        return Database::update_user_balance($user_id, abs($amount), 'debit', $reason);
    }
}
