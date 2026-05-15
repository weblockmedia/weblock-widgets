<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wlw wlw-trustmark wlw-trustmark--pill wlw-trustmark--<?php echo esc_attr( $size ); ?>" style="--wlw-tm-color: <?php echo esc_attr( $badge['color'] ); ?>">
    <div class="wlw-trustmark__pill">
        <span class="wlw-trustmark__icon" aria-hidden="true"><?php echo $icon_svg; /* trusted inline SVG */ ?></span>
        <span class="wlw-trustmark__label"><?php echo esc_html( $badge['label'] ); ?></span>
    </div>
    <?php if ( ! empty( $verified_by ) ) : ?>
        <div class="wlw-trustmark__caption">
            <?php
            /* translators: %s: brand or company name that verifies the badge */
            printf( esc_html__( 'Igazolta: %s', 'weblock-widgets' ), esc_html( $verified_by ) );
            ?>
        </div>
    <?php endif; ?>
</div>
