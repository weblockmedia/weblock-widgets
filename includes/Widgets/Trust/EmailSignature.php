<?php
namespace WeblockWidgets\Widgets\Trust;

use WeblockWidgets\Widgets\AbstractWidget;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EmailSignature extends AbstractWidget {
    private static $instance = null;
    protected $shortcode = 'wlw_email_signature';
    protected $block_name = '';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_meta() {
        return [
            'id'          => $this->shortcode,
            'label'       => __( 'E-mail aláírás', 'weblock-widgets' ),
            'icon'        => 'email-alt',
            'color'       => '#4285f4',
            'category'    => 'trust',
            'output_type' => 'html',
            'requires_api'=> false,
            'description' => __( 'HTML email aláírás generátor (Gmail / Outlook / Mailchimp) — Google csillagokkal.', 'weblock-widgets' ),
            'fields'      => [
                [
                    'name'    => 'template',
                    'label'   => __( 'Sablon', 'weblock-widgets' ),
                    'type'    => 'select',
                    'default' => 'text',
                    'options' => [
                        'text'  => __( 'Szöveges', 'weblock-widgets' ),
                        'image' => __( 'Profilképpel', 'weblock-widgets' ),
                        'logo'  => __( 'Saját logóval', 'weblock-widgets' ),
                    ],
                ],
                [
                    'name'        => 'name',
                    'label'       => __( 'Név', 'weblock-widgets' ),
                    'type'        => 'text',
                    'required'    => true,
                    'placeholder' => 'Kovács István',
                ],
                [
                    'name'        => 'title',
                    'label'       => __( 'Beosztás', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => 'Marketing manager',
                ],
                [
                    'name'        => 'company',
                    'label'       => __( 'Cég', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => 'My Company Kft.',
                ],
                [
                    'name'        => 'phone',
                    'label'       => __( 'Telefon', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => '+36 30 123 4567',
                ],
                [
                    'name'        => 'email',
                    'label'       => __( 'E-mail', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => 'kovacs.istvan@mycompany.hu',
                ],
                [
                    'name'        => 'website',
                    'label'       => __( 'Weboldal', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => 'mycompany.hu',
                ],
                [
                    'name'        => 'avatar_url',
                    'label'       => __( 'Profilkép URL (Profilképpel sablonhoz)', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => 'https://example.com/avatar.jpg',
                    'help'        => __( 'Négyzet vagy közeli kör alakú kép, min. 200×200 px ajánlott.', 'weblock-widgets' ),
                ],
                [
                    'name'        => 'logo_url',
                    'label'       => __( 'Logó URL (Saját logóval sablonhoz)', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => 'https://example.com/logo.png',
                ],
                [
                    'name'    => 'show_google_rating',
                    'label'   => __( 'Google csillag mutatása', 'weblock-widgets' ),
                    'type'    => 'toggle',
                    'default' => 'yes',
                ],
                [
                    'name'        => 'google_rating',
                    'label'       => __( 'Google csillag rating', 'weblock-widgets' ),
                    'type'        => 'number',
                    'default'     => 5,
                    'min'         => 0,
                    'max'         => 5,
                ],
                [
                    'name'        => 'google_review_count',
                    'label'       => __( 'Google értékelések száma', 'weblock-widgets' ),
                    'type'        => 'number',
                    'default'     => 0,
                    'min'         => 0,
                    'max'         => 99999,
                ],
                [
                    'name'        => 'google_reviews_url',
                    'label'       => __( 'Google Reviews link', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => 'https://g.page/r/...',
                    'help'        => __( 'Erre fognak rákattintani a "Google" logóra.', 'weblock-widgets' ),
                ],
                [
                    'name'    => 'accent_color',
                    'label'   => __( 'Kiemelő szín (hex)', 'weblock-widgets' ),
                    'type'    => 'text',
                    'default' => '#4285f4',
                ],
            ],
        ];
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'template'             => 'text',
            'name'                 => '',
            'title'                => '',
            'company'              => '',
            'phone'                => '',
            'email'                => '',
            'website'              => '',
            'avatar_url'           => '',
            'logo_url'             => '',
            'show_google_rating'   => 'yes',
            'google_rating'        => 5,
            'google_review_count'  => 0,
            'google_reviews_url'   => '',
            'accent_color'         => '#4285f4',
        ], $atts, $this->shortcode );

        if ( empty( $atts['name'] ) ) {
            return $this->error_message( __( 'Hiányzó név.', 'weblock-widgets' ) );
        }

        $template = in_array( $atts['template'], [ 'text', 'image', 'logo' ], true ) ? $atts['template'] : 'text';

        return $this->load_template( "trust/signature-{$template}.php", [
            'name'                => $atts['name'],
            'title'               => $atts['title'],
            'company'             => $atts['company'],
            'phone'               => $atts['phone'],
            'email'               => $atts['email'],
            'website'             => $atts['website'],
            'avatar_url'          => $atts['avatar_url'],
            'logo_url'            => $atts['logo_url'],
            'show_google_rating'  => 'yes' === $atts['show_google_rating'],
            'google_rating'       => max( 0, min( 5, (float) $atts['google_rating'] ) ),
            'google_review_count' => max( 0, (int) $atts['google_review_count'] ),
            'google_reviews_url'  => $atts['google_reviews_url'],
            'accent_color'        => $atts['accent_color'],
        ] );
    }
}
