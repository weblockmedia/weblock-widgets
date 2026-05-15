<?php
namespace WeblockWidgets\Widgets\Social;

use WeblockWidgets\Widgets\AbstractWidget;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TwitterFeed extends AbstractWidget {
    private static $instance = null;
    protected $shortcode = 'wlw_twitter_feed';
    protected $block_name = 'weblock-widgets/twitter-feed';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_meta() {
        return [
            'id'          => $this->shortcode,
            'label'       => __( 'Twitter / X Feed', 'weblock-widgets' ),
            'icon'        => 'twitter',
            'color'       => '#000000',
            'description' => __( 'Twitter / X timeline beágyazása felhasználónévvel (nem kell API kulcs).', 'weblock-widgets' ),
            'requires_api'=> false,
            'fields'      => [
                [
                    'name'        => 'username',
                    'label'       => __( 'Twitter / X felhasználónév', 'weblock-widgets' ),
                    'type'        => 'text',
                    'required'    => true,
                    'placeholder' => 'weblockgroup',
                    'help'        => __( '@-jel nélkül. A profil publikus kell legyen.', 'weblock-widgets' ),
                ],
                [
                    'name'    => 'height',
                    'label'   => __( 'Magasság (px)', 'weblock-widgets' ),
                    'type'    => 'number',
                    'default' => 600,
                    'min'     => 300,
                    'max'     => 1500,
                ],
                [
                    'name'    => 'theme',
                    'label'   => __( 'Téma', 'weblock-widgets' ),
                    'type'    => 'select',
                    'default' => 'light',
                    'options' => [
                        'light' => __( 'Világos', 'weblock-widgets' ),
                        'dark'  => __( 'Sötét', 'weblock-widgets' ),
                    ],
                ],
            ],
        ];
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'username' => '',
            'height'   => 600,
            'theme'    => 'light',
        ], $atts, $this->shortcode );

        $username = ltrim( trim( $atts['username'] ), '@' );
        if ( ! $username ) {
            return $this->error_message( __( 'Adj meg egy Twitter/X felhasználónevet.', 'weblock-widgets' ) );
        }

        return $this->load_template( 'social/twitter-embed.php', [
            'username' => $username,
            'height'   => max( 300, min( 1500, (int) $atts['height'] ) ),
            'theme'    => 'dark' === $atts['theme'] ? 'dark' : 'light',
        ] );
    }

    public function register_block() {
        parent::register_block();
        register_block_type( $this->block_name, [
            'api_version'     => 2,
            'title'           => __( 'Twitter / X Feed', 'weblock-widgets' ),
            'category'        => 'widgets',
            'icon'            => 'twitter',
            'render_callback' => function ( $attrs ) {
                return $this->render_shortcode( [
                    'username' => $attrs['username'] ?? '',
                    'height'   => $attrs['height']   ?? 600,
                    'theme'    => $attrs['theme']    ?? 'light',
                ] );
            },
            'attributes' => [
                'username' => [ 'type' => 'string', 'default' => '' ],
                'height'   => [ 'type' => 'number', 'default' => 600 ],
                'theme'    => [ 'type' => 'string', 'default' => 'light' ],
            ],
        ] );
    }
}
