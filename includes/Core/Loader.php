<?php
namespace WeblockWidgets\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Loader {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function boot() {
        spl_autoload_register( [ $this, 'autoload' ] );

        load_plugin_textdomain( 'weblock-widgets', false, dirname( WLW_BASENAME ) . '/languages' );

        Admin::instance()->init();
        ApiCache::instance();

        \WeblockWidgets\Widgets\Reviews\GoogleReviews::instance()->init();
        \WeblockWidgets\Widgets\Social\InstagramFeed::instance()->init();
        \WeblockWidgets\Widgets\Social\FacebookFeed::instance()->init();
        \WeblockWidgets\Widgets\Social\YoutubeGallery::instance()->init();
        \WeblockWidgets\Widgets\Tools\GoogleMaps::instance()->init();

        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function autoload( $class ) {
        if ( strpos( $class, 'WeblockWidgets\\' ) !== 0 ) {
            return;
        }
        $relative = substr( $class, strlen( 'WeblockWidgets\\' ) );
        $path     = WLW_DIR . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';
        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'weblock-widgets',
            WLW_URL . 'assets/css/widgets.css',
            [],
            WLW_VERSION
        );
        wp_enqueue_script(
            'weblock-widgets',
            WLW_URL . 'assets/js/widgets.js',
            [],
            WLW_VERSION,
            true
        );
    }
}
