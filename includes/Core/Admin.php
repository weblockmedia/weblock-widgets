<?php
namespace WeblockWidgets\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {
    private static $instance = null;
    private $widgets_cache = null;

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
        add_action( 'wp_ajax_wlw_preview', [ $this, 'ajax_preview' ] );
        add_action( 'wp_ajax_wlw_search_place', [ $this, 'ajax_search_place' ] );
    }

    public function ajax_search_place() {
        check_ajax_referer( 'wlw_preview', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
        }
        $query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
        if ( strlen( $query ) < 3 ) {
            wp_send_json_error( [ 'message' => 'too short' ], 400 );
        }
        $settings = get_option( 'wlw_settings', [] );
        $key = $settings['google_api_key'] ?? '';
        if ( ! $key ) {
            wp_send_json_error( [ 'message' => __( 'Hiányzó Google API kulcs.', 'weblock-widgets' ) ], 400 );
        }

        $url = add_query_arg( [
            'query'    => $query,
            'language' => 'hu',
            'key'      => $key,
        ], 'https://maps.googleapis.com/maps/api/place/textsearch/json' );

        $response = wp_remote_get( $url, [ 'timeout' => 15 ] );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( [ 'message' => $response->get_error_message() ], 500 );
        }
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $data ) ) {
            wp_send_json_error( [ 'message' => 'invalid response' ], 500 );
        }
        if ( isset( $data['status'] ) && 'OK' !== $data['status'] && 'ZERO_RESULTS' !== $data['status'] ) {
            wp_send_json_error( [ 'message' => $data['status'] . ': ' . ( $data['error_message'] ?? '' ) ], 400 );
        }
        $results = [];
        foreach ( ( $data['results'] ?? [] ) as $r ) {
            $results[] = [
                'place_id' => $r['place_id'] ?? '',
                'name'     => $r['name'] ?? '',
                'address'  => $r['formatted_address'] ?? '',
                'rating'   => isset( $r['rating'] ) ? (float) $r['rating'] : 0,
                'count'    => isset( $r['user_ratings_total'] ) ? (int) $r['user_ratings_total'] : 0,
            ];
            if ( count( $results ) >= 5 ) break;
        }
        wp_send_json_success( [ 'results' => $results ] );
    }

    public function register_menu() {
        add_menu_page(
            __( 'Weblock Widgets', 'weblock-widgets' ),
            __( 'Weblock Widgets', 'weblock-widgets' ),
            'manage_options',
            'weblock-widgets',
            [ $this, 'render_widgets_page' ],
            'dashicons-screenoptions',
            58
        );
        add_submenu_page(
            'weblock-widgets',
            __( 'Widgetek', 'weblock-widgets' ),
            __( 'Widgetek', 'weblock-widgets' ),
            'manage_options',
            'weblock-widgets',
            [ $this, 'render_widgets_page' ]
        );
        add_submenu_page(
            'weblock-widgets',
            __( 'Beállítások', 'weblock-widgets' ),
            __( 'Beállítások', 'weblock-widgets' ),
            'manage_options',
            'weblock-widgets-settings',
            [ $this, 'render_settings_page' ]
        );
        add_submenu_page(
            'weblock-widgets',
            __( 'Súgó', 'weblock-widgets' ),
            __( 'Súgó', 'weblock-widgets' ),
            'manage_options',
            'weblock-widgets-help',
            [ $this, 'render_help_page' ]
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
        wp_enqueue_style( 'wlw-frontend', WLW_URL . 'assets/css/widgets.css', [], WLW_VERSION );
        wp_enqueue_script( 'wlw-admin', WLW_URL . 'assets/js/admin.js', [ 'wp-i18n' ], WLW_VERSION, true );
        wp_localize_script( 'wlw-admin', 'WLW_ADMIN', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'wlw_preview' ),
            'i18n'    => [
                'copied'      => __( 'Másolva!', 'weblock-widgets' ),
                'copyShort'   => __( 'Shortcode másolása', 'weblock-widgets' ),
                'loading'     => __( 'Előnézet betöltése…', 'weblock-widgets' ),
                'previewErr'  => __( 'Hiba az előnézet betöltésekor.', 'weblock-widgets' ),
                'missingReq'  => __( 'Töltsd ki a kötelező mezőket.', 'weblock-widgets' ),
            ],
        ] );
    }

    public function handle_flush_cache() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Nincs jogosultság.', 'weblock-widgets' ) );
        }
        check_admin_referer( 'wlw_flush_cache' );
        ApiCache::instance()->flush_all();
        wp_safe_redirect( add_query_arg( [ 'page' => 'weblock-widgets-settings', 'flushed' => 1 ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function ajax_preview() {
        check_ajax_referer( 'wlw_preview', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
        }
        $shortcode = isset( $_POST['shortcode'] ) ? wp_unslash( $_POST['shortcode'] ) : '';
        if ( ! $shortcode || strpos( $shortcode, '[wlw_' ) !== 0 ) {
            wp_send_json_error( [ 'message' => 'invalid shortcode' ], 400 );
        }
        $rendered = do_shortcode( $shortcode );
        wp_send_json_success( [ 'html' => $rendered ] );
    }

    private function get_all_widgets() {
        if ( null !== $this->widgets_cache ) {
            return $this->widgets_cache;
        }
        $this->widgets_cache = [
            \WeblockWidgets\Widgets\Reviews\GoogleReviews::instance()->get_meta(),
            \WeblockWidgets\Widgets\Social\InstagramFeed::instance()->get_meta(),
            \WeblockWidgets\Widgets\Social\FacebookFeed::instance()->get_meta(),
            \WeblockWidgets\Widgets\Social\YoutubeGallery::instance()->get_meta(),
            \WeblockWidgets\Widgets\Tools\GoogleMaps::instance()->get_meta(),
        ];
        return $this->widgets_cache;
    }

    private function api_keys_status() {
        $s = get_option( 'wlw_settings', [] );
        return [
            'google'   => ! empty( $s['google_api_key'] ),
            'ig'       => ! empty( $s['instagram_token'] ),
            'fb'       => ! empty( $s['facebook_token'] ) && ! empty( $s['facebook_page_id'] ),
            'youtube'  => ! empty( $s['youtube_api_key'] ) || ! empty( $s['google_api_key'] ),
            'maps'     => ! empty( $s['google_api_key'] ),
        ];
    }

    private function widget_api_status( $widget_id ) {
        $s = $this->api_keys_status();
        switch ( $widget_id ) {
            case 'wlw_google_reviews':  return $s['google'];
            case 'wlw_instagram_feed':  return $s['ig'];
            case 'wlw_facebook_feed':   return $s['fb'];
            case 'wlw_youtube_gallery': return $s['youtube'];
            case 'wlw_google_map':      return $s['maps'];
        }
        return false;
    }

    public function render_widgets_page() {
        $widgets   = $this->get_all_widgets();
        $selected  = isset( $_GET['widget'] ) ? sanitize_key( $_GET['widget'] ) : '';
        $widget    = null;
        foreach ( $widgets as $w ) {
            if ( $w['id'] === $selected ) { $widget = $w; break; }
        }
        ?>
        <div class="wrap wlw-admin">
            <h1 class="wlw-page-title">
                <span class="dashicons dashicons-screenoptions"></span>
                <?php esc_html_e( 'Weblock Widgets', 'weblock-widgets' ); ?>
            </h1>

            <?php if ( $widget ) : ?>
                <?php $this->render_configurator( $widget ); ?>
            <?php else : ?>
                <?php $this->render_gallery( $widgets ); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_gallery( $widgets ) {
        ?>
        <p class="wlw-lead"><?php esc_html_e( 'Válassz egy widgetet, állítsd be a paramétereket, majd egyetlen kattintással másold a kész shortcode-ot bármelyik oldalra vagy posztba.', 'weblock-widgets' ); ?></p>

        <div class="wlw-gallery">
            <?php foreach ( $widgets as $w ) :
                $ready = $this->widget_api_status( $w['id'] );
                $url = add_query_arg( [
                    'page'   => 'weblock-widgets',
                    'widget' => $w['id'],
                ], admin_url( 'admin.php' ) );
            ?>
                <a class="wlw-card" href="<?php echo esc_url( $url ); ?>" style="--wlw-card-color: <?php echo esc_attr( $w['color'] ); ?>">
                    <span class="wlw-card__icon dashicons dashicons-<?php echo esc_attr( $w['icon'] ); ?>" aria-hidden="true"></span>
                    <span class="wlw-card__body">
                        <span class="wlw-card__label"><?php echo esc_html( $w['label'] ); ?></span>
                        <span class="wlw-card__desc"><?php echo esc_html( $w['description'] ); ?></span>
                    </span>
                    <span class="wlw-card__status <?php echo $ready ? 'is-ready' : 'is-missing'; ?>">
                        <?php if ( $ready ) : ?>
                            <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                            <?php esc_html_e( 'Beállítva', 'weblock-widgets' ); ?>
                        <?php else : ?>
                            <span class="dashicons dashicons-warning" aria-hidden="true"></span>
                            <?php esc_html_e( 'API kulcs kell', 'weblock-widgets' ); ?>
                        <?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="wlw-notice">
            <h3><?php esc_html_e( 'Hogyan használd?', 'weblock-widgets' ); ?></h3>
            <ol>
                <li><?php esc_html_e( 'Kattints egy widget kártyára.', 'weblock-widgets' ); ?></li>
                <li><?php esc_html_e( 'Töltsd ki a paramétereket — látod az élő előnézetet.', 'weblock-widgets' ); ?></li>
                <li><?php esc_html_e( '"Shortcode másolása" gombbal vágólapra.', 'weblock-widgets' ); ?></li>
                <li><?php esc_html_e( 'Illeszd be bármilyen oldalra (Gutenberg "Shortcode" block, klasszikus editor, Bricks/Elementor shortcode element).', 'weblock-widgets' ); ?></li>
            </ol>
        </div>
        <?php
    }

    private function render_configurator( $widget ) {
        $back = add_query_arg( [ 'page' => 'weblock-widgets' ], admin_url( 'admin.php' ) );
        $api_ok = $this->widget_api_status( $widget['id'] );
        ?>
        <a class="wlw-back" href="<?php echo esc_url( $back ); ?>">
            <span class="dashicons dashicons-arrow-left-alt"></span>
            <?php esc_html_e( 'Vissza a widgetekhez', 'weblock-widgets' ); ?>
        </a>

        <div class="wlw-configurator">
            <div class="wlw-configurator__head" style="--wlw-card-color: <?php echo esc_attr( $widget['color'] ); ?>">
                <span class="wlw-card__icon dashicons dashicons-<?php echo esc_attr( $widget['icon'] ); ?>" aria-hidden="true"></span>
                <div>
                    <h2><?php echo esc_html( $widget['label'] ); ?></h2>
                    <p><?php echo esc_html( $widget['description'] ); ?></p>
                </div>
            </div>

            <?php if ( ! $api_ok ) : ?>
                <div class="notice notice-warning">
                    <p>
                        <?php esc_html_e( 'Ehhez a widgethez nincs beállítva API kulcs. ', 'weblock-widgets' ); ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=weblock-widgets-settings' ) ); ?>"><?php esc_html_e( 'Beállítás most →', 'weblock-widgets' ); ?></a>
                    </p>
                </div>
            <?php endif; ?>

            <div class="wlw-configurator__body">
                <div class="wlw-configurator__form">
                    <h3><?php esc_html_e( 'Paraméterek', 'weblock-widgets' ); ?></h3>
                    <form data-wlw-form data-shortcode="<?php echo esc_attr( $widget['id'] ); ?>">
                        <?php foreach ( $widget['fields'] as $field ) : ?>
                            <?php $this->render_field( $field ); ?>
                        <?php endforeach; ?>
                    </form>
                </div>

                <div class="wlw-configurator__output">
                    <h3><?php esc_html_e( 'Eredmény', 'weblock-widgets' ); ?></h3>

                    <div class="wlw-shortcode">
                        <label class="wlw-shortcode__label"><?php esc_html_e( 'Shortcode (másold be bárhova)', 'weblock-widgets' ); ?></label>
                        <div class="wlw-shortcode__row">
                            <input type="text" data-wlw-code readonly value="" class="wlw-shortcode__input" />
                            <button type="button" class="button button-primary" data-wlw-copy>
                                <span class="dashicons dashicons-clipboard"></span>
                                <?php esc_html_e( 'Másolás', 'weblock-widgets' ); ?>
                            </button>
                        </div>
                    </div>

                    <div class="wlw-preview">
                        <div class="wlw-preview__head">
                            <h4><?php esc_html_e( 'Élő előnézet', 'weblock-widgets' ); ?></h4>
                            <button type="button" class="button" data-wlw-refresh>
                                <span class="dashicons dashicons-update"></span>
                                <?php esc_html_e( 'Frissítés', 'weblock-widgets' ); ?>
                            </button>
                        </div>
                        <div class="wlw-preview__frame" data-wlw-preview>
                            <p class="wlw-preview__hint"><?php esc_html_e( 'Töltsd ki a kötelező mezőket, és az előnézet itt jelenik meg.', 'weblock-widgets' ); ?></p>
                        </div>
                    </div>

                    <details class="wlw-tip">
                        <summary><?php esc_html_e( 'Hogyan használd a shortcode-ot?', 'weblock-widgets' ); ?></summary>
                        <ul>
                            <li><strong>Gutenberg:</strong> <?php esc_html_e( 'Add Block → keresd "Shortcode" → paszt-old be.', 'weblock-widgets' ); ?></li>
                            <li><strong>Klasszikus editor:</strong> <?php esc_html_e( 'paszt-old be közvetlenül a szövegbe.', 'weblock-widgets' ); ?></li>
                            <li><strong>Bricks Builder:</strong> <?php esc_html_e( 'Add a "Shortcode" element → paszt-old be.', 'weblock-widgets' ); ?></li>
                            <li><strong>Elementor:</strong> <?php esc_html_e( 'Add a "Shortcode" widget → paszt-old be.', 'weblock-widgets' ); ?></li>
                            <li><strong>PHP template-ben:</strong> <code>&lt;?php echo do_shortcode( '...' ); ?&gt;</code></li>
                        </ul>
                    </details>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_field( $field ) {
        $name        = $field['name'];
        $label       = $field['label'];
        $type        = $field['type'] ?? 'text';
        $default     = $field['default'] ?? '';
        $placeholder = $field['placeholder'] ?? '';
        $help        = $field['help'] ?? '';
        $required    = ! empty( $field['required'] );

        $id = 'wlw-field-' . sanitize_key( $name );
        ?>
        <div class="wlw-field">
            <label for="<?php echo esc_attr( $id ); ?>" class="wlw-field__label">
                <?php echo esc_html( $label ); ?>
                <?php if ( $required ) : ?><span class="wlw-field__required" aria-label="kötelező">*</span><?php endif; ?>
            </label>

            <?php if ( 'place_search' === $type ) : ?>
                <div class="wlw-place-search" data-wlw-place>
                    <div class="wlw-place-search__row">
                        <input
                            type="text"
                            id="<?php echo esc_attr( $id ); ?>-q"
                            placeholder="<?php echo esc_attr( $placeholder ); ?>"
                            data-wlw-place-query
                            class="regular-text"
                            autocomplete="off"
                        />
                        <button type="button" class="button" data-wlw-place-go>
                            <span class="dashicons dashicons-search"></span>
                            <?php esc_html_e( 'Keresés', 'weblock-widgets' ); ?>
                        </button>
                    </div>
                    <input
                        type="hidden"
                        id="<?php echo esc_attr( $id ); ?>"
                        name="<?php echo esc_attr( $name ); ?>"
                        value="<?php echo esc_attr( $default ); ?>"
                        data-wlw-input
                        data-default=""
                        <?php echo $required ? 'required' : ''; ?>
                    />
                    <div class="wlw-place-search__results" data-wlw-place-results hidden></div>
                    <div class="wlw-place-search__selected" data-wlw-place-selected hidden>
                        <span class="dashicons dashicons-yes-alt"></span>
                        <span data-wlw-place-name></span>
                        <code data-wlw-place-id></code>
                        <button type="button" class="button-link" data-wlw-place-clear><?php esc_html_e( 'Csere', 'weblock-widgets' ); ?></button>
                    </div>
                </div>

            <?php elseif ( 'select' === $type ) : ?>
                <select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" data-wlw-input data-default="<?php echo esc_attr( $default ); ?>">
                    <?php foreach ( ( $field['options'] ?? [] ) as $val => $opt_label ) : ?>
                        <option value="<?php echo esc_attr( $val ); ?>" <?php selected( (string) $default, (string) $val ); ?>>
                            <?php echo esc_html( $opt_label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            <?php elseif ( 'toggle' === $type ) : ?>
                <label class="wlw-toggle">
                    <input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" data-wlw-input data-default="<?php echo esc_attr( $default ); ?>" value="yes" <?php checked( 'yes', $default ); ?> />
                    <span class="wlw-toggle__slider" aria-hidden="true"></span>
                    <span class="wlw-toggle__text">
                        <?php esc_html_e( 'Be / Ki', 'weblock-widgets' ); ?>
                    </span>
                </label>

            <?php elseif ( 'number' === $type ) : ?>
                <input
                    type="number"
                    id="<?php echo esc_attr( $id ); ?>"
                    name="<?php echo esc_attr( $name ); ?>"
                    value="<?php echo esc_attr( $default ); ?>"
                    placeholder="<?php echo esc_attr( $placeholder ); ?>"
                    min="<?php echo isset( $field['min'] ) ? esc_attr( $field['min'] ) : ''; ?>"
                    max="<?php echo isset( $field['max'] ) ? esc_attr( $field['max'] ) : ''; ?>"
                    data-wlw-input
                    data-default="<?php echo esc_attr( $default ); ?>"
                    class="regular-text"
                />

            <?php else : ?>
                <input
                    type="text"
                    id="<?php echo esc_attr( $id ); ?>"
                    name="<?php echo esc_attr( $name ); ?>"
                    value="<?php echo esc_attr( $default ); ?>"
                    placeholder="<?php echo esc_attr( $placeholder ); ?>"
                    <?php echo $required ? 'required' : ''; ?>
                    data-wlw-input
                    data-default="<?php echo esc_attr( $default ); ?>"
                    class="regular-text"
                />
            <?php endif; ?>

            <?php if ( $help ) : ?>
                <p class="wlw-field__help"><?php echo wp_kses_post( $help ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_settings_page() {
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
            <h1 class="wlw-page-title">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php esc_html_e( 'Beállítások', 'weblock-widgets' ); ?>
            </h1>

            <?php if ( $flushed ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Cache törölve.', 'weblock-widgets' ); ?></p></div>
            <?php endif; ?>

            <form method="post" action="options.php" class="wlw-settings-form">
                <?php settings_fields( 'wlw_settings_group' ); ?>

                <div class="wlw-settings-section">
                    <h2><span class="dashicons dashicons-google"></span> <?php esc_html_e( 'Google', 'weblock-widgets' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Egyetlen Google API kulcs elég a Reviews + Maps + YouTube funkciókhoz. Google Cloud Console-ban hozd létre, és engedélyezd: Places API, Maps Embed API, YouTube Data API v3.', 'weblock-widgets' ); ?></p>

                    <label class="wlw-setting">
                        <span class="wlw-setting__label"><?php esc_html_e( 'Google API kulcs', 'weblock-widgets' ); ?></span>
                        <input type="text" name="wlw_settings[google_api_key]" value="<?php echo esc_attr( $settings['google_api_key'] ); ?>" class="regular-text" autocomplete="off" placeholder="AIzaSy..." />
                    </label>

                    <label class="wlw-setting">
                        <span class="wlw-setting__label"><?php esc_html_e( 'YouTube külön kulcs (opcionális)', 'weblock-widgets' ); ?></span>
                        <input type="text" name="wlw_settings[youtube_api_key]" value="<?php echo esc_attr( $settings['youtube_api_key'] ); ?>" class="regular-text" autocomplete="off" />
                        <span class="wlw-setting__help"><?php esc_html_e( 'Ha üres, a fenti Google kulcsot használja.', 'weblock-widgets' ); ?></span>
                    </label>
                </div>

                <div class="wlw-settings-section">
                    <h2><span class="dashicons dashicons-instagram"></span> <?php esc_html_e( 'Instagram', 'weblock-widgets' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Long-lived user access token a Meta Developers App-ból (Instagram Graph API).', 'weblock-widgets' ); ?></p>

                    <label class="wlw-setting">
                        <span class="wlw-setting__label"><?php esc_html_e( 'Access Token', 'weblock-widgets' ); ?></span>
                        <input type="text" name="wlw_settings[instagram_token]" value="<?php echo esc_attr( $settings['instagram_token'] ); ?>" class="regular-text" autocomplete="off" />
                    </label>
                </div>

                <div class="wlw-settings-section">
                    <h2><span class="dashicons dashicons-facebook"></span> <?php esc_html_e( 'Facebook', 'weblock-widgets' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Facebook Page Access Token (long-lived) + a Page ID.', 'weblock-widgets' ); ?></p>

                    <label class="wlw-setting">
                        <span class="wlw-setting__label"><?php esc_html_e( 'Page Access Token', 'weblock-widgets' ); ?></span>
                        <input type="text" name="wlw_settings[facebook_token]" value="<?php echo esc_attr( $settings['facebook_token'] ); ?>" class="regular-text" autocomplete="off" />
                    </label>

                    <label class="wlw-setting">
                        <span class="wlw-setting__label"><?php esc_html_e( 'Page ID', 'weblock-widgets' ); ?></span>
                        <input type="text" name="wlw_settings[facebook_page_id]" value="<?php echo esc_attr( $settings['facebook_page_id'] ); ?>" class="regular-text" autocomplete="off" />
                    </label>
                </div>

                <div class="wlw-settings-section">
                    <h2><span class="dashicons dashicons-database"></span> <?php esc_html_e( 'Cache', 'weblock-widgets' ); ?></h2>
                    <label class="wlw-setting">
                        <span class="wlw-setting__label"><?php esc_html_e( 'TTL (óra)', 'weblock-widgets' ); ?></span>
                        <input type="number" min="1" max="168" name="wlw_settings[cache_ttl]" value="<?php echo esc_attr( $settings['cache_ttl'] ); ?>" class="small-text" />
                        <span class="wlw-setting__help"><?php esc_html_e( 'API válaszok cache-elési ideje. Ajánlott: 24 óra.', 'weblock-widgets' ); ?></span>
                    </label>
                </div>

                <?php submit_button( __( 'Beállítások mentése', 'weblock-widgets' ) ); ?>
            </form>

            <div class="wlw-settings-section">
                <h2><span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Karbantartás', 'weblock-widgets' ); ?></h2>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
                    <input type="hidden" name="action" value="wlw_flush_cache" />
                    <?php wp_nonce_field( 'wlw_flush_cache' ); ?>
                    <button type="submit" class="button button-secondary"><?php esc_html_e( 'Cache törlése', 'weblock-widgets' ); ?></button>
                </form>
                <p class="description"><?php esc_html_e( 'API válaszok azonnal újra letöltődnek a következő megjelenítéskor.', 'weblock-widgets' ); ?></p>
            </div>
        </div>
        <?php
    }

    public function render_help_page() {
        ?>
        <div class="wrap wlw-admin">
            <h1 class="wlw-page-title">
                <span class="dashicons dashicons-editor-help"></span>
                <?php esc_html_e( 'Súgó', 'weblock-widgets' ); ?>
            </h1>

            <div class="wlw-help">
                <h2><?php esc_html_e( 'API kulcsok beszerzése', 'weblock-widgets' ); ?></h2>

                <h3><?php esc_html_e( 'Google API (Reviews + Maps + YouTube)', 'weblock-widgets' ); ?></h3>
                <ol>
                    <li><?php printf( wp_kses_post( __( 'Nyisd meg: <a href="%s" target="_blank">Google Cloud Console</a>', 'weblock-widgets' ) ), 'https://console.cloud.google.com/' ); ?></li>
                    <li><?php esc_html_e( 'Hozz létre projektet vagy válaszd ki a meglévőt.', 'weblock-widgets' ); ?></li>
                    <li><?php esc_html_e( 'APIs & Services → Library → engedélyezd: Places API, Maps Embed API, YouTube Data API v3.', 'weblock-widgets' ); ?></li>
                    <li><?php esc_html_e( 'APIs & Services → Credentials → Create Credentials → API key.', 'weblock-widgets' ); ?></li>
                    <li><?php esc_html_e( 'Application restrictions → HTTP referrers → add hozzá az oldalad domain-jét.', 'weblock-widgets' ); ?></li>
                    <li><?php esc_html_e( 'Másold a kulcsot a Beállítások → Google szekcióba.', 'weblock-widgets' ); ?></li>
                </ol>

                <h3><?php esc_html_e( 'Place ID megtalálása', 'weblock-widgets' ); ?></h3>
                <p><?php printf( wp_kses_post( __( 'Használd a hivatalos finder eszközt: <a href="%s" target="_blank">Place ID Finder</a>', 'weblock-widgets' ) ), 'https://developers.google.com/maps/documentation/places/web-service/place-id' ); ?></p>

                <h3><?php esc_html_e( 'Instagram Access Token', 'weblock-widgets' ); ?></h3>
                <ol>
                    <li><?php printf( wp_kses_post( __( '<a href="%s" target="_blank">Meta for Developers</a> → Create App → Business.', 'weblock-widgets' ) ), 'https://developers.facebook.com/' ); ?></li>
                    <li><?php esc_html_e( 'Add Product → Instagram Graph API.', 'weblock-widgets' ); ?></li>
                    <li><?php esc_html_e( 'Generate Token → kérd a long-lived user token-t.', 'weblock-widgets' ); ?></li>
                </ol>

                <h3><?php esc_html_e( 'Facebook Page Token', 'weblock-widgets' ); ?></h3>
                <ol>
                    <li><?php esc_html_e( 'Ugyanaz a Meta App, csak Facebook Login + Pages API.', 'weblock-widgets' ); ?></li>
                    <li><?php esc_html_e( 'Graph API Explorer → válaszd a page-t → generálj page access token-t.', 'weblock-widgets' ); ?></li>
                    <li><?php esc_html_e( 'Hosszítsd long-lived-re (debug_token endpoint).', 'weblock-widgets' ); ?></li>
                </ol>
            </div>
        </div>
        <?php
    }
}
