<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/_signature-helpers.php';
$accent = $accent_color ? $accent_color : '#4285f4';
?>
<table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;color:#1a1a1a;line-height:1.45;">
    <tr>
        <td style="padding:0;">
            <div style="font-size:16px;font-weight:700;color:#1a1a1a;"><?php echo esc_html( $name ); ?></div>
            <?php if ( $title ) : ?>
                <div style="font-size:13px;color:#5f6368;"><?php echo esc_html( $title ); ?><?php if ( $company ) : ?> — <strong style="color:#1a1a1a;"><?php echo esc_html( $company ); ?></strong><?php endif; ?></div>
            <?php elseif ( $company ) : ?>
                <div style="font-size:13px;color:#5f6368;"><strong style="color:#1a1a1a;"><?php echo esc_html( $company ); ?></strong></div>
            <?php endif; ?>

            <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin-top:6px;font-size:13px;color:#1a1a1a;">
                <?php if ( $phone ) : ?>
                <tr><td style="padding:2px 8px 2px 0;color:#5f6368;font-weight:700;">M</td><td style="padding:2px 0;"><?php echo esc_html( $phone ); ?></td></tr>
                <?php endif; ?>
                <?php if ( $email ) : ?>
                <tr><td style="padding:2px 8px 2px 0;color:#5f6368;font-weight:700;">E</td><td style="padding:2px 0;"><a href="mailto:<?php echo esc_attr( $email ); ?>" style="color:<?php echo esc_attr( $accent ); ?>;text-decoration:none;"><?php echo esc_html( $email ); ?></a></td></tr>
                <?php endif; ?>
                <?php if ( $website ) : ?>
                <tr><td style="padding:2px 8px 2px 0;color:#5f6368;font-weight:700;">W</td><td style="padding:2px 0;"><a href="<?php echo esc_url( ( strpos( $website, '://' ) === false ? 'https://' : '' ) . $website ); ?>" style="color:<?php echo esc_attr( $accent ); ?>;text-decoration:none;"><?php echo esc_html( $website ); ?></a></td></tr>
                <?php endif; ?>
            </table>

            <?php if ( ! empty( $show_google_rating ) ) : ?>
                <?php echo wlw_sig_google_block( $google_rating, $google_review_count, $google_reviews_url, $accent ); ?>
            <?php endif; ?>
        </td>
    </tr>
</table>
