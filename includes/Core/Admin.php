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
        $out['google_api_key'] = isset( $input['google_api_key'] ) ? sanitize_text_field( $input['google_api_key'] ) : '';
        $out['cache_ttl']      = isset( $input['cache_ttl'] ) ? max( 1, min( 168, (int) $input['cache_ttl'] ) ) : 24;
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
            \WeblockWidgets\Widgets\Social\TikTokFeed::instance()->get_meta(),
            \WeblockWidgets\Widgets\Social\TwitterFeed::instance()->get_meta(),
            \WeblockWidgets\Widgets\Social\PinterestFeed::instance()->get_meta(),
            \WeblockWidgets\Widgets\Social\YoutubeGallery::instance()->get_meta(),
            \WeblockWidgets\Widgets\Tools\GoogleMaps::instance()->get_meta(),
            \WeblockWidgets\Widgets\Trust\TrustmarkBadge::instance()->get_meta(),
            \WeblockWidgets\Widgets\Trust\EmailSignature::instance()->get_meta(),
        ];
        return $this->widgets_cache;
    }

    private function widget_api_status( $widget_id ) {
        $widgets = $this->get_all_widgets();
        foreach ( $widgets as $w ) {
            if ( $w['id'] === $widget_id ) {
                if ( empty( $w['requires_api'] ) ) {
                    return true;
                }
                $settings = get_option( 'wlw_settings', [] );
                return ! empty( $settings['google_api_key'] );
            }
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

    private function get_categories() {
        return [
            'all'      => [ 'label' => __( 'Mind', 'weblock-widgets' ),         'icon' => 'grid-view' ],
            'reviews'  => [ 'label' => __( 'Vélemények', 'weblock-widgets' ),   'icon' => 'star-filled' ],
            'social'   => [ 'label' => __( 'Közösségi', 'weblock-widgets' ),    'icon' => 'share' ],
            'trust'    => [ 'label' => __( 'Trust', 'weblock-widgets' ),        'icon' => 'shield' ],
            'tools'    => [ 'label' => __( 'Eszközök', 'weblock-widgets' ),     'icon' => 'admin-tools' ],
            'gallery'  => [ 'label' => __( 'Galéria', 'weblock-widgets' ),      'icon' => 'format-gallery' ],
            'sales'    => [ 'label' => __( 'Értékesítés', 'weblock-widgets' ),  'icon' => 'cart' ],
            'contact'  => [ 'label' => __( 'Kapcsolat', 'weblock-widgets' ),    'icon' => 'phone' ],
            'forms'    => [ 'label' => __( 'Form', 'weblock-widgets' ),         'icon' => 'feedback' ],
        ];
    }

    private function render_gallery( $widgets ) {
        $categories  = $this->get_categories();
        $counts      = array_fill_keys( array_keys( $categories ), 0 );
        $counts['all'] = count( $widgets );
        foreach ( $widgets as $w ) {
            $cat = $w['category'] ?? 'tools';
            if ( isset( $counts[ $cat ] ) ) {
                $counts[ $cat ]++;
            }
        }
        ?>
        <p class="wlw-lead"><?php esc_html_e( 'Válassz egy widgetet, állítsd be a paramétereket, majd egyetlen kattintással másold a kész shortcode-ot bármelyik oldalra vagy posztba.', 'weblock-widgets' ); ?></p>

        <div class="wlw-toolbar">
            <div class="wlw-search">
                <span class="dashicons dashicons-search" aria-hidden="true"></span>
                <input
                    type="search"
                    id="wlw-gallery-search"
                    placeholder="<?php esc_attr_e( 'Keresés widget név vagy leírás alapján…', 'weblock-widgets' ); ?>"
                    autocomplete="off"
                    aria-label="<?php esc_attr_e( 'Widget keresés', 'weblock-widgets' ); ?>"
                />
            </div>

            <nav class="wlw-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Widget kategóriák', 'weblock-widgets' ); ?>">
                <?php foreach ( $categories as $key => $cat ) :
                    if ( 'all' !== $key && empty( $counts[ $key ] ) ) { continue; }
                ?>
                    <button
                        type="button"
                        class="wlw-tab<?php echo 'all' === $key ? ' is-active' : ''; ?>"
                        data-wlw-cat="<?php echo esc_attr( $key ); ?>"
                        role="tab"
                        aria-selected="<?php echo 'all' === $key ? 'true' : 'false'; ?>">
                        <span class="dashicons dashicons-<?php echo esc_attr( $cat['icon'] ); ?>" aria-hidden="true"></span>
                        <span class="wlw-tab__label"><?php echo esc_html( $cat['label'] ); ?></span>
                        <span class="wlw-tab__count"><?php echo (int) $counts[ $key ]; ?></span>
                    </button>
                <?php endforeach; ?>
            </nav>
        </div>

        <div class="wlw-gallery" data-wlw-gallery>
            <?php foreach ( $widgets as $w ) :
                $needs_api = ! empty( $w['requires_api'] );
                $ready     = $this->widget_api_status( $w['id'] );
                $cat       = $w['category'] ?? 'tools';
                $url = add_query_arg( [
                    'page'   => 'weblock-widgets',
                    'widget' => $w['id'],
                ], admin_url( 'admin.php' ) );
                $search_haystack = strtolower( $w['label'] . ' ' . ( $w['description'] ?? '' ) );
            ?>
                <a class="wlw-card"
                   href="<?php echo esc_url( $url ); ?>"
                   data-wlw-cat="<?php echo esc_attr( $cat ); ?>"
                   data-wlw-search="<?php echo esc_attr( $search_haystack ); ?>"
                   style="--wlw-card-color: <?php echo esc_attr( $w['color'] ); ?>">
                    <span class="wlw-card__icon dashicons dashicons-<?php echo esc_attr( $w['icon'] ); ?>" aria-hidden="true"></span>
                    <span class="wlw-card__body">
                        <span class="wlw-card__label"><?php echo esc_html( $w['label'] ); ?></span>
                        <span class="wlw-card__desc"><?php echo esc_html( $w['description'] ); ?></span>
                    </span>
                    <span class="wlw-card__status <?php echo $ready ? 'is-ready' : 'is-missing'; ?>">
                        <?php if ( ! $needs_api ) : ?>
                            <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                            <?php esc_html_e( 'Nem kell API kulcs', 'weblock-widgets' ); ?>
                        <?php elseif ( $ready ) : ?>
                            <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                            <?php esc_html_e( 'API kulcs OK', 'weblock-widgets' ); ?>
                        <?php else : ?>
                            <span class="dashicons dashicons-warning" aria-hidden="true"></span>
                            <?php esc_html_e( 'API kulcs kell', 'weblock-widgets' ); ?>
                        <?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
            <p class="wlw-gallery__empty" hidden><?php esc_html_e( 'Nincs találat a megadott szűrőkre.', 'weblock-widgets' ); ?></p>
        </div>
        <?php
    }

    private function render_configurator( $widget ) {
        $back = add_query_arg( [ 'page' => 'weblock-widgets' ], admin_url( 'admin.php' ) );
        $api_ok = $this->widget_api_status( $widget['id'] );
        $output_type = $widget['output_type'] ?? 'shortcode';
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
                    <form data-wlw-form data-shortcode="<?php echo esc_attr( $widget['id'] ); ?>" data-output="<?php echo esc_attr( $output_type ); ?>">
                        <?php foreach ( $widget['fields'] as $field ) : ?>
                            <?php $this->render_field( $field ); ?>
                        <?php endforeach; ?>
                    </form>
                </div>

                <div class="wlw-configurator__output">
                    <h3><?php esc_html_e( 'Eredmény', 'weblock-widgets' ); ?></h3>

                    <div class="wlw-shortcode">
                        <label class="wlw-shortcode__label">
                            <?php if ( 'html' === $output_type ) : ?>
                                <?php esc_html_e( 'HTML kód (másold a Gmail / Outlook aláírásba)', 'weblock-widgets' ); ?>
                            <?php else : ?>
                                <?php esc_html_e( 'Shortcode (másold be bárhova)', 'weblock-widgets' ); ?>
                            <?php endif; ?>
                        </label>
                        <div class="wlw-shortcode__row">
                            <?php if ( 'html' === $output_type ) : ?>
                                <textarea data-wlw-code readonly rows="4" class="wlw-shortcode__input wlw-shortcode__input--html"></textarea>
                            <?php else : ?>
                                <input type="text" data-wlw-code readonly value="" class="wlw-shortcode__input" />
                            <?php endif; ?>
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

            <?php elseif ( 'textarea' === $type ) : ?>
                <textarea
                    id="<?php echo esc_attr( $id ); ?>"
                    name="<?php echo esc_attr( $name ); ?>"
                    placeholder="<?php echo esc_attr( $placeholder ); ?>"
                    rows="5"
                    data-wlw-input
                    data-default=""
                    <?php echo $required ? 'required' : ''; ?>
                    class="large-text code"><?php echo esc_textarea( $default ); ?></textarea>

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
            'google_api_key' => '',
            'cache_ttl'      => 24,
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

            <div class="notice notice-info">
                <p>
                    <strong><?php esc_html_e( '4 widget API kulcs nélkül működik:', 'weblock-widgets' ); ?></strong>
                    <?php esc_html_e( 'Instagram Feed, Facebook Feed, YouTube Gallery, Google Map.', 'weblock-widgets' ); ?>
                    <?php esc_html_e( 'Csak a Google Reviews igényel Google API kulcsot — a Google ezt ingyenesen adja ($200 credit/hó, 12 oldal terhelése ~$5/hó).', 'weblock-widgets' ); ?>
                </p>
            </div>

            <form method="post" action="options.php" class="wlw-settings-form">
                <?php settings_fields( 'wlw_settings_group' ); ?>

                <div class="wlw-settings-section">
                    <h2><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'Google API kulcs (csak a Reviews-hoz kell)', 'weblock-widgets' ); ?></h2>
                    <p class="description">
                        <?php esc_html_e( 'A Google Reviews widget működéséhez szükséges. Másnak NEM kell.', 'weblock-widgets' ); ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=weblock-widgets-help' ) ); ?>"><?php esc_html_e( 'Hogyan szerzem be? →', 'weblock-widgets' ); ?></a>
                    </p>

                    <label class="wlw-setting">
                        <span class="wlw-setting__label"><?php esc_html_e( 'API kulcs', 'weblock-widgets' ); ?></span>
                        <input type="text" name="wlw_settings[google_api_key]" value="<?php echo esc_attr( $settings['google_api_key'] ); ?>" class="regular-text" autocomplete="off" placeholder="AIzaSy..." />
                    </label>
                </div>

                <div class="wlw-settings-section">
                    <h2><span class="dashicons dashicons-database"></span> <?php esc_html_e( 'Cache', 'weblock-widgets' ); ?></h2>
                    <label class="wlw-setting">
                        <span class="wlw-setting__label"><?php esc_html_e( 'TTL (óra)', 'weblock-widgets' ); ?></span>
                        <input type="number" min="1" max="168" name="wlw_settings[cache_ttl]" value="<?php echo esc_attr( $settings['cache_ttl'] ); ?>" class="small-text" />
                        <span class="wlw-setting__help"><?php esc_html_e( 'API válaszok és RSS feed-ek cache-elési ideje. Ajánlott: 24 óra.', 'weblock-widgets' ); ?></span>
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
                <p class="description"><?php esc_html_e( 'A feed-ek és API válaszok azonnal újra letöltődnek a következő megjelenítéskor.', 'weblock-widgets' ); ?></p>
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
                <h2><?php esc_html_e( 'Mit kell beállítani widgetenként', 'weblock-widgets' ); ?></h2>

                <table class="wlw-help-matrix">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Widget', 'weblock-widgets' ); ?></th>
                            <th><?php esc_html_e( 'API kulcs?', 'weblock-widgets' ); ?></th>
                            <th><?php esc_html_e( 'Mit kell csak?', 'weblock-widgets' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><?php esc_html_e( 'YouTube Gallery', 'weblock-widgets' ); ?></td><td>❌</td><td>Channel ID vagy Playlist ID</td></tr>
                        <tr><td><?php esc_html_e( 'Google Map', 'weblock-widgets' ); ?></td><td>❌</td><td>Cím (pl. "Budapest, Király u. 1.")</td></tr>
                        <tr><td><?php esc_html_e( 'Facebook Feed', 'weblock-widgets' ); ?></td><td>❌</td><td>Facebook oldal URL</td></tr>
                        <tr><td><?php esc_html_e( 'Instagram Feed', 'weblock-widgets' ); ?></td><td>❌</td><td>Instagram poszt URL-ek listája</td></tr>
                        <tr><td><?php esc_html_e( 'TikTok Feed', 'weblock-widgets' ); ?></td><td>❌</td><td>TikTok videó URL-ek listája</td></tr>
                        <tr><td><?php esc_html_e( 'Google Reviews', 'weblock-widgets' ); ?></td><td>✅</td><td>Google API kulcs (ingyenes) + Place ID</td></tr>
                    </tbody>
                </table>

                <h2><?php esc_html_e( 'Google API kulcs beszerzése (csak Reviews-hoz)', 'weblock-widgets' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Ingyenes — a Google havi $200 credit-et ad. 12 oldalon ~$5/hó terhelés, tehát mindig 0 Ft.', 'weblock-widgets' ); ?></p>
                <ol>
                    <li><?php printf( wp_kses_post( __( 'Nyisd meg: <a href="%s" target="_blank">Google Cloud Console</a>', 'weblock-widgets' ) ), 'https://console.cloud.google.com/' ); ?></li>
                    <li><?php esc_html_e( 'Hozz létre projektet (vagy válaszd a meglévőt).', 'weblock-widgets' ); ?></li>
                    <li><?php esc_html_e( 'APIs & Services → Library → engedélyezd: "Places API".', 'weblock-widgets' ); ?></li>
                    <li><?php esc_html_e( 'APIs & Services → Credentials → Create Credentials → API key.', 'weblock-widgets' ); ?></li>
                    <li><?php esc_html_e( 'Restrict: HTTP referrers → add az oldalad domain-jét (biztonsági okból).', 'weblock-widgets' ); ?></li>
                    <li><?php esc_html_e( 'Másold a kulcsot a Beállítások oldalra.', 'weblock-widgets' ); ?></li>
                </ol>

                <h2><?php esc_html_e( 'Tipp: hol találom a Channel ID-t / Page URL-t / Post URL-t?', 'weblock-widgets' ); ?></h2>
                <ul>
                    <li><strong>YouTube Channel ID:</strong> <?php esc_html_e( 'YouTube oldalon a csatorna profil → URL-ben "youtube.com/channel/UC..." → ez kell.', 'weblock-widgets' ); ?></li>
                    <li><strong>YouTube Playlist ID:</strong> <?php esc_html_e( 'Playlist-megnyitva URL-ben "list=PL..." → a PL... rész kell.', 'weblock-widgets' ); ?></li>
                    <li><strong>Facebook Page URL:</strong> <?php esc_html_e( 'Az oldalra navigálva fent a böngészősávban (pl. https://www.facebook.com/weblockgroup).', 'weblock-widgets' ); ?></li>
                    <li><strong>Instagram Post URL:</strong> <?php esc_html_e( 'A posztra kattintva a tetején lévő URL (pl. https://www.instagram.com/p/Cxxxxx/).', 'weblock-widgets' ); ?></li>
                    <li><strong>TikTok Video URL:</strong> <?php esc_html_e( 'A videóra kattintva a böngészősávban (pl. https://www.tiktok.com/@user/video/700...).', 'weblock-widgets' ); ?></li>
                    <li><strong>Google Place ID:</strong> <?php esc_html_e( 'A Google Reviews konfigurátorban beépített kereső megtalálja cégnév alapján.', 'weblock-widgets' ); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
}
