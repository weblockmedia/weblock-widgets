<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wlw wlw-youtube wlw-youtube--list">
    <?php if ( empty( $items ) ) : ?>
        <p class="wlw-youtube__empty"><?php esc_html_e( 'Nem érhető el videó.', 'weblock-widgets' ); ?></p>
    <?php else : ?>
        <ul class="wlw-youtube__list">
            <?php foreach ( $items as $video ) :
                $url = 'https://www.youtube.com/watch?v=' . rawurlencode( $video['video_id'] );
            ?>
                <li class="wlw-youtube__row">
                    <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener nofollow">
                        <span class="wlw-youtube__row-thumb">
                            <?php if ( ! empty( $video['thumbnail'] ) ) : ?>
                                <img src="<?php echo esc_url( $video['thumbnail'] ); ?>" alt="" loading="lazy" />
                            <?php endif; ?>
                        </span>
                        <span class="wlw-youtube__row-title"><?php echo esc_html( $video['title'] ); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
