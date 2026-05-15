<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wlw wlw-pinterest wlw-pinterest--embed">
    <a data-pin-do="<?php echo esc_attr( $type ); ?>"
       data-pin-board-width="<?php echo esc_attr( (int) $width ); ?>"
       data-pin-scale-height="<?php echo esc_attr( (int) $height ); ?>"
       data-pin-scale-width="<?php echo esc_attr( (int) ( $width / 5 ) ); ?>"
       href="<?php echo esc_url( $url ); ?>">
        <?php esc_html_e( 'Megnyitás Pinteresten', 'weblock-widgets' ); ?>
    </a>
    <script async defer src="//assets.pinterest.com/js/pinit.js"></script>
</div>
