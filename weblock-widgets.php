<?php
/**
 * Plugin Name: Weblock Widgets
 * Plugin URI: https://github.com/weblockmedia/weblock-widgets
 * Description: Google Reviews, Instagram, Facebook, YouTube és Google Maps widgetek egy pluginban — Trustindex és Elfsight kiváltása.
 * Version: 0.6.0
 * Author: Weblock Group
 * Author URI: https://weblockgroup.com
 * License: GPL-2.0-or-later
 * Text Domain: weblock-widgets
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WLW_VERSION', '0.6.0' );
define( 'WLW_FILE', __FILE__ );
define( 'WLW_DIR', plugin_dir_path( __FILE__ ) );
define( 'WLW_URL', plugin_dir_url( __FILE__ ) );
define( 'WLW_BASENAME', plugin_basename( __FILE__ ) );

require_once WLW_DIR . 'includes/Core/Loader.php';

add_action( 'plugins_loaded', function () {
    \WeblockWidgets\Core\Loader::instance()->boot();
} );

if ( file_exists( WLW_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php' ) ) {
    require_once WLW_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';
    add_action( 'init', function () {
        if ( ! class_exists( '\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
            return;
        }
        $update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/weblockmedia/weblock-widgets/',
            WLW_FILE,
            'weblock-widgets'
        );
        $update_checker->setBranch( 'main' );
        $update_checker->getVcsApi()->enableReleaseAssets();

        $token = defined( 'WLW_GITHUB_TOKEN' ) ? WLW_GITHUB_TOKEN : '';
        if ( $token ) {
            $update_checker->setAuthentication( $token );
        }
    } );
}

register_activation_hook( __FILE__, function () {
    if ( ! get_option( 'wlw_settings' ) ) {
        add_option( 'wlw_settings', [
            'google_api_key'   => '',
            'instagram_token'  => '',
            'facebook_token'   => '',
            'youtube_api_key'  => '',
            'cache_ttl'        => 24,
        ] );
    }
} );

register_deactivation_hook( __FILE__, function () {
    wp_clear_scheduled_hook( 'wlw_refresh_caches' );
} );
