<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'wlw_sig_stars' ) ) {
    function wlw_sig_stars( $rating ) {
        $rating = max( 0, min( 5, (float) $rating ) );
        $full   = (int) floor( $rating );
        $half   = ( $rating - $full ) >= 0.5;
        $empty  = 5 - $full - ( $half ? 1 : 0 );
        $out = '';
        for ( $i = 0; $i < $full;  $i++ ) { $out .= '★'; }
        if ( $half ) { $out .= '★'; }
        for ( $i = 0; $i < $empty; $i++ ) { $out .= '☆'; }
        return $out;
    }
}

if ( ! function_exists( 'wlw_sig_google_block' ) ) {
    function wlw_sig_google_block( $rating, $count, $url, $accent ) {
        $stars = wlw_sig_stars( $rating );
        $link  = $url ? esc_url( $url ) : '#';
        $count_str = $count > 0
            ? sprintf( /* translators: %s: number of reviews */ esc_html__( '%s értékelés', 'weblock-widgets' ), number_format_i18n( $count ) )
            : '';
        ob_start();
        ?>
        <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:6px 0 0 0;">
            <tr>
                <td style="padding:0 8px 0 0;vertical-align:middle;">
                    <a href="<?php echo esc_attr( $link ); ?>" target="_blank" style="text-decoration:none;color:#1a73e8;font-family:Arial,sans-serif;font-size:13px;font-weight:700;">Google</a>
                </td>
                <td style="vertical-align:middle;color:#f5a623;font-family:Arial,sans-serif;font-size:14px;letter-spacing:1px;">
                    <?php echo esc_html( $stars ); ?>
                </td>
                <?php if ( $count_str ) : ?>
                    <td style="padding:0 0 0 8px;vertical-align:middle;color:#5f6368;font-family:Arial,sans-serif;font-size:12px;">
                        <?php echo esc_html( $count_str ); ?>
                    </td>
                <?php endif; ?>
            </tr>
        </table>
        <?php
        return ob_get_clean();
    }
}
