<?php
namespace WeblockWidgets\Widgets\Social;

use WeblockWidgets\Widgets\AbstractWidget;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PinterestFeed extends AbstractWidget {
    private static $instance = null;
    protected $shortcode = 'wlw_pinterest_feed';
    protected $block_name = 'weblock-widgets/pinterest-feed';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_meta() {
        return [
            'id'          => $this->shortcode,
            'label'       => __( 'Pinterest Feed', 'weblock-widgets' ),
            'icon'        => 'pinterest',
            'color'       => '#E60023',
            'category'    => 'social',
            'description' => __( 'Pinterest profil, board vagy egyes pin beágyazása (nem kell API kulcs).', 'weblock-widgets' ),
            'requires_api'=> false,
            'fields'      => [
                [
                    'name'        => 'url',
                    'label'       => __( 'Pinterest URL', 'weblock-widgets' ),
                    'type'        => 'text',
                    'required'    => true,
                    'placeholder' => 'https://www.pinterest.com/USERNAME/ vagy /BOARD/',
                    'help'        => __( 'Profil URL (pinterest.com/user/), board URL (pinterest.com/user/board/), vagy egy konkrét pin URL.', 'weblock-widgets' ),
                ],
                [
                    'name'    => 'type',
                    'label'   => __( 'Típus', 'weblock-widgets' ),
                    'type'    => 'select',
                    'default' => 'embedUser',
                    'options' => [
                        'embedUser'  => __( 'Felhasználói profil', 'weblock-widgets' ),
                        'embedBoard' => __( 'Board', 'weblock-widgets' ),
                        'embedPin'   => __( 'Egy pin', 'weblock-widgets' ),
                    ],
                ],
                [
                    'name'    => 'width',
                    'label'   => __( 'Szélesség (px)', 'weblock-widgets' ),
                    'type'    => 'number',
                    'default' => 500,
                    'min'     => 200,
                    'max'     => 1200,
                ],
                [
                    'name'    => 'height',
                    'label'   => __( 'Magasság (px)', 'weblock-widgets' ),
                    'type'    => 'number',
                    'default' => 600,
                    'min'     => 200,
                    'max'     => 1500,
                ],
            ],
        ];
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'url'    => '',
            'type'   => 'embedUser',
            'width'  => 500,
            'height' => 600,
        ], $atts, $this->shortcode );

        if ( empty( $atts['url'] ) ) {
            return $this->error_message( __( 'Adj meg egy Pinterest URL-t.', 'weblock-widgets' ) );
        }

        $type = in_array( $atts['type'], [ 'embedUser', 'embedBoard', 'embedPin' ], true ) ? $atts['type'] : 'embedUser';

        return $this->load_template( 'social/pinterest-embed.php', [
            'url'    => $atts['url'],
            'type'   => $type,
            'width'  => max( 200, min( 1200, (int) $atts['width'] ) ),
            'height' => max( 200, min( 1500, (int) $atts['height'] ) ),
        ] );
    }

    public function register_block() {
        parent::register_block();
        register_block_type( $this->block_name, [
            'api_version'     => 2,
            'title'           => __( 'Pinterest Feed', 'weblock-widgets' ),
            'category'        => 'widgets',
            'icon'            => 'pinterest',
            'render_callback' => function ( $attrs ) {
                return $this->render_shortcode( [
                    'url'    => $attrs['url']    ?? '',
                    'type'   => $attrs['type']   ?? 'embedUser',
                    'width'  => $attrs['width']  ?? 500,
                    'height' => $attrs['height'] ?? 600,
                ] );
            },
            'attributes' => [
                'url'    => [ 'type' => 'string', 'default' => '' ],
                'type'   => [ 'type' => 'string', 'default' => 'embedUser' ],
                'width'  => [ 'type' => 'number', 'default' => 500 ],
                'height' => [ 'type' => 'number', 'default' => 600 ],
            ],
        ] );
    }
}
