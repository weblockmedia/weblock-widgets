<?php
namespace WeblockWidgets\Widgets\Reviews;

use WeblockWidgets\Core\ApiCache;
use WeblockWidgets\Widgets\AbstractWidget;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GoogleReviews extends AbstractWidget {
    private static $instance = null;
    protected $shortcode = 'wlw_google_reviews';
    protected $block_name = 'weblock-widgets/google-reviews';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_meta() {
        return [
            'id'          => $this->shortcode,
            'label'       => __( 'Google Reviews', 'weblock-widgets' ),
            'icon'        => 'star-filled',
            'color'       => '#fbbc04',
            'description' => __( 'Google Cégem értékelések megjelenítése a Google Places API-ról.', 'weblock-widgets' ),
            'fields'      => [
                [
                    'name'        => 'place_id',
                    'label'       => __( 'Google Place ID', 'weblock-widgets' ),
                    'type'        => 'text',
                    'required'    => true,
                    'placeholder' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',
                    'help'        => __( 'A Google Place ID-t itt találod: https://developers.google.com/maps/documentation/places/web-service/place-id', 'weblock-widgets' ),
                ],
                [
                    'name'    => 'count',
                    'label'   => __( 'Vélemények száma', 'weblock-widgets' ),
                    'type'    => 'number',
                    'default' => 6,
                    'min'     => 1,
                    'max'     => 5,
                    'help'    => __( 'A Google API maximum 5 véleményt ad vissza egy hívásra.', 'weblock-widgets' ),
                ],
                [
                    'name'    => 'layout',
                    'label'   => __( 'Elrendezés', 'weblock-widgets' ),
                    'type'    => 'select',
                    'default' => 'grid',
                    'options' => [
                        'grid'     => __( 'Rács', 'weblock-widgets' ),
                        'list'     => __( 'Lista', 'weblock-widgets' ),
                        'carousel' => __( 'Karusszel', 'weblock-widgets' ),
                    ],
                ],
                [
                    'name'    => 'min_rating',
                    'label'   => __( 'Minimum csillagok', 'weblock-widgets' ),
                    'type'    => 'select',
                    'default' => 0,
                    'options' => [
                        0 => __( 'Mind', 'weblock-widgets' ),
                        3 => __( '3★ és felette', 'weblock-widgets' ),
                        4 => __( '4★ és felette', 'weblock-widgets' ),
                        5 => __( 'Csak 5★', 'weblock-widgets' ),
                    ],
                ],
                [
                    'name'    => 'show_header',
                    'label'   => __( 'Fejléc megjelenítése (átlag, csillagok, CTA)', 'weblock-widgets' ),
                    'type'    => 'toggle',
                    'default' => 'yes',
                ],
            ],
        ];
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'place_id'   => '',
            'count'      => 6,
            'layout'     => 'grid',
            'min_rating' => 0,
            'lang'       => 'hu',
            'show_header'=> 'yes',
        ], $atts, $this->shortcode );

        if ( empty( $atts['place_id'] ) ) {
            return $this->error_message( __( 'Hiányzó place_id paraméter.', 'weblock-widgets' ) );
        }

        $api_key = $this->get_setting( 'google_api_key' );
        if ( ! $api_key ) {
            return $this->error_message( __( 'Google API kulcs nincs beállítva (Weblock Widgets → Beállítások).', 'weblock-widgets' ) );
        }

        $data = $this->fetch_place_details( $atts['place_id'], $api_key, $atts['lang'] );
        if ( is_wp_error( $data ) ) {
            return $this->error_message( __( 'Google Places API hiba: ', 'weblock-widgets' ) . $data->get_error_message() );
        }

        $reviews = isset( $data['reviews'] ) && is_array( $data['reviews'] ) ? $data['reviews'] : [];
        if ( (int) $atts['min_rating'] > 0 ) {
            $min = (int) $atts['min_rating'];
            $reviews = array_filter( $reviews, function ( $r ) use ( $min ) {
                return isset( $r['rating'] ) && (int) $r['rating'] >= $min;
            } );
        }
        $reviews = array_slice( $reviews, 0, max( 1, (int) $atts['count'] ) );

        $layout = in_array( $atts['layout'], [ 'grid', 'list', 'carousel' ], true ) ? $atts['layout'] : 'grid';

        return $this->load_template( "reviews/google-{$layout}.php", [
            'reviews'      => $reviews,
            'rating'       => isset( $data['rating'] ) ? (float) $data['rating'] : 0,
            'total_count'  => isset( $data['user_ratings_total'] ) ? (int) $data['user_ratings_total'] : 0,
            'place_name'   => isset( $data['name'] ) ? $data['name'] : '',
            'place_url'    => isset( $data['url'] ) ? $data['url'] : '',
            'show_header'  => 'yes' === $atts['show_header'],
        ] );
    }

    private function fetch_place_details( $place_id, $api_key, $lang = 'hu' ) {
        $url = add_query_arg( [
            'place_id' => $place_id,
            'fields'   => 'name,rating,user_ratings_total,reviews,url',
            'key'      => $api_key,
            'language' => $lang,
            'reviews_sort' => 'newest',
        ], 'https://maps.googleapis.com/maps/api/place/details/json' );

        $response = ApiCache::instance()->fetch( $url );
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        if ( isset( $response['status'] ) && 'OK' !== $response['status'] ) {
            return new \WP_Error( 'wlw_places_error', sprintf( '%s: %s',
                $response['status'],
                $response['error_message'] ?? ''
            ) );
        }
        return $response['result'] ?? [];
    }

    public function register_block() {
        parent::register_block();
        register_block_type( $this->block_name, [
            'api_version'     => 2,
            'title'           => __( 'Google Reviews', 'weblock-widgets' ),
            'category'        => 'widgets',
            'icon'            => 'star-filled',
            'render_callback' => function ( $attrs ) {
                return $this->render_shortcode( [
                    'place_id'   => $attrs['placeId']   ?? '',
                    'count'      => $attrs['count']     ?? 6,
                    'layout'     => $attrs['layout']    ?? 'grid',
                    'min_rating' => $attrs['minRating'] ?? 0,
                    'lang'       => $attrs['lang']      ?? 'hu',
                    'show_header'=> ! empty( $attrs['showHeader'] ) ? 'yes' : 'no',
                ] );
            },
            'attributes' => [
                'placeId'    => [ 'type' => 'string', 'default' => '' ],
                'count'      => [ 'type' => 'number', 'default' => 6 ],
                'layout'     => [ 'type' => 'string', 'default' => 'grid' ],
                'minRating'  => [ 'type' => 'number', 'default' => 0 ],
                'lang'       => [ 'type' => 'string', 'default' => 'hu' ],
                'showHeader' => [ 'type' => 'boolean', 'default' => true ],
            ],
        ] );
    }
}
