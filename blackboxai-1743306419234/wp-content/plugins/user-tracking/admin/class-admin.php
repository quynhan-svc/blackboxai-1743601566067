<?php
namespace UserTracking;

class Admin {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_pages']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
        add_action('admin_post_export_fraud_logs', [__CLASS__, 'export_fraud_logs']);
        add_action('admin_post_user_tracking_update_db', [__CLASS__, 'update_database']);
        add_action('wp_ajax_user_tracking_test_ga4', [__CLASS__, 'test_ga4_connection']);
    }

    public static function update_database() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'user_tracking_update_db_nonce')) {
            wp_die('Invalid request');
        }

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Thêm thông báo đang cập nhật
        set_transient('user_tracking_db_updating', true, 30);
        
        try {
            \UserTracking\Database::install();
            wp_redirect(admin_url('admin.php?page=user-tracking&db_updated=1'));
        } catch (Exception $e) {
            wp_redirect(admin_url('admin.php?page=user-tracking&db_error=1&message=' . urlencode($e->getMessage())));
        }
        exit;
    }

    public static function export_fraud_logs() {
        global $wpdb;
        
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'export_fraud_logs_nonce')) {
            wp_die('Invalid request');
        }

        // Get logs data
        $logs = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}user_tracking_fraud_logs 
            ORDER BY created_at DESC
        ");

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=fraud-logs-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Open output stream
        $output = fopen('php://output', 'w');

        // Write CSV header with all fields
        fputcsv($output, [
            'Date',
            'IP Address',
            'User Agent', 
            'Device Info',
            'Country',
            'City',
            'URLs Accessed',
            'Referrers',
            'Reason',
            'Blocked',
            'Session Duration'
        ]);

        // Write data rows with all fields
        foreach ($logs as $log) {
            // Get session data if available
            $session_data = $log->session_id ? $wpdb->get_row($wpdb->prepare(
                "SELECT device_info, country, city, TIMESTAMPDIFF(SECOND, entry_time, exit_time) as duration 
                 FROM {$wpdb->prefix}user_tracking_sessions 
                 WHERE session_id = %d", 
                $log->session_id
            )) : null;

            fputcsv($output, [
                $log->created_at,
                $log->ip_address,
                $log->user_agent,
                $session_data->device_info ?? 'N/A',
                $session_data->country ?? 'N/A',
                $session_data->city ?? 'N/A',
                $log->urls_accessed ?? 'N/A',
                $log->referrers ?? 'N/A',
                $log->reason,
                $log->is_blocked ? 'Yes' : 'No',
                $session_data->duration ?? 'N/A'
            ]);
        }

        fclose($output);
        exit;
    }

    public static function add_admin_pages() {
        add_menu_page(
            'User Tracking',
            'User Tracking',
            'manage_options',
            'user-tracking',
            [__CLASS__, 'render_dashboard'],
            'dashicons-chart-line',
            30
        );

        add_submenu_page(
            'user-tracking',
            'Tracking Dashboard',
            'Dashboard',
            'manage_options',
            'user-tracking',
            [__CLASS__, 'render_dashboard']
        );

        add_submenu_page(
            'user-tracking',
            'Fraud Detection',
            'Fraud Logs',
            'manage_options',
            'user-tracking-fraud',
            [__CLASS__, 'render_fraud_logs']
        );

        add_submenu_page(
            'user-tracking',
            'Settings',
            'Settings',
            'manage_options',
            'user-tracking-settings',
            [__CLASS__, 'render_settings']
        );
    }

    public static function register_settings() {
        register_setting('user_tracking_settings', 'user_tracking_settings', [
            'sanitize_callback' => [__CLASS__, 'sanitize_settings']
        ]);

        add_settings_section(
            'user_tracking_alert_settings',
            'Alert Settings',
            null,
            'user-tracking-settings'
        );

        add_settings_field(
            'email_alerts',
            'Enable Email Alerts',
            [__CLASS__, 'render_checkbox_field'],
            'user-tracking-settings',
            'user_tracking_alert_settings',
            [
                'name' => 'email_alerts',
                'label' => 'Send email alerts for detected fraud'
            ]
        );

        add_settings_field(
            'alert_email',
            'Alert Email Address',
            [__CLASS__, 'render_text_field'],
            'user-tracking-settings',
            'user_tracking_alert_settings',
            [
                'name' => 'alert_email',
                'placeholder' => 'admin@example.com'
            ]
        );

        add_settings_field(
            'telegram_bot_token',
            'Telegram Bot Token',
            [__CLASS__, 'render_text_field'],
            'user-tracking-settings',
            'user_tracking_alert_settings',
            [
                'name' => 'telegram_bot_token',
                'placeholder' => '123456789:ABC-DEF1234ghIkl-zyx57W2v1u123ew11'
            ]
        );

        add_settings_field(
            'telegram_chat_id',
            'Telegram Chat ID',
            [__CLASS__, 'render_text_field'],
            'user-tracking-settings',
            'user_tracking_alert_settings',
            [
                'name' => 'telegram_chat_id',
                'placeholder' => '-123456789'
            ]
        );

        // Add GA4 settings section
        add_settings_section(
            'user_tracking_ga4_settings',
            'GA4 Integration',
            null,
            'user-tracking-settings'
        );

        add_settings_field(
            'ga4_property_id', 
            'GA4 Property ID',
            [__CLASS__, 'render_text_field'],
            'user-tracking-settings',
            'user_tracking_ga4_settings',
            [
                'name' => 'ga4_property_id',
                'placeholder' => 'G-XXXXXXX or properties/XXXXXXX'
            ]
        );

        add_settings_field(
            'ga4_api_key',
            'GA4 API Key',
            [__CLASS__, 'render_text_field'],
            'user-tracking-settings',
            'user_tracking_ga4_settings',
            [
                'name' => 'ga4_api_key',
                'type' => 'password'
            ]
        );
    }

    public static function enqueue_admin_assets($hook) {
        if (strpos($hook, 'user-tracking') === false) return;

        // Enqueue Chart.js
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            '3.7.1',
            true
        );

        // Enqueue admin CSS
        wp_enqueue_style(
            'user-tracking-admin',
            USER_TRACKING_PLUGIN_URL . 'assets/css/admin.css',
            [],
            USER_TRACKING_VERSION
        );

        // Enqueue admin JS
        wp_enqueue_script(
            'user-tracking-admin',
            USER_TRACKING_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'chart-js'],
            USER_TRACKING_VERSION,
            true
        );
    }

    public static function render_dashboard() {
        include USER_TRACKING_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }

    public static function ajax_load_dashboard() {
        global $wpdb;

        try {
            // Get stats with optimized queries
            $stats = [
                'total_sessions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}user_tracking_sessions LIMIT 1") ?: 0,
                'today_sessions' => $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}user_tracking_sessions WHERE DATE(created_at) = %s LIMIT 1", 
                    current_time('mysql', 1)
                )) ?: 0,
                'fraud_attempts' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}user_tracking_fraud_logs LIMIT 1") ?: 0,
                'top_countries' => $wpdb->get_results(
                    "SELECT country, COUNT(*) as count 
                     FROM {$wpdb->prefix}user_tracking_sessions 
                     WHERE country != '' 
                     GROUP BY country 
                     ORDER BY count DESC 
                     LIMIT 5"
                ) ?: []
            ];

            // Get chart data for last 30 days with complete date range
            $chart_data = [];
            $dates = [];
            
            // Generate all dates for the last 30 days
            for ($i = 29; $i >= 0; $i--) {
                $dates[] = date('Y-m-d', strtotime("-$i days"));
            }
            
            // Get session counts for these dates
            $results = $wpdb->get_results(
                "SELECT DATE(created_at) as date, COUNT(*) as count 
                 FROM {$wpdb->prefix}user_tracking_sessions 
                 WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND NOW()
                 GROUP BY DATE(created_at)"
            ) ?: [];
            
            // Create a map of date => count
            $counts = [];
            foreach ($results as $row) {
                $counts[$row->date] = $row->count;
            }
            
            // Build complete chart data with 0 for dates without sessions
            foreach ($dates as $date) {
                $chart_data[] = [
                    'date' => $date,
                    'count' => $counts[$date] ?? 0
                ];
            }
            
            // Clean up old sessions to prevent data bloat
            $wpdb->query(
                "DELETE FROM {$wpdb->prefix}user_tracking_sessions 
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
            );

            // Prepare response
            wp_send_json_success([
                'stats' => $stats,
                'chart_data' => [
                    'labels' => array_map(function($item) { 
                        return date('M j', strtotime($item['date'])); 
                    }, $chart_data),
                    'values' => array_map(function($item) { 
                        return $item['count']; 
                    }, $chart_data)
                ]
            ]);
        } catch (Exception $e) {
            // Return empty data set when error occurs
            wp_send_json_success([
                'stats' => [
                    'total_sessions' => 0,
                    'today_sessions' => 0,
                    'fraud_attempts' => 0,
                    'top_countries' => []
                ],
                'chart_data' => [
                    'labels' => [],
                    'values' => []
                ]
            ]);
        }
    }

    public static function render_fraud_logs() {
        global $wpdb;

        $fraud_logs = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}user_tracking_fraud_logs 
            ORDER BY created_at DESC 
            LIMIT 100
        ");

        include USER_TRACKING_PLUGIN_DIR . 'admin/partials/fraud-logs.php';
    }

    public static function render_settings() {
        include USER_TRACKING_PLUGIN_DIR . 'admin/partials/settings.php';
    }

    public static function render_text_field($args) {
        $options = get_option('user_tracking_settings');
        $value = $options[$args['name']] ?? '';
        ?>
        <input type="<?php echo esc_attr($args['type'] ?? 'text'); ?>" 
               name="user_tracking_settings[<?php echo esc_attr($args['name']); ?>]" 
               value="<?php echo esc_attr($value); ?>"
               placeholder="<?php echo esc_attr($args['placeholder'] ?? ''); ?>"
               class="regular-text">
        <?php
    }

    public static function test_email_connection() {
        $options = get_option('user_tracking_settings');
        $email = $options['alert_email'] ?? '';
        
        if (empty($email)) {
            wp_send_json_error('No email address configured');
            return;
        }

        $time = current_time('mysql');
        $message = [
            'subject' => '[User Tracking] Fraud Detected',
            'message' => "Fraudulent activity detected:\n\n" .
                         "IP: 192.168.1.1\n" .
                         "User Agent: Test Bot\n" .
                         "Country: Test Country\n" .
                         "Reason: Test Connection\n" .
                         "Time: $time"
        ];

        $result = wp_mail($email, $message['subject'], $message['message'], ['Content-Type: text/plain; charset=UTF-8']);
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to send test email');
        }
    }

    public static function test_ga4_connection() {
        $options = get_option('user_tracking_settings');
        $property_id = $options['ga4_property_id'] ?? '';
        $api_key = $options['ga4_api_key'] ?? '';
        
        if (empty($property_id) || empty($api_key)) {
            wp_send_json_error(
                'Vui lòng nhập đầy đủ GA4 Property ID và API Key trong trang Settings trước khi test.<br>' .
                '1. Vào User Tracking > Settings<br>' .
                '2. Nhập Property ID và API Key<br>' .
                '3. Nhấn Save Settings<br>' .
                '4. Thử lại nút Test Connection'
            );
            return;
        }

        // Validate API key format
        if (!preg_match('/^[A-Za-z0-9_-]{39}$/', $api_key)) {
            wp_send_json_error('API Key không đúng định dạng. Vui lòng kiểm tra lại.');
            return;
        }

        // Standardize property_id format
        if (strpos($property_id, 'properties/') === false) {
            if (strpos($property_id, 'G-') === 0) {
                $property_id = 'properties/' . substr($property_id, 2);
            } else {
                $property_id = 'properties/' . $property_id;
            }
        }

        try {
            $url = "https://analyticsdata.googleapis.com/v1beta/$property_id:runReport";
            
            $args = [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'dateRanges' => [['startDate' => '7daysAgo', 'endDate' => 'today']],
                    'metrics' => [['name' => 'activeUsers']]
                ]),
                'timeout' => 15
            ];

            // For API keys, we add it as query parameter
            $url = add_query_arg(['key' => $api_key], $url);

            $response = wp_remote_post($url, $args);

            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                if (strpos($error_message, 'cURL error 28') !== false) {
                    wp_send_json_error('Request timeout. Please check your internet connection.');
                } else {
                    wp_send_json_error($error_message);
                }
                return;
            }

            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($status_code === 200) {
                wp_send_json_success();
            } else {
                $error_data = json_decode($body, true);
                $error_msg = $error_data['error']['message'] ?? 'Connection failed';
                
                if ($status_code === 403) {
                    $error_msg .= '. Please ensure:';
                    $error_msg .= '<br>- API Key is enabled for Analytics Data API';
                    $error_msg .= '<br>- Property ID is correct';
                    $error_msg .= '<br>- API Key has proper permissions';
                } elseif ($status_code === 404) {
                    $error_msg .= '. Please verify:';
                    $error_msg .= '<br>- Property ID format is correct';
                    $error_msg .= '<br>- Property exists in your GA4 account';
                }
                
                wp_send_json_error($error_msg);
            }
        } catch (Exception $e) {
            wp_send_json_error('Exception: ' . $e->getMessage());
        }
    }

    public static function test_telegram_connection() {
        $options = get_option('user_tracking_settings');
        $bot_token = $options['telegram_bot_token'] ?? '';
        $chat_id = $options['telegram_chat_id'] ?? '';
        
        if (empty($bot_token) || empty($chat_id)) {
            wp_send_json_error('Telegram settings not configured');
            return;
        }

        $time = current_time('mysql');
        $message = "🚨 Fraud Detected 🚨\n" .
                  "IP: 192.168.1.1\n" .
                  "User Agent: Test Bot\n" .
                  "Country: Test Country\n" .
                  "Reason: Test Connection\n" .
                  "Time: $time";

        $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
        $response = wp_remote_post($url, [
            'body' => [
                'chat_id' => $chat_id,
                'text' => $message
            ]
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
            return;
        }

        $body = json_decode($response['body'], true);
        if ($body && $body['ok']) {
            wp_send_json_success();
        } else {
            wp_send_json_error($body['description'] ?? 'Unknown Telegram API error');
        }
    }

    public static function sanitize_settings($input) {
        $output = get_option('user_tracking_settings', []);
        
        // Sanitize Telegram settings
        if (isset($input['telegram_bot_token'])) {
            $output['telegram_bot_token'] = sanitize_text_field($input['telegram_bot_token']);
        }
        if (isset($input['telegram_chat_id'])) {
            $output['telegram_chat_id'] = sanitize_text_field($input['telegram_chat_id']);
        }
        
        // Sanitize GA4 settings
        if (isset($input['ga4_property_id'])) {
            $output['ga4_property_id'] = sanitize_text_field($input['ga4_property_id']);
        }
        if (isset($input['ga4_api_key'])) {
            $output['ga4_api_key'] = sanitize_text_field($input['ga4_api_key']);
        }
        
        // Sanitize email settings
        if (isset($input['alert_email'])) {
            $output['alert_email'] = sanitize_email($input['alert_email']);
        }
        
        // Sanitize checkbox fields
        if (isset($input['email_alerts'])) {
            $output['email_alerts'] = 1;
        } else {
            $output['email_alerts'] = 0;
        }
        
        return $output;
    }

    public static function render_checkbox_field($args) {
        $options = get_option('user_tracking_settings');
        $checked = isset($options[$args['name']]) ? checked(1, $options[$args['name']], false) : '';
        ?>
        <label>
            <input type="checkbox" 
                   name="user_tracking_settings[<?php echo esc_attr($args['name']); ?>]" 
                   value="1" <?php echo $checked; ?>>
            <?php echo esc_html($args['label']); ?>
        </label>
        <?php
    }
}