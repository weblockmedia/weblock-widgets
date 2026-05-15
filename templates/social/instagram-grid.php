<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/_helpers.php';
?>
<div class="wlw wlw-instagram wlw-instagram--grid">
    <?php if ( empty( $items ) ) : ?>
        <p class="wlw-instagram__empty"><?php esc_html_e( 'Nem érhető el Instagram tartalom.', 'weblock-widgets' ); ?></p>
    <?php else : ?>
        <div class="wlw-instagram__grid">
            <?php foreach ( $items as $item ) :
                $thumb = wlw_ig_thumbnail( $item );
                if ( ! $thumb ) { continue; }
                $caption = $item['caption'] ?? '';
                $link    = $item['permalink'] ?? '#';
                $is_video = isset( $item['media_type'] ) && 'VIDEO' === $item['media_type'];
            ?>
                <a class="wlw-instagram__item<?php echo $is_video ? ' is-video' : ''; ?>" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener nofollow">
                    <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( wlw_excerpt( $caption, 8 ) ); ?>" loading="lazy" />
                    <?php if ( $is_video ) : ?>
                        <span class="wlw-instagram__badge" aria-hidden="true">▶</span>
                    <?php endif; ?>
                    <?php if ( ! empty( $show_caption ) && $caption ) : ?>
                        <span class="wlw-instagram__caption"><?php echo esc_html( wlw_excerpt( $caption ) ); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
