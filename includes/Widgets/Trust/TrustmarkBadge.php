<?php
namespace WeblockWidgets\Widgets\Trust;

use WeblockWidgets\Widgets\AbstractWidget;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TrustmarkBadge extends AbstractWidget {
    private static $instance = null;
    protected $shortcode = 'wlw_trustmark';
    protected $block_name = 'weblock-widgets/trustmark';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function badges() {
        return [
            'certified_secure' => [
                'label'  => __( 'Tanúsítottan Biztonságos', 'weblock-widgets' ),
                'icon'   => 'shield',
                'color'  => '#16a34a',
            ],
            'secure_checkout' => [
                'label'  => __( 'Biztonságos fizetés', 'weblock-widgets' ),
                'icon'   => 'cart',
                'color'  => '#16a34a',
            ],
            'secure_form' => [
                'label'  => __( 'Biztonságos űrlap', 'weblock-widgets' ),
                'icon'   => 'lock',
                'color'  => '#16a34a',
            ],
            'secure_ssl' => [
                'label'  => __( 'Biztonságos SSL', 'weblock-widgets' ),
                'icon'   => 'lock-circle',
                'color'  => '#16a34a',
            ],
            'secure_login' => [
                'label'  => __( 'Biztonságos bejelentkezés', 'weblock-widgets' ),
                'icon'   => 'key',
                'color'  => '#16a34a',
            ],
            'issue_free_orders' => [
                'label'  => __( 'Problémamentes rendelés', 'weblock-widgets' ),
                'icon'   => 'package',
                'color'  => '#16a34a',
            ],
            'spam_free' => [
                'label'  => __( 'Spammentes', 'weblock-widgets' ),
                'icon'   => 'check',
                'color'  => '#16a34a',
            ],
            'free_shipping' => [
                'label'  => __( 'Ingyenes szállítás', 'weblock-widgets' ),
                'icon'   => 'truck',
                'color'  => '#0ea5e9',
            ],
            'exceptional_support' => [
                'label'  => __( 'Rendkívüli támogatás', 'weblock-widgets' ),
                'icon'   => 'headset',
                'color'  => '#0ea5e9',
            ],
            'exceptional_service' => [
                'label'  => __( 'Rendkívüli ügyfélszolgálat', 'weblock-widgets' ),
                'icon'   => 'star',
                'color'  => '#0ea5e9',
            ],
            'money_back_30' => [
                'label'  => __( '30 napos pénzvisszafizetési garancia', 'weblock-widgets' ),
                'icon'   => 'rotate',
                'color'  => '#f59e0b',
            ],
        ];
    }

    public function get_meta() {
        $badges  = self::badges();
        $options = [];
        foreach ( $badges as $key => $b ) {
            $options[ $key ] = $b['label'];
        }

        return [
            'id'          => $this->shortcode,
            'label'       => __( 'Trustmark badge', 'weblock-widgets' ),
            'icon'        => 'shield',
            'color'       => '#16a34a',
            'category'    => 'trust',
            'requires_api'=> false,
            'description' => __( 'Biztonsági és bizalmi jelvények (SSL, spam-free, money-back, support) — nem kell API kulcs.', 'weblock-widgets' ),
            'fields'      => [
                [
                    'name'    => 'badge',
                    'label'   => __( 'Badge típus', 'weblock-widgets' ),
                    'type'    => 'select',
                    'default' => 'certified_secure',
                    'options' => $options,
                ],
                [
                    'name'    => 'style',
                    'label'   => __( 'Stílus', 'weblock-widgets' ),
                    'type'    => 'select',
                    'default' => 'pill',
                    'options' => [
                        'pill'     => __( 'Pirula (címke + tooltip)', 'weblock-widgets' ),
                        'compact'  => __( 'Kompakt címke', 'weblock-widgets' ),
                        'card'     => __( 'Kártya (ikon + szöveg)', 'weblock-widgets' ),
                    ],
                ],
                [
                    'name'    => 'verified_by',
                    'label'   => __( 'Igazolta (alsó szöveg)', 'weblock-widgets' ),
                    'type'    => 'text',
                    'default' => '',
                    'placeholder' => __( 'pl. Weblock Group', 'weblock-widgets' ),
                    'help'    => __( 'Ha üres, nem jelenik meg a kis "Igazolta:" sor. Csak Pirula stílusnál látszik.', 'weblock-widgets' ),
                ],
                [
                    'name'    => 'size',
                    'label'   => __( 'Méret', 'weblock-widgets' ),
                    'type'    => 'select',
                    'default' => 'medium',
                    'options' => [
                        'small'  => __( 'Kicsi', 'weblock-widgets' ),
                        'medium' => __( 'Közepes', 'weblock-widgets' ),
                        'large'  => __( 'Nagy', 'weblock-widgets' ),
                    ],
                ],
            ],
        ];
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'badge'       => 'certified_secure',
            'style'       => 'pill',
            'verified_by' => '',
            'size'        => 'medium',
        ], $atts, $this->shortcode );

        $badges = self::badges();
        if ( ! isset( $badges[ $atts['badge'] ] ) ) {
            return $this->error_message( __( 'Ismeretlen badge típus.', 'weblock-widgets' ) );
        }
        $badge = $badges[ $atts['badge'] ];
        $style = in_array( $atts['style'], [ 'pill', 'compact', 'card' ], true ) ? $atts['style'] : 'pill';
        $size  = in_array( $atts['size'], [ 'small', 'medium', 'large' ], true ) ? $atts['size'] : 'medium';

        return $this->load_template( "trust/badge-{$style}.php", [
            'badge'       => $badge,
            'icon_svg'    => $this->get_icon_svg( $badge['icon'] ),
            'verified_by' => $atts['verified_by'],
            'size'        => $size,
        ] );
    }

    private function get_icon_svg( $name ) {
        $icons = [
            'shield'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v6c0 5.5 3.8 10.7 8 12 4.2-1.3 8-6.5 8-12V5l-8-3z"/><path d="m9 12 2 2 4-4"/></svg>',
            'cart'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>',
            'lock'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
            'lock-circle' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><rect x="8" y="11" width="8" height="6" rx="1"/><path d="M10 11V9a2 2 0 0 1 4 0v2"/></svg>',
            'key'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="3.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg>',
            'package'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 9.4 7.55 4.24"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>',
            'check'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
            'truck'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17h4V5H2v12h3"/><path d="M14 9h4l4 4v4h-2"/><path d="M14 17h5"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>',
            'headset'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1v-7h3v5zM3 19a2 2 0 0 0 2 2h1v-7H3v5z"/></svg>',
            'star'        => '<svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
            'rotate'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v6h6"/></svg>',
        ];
        return $icons[ $name ] ?? $icons['check'];
    }

    public function register_block() {
        parent::register_block();
        register_block_type( $this->block_name, [
            'api_version'     => 2,
            'title'           => __( 'Trustmark Badge', 'weblock-widgets' ),
            'category'        => 'widgets',
            'icon'            => 'shield',
            'render_callback' => function ( $attrs ) {
                return $this->render_shortcode( [
                    'badge'       => $attrs['badge']      ?? 'certified_secure',
                    'style'       => $attrs['style']      ?? 'pill',
                    'verified_by' => $attrs['verifiedBy'] ?? '',
                    'size'        => $attrs['size']       ?? 'medium',
                ] );
            },
            'attributes' => [
                'badge'      => [ 'type' => 'string', 'default' => 'certified_secure' ],
                'style'      => [ 'type' => 'string', 'default' => 'pill' ],
                'verifiedBy' => [ 'type' => 'string', 'default' => '' ],
                'size'       => [ 'type' => 'string', 'default' => 'medium' ],
            ],
        ] );
    }
}
