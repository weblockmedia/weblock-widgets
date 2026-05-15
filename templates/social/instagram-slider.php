<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/_helpers.php';
?>
<div class="wlw wlw-instagram wlw-instagram--slider">
    <?php if ( empty( $items ) ) : ?>
        <p class="wlw-instagram__empty"><?php esc_html_e( 'Nem érhető el Instagram tartalom.', 'weblock-widgets' ); ?></p>
    <?php else : ?>
        <div class="wlw-carousel" data-wlw-carousel="ig">
            <button class="wlw-carousel__nav wlw-carousel__nav--prev" type="button" aria-label="<?php esc_attr_e( 'Előző', 'weblock-widgets' ); ?>">‹</button>
            <div class="wlw-carousel__track" tabindex="0">
                <?php foreach ( $items as $item ) :
                    $thumb = wlw_ig_thumbnail( $item );
                    if ( ! $thumb ) { continue; }
                    $caption = $item['caption'] ?? '';
                    $link    = $item['permalink'] ?? '#';
                ?>
                    <div class="wlw-carousel__slide">
                        <a class="wlw-instagram__item" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener nofollow">
                            <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( wlw_excerpt( $caption, 8 ) ); ?>" loading="lazy" />
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="wlw-carousel__nav wlw-carousel__nav--next" type="button" aria-label="<?php esc_attr_e( 'Következő', 'weblock-widgets' ); ?>">›</button>
        </div>
    <?php endif; ?>
</div>
