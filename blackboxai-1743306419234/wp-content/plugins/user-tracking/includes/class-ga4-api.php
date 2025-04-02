<?php
namespace UserTracking;

class GA4_API {
    private static $api_key;
    private static $property_id;

    public static function init() {
        $options = get_option('user_tracking_settings');
        self::$api_key = $options['ga4_api_key'] ?? '';
        self::$property_id = $options['ga4_property_id'] ?? '';
    }

    public static function fetch_data($start_date, $end_date) {
        if (empty(self::$api_key) || empty(self::$property_id)) {
            return new \WP_Error('missing_credentials', 'GA4 API Key or Property ID not set');
        }

        $url = "https://analyticsdata.googleapis.com/v1beta/properties/" . self::$property_id . ":runReport";
        
        $body = [
            'dateRanges' => [
                ['startDate' => $start_date, 'endDate' => $end_date]
            ],
            'metrics' => [
                ['name' => 'sessions'],
                ['name' => 'activeUsers'],
                ['name' => 'screenPageViews'],
                ['name' => 'bounceRate']
            ],
            'dimensions' => [
                ['name' => 'pageTitle']
            ]
        ];

        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . self::$api_key
            ],
            'body' => json_encode($body)
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        return json_decode($response['body'], true);
    }
}