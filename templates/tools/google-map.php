<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wlw wlw-map" style="--wlw-map-height: <?php echo (int) $height; ?>px;">
    <iframe
        src="<?php echo esc_url( $embed_url ); ?>"
        title="<?php echo esc_attr( $address ? $address : __( 'Térkép', 'weblock-widgets' ) ); ?>"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        allowfullscreen></iframe>
</div>
