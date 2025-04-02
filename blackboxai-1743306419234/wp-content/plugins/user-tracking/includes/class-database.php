<?php
namespace UserTracking;

class Database {
    public static function install() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = [
            "CREATE TABLE {$wpdb->prefix}user_tracking_sessions (
                session_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                ip_address VARCHAR(45) NOT NULL,
                country VARCHAR(100),
                city VARCHAR(100),
                user_agent TEXT NOT NULL,
                device_info TEXT,
                referrer TEXT,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (session_id),
                KEY idx_ip (ip_address),
                KEY idx_created (created_at)
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}user_tracking_pageviews (
                view_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                session_id BIGINT UNSIGNED NOT NULL,
                page_url TEXT NOT NULL,
                time_on_page INT,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (view_id),
                KEY idx_session (session_id),
                FOREIGN KEY (session_id) REFERENCES {$wpdb->prefix}user_tracking_sessions(session_id) ON DELETE CASCADE
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}user_tracking_fraud_logs (
                log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                reason TEXT NOT NULL,
                is_blocked BOOLEAN DEFAULT 0,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (log_id),
                KEY idx_ip (ip_address),
                KEY idx_blocked (is_blocked)
            ) $charset_collate;"
        ];
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        
        // Schedule cron job for fraud detection
        if (!wp_next_scheduled('user_tracking_fraud_detection')) {
            wp_schedule_event(time(), 'hourly', 'user_tracking_fraud_detection');
        }

        // Save installation date
        update_option('user_tracking_install_date', current_time('mysql'));
    }
    
    public static function uninstall() {
        global $wpdb;
        
        $tables = [
            "{$wpdb->prefix}user_tracking_sessions",
            "{$wpdb->prefix}user_tracking_pageviews",
            "{$wpdb->prefix}user_tracking_fraud_logs"
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Clear scheduled cron job
        wp_clear_scheduled_hook('user_tracking_fraud_detection');

        // Remove options
        delete_option('user_tracking_settings');
        delete_option('user_tracking_install_date');
        delete_option('user_tracking_last_purge');
    }
}