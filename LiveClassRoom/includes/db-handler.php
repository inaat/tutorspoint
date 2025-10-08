<?php
if (!defined('ABSPATH')) {
    require_once(__DIR__ . '/../../../wp-load.php');
}

global $wpdb;

class LiveClassroom_Analytics {
    const TABLE = 'wpC_liveclassroom_token_analytics';
    
    public static function log_token($token_data) {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'liveclassroom_token_analytics',
            [
                'token_hash' => hash('sha256', $token_data['token']),
                'user_role' => sanitize_text_field($token_data['role']),
                'room_id' => sanitize_text_field($token_data['room_id']),
                'wp_user_id' => get_current_user_id(),
                'ip_address' => self::get_client_ip(),
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT']),
                'expires_at' => date('Y-m-d H:i:s', $token_data['expires_at']),
                'meta_data' => json_encode([
                    'browser' => self::get_browser(),
                    'os' => self::get_os(),
                    'country' => self::get_country_from_ip()
                ])
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s']
        );
    }
    
    private static function get_client_ip() {
        $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                return sanitize_text_field($_SERVER[$key]);
            }
        }
        return 'unknown';
    }
    
    // ... (Add get_browser(), get_os(), get_country_from_ip() helper methods)
}