<?php
namespace WeblockWidgets\Widgets\Social;

use WeblockWidgets\Widgets\AbstractWidget;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FacebookFeed extends AbstractWidget {
    private static $instance = null;
    protected $shortcode = 'wlw_facebook_feed';
    protected $block_name = 'weblock-widgets/facebook-feed';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_meta() {
        return [
            'id'          => $this->shortcode,
            'label'       => __( 'Facebook Feed', 'weblock-widgets' ),
            'icon'        => 'facebook',
            'color'       => '#1877F2',
            'category'    => 'social',
            'description' => __( 'Facebook oldal posztjai a hivatalos Page Plugin-nal (nem kell API kulcs).', 'weblock-widgets' ),
            'requires_api'=> false,
            'fields'      => [
                [
                    'name'        => 'page_url',
                    'label'       => __( 'Facebook oldal URL', 'weblock-widgets' ),
                    'type'        => 'text',
                    'required'    => true,
                    'placeholder' => 'https://www.facebook.com/weblockgroup',
                    'help'        => __( 'A Facebook oldal teljes URL-je. Az oldalnak publikusnak kell lennie.', 'weblock-widgets' ),
                ],
                [
                    'name'    => 'tabs',
                    'label'   => __( 'Megjelenítendő tartalom', 'weblock-widgets' ),
                    'type'    => 'select',
                    'default' => 'timeline',
                    'options' => [
                        'timeline'        => __( 'Posztok (timeline)', 'weblock-widgets' ),
                        'events'          => __( 'Események', 'weblock-widgets' ),
                        'messages'        => __( 'Üzenetek', 'weblock-widgets' ),
                        'timeline,events' => __( 'Posztok + Események', 'weblock-widgets' ),
                    ],
                ],
                [
                    'name'    => 'height',
                    'label'   => __( 'Magasság (px)', 'weblock-widgets' ),
                    'type'    => 'number',
                    'default' => 600,
                    'min'     => 200,
                    'max'     => 1200,
                ],
                [
                    'name'    => 'show_cover',
                    'label'   => __( 'Borítókép megjelenítése', 'weblock-widgets' ),
                    'type'    => 'toggle',
                    'default' => 'yes',
                ],
                [
                    'name'    => 'show_facepile',
                    'label'   => __( 'Ismerősök arcképei', 'weblock-widgets' ),
                    'type'    => 'toggle',
                    'default' => 'yes',
                ],
                [
                    'name'    => 'small_header',
                    'label'   => __( 'Kompakt fejléc', 'weblock-widgets' ),
                    'type'    => 'toggle',
                    'default' => 'no',
                ],
            ],
        ];
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'page_url'      => '',
            'tabs'          => 'timeline',
            'height'        => 600,
            'show_cover'    => 'yes',
            'show_facepile' => 'yes',
            'small_header'  => 'no',
        ], $atts, $this->shortcode );

        if ( empty( $atts['page_url'] ) ) {
            return $this->error_message( __( 'Hiányzó Facebook oldal URL.', 'weblock-widgets' ) );
        }

        $url = add_query_arg( [
            'href'           => $atts['page_url'],
            'tabs'           => $atts['tabs'],
            'width'          => 500,
            'height'         => max( 200, min( 1200, (int) $atts['height'] ) ),
            'hide_cover'     => 'yes' === $atts['show_cover']    ? 'false' : 'true',
            'show_facepile'  => 'yes' === $atts['show_facepile'] ? 'true'  : 'false',
            'small_header'   => 'yes' === $atts['small_header']  ? 'true'  : 'false',
            'adapt_container_width' => 'true',
        ], 'https://www.facebook.com/plugins/page.php' );

        return $this->load_template( 'social/facebook-embed.php', [
            'embed_url' => $url,
            'height'    => max( 200, min( 1200, (int) $atts['height'] ) ),
            'page_url'  => $atts['page_url'],
        ] );
    }

    public function register_block() {
        parent::register_block();
        register_block_type( $this->block_name, [
            'api_version'     => 2,
            'title'           => __( 'Facebook Feed', 'weblock-widgets' ),
            'category'        => 'widgets',
            'icon'            => 'facebook',
            'render_callback' => function ( $attrs ) {
                return $this->render_shortcode( [
                    'page_url'      => $attrs['pageUrl']      ?? '',
                    'tabs'          => $attrs['tabs']         ?? 'timeline',
                    'height'        => $attrs['height']       ?? 600,
                    'show_cover'    => ! empty( $attrs['showCover'] )    ? 'yes' : 'no',
                    'show_facepile' => ! empty( $attrs['showFacepile'] ) ? 'yes' : 'no',
                    'small_header'  => ! empty( $attrs['smallHeader'] )  ? 'yes' : 'no',
                ] );
            },
            'attributes' => [
                'pageUrl'      => [ 'type' => 'string',  'default' => '' ],
                'tabs'         => [ 'type' => 'string',  'default' => 'timeline' ],
                'height'       => [ 'type' => 'number',  'default' => 600 ],
                'showCover'    => [ 'type' => 'boolean', 'default' => true ],
                'showFacepile' => [ 'type' => 'boolean', 'default' => true ],
                'smallHeader'  => [ 'type' => 'boolean', 'default' => false ],
            ],
        ] );
    }
}
