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
                entry_time DATETIME,
                exit_time DATETIME,
                interactions TEXT,
                ad_clicks INT DEFAULT 0,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (session_id),
                KEY idx_ip (ip_address),
                KEY idx_created (created_at),
                KEY idx_entry (entry_time)
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
                session_id BIGINT UNSIGNED,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                urls_accessed TEXT,
                referrers TEXT,
                reason TEXT NOT NULL,
                is_blocked BOOLEAN DEFAULT 0,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (log_id),
                KEY idx_ip (ip_address),
                KEY idx_blocked (is_blocked),
                FOREIGN KEY (session_id) REFERENCES {$wpdb->prefix}user_tracking_sessions(session_id) ON DELETE SET NULL
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}fraud_patterns (
                pattern_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                ip_address VARCHAR(45),
                device_hash VARCHAR(255),
                pattern TEXT NOT NULL,
                last_occurrence DATETIME NOT NULL,
                PRIMARY KEY (pattern_id),
                KEY idx_ip_hash (ip_address, device_hash)
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
    
    public static function check_schema() {
        global $wpdb;
        
        $required_columns = [
            "{$wpdb->prefix}user_tracking_sessions" => ['entry_time', 'exit_time', 'interactions', 'ad_clicks'],
            "{$wpdb->prefix}user_tracking_fraud_logs" => ['session_id', 'urls_accessed', 'referrers'],
        ];
        
        $missing = [];
        
        foreach ($required_columns as $table => $columns) {
            $existing_columns = $wpdb->get_col("DESCRIBE $table", 0);
            
            foreach ($columns as $column) {
                if (!in_array($column, $existing_columns)) {
                    $missing[] = "$table.$column";
                }
            }
        }
        
        // Check if fraud_patterns table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}fraud_patterns'") != $wpdb->prefix.'fraud_patterns') {
            $missing[] = "{$wpdb->prefix}fraud_patterns";
        }
        
        return empty($missing) ? true : $missing;
    }

    public static function uninstall() {
        global $wpdb;
        
        $tables = [
            "{$wpdb->prefix}user_tracking_sessions",
            "{$wpdb->prefix}user_tracking_pageviews",
            "{$wpdb->prefix}user_tracking_fraud_logs",
            "{$wpdb->prefix}fraud_patterns"
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