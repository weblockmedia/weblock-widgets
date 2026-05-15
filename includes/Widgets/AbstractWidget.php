<?php
namespace WeblockWidgets\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class AbstractWidget {
    protected $shortcode = '';
    protected $block_name = '';

    public function init() {
        if ( $this->shortcode ) {
            add_shortcode( $this->shortcode, [ $this, 'render_shortcode' ] );
        }
        if ( $this->block_name ) {
            add_action( 'init', [ $this, 'register_block' ] );
        }
    }

    abstract public function render_shortcode( $atts );

    /**
     * Widget metaadat a vizuális admin generátorhoz.
     * Visszaad: id, label, icon (dashicon), description, color, fields[].
     */
    public function get_meta() {
        return [
            'id'          => $this->shortcode,
            'label'       => $this->shortcode,
            'icon'        => 'screenoptions',
            'color'       => '#1a73e8',
            'description' => '',
            'fields'      => [],
        ];
    }

    public function register_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }
    }

    protected function get_setting( $key, $default = '' ) {
        $settings = get_option( 'wlw_settings', [] );
        return isset( $settings[ $key ] ) && $settings[ $key ] !== '' ? $settings[ $key ] : $default;
    }

    protected function load_template( $template, $vars = [] ) {
        $file = WLW_DIR . 'templates/' . ltrim( $template, '/' );
        if ( ! file_exists( $file ) ) {
            return '<!-- wlw: template not found: ' . esc_html( $template ) . ' -->';
        }
        extract( $vars, EXTR_SKIP );
        ob_start();
        include $file;
        return ob_get_clean();
    }

    protected function error_message( $message ) {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return '';
        }
        return '<div class="wlw-error" role="alert">' . esc_html( $message ) . '</div>';
    }
}
