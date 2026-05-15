<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/_signature-helpers.php';
$accent = $accent_color ? $accent_color : '#4285f4';
$rows = [
    [ 'label' => $phone_label,   'value' => $phone,   'href' => $phone   ? 'tel:'    . preg_replace( '/[^\d+]/', '', $phone )   : '' ],
    [ 'label' => $email_label,   'value' => $email,   'href' => $email   ? 'mailto:' . $email                                   : '' ],
    [ 'label' => $website_label, 'value' => $website, 'href' => wlw_sig_website_href( $website ) ],
];
?>
<table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;color:#1a1a1a;line-height:1.45;">
    <tr>
        <?php if ( $avatar_url ) : ?>
        <td style="padding:0 16px 0 0;vertical-align:top;">
            <img src="<?php echo esc_url( $avatar_url ); ?>" alt="" width="96" height="96" style="display:block;width:96px;height:96px;border-radius:6px;object-fit:cover;border:0;" />
        </td>
        <?php endif; ?>
        <?php if ( ! empty( $separator_line ) && $avatar_url ) : ?>
        <td style="padding:0 16px 0 0;vertical-align:middle;">
            <div style="width:1px;height:80px;background:<?php echo esc_attr( $accent ); ?>;"></div>
        </td>
        <?php endif; ?>
        <td style="vertical-align:top;">
            <div style="font-size:<?php echo (int) $name_size; ?>px;font-weight:700;color:#1a1a1a;"><?php echo esc_html( $name ); ?></div>
            <?php if ( $title ) : ?>
                <div style="font-size:<?php echo (int) $contact_size; ?>px;color:#5f6368;"><?php echo esc_html( $title ); ?></div>
            <?php endif; ?>

            <?php echo wlw_sig_contact_table( $rows, $accent, $contact_size ); ?>

            <?php if ( $logo_url || ! empty( $show_google_rating ) ) : ?>
                <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin-top:8px;">
                    <tr>
                        <?php if ( $logo_url ) : ?>
                        <td style="padding:0 12px 0 0;vertical-align:middle;">
                            <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $company ); ?>" height="32" style="display:block;height:32px;width:auto;border:0;" />
                        </td>
                        <?php endif; ?>
                        <?php if ( ! empty( $show_google_rating ) ) : ?>
                        <td style="vertical-align:middle;">
                            <?php echo wlw_sig_google_block( $google_rating, $google_review_count, $google_reviews_url, $accent ); ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                </table>
            <?php endif; ?>

            <?php echo wlw_sig_footer_text( $env_footer, $confidential_footer ); ?>
        </td>
    </tr>
</table>
