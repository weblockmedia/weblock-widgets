<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/_helpers.php';
?>
<div class="wlw wlw-facebook wlw-facebook--list">
    <?php if ( empty( $items ) ) : ?>
        <p class="wlw-facebook__empty"><?php esc_html_e( 'Nem érhető el Facebook tartalom.', 'weblock-widgets' ); ?></p>
    <?php else : ?>
        <div class="wlw-facebook__list">
            <?php foreach ( $items as $post ) :
                $message = $post['message']       ?? '';
                $link    = $post['permalink_url'] ?? '#';
                $img     = $post['full_picture']  ?? '';
                $time    = isset( $post['created_time'] ) ? strtotime( $post['created_time'] ) : 0;
            ?>
                <article class="wlw-facebook__post">
                    <?php if ( ! empty( $show_image ) && $img ) : ?>
                        <a class="wlw-facebook__media" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener nofollow">
                            <img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy" />
                        </a>
                    <?php endif; ?>
                    <div class="wlw-facebook__body">
                        <?php if ( $time ) : ?>
                            <time class="wlw-facebook__date" datetime="<?php echo esc_attr( gmdate( 'c', $time ) ); ?>">
                                <?php echo esc_html( date_i18n( get_option( 'date_format' ), $time ) ); ?>
                            </time>
                        <?php endif; ?>
                        <?php if ( $message ) : ?>
                            <p class="wlw-facebook__text"><?php echo esc_html( wlw_excerpt( $message, 40 ) ); ?></p>
                        <?php endif; ?>
                        <a class="wlw-facebook__more" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener nofollow">
                            <?php esc_html_e( 'Tovább a Facebookon →', 'weblock-widgets' ); ?>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
