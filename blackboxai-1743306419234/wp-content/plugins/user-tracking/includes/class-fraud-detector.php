<?php
namespace UserTracking;

class FraudDetector {
    public static function init() {
        add_action('user_tracking_fraud_detection', [__CLASS__, 'run_detection']);
    }

    public static function run_detection() {
        global $wpdb;

        // 1. Detect high click rates from same IP
        $high_click_ips = $wpdb->get_results("
            SELECT ip_address, COUNT(*) as click_count 
            FROM {$wpdb->prefix}user_tracking_sessions 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
            GROUP BY ip_address 
            HAVING click_count > 50
        ");

        foreach ($high_click_ips as $ip) {
            self::log_fraud($ip->ip_address, "High click rate: {$ip->click_count} clicks in 10 minutes");
            self::send_alert("High click rate detected from IP: {$ip->ip_address} ({$ip->click_count} clicks)");
        }

        // 2. Detect same User-Agent with different IPs
        $suspicious_agents = $wpdb->get_results("
            SELECT user_agent, COUNT(DISTINCT ip_address) as ip_count 
            FROM {$wpdb->prefix}user_tracking_sessions 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY user_agent 
            HAVING ip_count > 5
        ");

        foreach ($suspicious_agents as $agent) {
            self::log_fraud($agent->user_agent, "Same User-Agent from {$agent->ip_count} different IPs");
            self::send_alert("Suspicious User-Agent: {$agent->user_agent} used from {$agent->ip_count} IPs");
        }

        // 3. Detect same device info with different IPs
        $suspicious_devices = $wpdb->get_results("
            SELECT device_info, COUNT(DISTINCT ip_address) as ip_count 
            FROM {$wpdb->prefix}user_tracking_sessions 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY device_info 
            HAVING ip_count > 3 AND device_info != ''
        ");

        foreach ($suspicious_devices as $device) {
            $device_info = maybe_unserialize($device->device_info);
            self::log_fraud($device_info, "Same device from {$device->ip_count} different IPs");
            self::send_alert("Suspicious device: " . json_encode($device_info) . " used from {$device->ip_count} IPs");
        }
    }

    private static function log_fraud($identifier, $reason) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'user_tracking_fraud_logs',
            [
                'ip_address' => is_string($identifier) ? $identifier : '',
                'user_agent' => is_string($identifier) ? $identifier : '',
                'reason' => $reason,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s']
        );
    }

    public static function get_alert_message($fraud_data) {
        $time = current_time('mysql');
        $ip = $fraud_data['ip'] ?? 'Unknown';
        $ua = $fraud_data['user_agent'] ?? 'Unknown';
        $country = $fraud_data['country'] ?? 'Unknown';
        $reason = $fraud_data['reason'] ?? 'Suspicious activity';

        // Email content
        $email = [
            'subject' => '[User Tracking] Fraud Detected',
            'message' => "Fraudulent activity detected:\n\n" .
                         "IP: $ip\n" .
                         "User Agent: $ua\n" .
                         "Country: $country\n" .
                         "Reason: $reason\n" .
                         "Time: $time"
        ];

        // Telegram content
        $telegram = "🚨 Fraud Detected 🚨\n" .
                   "IP: $ip\n" .
                   "User Agent: $ua\n" .
                   "Country: $country\n" .
                   "Reason: $reason\n" .
                   "Time: $time";

        return [
            'email' => $email,
            'telegram' => $telegram
        ];
    }

    private static function send_alert($fraud_data) {
        $options = get_option('user_tracking_settings');
        $message = self::get_alert_message($fraud_data);

        // Send email alert if enabled
        if (!empty($options['email_alerts']) && !empty($options['alert_email'])) {
            wp_mail(
                $options['alert_email'],
                $message['email']['subject'],
                $message['email']['message'],
                ['Content-Type: text/plain; charset=UTF-8']
            );
        }

        // Send Telegram alert if configured
        if (!empty($options['telegram_bot_token']) && !empty($options['telegram_chat_id'])) {
            wp_remote_post(
                "https://api.telegram.org/bot{$options['telegram_bot_token']}/sendMessage",
                [
                    'body' => [
                        'chat_id' => $options['telegram_chat_id'],
                        'text' => $message['telegram']
                    ]
                ]
            );
        }
    }
}