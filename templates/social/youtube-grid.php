<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wlw wlw-youtube wlw-youtube--grid">
    <?php if ( empty( $items ) ) : ?>
        <p class="wlw-youtube__empty"><?php esc_html_e( 'Nem érhető el videó.', 'weblock-widgets' ); ?></p>
    <?php else : ?>
        <div class="wlw-youtube__grid">
            <?php foreach ( $items as $video ) :
                $url = 'https://www.youtube.com/watch?v=' . rawurlencode( $video['video_id'] );
            ?>
                <a class="wlw-youtube__item" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener nofollow"
                   data-wlw-yt="<?php echo esc_attr( $video['video_id'] ); ?>">
                    <span class="wlw-youtube__thumb">
                        <?php if ( ! empty( $video['thumbnail'] ) ) : ?>
                            <img src="<?php echo esc_url( $video['thumbnail'] ); ?>" alt="<?php echo esc_attr( $video['title'] ); ?>" loading="lazy" />
                        <?php endif; ?>
                        <span class="wlw-youtube__play" aria-hidden="true">▶</span>
                    </span>
                    <span class="wlw-youtube__title"><?php echo esc_html( $video['title'] ); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
