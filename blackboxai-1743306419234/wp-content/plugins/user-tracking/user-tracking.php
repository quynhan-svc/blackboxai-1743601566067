<?php
/**
 * Plugin Name: User Tracking for Google Ads
 * Description: Tracks user behavior from Google Ads and detects fraudulent clicks.
 * Version: 1.0.7
 * Requires at least: 6.7.2
 * Requires PHP: 8.0
 * Author: Quỳnh An Solar Nha Trang
 */

defined('ABSPATH') or die('Direct access not allowed!');

// Define plugin constants
define('USER_TRACKING_VERSION', '1.0.1');
define('USER_TRACKING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('USER_TRACKING_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once USER_TRACKING_PLUGIN_DIR . 'includes/class-database.php';
require_once USER_TRACKING_PLUGIN_DIR . 'includes/class-tracker.php';
require_once USER_TRACKING_PLUGIN_DIR . 'includes/class-fraud-detector.php';
require_once USER_TRACKING_PLUGIN_DIR . 'admin/class-admin.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, ['UserTracking\Database', 'install']);
register_deactivation_hook(__FILE__, ['UserTracking\Database', 'uninstall']);

// Initialize the plugin
add_action('plugins_loaded', function() {
    UserTracking\Tracker::init();
    UserTracking\FraudDetector::init();
    
    if (is_admin()) {
        UserTracking\Admin::init();
    }

    // Register AJAX handlers
    add_action('wp_ajax_user_tracking_load_dashboard', ['UserTracking\Admin', 'ajax_load_dashboard']);
    add_action('wp_ajax_user_tracking_test_email', ['UserTracking\Admin', 'test_email_connection']);
    add_action('wp_ajax_user_tracking_test_telegram', ['UserTracking\Admin', 'test_telegram_connection']);
});
