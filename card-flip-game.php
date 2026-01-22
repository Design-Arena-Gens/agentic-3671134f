<?php
/**
 * Plugin Name: The Card Flip - Wallet Game
 * Plugin URI: https://example.com/card-flip-game
 * Description: Real-time wallet-based card game with server-authoritative logic
 * Version: 1.0.0
 * Author: Card Flip Team
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: card-flip-game
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CFG_VERSION', '1.0.0');
define('CFG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CFG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CFG_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'CardFlipGame\\';
    $base_dir = CFG_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Main plugin class
final class CardFlipGame {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'init'));
    }

    public function activate() {
        // No output, no external calls during activation
        if (!get_option('cfg_db_version')) {
            add_option('cfg_db_version', CFG_VERSION);
        }

        // Schedule database creation for next request
        set_transient('cfg_needs_db_setup', true, 300);
    }

    public function deactivate() {
        // Cleanup transients
        delete_transient('cfg_needs_db_setup');
    }

    public function init() {
        // Check if database setup is needed
        if (get_transient('cfg_needs_db_setup')) {
            $this->create_tables();
            delete_transient('cfg_needs_db_setup');
        }

        // Initialize components
        if (class_exists('CardFlipGame\\Database')) {
            new CardFlipGame\Database();
        }

        if (class_exists('CardFlipGame\\Wallet')) {
            new CardFlipGame\Wallet();
        }

        if (class_exists('CardFlipGame\\Game')) {
            new CardFlipGame\Game();
        }

        if (class_exists('CardFlipGame\\Admin')) {
            new CardFlipGame\Admin();
        }

        if (class_exists('CardFlipGame\\Shortcodes')) {
            new CardFlipGame\Shortcodes();
        }

        if (class_exists('CardFlipGame\\Ajax')) {
            new CardFlipGame\Ajax();
        }

        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Wallet transactions table
        $table_wallet = $wpdb->prefix . 'cfg_wallet_transactions';
        $sql_wallet = "CREATE TABLE IF NOT EXISTS $table_wallet (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            reason varchar(255) NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_wallet);

        // Withdrawals table
        $table_withdrawals = $wpdb->prefix . 'cfg_withdrawals';
        $sql_withdrawals = "CREATE TABLE IF NOT EXISTS $table_withdrawals (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            upi_id varchar(255) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            request_date datetime DEFAULT CURRENT_TIMESTAMP,
            processed_date datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_withdrawals);

        // Game rounds table
        $table_rounds = $wpdb->prefix . 'cfg_game_rounds';
        $sql_rounds = "CREATE TABLE IF NOT EXISTS $table_rounds (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            start_time datetime NOT NULL,
            end_time datetime NOT NULL,
            winner_card varchar(50) DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_rounds);

        // Bookings table
        $table_bookings = $wpdb->prefix . 'cfg_bookings';
        $sql_bookings = "CREATE TABLE IF NOT EXISTS $table_bookings (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            round_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            card_name varchar(50) NOT NULL,
            quantity int(11) NOT NULL,
            cost decimal(10,2) NOT NULL,
            payout decimal(10,2) DEFAULT 0,
            is_winner tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY round_id (round_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_bookings);

        // User balances table
        $table_balances = $wpdb->prefix . 'cfg_user_balances';
        $sql_balances = "CREATE TABLE IF NOT EXISTS $table_balances (
            user_id bigint(20) NOT NULL,
            balance decimal(10,2) DEFAULT 0,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id)
        ) $charset_collate;";
        dbDelta($sql_balances);

        // Set default options
        $this->set_default_options();
    }

    private function set_default_options() {
        $defaults = array(
            'cfg_round_duration' => 60,
            'cfg_pause_duration' => 5,
            'cfg_card_cost' => 10,
            'cfg_winner_payout' => 40,
            'cfg_min_recharge' => 50,
            'cfg_min_withdrawal' => 200,
            'cfg_card_maruti_normal' => '',
            'cfg_card_maruti_winner' => '',
            'cfg_card_maruti_loss' => '',
            'cfg_card_ganpati_normal' => '',
            'cfg_card_ganpati_winner' => '',
            'cfg_card_ganpati_loss' => '',
            'cfg_card_superman_normal' => '',
            'cfg_card_superman_winner' => '',
            'cfg_card_superman_loss' => '',
            'cfg_card_spiderman_normal' => '',
            'cfg_card_spiderman_winner' => '',
            'cfg_card_spiderman_loss' => '',
        );

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style('cfg-frontend', CFG_PLUGIN_URL . 'assets/css/frontend.css', array(), CFG_VERSION);
        wp_enqueue_script('cfg-frontend', CFG_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), CFG_VERSION, true);

        wp_localize_script('cfg-frontend', 'cfgData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cfg_nonce'),
        ));
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'card-flip-game') === false) {
            return;
        }

        wp_enqueue_style('cfg-admin', CFG_PLUGIN_URL . 'assets/css/admin.css', array(), CFG_VERSION);
        wp_enqueue_script('cfg-admin', CFG_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), CFG_VERSION, true);
        wp_enqueue_media();
    }
}

// Initialize plugin
function card_flip_game() {
    return CardFlipGame::instance();
}

// Start the plugin
card_flip_game();
