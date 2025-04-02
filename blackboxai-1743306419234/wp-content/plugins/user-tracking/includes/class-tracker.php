<?php
namespace UserTracking;

class Tracker {
    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        add_action('wp_ajax_user_tracking_log', [__CLASS__, 'log_visit']);
        add_action('wp_ajax_nopriv_user_tracking_log', [__CLASS__, 'log_visit']);
    }

    public static function enqueue_scripts() {
        wp_enqueue_script(
            'user-tracking',
            USER_TRACKING_PLUGIN_URL . 'assets/js/tracker.js',
            [],
            USER_TRACKING_VERSION,
            true
        );

        wp_localize_script('user-tracking', 'userTracking', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('user-tracking-nonce')
        ]);
    }

    public static function log_visit() {
        check_ajax_referer('user-tracking-nonce', 'security');

        global $wpdb;

        $data = [
            'ip_address' => self::get_client_ip(),
            'country' => sanitize_text_field($_POST['country'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'user_agent' => sanitize_text_field($_POST['user_agent'] ?? ''),
            'device_info' => maybe_serialize($_POST['device_info'] ?? []),
            'referrer' => esc_url_raw($_POST['referrer'] ?? ''),
            'created_at' => current_time('mysql')
        ];

        $wpdb->insert(
            $wpdb->prefix . 'user_tracking_sessions',
            $data,
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        $session_id = $wpdb->insert_id;

        if ($session_id && isset($_POST['page_url'])) {
            $wpdb->insert(
                $wpdb->prefix . 'user_tracking_pageviews',
                [
                    'session_id' => $session_id,
                    'page_url' => esc_url_raw($_POST['page_url']),
                    'time_on_page' => absint($_POST['time_on_page'] ?? 0),
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%s', '%d', '%s']
            );
        }

        wp_send_json_success();
    }

    private static function get_client_ip() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']);
        }
        return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
    }
}