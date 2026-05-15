<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'wlw_render_stars' ) ) {
    function wlw_render_stars( $rating ) {
        $rating = max( 0, min( 5, (float) $rating ) );
        $full   = (int) floor( $rating );
        $half   = ( $rating - $full ) >= 0.5 ? 1 : 0;
        $empty  = 5 - $full - $half;
        $out  = '<span class="wlw-stars" aria-label="' . esc_attr( sprintf( '%s / 5', number_format_i18n( $rating, 1 ) ) ) . '">';
        $out .= str_repeat( '<span class="wlw-star wlw-star--full" aria-hidden="true">★</span>', $full );
        if ( $half ) {
            $out .= '<span class="wlw-star wlw-star--half" aria-hidden="true">★</span>';
        }
        $out .= str_repeat( '<span class="wlw-star wlw-star--empty" aria-hidden="true">☆</span>', $empty );
        $out .= '</span>';
        return $out;
    }
}

if ( ! function_exists( 'wlw_relative_time' ) ) {
    function wlw_relative_time( $timestamp ) {
        if ( ! $timestamp ) {
            return '';
        }
        return sprintf( _x( '%s', 'review relative time', 'weblock-widgets' ),
            human_time_diff( (int) $timestamp, current_time( 'timestamp' ) ) . ' ' . __( 'óta', 'weblock-widgets' )
        );
    }
}

if ( ! function_exists( 'wlw_review_header' ) ) {
    function wlw_review_header( $place_name, $rating, $total_count, $place_url ) {
        ob_start();
        ?>
        <div class="wlw-reviews__header">
            <?php if ( $place_name ) : ?>
                <div class="wlw-reviews__title"><?php echo esc_html( $place_name ); ?></div>
            <?php endif; ?>
            <div class="wlw-reviews__summary">
                <span class="wlw-reviews__rating"><?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?></span>
                <?php echo wlw_render_stars( $rating ); ?>
                <?php if ( $total_count ) : ?>
                    <span class="wlw-reviews__count">
                        <?php
                        printf(
                            esc_html( _n( '%s értékelés', '%s értékelés', $total_count, 'weblock-widgets' ) ),
                            esc_html( number_format_i18n( $total_count ) )
                        );
                        ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php if ( $place_url ) : ?>
                <a class="wlw-reviews__cta" href="<?php echo esc_url( $place_url ); ?>" target="_blank" rel="noopener nofollow">
                    <?php esc_html_e( 'Értékelj a Google-on', 'weblock-widgets' ); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

if ( ! function_exists( 'wlw_review_card' ) ) {
    function wlw_review_card( $review ) {
        $author = $review['author_name']  ?? '';
        $photo  = $review['profile_photo_url'] ?? '';
        $rating = $review['rating']       ?? 0;
        $time   = $review['time']         ?? 0;
        $text   = $review['text']         ?? '';
        $url    = $review['author_url']   ?? '';
        ob_start();
        ?>
        <article class="wlw-review">
            <header class="wlw-review__head">
                <?php if ( $photo ) : ?>
                    <img class="wlw-review__avatar" src="<?php echo esc_url( $photo ); ?>" alt="" loading="lazy" width="48" height="48" />
                <?php else : ?>
                    <span class="wlw-review__avatar wlw-review__avatar--placeholder" aria-hidden="true"><?php echo esc_html( mb_substr( $author, 0, 1 ) ); ?></span>
                <?php endif; ?>
                <div class="wlw-review__meta">
                    <?php if ( $url ) : ?>
                        <a class="wlw-review__author" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( $author ); ?></a>
                    <?php else : ?>
                        <span class="wlw-review__author"><?php echo esc_html( $author ); ?></span>
                    <?php endif; ?>
                    <div class="wlw-review__rating-row">
                        <?php echo wlw_render_stars( $rating ); ?>
                        <?php if ( $time ) : ?>
                            <span class="wlw-review__date"><?php echo esc_html( wlw_relative_time( $time ) ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </header>
            <?php if ( $text ) : ?>
                <p class="wlw-review__text"><?php echo esc_html( $text ); ?></p>
            <?php endif; ?>
        </article>
        <?php
        return ob_get_clean();
    }
}
