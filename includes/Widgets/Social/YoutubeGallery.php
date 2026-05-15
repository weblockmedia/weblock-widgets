<?php
namespace WeblockWidgets\Widgets\Social;

use WeblockWidgets\Core\ApiCache;
use WeblockWidgets\Widgets\AbstractWidget;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class YoutubeGallery extends AbstractWidget {
    private static $instance = null;
    protected $shortcode = 'wlw_youtube_gallery';
    protected $block_name = 'weblock-widgets/youtube-gallery';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_meta() {
        return [
            'id'          => $this->shortcode,
            'label'       => __( 'YouTube Gallery', 'weblock-widgets' ),
            'icon'        => 'video-alt3',
            'color'       => '#FF0000',
            'description' => __( 'YouTube csatorna utolsó videói vagy egy playlist videói (nem kell API kulcs).', 'weblock-widgets' ),
            'requires_api'=> false,
            'fields'      => [
                [
                    'name'        => 'channel_id',
                    'label'       => __( 'YouTube csatorna ID', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => 'UCBR8-60-B28hp2BmDPdntcQ',
                    'help'        => __( 'A csatorna URL-jében a UC kezdetű string (pl. youtube.com/channel/UC...). Vagy hagyd üresen és add meg a playlist ID-t.', 'weblock-widgets' ),
                ],
                [
                    'name'        => 'playlist_id',
                    'label'       => __( 'VAGY Playlist ID', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => 'PLxxxxxxxxxxxx',
                ],
                [
                    'name'    => 'count',
                    'label'   => __( 'Videók száma', 'weblock-widgets' ),
                    'type'    => 'number',
                    'default' => 6,
                    'min'     => 1,
                    'max'     => 15,
                    'help'    => __( 'A YouTube RSS feed maximum 15 legutóbbi videót ad vissza.', 'weblock-widgets' ),
                ],
                [
                    'name'    => 'layout',
                    'label'   => __( 'Elrendezés', 'weblock-widgets' ),
                    'type'    => 'select',
                    'default' => 'grid',
                    'options' => [
                        'grid' => __( 'Rács', 'weblock-widgets' ),
                        'list' => __( 'Lista', 'weblock-widgets' ),
                    ],
                ],
            ],
        ];
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'channel_id'  => '',
            'playlist_id' => '',
            'count'       => 6,
            'layout'      => 'grid',
        ], $atts, $this->shortcode );

        $count = max( 1, min( 15, (int) $atts['count'] ) );

        if ( ! empty( $atts['playlist_id'] ) ) {
            $url = 'https://www.youtube.com/feeds/videos.xml?playlist_id=' . rawurlencode( $atts['playlist_id'] );
        } elseif ( ! empty( $atts['channel_id'] ) ) {
            $url = 'https://www.youtube.com/feeds/videos.xml?channel_id=' . rawurlencode( $atts['channel_id'] );
        } else {
            return $this->error_message( __( 'Hiányzó channel_id vagy playlist_id paraméter.', 'weblock-widgets' ) );
        }

        $items = $this->fetch_rss( $url, $count );
        if ( is_wp_error( $items ) ) {
            return $this->error_message( __( 'YouTube RSS hiba: ', 'weblock-widgets' ) . $items->get_error_message() );
        }

        $layout = in_array( $atts['layout'], [ 'grid', 'list' ], true ) ? $atts['layout'] : 'grid';

        return $this->load_template( "social/youtube-{$layout}.php", [
            'items' => $items,
        ] );
    }

    private function fetch_rss( $url, $count ) {
        $cache = ApiCache::instance();
        $cache_key = 'yt_rss_' . md5( $url );
        $cached = $cache->get( $cache_key );
        if ( false !== $cached ) {
            return array_slice( $cached, 0, $count );
        }

        $response = wp_remote_get( $url, [
            'timeout' => 15,
            'headers' => [ 'Accept' => 'application/atom+xml' ],
        ] );
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        $code = wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            return new \WP_Error( 'wlw_yt_rss', sprintf( 'HTTP %d', $code ) );
        }
        $body = wp_remote_retrieve_body( $response );
        if ( ! $body ) {
            return new \WP_Error( 'wlw_yt_empty', 'Empty response' );
        }

        $previous = libxml_use_internal_errors( true );
        $xml = simplexml_load_string( $body );
        libxml_use_internal_errors( $previous );
        if ( false === $xml ) {
            return new \WP_Error( 'wlw_yt_xml', 'Invalid XML' );
        }

        $items = [];
        $entries = $xml->entry ?? [];
        foreach ( $entries as $entry ) {
            $yt_ns    = $entry->children( 'yt', true );
            $media_ns = $entry->children( 'media', true );
            $video_id = isset( $yt_ns->videoId ) ? (string) $yt_ns->videoId : '';
            if ( ! $video_id ) { continue; }

            $thumb = isset( $media_ns->group->thumbnail ) ? (string) $media_ns->group->thumbnail->attributes()['url'] : '';
            if ( ! $thumb ) {
                $thumb = "https://i.ytimg.com/vi/{$video_id}/hqdefault.jpg";
            }

            $items[] = [
                'video_id'  => $video_id,
                'title'     => (string) $entry->title,
                'thumbnail' => $thumb,
                'published' => (string) $entry->published,
            ];
        }

        $cache->set( $cache_key, $items );
        return array_slice( $items, 0, $count );
    }

    public function register_block() {
        parent::register_block();
        register_block_type( $this->block_name, [
            'api_version'     => 2,
            'title'           => __( 'YouTube Gallery', 'weblock-widgets' ),
            'category'        => 'widgets',
            'icon'            => 'video-alt3',
            'render_callback' => function ( $attrs ) {
                return $this->render_shortcode( [
                    'channel_id'  => $attrs['channelId']  ?? '',
                    'playlist_id' => $attrs['playlistId'] ?? '',
                    'count'       => $attrs['count']      ?? 6,
                    'layout'      => $attrs['layout']     ?? 'grid',
                ] );
            },
            'attributes' => [
                'channelId'  => [ 'type' => 'string', 'default' => '' ],
                'playlistId' => [ 'type' => 'string', 'default' => '' ],
                'count'      => [ 'type' => 'number', 'default' => 6 ],
                'layout'     => [ 'type' => 'string', 'default' => 'grid' ],
            ],
        ] );
    }
}
