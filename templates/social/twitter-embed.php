<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wlw wlw-twitter wlw-twitter--embed" style="--wlw-tw-height: <?php echo (int) $height; ?>px;">
    <a class="twitter-timeline"
       data-theme="<?php echo esc_attr( $theme ); ?>"
       data-height="<?php echo esc_attr( (int) $height ); ?>"
       href="<?php echo esc_url( 'https://twitter.com/' . $username ); ?>">
        <?php /* translators: %s: twitter username */
        printf( esc_html__( '@%s posztjai', 'weblock-widgets' ), esc_html( $username ) ); ?>
    </a>
    <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
</div>
