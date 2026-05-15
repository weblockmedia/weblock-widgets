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
            'description' => __( 'HTML email aláírás generátor (Gmail / Outlook / Spark / Apple Mail) — Google csillagokkal és lábléc-szöveggel.', 'weblock-widgets' ),
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

                // --- Személyes adatok ---
                [
                    'name'        => 'name',
                    'label'       => __( 'Név', 'weblock-widgets' ),
                    'type'        => 'text',
                    'required'    => true,
                    'placeholder' => 'Kovács István',
                ],
                [
                    'name'        => 'title',
                    'label'       => __( 'Pozíció', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => 'Marketing manager',
                ],
                [
                    'name'        => 'company',
                    'label'       => __( 'Cég', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => 'My Company Kft.',
                ],

                // --- Kontakt ---
                [
                    'name'        => 'phone',
                    'label'       => __( 'Mobil / Telefon', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => '+36 30 123 4567',
                ],
                [
                    'name'        => 'phone_label',
                    'label'       => __( 'Mobil címke', 'weblock-widgets' ),
                    'type'        => 'text',
                    'default'     => 'Mobil',
                ],
                [
                    'name'        => 'email',
                    'label'       => __( 'E-mail', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => 'kovacs.istvan@mycompany.hu',
                ],
                [
                    'name'        => 'email_label',
                    'label'       => __( 'E-mail címke', 'weblock-widgets' ),
                    'type'        => 'text',
                    'default'     => 'E-mail',
                ],
                [
                    'name'        => 'website',
                    'label'       => __( 'Weboldal', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => 'mycompany.hu',
                ],
                [
                    'name'        => 'website_label',
                    'label'       => __( 'Weboldal címke', 'weblock-widgets' ),
                    'type'        => 'text',
                    'default'     => 'Web',
                ],

                // --- Képek ---
                [
                    'name'        => 'avatar_url',
                    'label'       => __( 'Profilkép URL (image / logo sablonhoz)', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => 'https://example.com/avatar.jpg',
                    'help'        => __( 'Min. 200×200 px ajánlott. A WP médiatárba feltöltött kép URL-jét másold be.', 'weblock-widgets' ),
                ],
                [
                    'name'        => 'logo_url',
                    'label'       => __( 'Logó URL (csak logo sablonhoz)', 'weblock-widgets' ),
                    'type'        => 'text',
                    'placeholder' => 'https://example.com/logo.png',
                ],
                [
                    'name'    => 'separator_line',
                    'label'   => __( 'Függőleges vonal a kép és szöveg között', 'weblock-widgets' ),
                    'type'    => 'toggle',
                    'default' => 'no',
                ],

                // --- Tipográfia ---
                [
                    'name'    => 'name_size',
                    'label'   => __( 'Név betűméret (px)', 'weblock-widgets' ),
                    'type'    => 'number',
                    'default' => 16,
                    'min'     => 10,
                    'max'     => 24,
                ],
                [
                    'name'    => 'contact_size',
                    'label'   => __( 'Kontakt betűméret (px)', 'weblock-widgets' ),
                    'type'    => 'number',
                    'default' => 13,
                    'min'     => 10,
                    'max'     => 20,
                ],
                [
                    'name'    => 'accent_color',
                    'label'   => __( 'Kiemelő szín (hex)', 'weblock-widgets' ),
                    'type'    => 'text',
                    'default' => '#4285f4',
                ],

                // --- Google rating ---
                [
                    'name'    => 'show_google_rating',
                    'label'   => __( 'Google csillag mutatása', 'weblock-widgets' ),
                    'type'    => 'toggle',
                    'default' => 'yes',
                ],
                [
                    'name'        => 'google_rating',
                    'label'       => __( 'Google csillag rating (0-5)', 'weblock-widgets' ),
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
                ],

                // --- Footer (jogi / környezeti) ---
                [
                    'name'        => 'env_footer',
                    'label'       => __( 'Környezetvédelmi lábléc-szöveg', 'weblock-widgets' ),
                    'type'        => 'textarea',
                    'placeholder' => __( 'pl. "Mielőtt kinyomtatja ezt az emailt, gondoljon a környezetre."', 'weblock-widgets' ),
                ],
                [
                    'name'        => 'confidential_footer',
                    'label'       => __( 'Titoktartási lábléc-szöveg', 'weblock-widgets' ),
                    'type'        => 'textarea',
                    'placeholder' => __( 'pl. GDPR / üzleti titok záradék', 'weblock-widgets' ),
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
            'phone_label'          => 'Mobil',
            'email'                => '',
            'email_label'          => 'E-mail',
            'website'              => '',
            'website_label'        => 'Web',
            'avatar_url'           => '',
            'logo_url'             => '',
            'separator_line'       => 'no',
            'name_size'            => 16,
            'contact_size'         => 13,
            'show_google_rating'   => 'yes',
            'google_rating'        => 5,
            'google_review_count'  => 0,
            'google_reviews_url'   => '',
            'accent_color'         => '#4285f4',
            'env_footer'           => '',
            'confidential_footer'  => '',
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
            'phone_label'         => $atts['phone_label'],
            'email'               => $atts['email'],
            'email_label'         => $atts['email_label'],
            'website'             => $atts['website'],
            'website_label'       => $atts['website_label'],
            'avatar_url'          => $atts['avatar_url'],
            'logo_url'            => $atts['logo_url'],
            'separator_line'      => 'yes' === $atts['separator_line'],
            'name_size'           => max( 10, min( 24, (int) $atts['name_size'] ) ),
            'contact_size'        => max( 10, min( 20, (int) $atts['contact_size'] ) ),
            'show_google_rating'  => 'yes' === $atts['show_google_rating'],
            'google_rating'       => max( 0, min( 5, (float) $atts['google_rating'] ) ),
            'google_review_count' => max( 0, (int) $atts['google_review_count'] ),
            'google_reviews_url'  => $atts['google_reviews_url'],
            'accent_color'        => $atts['accent_color'],
            'env_footer'          => $atts['env_footer'],
            'confidential_footer' => $atts['confidential_footer'],
        ] );
    }
}
