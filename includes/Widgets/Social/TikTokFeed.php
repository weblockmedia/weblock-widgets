<?php
namespace WeblockWidgets\Widgets\Social;

use WeblockWidgets\Widgets\AbstractWidget;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TikTokFeed extends AbstractWidget {
    private static $instance = null;
    protected $shortcode = 'wlw_tiktok_feed';
    protected $block_name = 'weblock-widgets/tiktok-feed';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_meta() {
        return [
            'id'          => $this->shortcode,
            'label'       => __( 'TikTok Feed', 'weblock-widgets' ),
            'icon'        => 'format-video',
            'color'       => '#000000',
            'category'    => 'social',
            'description' => __( 'TikTok videók beágyazása URL-lista alapján (nem kell API kulcs).', 'weblock-widgets' ),
            'requires_api'=> false,
            'fields'      => [
                [
                    'name'        => 'video_urls',
                    'label'       => __( 'TikTok videó URL-ek', 'weblock-widgets' ),
                    'type'        => 'textarea',
                    'required'    => true,
                    'placeholder' => "https://www.tiktok.com/@user/video/7000000000000000000\nhttps://www.tiktok.com/@user/video/7000000000000000001",
                    'help'        => __( 'Soronként 1 URL. Mindegyik TikTok videó publikus URL-je.', 'weblock-widgets' ),
                ],
                [
                    'name'    => 'columns',
                    'label'   => __( 'Oszlopok (desktop)', 'weblock-widgets' ),
                    'type'    => 'select',
                    'default' => '2',
                    'options' => [
                        '1' => __( '1 oszlop', 'weblock-widgets' ),
                        '2' => __( '2 oszlop', 'weblock-widgets' ),
                        '3' => __( '3 oszlop', 'weblock-widgets' ),
                    ],
                ],
            ],
        ];
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'video_urls' => '',
            'columns'    => '2',
        ], $atts, $this->shortcode );

        $urls = array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', $atts['video_urls'] ) ) );

        $videos = [];
        foreach ( $urls as $url ) {
            if ( preg_match( '#tiktok\.com/(?:@[^/]+/video|v|t)/(\d+)#', $url, $m ) ) {
                $videos[] = [ 'url' => $url, 'id' => $m[1] ];
            }
        }
        if ( empty( $videos ) ) {
            return $this->error_message( __( 'Adj meg legalább egy érvényes TikTok videó URL-t.', 'weblock-widgets' ) );
        }

        return $this->load_template( 'social/tiktok-embed.php', [
            'videos'  => $videos,
            'columns' => max( 1, min( 3, (int) $atts['columns'] ) ),
        ] );
    }

    public function register_block() {
        parent::register_block();
        register_block_type( $this->block_name, [
            'api_version'     => 2,
            'title'           => __( 'TikTok Feed', 'weblock-widgets' ),
            'category'        => 'widgets',
            'icon'            => 'format-video',
            'render_callback' => function ( $attrs ) {
                return $this->render_shortcode( [
                    'video_urls' => $attrs['videoUrls'] ?? '',
                    'columns'    => $attrs['columns']   ?? '2',
                ] );
            },
            'attributes' => [
                'videoUrls' => [ 'type' => 'string', 'default' => '' ],
                'columns'   => [ 'type' => 'string', 'default' => '2' ],
            ],
        ] );
    }
}
