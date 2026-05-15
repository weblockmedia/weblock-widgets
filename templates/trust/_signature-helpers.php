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

if ( ! function_exists( 'wlw_sig_contact_table' ) ) {
    function wlw_sig_contact_table( $rows, $accent, $contact_size ) {
        if ( empty( $rows ) ) {
            return '';
        }
        ob_start();
        ?>
        <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin-top:6px;font-size:<?php echo (int) $contact_size; ?>px;color:#1a1a1a;font-family:Arial,Helvetica,sans-serif;">
            <?php foreach ( $rows as $row ) :
                if ( empty( $row['value'] ) ) { continue; }
                $label = $row['label'] ?? '';
                $value = $row['value'];
                $href  = $row['href']  ?? '';
            ?>
                <tr>
                    <td style="padding:2px 8px 2px 0;color:#5f6368;font-weight:700;white-space:nowrap;">
                        <?php echo esc_html( $label ); ?>
                    </td>
                    <td style="padding:2px 0;">
                        <?php if ( $href ) : ?>
                            <a href="<?php echo esc_attr( $href ); ?>" style="color:<?php echo esc_attr( $accent ); ?>;text-decoration:none;">
                                <?php echo esc_html( $value ); ?>
                            </a>
                        <?php else : ?>
                            <?php echo esc_html( $value ); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
        return ob_get_clean();
    }
}

if ( ! function_exists( 'wlw_sig_footer_text' ) ) {
    function wlw_sig_footer_text( $env, $confidential ) {
        if ( ! $env && ! $confidential ) {
            return '';
        }
        ob_start();
        ?>
        <div style="margin-top:12px;padding-top:8px;border-top:1px solid #e5e7eb;font-family:Arial,Helvetica,sans-serif;font-size:10px;color:#9ca3af;line-height:1.45;">
            <?php if ( $env ) : ?>
                <p style="margin:0 0 6px;"><?php echo esc_html( $env ); ?></p>
            <?php endif; ?>
            <?php if ( $confidential ) : ?>
                <p style="margin:0;"><?php echo esc_html( $confidential ); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

if ( ! function_exists( 'wlw_sig_website_href' ) ) {
    function wlw_sig_website_href( $website ) {
        if ( ! $website ) { return ''; }
        return strpos( $website, '://' ) === false ? 'https://' . $website : $website;
    }
}
