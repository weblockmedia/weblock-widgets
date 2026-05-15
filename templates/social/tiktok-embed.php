<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cols = max( 1, min( 3, (int) $columns ) );
?>
<div class="wlw wlw-tiktok wlw-tiktok--embed" style="--wlw-tt-cols: <?php echo (int) $cols; ?>;">
    <div class="wlw-tiktok__grid">
        <?php foreach ( $videos as $v ) : ?>
            <div class="wlw-tiktok__item">
                <blockquote
                    class="tiktok-embed"
                    cite="<?php echo esc_url( $v['url'] ); ?>"
                    data-video-id="<?php echo esc_attr( $v['id'] ); ?>"
                    style="max-width: 605px; min-width: 280px;">
                    <section>
                        <a target="_blank" rel="noopener nofollow" href="<?php echo esc_url( $v['url'] ); ?>">
                            <?php esc_html_e( 'Megnyitás TikTokon', 'weblock-widgets' ); ?>
                        </a>
                    </section>
                </blockquote>
            </div>
        <?php endforeach; ?>
    </div>
    <script async src="https://www.tiktok.com/embed.js"></script>
</div>
