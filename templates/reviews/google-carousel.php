<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/_helpers.php';
$carousel_id = 'wlw-rc-' . wp_generate_uuid4();
?>
<div class="wlw wlw-reviews wlw-reviews--google wlw-reviews--carousel" data-wlw-carousel="<?php echo esc_attr( $carousel_id ); ?>">
    <?php if ( ! empty( $show_header ) ) : ?>
        <?php echo wlw_review_header( $place_name, $rating, $total_count, $place_url ); ?>
    <?php endif; ?>

    <?php if ( empty( $reviews ) ) : ?>
        <p class="wlw-reviews__empty"><?php esc_html_e( 'Még nincs megjeleníthető értékelés.', 'weblock-widgets' ); ?></p>
    <?php else : ?>
        <div class="wlw-carousel" id="<?php echo esc_attr( $carousel_id ); ?>">
            <button class="wlw-carousel__nav wlw-carousel__nav--prev" type="button" aria-label="<?php esc_attr_e( 'Előző', 'weblock-widgets' ); ?>">‹</button>
            <div class="wlw-carousel__track" tabindex="0">
                <?php foreach ( $reviews as $review ) : ?>
                    <div class="wlw-carousel__slide"><?php echo wlw_review_card( $review ); ?></div>
                <?php endforeach; ?>
            </div>
            <button class="wlw-carousel__nav wlw-carousel__nav--next" type="button" aria-label="<?php esc_attr_e( 'Következő', 'weblock-widgets' ); ?>">›</button>
        </div>
    <?php endif; ?>
</div>
