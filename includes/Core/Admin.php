<?php
namespace WeblockWidgets\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'admin_post_wlw_flush_cache', [ $this, 'handle_flush_cache' ] );
    }

    public function register_menu() {
        add_menu_page(
            __( 'Weblock Widgets', 'weblock-widgets' ),
            __( 'Weblock Widgets', 'weblock-widgets' ),
            'manage_options',
            'weblock-widgets',
            [ $this, 'render_page' ],
            'dashicons-screenoptions',
            58
        );
    }

    public function register_settings() {
        register_setting( 'wlw_settings_group', 'wlw_settings', [
            'sanitize_callback' => [ $this, 'sanitize' ],
            'default'           => [],
        ] );
    }

    public function sanitize( $input ) {
        $out = [];
        $out['google_api_key']  = isset( $input['google_api_key'] )  ? sanitize_text_field( $input['google_api_key'] )  : '';
        $out['instagram_token'] = isset( $input['instagram_token'] ) ? sanitize_text_field( $input['instagram_token'] ) : '';
        $out['facebook_token']  = isset( $input['facebook_token'] )  ? sanitize_text_field( $input['facebook_token'] )  : '';
        $out['facebook_page_id']= isset( $input['facebook_page_id'] )? sanitize_text_field( $input['facebook_page_id'] )  : '';
        $out['youtube_api_key'] = isset( $input['youtube_api_key'] ) ? sanitize_text_field( $input['youtube_api_key'] ) : '';
        $out['cache_ttl']       = isset( $input['cache_ttl'] ) ? max( 1, min( 168, (int) $input['cache_ttl'] ) ) : 24;
        return $out;
    }

    public function enqueue_admin_assets( $hook ) {
        if ( strpos( (string) $hook, 'weblock-widgets' ) === false ) {
            return;
        }
        wp_enqueue_style( 'wlw-admin', WLW_URL . 'assets/css/admin.css', [], WLW_VERSION );
    }

    public function handle_flush_cache() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Nincs jogosultság.', 'weblock-widgets' ) );
        }
        check_admin_referer( 'wlw_flush_cache' );
        ApiCache::instance()->flush_all();
        wp_safe_redirect( add_query_arg( [ 'page' => 'weblock-widgets', 'flushed' => 1 ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function render_page() {
        $settings = wp_parse_args( get_option( 'wlw_settings', [] ), [
            'google_api_key'  => '',
            'instagram_token' => '',
            'facebook_token'  => '',
            'facebook_page_id'=> '',
            'youtube_api_key' => '',
            'cache_ttl'       => 24,
        ] );
        $flushed = ! empty( $_GET['flushed'] );
        ?>
        <div class="wrap wlw-admin">
            <h1><?php esc_html_e( 'Weblock Widgets — Beállítások', 'weblock-widgets' ); ?></h1>

            <?php if ( $flushed ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Cache törölve.', 'weblock-widgets' ); ?></p></div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields( 'wlw_settings_group' ); ?>

                <h2><?php esc_html_e( 'API kulcsok', 'weblock-widgets' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="wlw_google_api_key"><?php esc_html_e( 'Google API kulcs', 'weblock-widgets' ); ?></label></th>
                        <td>
                            <input type="text" id="wlw_google_api_key" name="wlw_settings[google_api_key]" value="<?php echo esc_attr( $settings['google_api_key'] ); ?>" class="regular-text" autocomplete="off" />
                            <p class="description"><?php esc_html_e( 'Places API + Maps Embed API + YouTube Data API. Google Cloud Console-ban generálható, ajánlott HTTP referrer-restrict.', 'weblock-widgets' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wlw_instagram_token"><?php esc_html_e( 'Instagram Access Token', 'weblock-widgets' ); ?></label></th>
                        <td>
                            <input type="text" id="wlw_instagram_token" name="wlw_settings[instagram_token]" value="<?php echo esc_attr( $settings['instagram_token'] ); ?>" class="regular-text" autocomplete="off" />
                            <p class="description"><?php esc_html_e( 'Long-lived user token (Instagram Graph API).', 'weblock-widgets' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wlw_facebook_token"><?php esc_html_e( 'Facebook Page Token', 'weblock-widgets' ); ?></label></th>
                        <td>
                            <input type="text" id="wlw_facebook_token" name="wlw_settings[facebook_token]" value="<?php echo esc_attr( $settings['facebook_token'] ); ?>" class="regular-text" autocomplete="off" />
                            <p class="description"><?php esc_html_e( 'Page access token (long-lived).', 'weblock-widgets' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wlw_facebook_page_id"><?php esc_html_e( 'Facebook Page ID', 'weblock-widgets' ); ?></label></th>
                        <td>
                            <input type="text" id="wlw_facebook_page_id" name="wlw_settings[facebook_page_id]" value="<?php echo esc_attr( $settings['facebook_page_id'] ); ?>" class="regular-text" autocomplete="off" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wlw_youtube_api_key"><?php esc_html_e( 'YouTube API kulcs (opcionális)', 'weblock-widgets' ); ?></label></th>
                        <td>
                            <input type="text" id="wlw_youtube_api_key" name="wlw_settings[youtube_api_key]" value="<?php echo esc_attr( $settings['youtube_api_key'] ); ?>" class="regular-text" autocomplete="off" />
                            <p class="description"><?php esc_html_e( 'Ha üres, a Google API kulcs lesz használva.', 'weblock-widgets' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Cache', 'weblock-widgets' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="wlw_cache_ttl"><?php esc_html_e( 'Cache élettartam (óra)', 'weblock-widgets' ); ?></label></th>
                        <td>
                            <input type="number" min="1" max="168" id="wlw_cache_ttl" name="wlw_settings[cache_ttl]" value="<?php echo esc_attr( $settings['cache_ttl'] ); ?>" class="small-text" />
                            <p class="description"><?php esc_html_e( 'API válaszok cache-elési ideje. Ajánlott: 24 óra.', 'weblock-widgets' ); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr />
            <h2><?php esc_html_e( 'Karbantartás', 'weblock-widgets' ); ?></h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="wlw_flush_cache" />
                <?php wp_nonce_field( 'wlw_flush_cache' ); ?>
                <button type="submit" class="button button-secondary"><?php esc_html_e( 'Cache törlése', 'weblock-widgets' ); ?></button>
            </form>

            <hr />
            <h2><?php esc_html_e( 'Használat', 'weblock-widgets' ); ?></h2>
            <p><?php esc_html_e( 'Shortcode-ok:', 'weblock-widgets' ); ?></p>
            <ul class="wlw-shortcodes">
                <li><code>[wlw_google_reviews place_id="ChIJ..." count="6" layout="grid" min_rating="4"]</code></li>
                <li><code>[wlw_instagram_feed count="9" layout="grid"]</code></li>
                <li><code>[wlw_facebook_feed count="5" layout="list"]</code></li>
                <li><code>[wlw_youtube_gallery channel_id="UC..." count="6"]</code> vagy <code>playlist_id="PL..."</code></li>
                <li><code>[wlw_google_map address="Budapest, Király u. 1." zoom="15" height="400"]</code></li>
            </ul>
        </div>
        <?php
    }
}
