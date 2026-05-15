<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cols = max( 1, min( 4, (int) $columns ) );
?>
<div class="wlw wlw-instagram wlw-instagram--embed" style="--wlw-ig-cols: <?php echo (int) $cols; ?>;">
    <div class="wlw-instagram__embed-grid">
        <?php foreach ( $urls as $url ) : ?>
            <div class="wlw-instagram__embed-item">
                <blockquote
                    class="instagram-media"
                    data-instgrm-permalink="<?php echo esc_url( $url ); ?>"
                    data-instgrm-version="14"
                    <?php if ( empty( $show_caption ) ) : ?>data-instgrm-captioned="false"<?php else : ?>data-instgrm-captioned="true"<?php endif; ?>
                    style="background:#FFF; border:0; margin: 1px; max-width:540px; min-width:240px; padding:0; width:99.375%;">
                    <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener nofollow">
                        <?php esc_html_e( 'Megnyitás Instagramban', 'weblock-widgets' ); ?>
                    </a>
                </blockquote>
            </div>
        <?php endforeach; ?>
    </div>
    <script async src="https://www.instagram.com/embed.js"></script>
</div>
