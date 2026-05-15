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
            'description' => __( 'Beágyazott Google térkép címmel (nem kell API kulcs).', 'weblock-widgets' ),
            'requires_api'=> false,
            'fields'      => [
                [
                    'name'        => 'address',
                    'label'       => __( 'Cím', 'weblock-widgets' ),
                    'type'        => 'text',
                    'required'    => true,
                    'placeholder' => 'Budapest, Király u. 1.',
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
            'address' => '',
            'zoom'    => 15,
            'height'  => 400,
        ], $atts, $this->shortcode );

        if ( empty( $atts['address'] ) ) {
            return $this->error_message( __( 'Hiányzó address paraméter.', 'weblock-widgets' ) );
        }

        $url = add_query_arg( [
            'q'      => $atts['address'],
            'z'      => max( 1, min( 21, (int) $atts['zoom'] ) ),
            'output' => 'embed',
        ], 'https://maps.google.com/maps' );

        return $this->load_template( 'tools/google-map.php', [
            'embed_url' => $url,
            'height'    => max( 150, (int) $atts['height'] ),
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
                    'address' => $attrs['address'] ?? '',
                    'zoom'    => $attrs['zoom']    ?? 15,
                    'height'  => $attrs['height']  ?? 400,
                ] );
            },
            'attributes' => [
                'address' => [ 'type' => 'string', 'default' => '' ],
                'zoom'    => [ 'type' => 'number', 'default' => 15 ],
                'height'  => [ 'type' => 'number', 'default' => 400 ],
            ],
        ] );
    }
}
