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

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'channel_id'  => '',
            'playlist_id' => '',
            'count'       => 6,
            'layout'      => 'grid',
        ], $atts, $this->shortcode );

        $api_key = $this->get_setting( 'youtube_api_key' );
        if ( ! $api_key ) {
            $api_key = $this->get_setting( 'google_api_key' );
        }
        if ( ! $api_key ) {
            return $this->error_message( __( 'YouTube/Google API kulcs nincs beállítva.', 'weblock-widgets' ) );
        }

        $count = max( 1, min( 25, (int) $atts['count'] ) );

        if ( ! empty( $atts['playlist_id'] ) ) {
            $items = $this->fetch_playlist( $atts['playlist_id'], $count, $api_key );
        } elseif ( ! empty( $atts['channel_id'] ) ) {
            $items = $this->fetch_channel( $atts['channel_id'], $count, $api_key );
        } else {
            return $this->error_message( __( 'Hiányzó channel_id vagy playlist_id paraméter.', 'weblock-widgets' ) );
        }

        if ( is_wp_error( $items ) ) {
            return $this->error_message( __( 'YouTube API hiba: ', 'weblock-widgets' ) . $items->get_error_message() );
        }

        $layout = in_array( $atts['layout'], [ 'grid', 'list' ], true ) ? $atts['layout'] : 'grid';

        return $this->load_template( "social/youtube-{$layout}.php", [
            'items' => $items,
        ] );
    }

    private function fetch_channel( $channel_id, $count, $api_key ) {
        $url = add_query_arg( [
            'key'        => $api_key,
            'channelId'  => $channel_id,
            'part'       => 'snippet',
            'order'      => 'date',
            'maxResults' => $count,
            'type'       => 'video',
        ], 'https://www.googleapis.com/youtube/v3/search' );

        $data = ApiCache::instance()->fetch( $url );
        if ( is_wp_error( $data ) ) { return $data; }
        if ( empty( $data['items'] ) ) { return []; }

        $out = [];
        foreach ( $data['items'] as $item ) {
            if ( empty( $item['id']['videoId'] ) ) { continue; }
            $out[] = [
                'video_id'  => $item['id']['videoId'],
                'title'     => $item['snippet']['title'] ?? '',
                'thumbnail' => $item['snippet']['thumbnails']['high']['url']
                              ?? $item['snippet']['thumbnails']['medium']['url']
                              ?? $item['snippet']['thumbnails']['default']['url']
                              ?? '',
                'published' => $item['snippet']['publishedAt'] ?? '',
            ];
        }
        return $out;
    }

    private function fetch_playlist( $playlist_id, $count, $api_key ) {
        $url = add_query_arg( [
            'key'        => $api_key,
            'playlistId' => $playlist_id,
            'part'       => 'snippet',
            'maxResults' => $count,
        ], 'https://www.googleapis.com/youtube/v3/playlistItems' );

        $data = ApiCache::instance()->fetch( $url );
        if ( is_wp_error( $data ) ) { return $data; }
        if ( empty( $data['items'] ) ) { return []; }

        $out = [];
        foreach ( $data['items'] as $item ) {
            $vid = $item['snippet']['resourceId']['videoId'] ?? '';
            if ( ! $vid ) { continue; }
            $out[] = [
                'video_id'  => $vid,
                'title'     => $item['snippet']['title'] ?? '',
                'thumbnail' => $item['snippet']['thumbnails']['high']['url']
                              ?? $item['snippet']['thumbnails']['medium']['url']
                              ?? $item['snippet']['thumbnails']['default']['url']
                              ?? '',
                'published' => $item['snippet']['publishedAt'] ?? '',
            ];
        }
        return $out;
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
