<?php
namespace WeblockWidgets\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ApiCache {
    private static $instance = null;
    private $prefix = 'wlw_cache_';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get( $key ) {
        return get_transient( $this->prefix . md5( $key ) );
    }

    public function set( $key, $value, $ttl_hours = null ) {
        if ( null === $ttl_hours ) {
            $settings  = get_option( 'wlw_settings', [] );
            $ttl_hours = isset( $settings['cache_ttl'] ) ? (int) $settings['cache_ttl'] : 24;
        }
        set_transient( $this->prefix . md5( $key ), $value, $ttl_hours * HOUR_IN_SECONDS );
    }

    public function fetch( $url, $args = [], $ttl_hours = null ) {
        $cache_key = $url . wp_json_encode( $args );
        $cached    = $this->get( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $defaults = [
            'timeout'     => 15,
            'redirection' => 3,
            'headers'     => [ 'Accept' => 'application/json' ],
        ];
        $response = wp_remote_get( $url, wp_parse_args( $args, $defaults ) );

        if ( is_wp_error( $response ) ) {
            return new \WP_Error( 'wlw_http_error', $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $code < 200 || $code >= 300 ) {
            return new \WP_Error( 'wlw_http_status', sprintf( 'HTTP %d: %s', $code, wp_strip_all_tags( $body ) ) );
        }

        $decoded = json_decode( $body, true );
        if ( null === $decoded && JSON_ERROR_NONE !== json_last_error() ) {
            return new \WP_Error( 'wlw_json_error', 'JSON decode failed: ' . json_last_error_msg() );
        }

        $this->set( $cache_key, $decoded, $ttl_hours );
        return $decoded;
    }

    public function flush_all() {
        global $wpdb;
        $like = $wpdb->esc_like( '_transient_' . $this->prefix ) . '%';
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
        $like_t = $wpdb->esc_like( '_transient_timeout_' . $this->prefix ) . '%';
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like_t ) );
    }
}
