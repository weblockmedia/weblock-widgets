<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wlw wlw-trustmark wlw-trustmark--card wlw-trustmark--<?php echo esc_attr( $size ); ?>" style="--wlw-tm-color: <?php echo esc_attr( $badge['color'] ); ?>">
    <div class="wlw-trustmark__icon-box" aria-hidden="true"><?php echo $icon_svg; ?></div>
    <div class="wlw-trustmark__body">
        <div class="wlw-trustmark__label"><?php echo esc_html( $badge['label'] ); ?></div>
        <?php if ( ! empty( $verified_by ) ) : ?>
            <div class="wlw-trustmark__caption">
                <?php
                /* translators: %s: brand name */
                printf( esc_html__( 'Igazolta: %s', 'weblock-widgets' ), esc_html( $verified_by ) );
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>
