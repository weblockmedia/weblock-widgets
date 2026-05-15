<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<span class="wlw wlw-trustmark wlw-trustmark--compact wlw-trustmark--<?php echo esc_attr( $size ); ?>" style="--wlw-tm-color: <?php echo esc_attr( $badge['color'] ); ?>">
    <span class="wlw-trustmark__icon" aria-hidden="true"><?php echo $icon_svg; ?></span>
    <span class="wlw-trustmark__label"><?php echo esc_html( $badge['label'] ); ?></span>
</span>
