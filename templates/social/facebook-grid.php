<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/_helpers.php';
?>
<div class="wlw wlw-facebook wlw-facebook--grid">
    <?php if ( empty( $items ) ) : ?>
        <p class="wlw-facebook__empty"><?php esc_html_e( 'Nem érhető el Facebook tartalom.', 'weblock-widgets' ); ?></p>
    <?php else : ?>
        <div class="wlw-facebook__grid">
            <?php foreach ( $items as $post ) :
                $message = $post['message']       ?? '';
                $link    = $post['permalink_url'] ?? '#';
                $img     = $post['full_picture']  ?? '';
            ?>
                <a class="wlw-facebook__card" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener nofollow">
                    <?php if ( $img ) : ?>
                        <span class="wlw-facebook__card-media" style="background-image:url('<?php echo esc_url( $img ); ?>')"></span>
                    <?php endif; ?>
                    <span class="wlw-facebook__card-text">
                        <?php echo esc_html( wlw_excerpt( $message, 20 ) ); ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
