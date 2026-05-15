<?php
namespace WeblockWidgets\Widgets\Social;

use WeblockWidgets\Core\ApiCache;
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
            'description' => __( 'Legutóbbi posztok az oldal Facebook oldaláról.', 'weblock-widgets' ),
            'fields'      => [
                [
                    'name'    => 'count',
                    'label'   => __( 'Posztok száma', 'weblock-widgets' ),
                    'type'    => 'number',
                    'default' => 5,
                    'min'     => 1,
                    'max'     => 25,
                ],
                [
                    'name'    => 'layout',
                    'label'   => __( 'Elrendezés', 'weblock-widgets' ),
                    'type'    => 'select',
                    'default' => 'list',
                    'options' => [
                        'list' => __( 'Lista', 'weblock-widgets' ),
                        'grid' => __( 'Rács', 'weblock-widgets' ),
                    ],
                ],
                [
                    'name'    => 'show_image',
                    'label'   => __( 'Borítókép megjelenítése', 'weblock-widgets' ),
                    'type'    => 'toggle',
                    'default' => 'yes',
                ],
            ],
        ];
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'count'  => 5,
            'layout' => 'list',
            'show_image' => 'yes',
        ], $atts, $this->shortcode );

        $token   = $this->get_setting( 'facebook_token' );
        $page_id = $this->get_setting( 'facebook_page_id' );

        if ( ! $token || ! $page_id ) {
            return $this->error_message( __( 'Facebook Page ID vagy token nincs beállítva.', 'weblock-widgets' ) );
        }

        $url = add_query_arg( [
            'fields'       => 'id,message,permalink_url,full_picture,created_time,attachments{media,type,title,description}',
            'access_token' => $token,
            'limit'        => max( 1, min( 25, (int) $atts['count'] ) ),
        ], 'https://graph.facebook.com/v19.0/' . rawurlencode( $page_id ) . '/posts' );

        $data = ApiCache::instance()->fetch( $url );
        if ( is_wp_error( $data ) ) {
            return $this->error_message( __( 'Facebook API hiba: ', 'weblock-widgets' ) . $data->get_error_message() );
        }

        $items = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : [];
        $items = array_slice( $items, 0, max( 1, (int) $atts['count'] ) );

        $layout = in_array( $atts['layout'], [ 'list', 'grid' ], true ) ? $atts['layout'] : 'list';

        return $this->load_template( "social/facebook-{$layout}.php", [
            'items'      => $items,
            'show_image' => 'yes' === $atts['show_image'],
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
                    'count'      => $attrs['count']  ?? 5,
                    'layout'     => $attrs['layout'] ?? 'list',
                    'show_image' => ! empty( $attrs['showImage'] ) ? 'yes' : 'no',
                ] );
            },
            'attributes' => [
                'count'     => [ 'type' => 'number',  'default' => 5 ],
                'layout'    => [ 'type' => 'string',  'default' => 'list' ],
                'showImage' => [ 'type' => 'boolean', 'default' => true ],
            ],
        ] );
    }
}
