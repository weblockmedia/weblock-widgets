<?php
namespace WeblockWidgets\Widgets\Social;

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
            'description' => __( 'Konkrét Instagram posztok beágyazva (nem kell API kulcs).', 'weblock-widgets' ),
            'requires_api'=> false,
            'fields'      => [
                [
                    'name'        => 'post_urls',
                    'label'       => __( 'Instagram poszt URL-ek', 'weblock-widgets' ),
                    'type'        => 'textarea',
                    'required'    => true,
                    'placeholder' => "https://www.instagram.com/p/Cabc123/\nhttps://www.instagram.com/p/Cdef456/",
                    'help'        => __( 'Soronként 1 URL. Mindegyik Instagram poszt vagy reel publikus URL-je.', 'weblock-widgets' ),
                ],
                [
                    'name'    => 'columns',
                    'label'   => __( 'Oszlopok száma (desktop)', 'weblock-widgets' ),
                    'type'    => 'select',
                    'default' => '3',
                    'options' => [
                        '1' => __( '1 oszlop', 'weblock-widgets' ),
                        '2' => __( '2 oszlop', 'weblock-widgets' ),
                        '3' => __( '3 oszlop', 'weblock-widgets' ),
                        '4' => __( '4 oszlop', 'weblock-widgets' ),
                    ],
                ],
                [
                    'name'    => 'show_caption',
                    'label'   => __( 'Felirat megjelenítése', 'weblock-widgets' ),
                    'type'    => 'toggle',
                    'default' => 'yes',
                ],
            ],
        ];
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'post_urls'    => '',
            'columns'      => '3',
            'show_caption' => 'yes',
        ], $atts, $this->shortcode );

        $urls = array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', $atts['post_urls'] ) ) );
        $urls = array_filter( $urls, function ( $u ) {
            return preg_match( '#^https?://(www\.)?instagram\.com/(p|reel|tv)/[^/\s]+#i', $u );
        } );
        if ( empty( $urls ) ) {
            return $this->error_message( __( 'Adj meg legalább egy érvényes Instagram poszt URL-t.', 'weblock-widgets' ) );
        }

        return $this->load_template( 'social/instagram-embed.php', [
            'urls'         => array_values( $urls ),
            'columns'      => max( 1, min( 4, (int) $atts['columns'] ) ),
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
                    'post_urls'    => $attrs['postUrls']    ?? '',
                    'columns'      => $attrs['columns']     ?? '3',
                    'show_caption' => ! empty( $attrs['showCaption'] ) ? 'yes' : 'no',
                ] );
            },
            'attributes' => [
                'postUrls'    => [ 'type' => 'string',  'default' => '' ],
                'columns'     => [ 'type' => 'string',  'default' => '3' ],
                'showCaption' => [ 'type' => 'boolean', 'default' => true ],
            ],
        ] );
    }
}
