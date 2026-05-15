<?php
namespace WeblockWidgets\Widgets\Social;

use WeblockWidgets\Core\ApiCache;
use WeblockWidgets\Widgets\AbstractWidget;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class InstagramFeed extends AbstractWidget {
    private static $instance = null;
    protected $shortcode = 'wlw_instagram_feed';
    protected $block_name = 'weblock-widgets/instagram-feed';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_meta() {
        return [
            'id'          => $this->shortcode,
            'label'       => __( 'Instagram Feed', 'weblock-widgets' ),
            'icon'        => 'instagram',
            'color'       => '#E1306C',
            'description' => __( 'Legutóbbi Instagram posztok az oldal Instagram fiókjából.', 'weblock-widgets' ),
            'fields'      => [
                [
                    'name'    => 'count',
                    'label'   => __( 'Posztok száma', 'weblock-widgets' ),
                    'type'    => 'number',
                    'default' => 9,
                    'min'     => 1,
                    'max'     => 25,
                ],
                [
                    'name'    => 'layout',
                    'label'   => __( 'Elrendezés', 'weblock-widgets' ),
                    'type'    => 'select',
                    'default' => 'grid',
                    'options' => [
                        'grid'   => __( 'Rács', 'weblock-widgets' ),
                        'slider' => __( 'Karusszel', 'weblock-widgets' ),
                    ],
                ],
                [
                    'name'    => 'show_caption',
                    'label'   => __( 'Felirat hover-en mutatva', 'weblock-widgets' ),
                    'type'    => 'toggle',
                    'default' => 'no',
                ],
            ],
        ];
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'count'  => 9,
            'layout' => 'grid',
            'show_caption' => 'no',
        ], $atts, $this->shortcode );

        $token = $this->get_setting( 'instagram_token' );
        if ( ! $token ) {
            return $this->error_message( __( 'Instagram token nincs beállítva.', 'weblock-widgets' ) );
        }

        $url = add_query_arg( [
            'fields'       => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp',
            'access_token' => $token,
            'limit'        => max( 1, min( 25, (int) $atts['count'] ) ),
        ], 'https://graph.instagram.com/me/media' );

        $data = ApiCache::instance()->fetch( $url );
        if ( is_wp_error( $data ) ) {
            return $this->error_message( __( 'Instagram API hiba: ', 'weblock-widgets' ) . $data->get_error_message() );
        }

        $items = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : [];
        $items = array_slice( $items, 0, max( 1, (int) $atts['count'] ) );

        $layout = in_array( $atts['layout'], [ 'grid', 'slider' ], true ) ? $atts['layout'] : 'grid';

        return $this->load_template( "social/instagram-{$layout}.php", [
            'items'        => $items,
            'show_caption' => 'yes' === $atts['show_caption'],
        ] );
    }

    public function register_block() {
        parent::register_block();
        register_block_type( $this->block_name, [
            'api_version'     => 2,
            'title'           => __( 'Instagram Feed', 'weblock-widgets' ),
            'category'        => 'widgets',
            'icon'            => 'instagram',
            'render_callback' => function ( $attrs ) {
                return $this->render_shortcode( [
                    'count'        => $attrs['count']  ?? 9,
                    'layout'       => $attrs['layout'] ?? 'grid',
                    'show_caption' => ! empty( $attrs['showCaption'] ) ? 'yes' : 'no',
                ] );
            },
            'attributes' => [
                'count'       => [ 'type' => 'number',  'default' => 9 ],
                'layout'      => [ 'type' => 'string',  'default' => 'grid' ],
                'showCaption' => [ 'type' => 'boolean', 'default' => false ],
            ],
        ] );
    }
}
