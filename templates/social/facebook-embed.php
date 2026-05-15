<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wlw wlw-facebook wlw-facebook--embed" style="--wlw-fb-height: <?php echo (int) $height; ?>px;">
    <iframe
        src="<?php echo esc_url( $embed_url ); ?>"
        title="<?php echo esc_attr( __( 'Facebook feed', 'weblock-widgets' ) ); ?>"
        scrolling="no"
        frameborder="0"
        allowfullscreen="true"
        allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
        loading="lazy"></iframe>
</div>
