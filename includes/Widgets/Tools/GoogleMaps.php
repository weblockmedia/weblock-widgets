<?php
namespace WeblockWidgets\Widgets\Tools;

use WeblockWidgets\Widgets\AbstractWidget;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GoogleMaps extends AbstractWidget {
    private static $instance = null;
    protected $shortcode = 'wlw_google_map';
    protected $block_name = 'weblock-widgets/google-map';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_meta() {
        return [
            'id'          => $this->shortcode,
            'label'       => __( 'Google Map', 'weblock-widgets' ),
            'icon'        => 'location-alt',
            'color'       => '#34A853',
            'description' => __( 'Beágyazott Google térkép címmel vagy Place ID-vel.', 'weblock-widgets' ),
            'fields'      => [
                [
                    'name'        => 'address',
                    'label'       => __( 'Cím', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => 'Budapest, Király u. 1.',
                    'help'        => __( 'Vagy hagyd üresen és használd a Place ID-t.', 'weblock-widgets' ),
                ],
                [
                    'name'        => 'place_id',
                    'label'       => __( 'VAGY Place ID', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => 'ChIJ...',
                ],
                [
                    'name'    => 'zoom',
                    'label'   => __( 'Zoom szint', 'weblock-widgets' ),
                    'type'    => 'number',
                    'default' => 15,
                    'min'     => 1,
                    'max'     => 21,
                ],
                [
                    'name'    => 'height',
                    'label'   => __( 'Magasság (px)', 'weblock-widgets' ),
                    'type'    => 'number',
                    'default' => 400,
                    'min'     => 150,
                    'max'     => 1200,
                ],
            ],
        ];
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'address'  => '',
            'place_id' => '',
            'zoom'     => 15,
            'height'   => 400,
            'mode'     => 'place',
        ], $atts, $this->shortcode );

        $api_key = $this->get_setting( 'google_api_key' );
        if ( ! $api_key ) {
            return $this->error_message( __( 'Google API kulcs nincs beállítva.', 'weblock-widgets' ) );
        }

        $params = [
            'key'  => $api_key,
            'zoom' => max( 1, min( 21, (int) $atts['zoom'] ) ),
        ];
        if ( ! empty( $atts['place_id'] ) ) {
            $params['q'] = 'place_id:' . $atts['place_id'];
        } elseif ( ! empty( $atts['address'] ) ) {
            $params['q'] = $atts['address'];
        } else {
            return $this->error_message( __( 'Hiányzó address vagy place_id paraméter.', 'weblock-widgets' ) );
        }

        $mode   = in_array( $atts['mode'], [ 'place', 'directions', 'search' ], true ) ? $atts['mode'] : 'place';
        $url    = add_query_arg( $params, 'https://www.google.com/maps/embed/v1/' . $mode );
        $height = max( 150, (int) $atts['height'] );

        return $this->load_template( 'tools/google-map.php', [
            'embed_url' => $url,
            'height'    => $height,
            'address'   => $atts['address'],
        ] );
    }

    public function register_block() {
        parent::register_block();
        register_block_type( $this->block_name, [
            'api_version'     => 2,
            'title'           => __( 'Google Map', 'weblock-widgets' ),
            'category'        => 'widgets',
            'icon'            => 'location-alt',
            'render_callback' => function ( $attrs ) {
                return $this->render_shortcode( [
                    'address'  => $attrs['address']  ?? '',
                    'place_id' => $attrs['placeId']  ?? '',
                    'zoom'     => $attrs['zoom']     ?? 15,
                    'height'   => $attrs['height']   ?? 400,
                    'mode'     => $attrs['mode']     ?? 'place',
                ] );
            },
            'attributes' => [
                'address' => [ 'type' => 'string', 'default' => '' ],
                'placeId' => [ 'type' => 'string', 'default' => '' ],
                'zoom'    => [ 'type' => 'number', 'default' => 15 ],
                'height'  => [ 'type' => 'number', 'default' => 400 ],
                'mode'    => [ 'type' => 'string', 'default' => 'place' ],
            ],
        ] );
    }
}
