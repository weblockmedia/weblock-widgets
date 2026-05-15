<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/_helpers.php';
?>
<div class="wlw wlw-reviews wlw-reviews--google wlw-reviews--grid">
    <?php if ( ! empty( $show_header ) ) : ?>
        <?php echo wlw_review_header( $place_name, $rating, $total_count, $place_url ); ?>
    <?php endif; ?>

    <?php if ( empty( $reviews ) ) : ?>
        <p class="wlw-reviews__empty"><?php esc_html_e( 'Még nincs megjeleníthető értékelés.', 'weblock-widgets' ); ?></p>
    <?php else : ?>
        <div class="wlw-reviews__grid">
            <?php foreach ( $reviews as $review ) : ?>
                <?php echo wlw_review_card( $review ); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
