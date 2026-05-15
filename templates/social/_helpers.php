<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'wlw_ig_thumbnail' ) ) {
    function wlw_ig_thumbnail( $item ) {
        if ( ! empty( $item['thumbnail_url'] ) ) {
            return $item['thumbnail_url'];
        }
        if ( ! empty( $item['media_url'] ) ) {
            return $item['media_url'];
        }
        return '';
    }
}

if ( ! function_exists( 'wlw_excerpt' ) ) {
    function wlw_excerpt( $text, $words = 18 ) {
        $text = wp_strip_all_tags( (string) $text );
        return wp_trim_words( $text, $words, '…' );
    }
}
