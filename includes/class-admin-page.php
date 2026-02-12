<?php
/**
 * Admin page enhancements.
 *
 * @package Woo_Smart_Discounts
 */

defined( 'ABSPATH' ) || exit;

class WSD_Admin_Page {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_assets( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen || WSD_Discount_Rules::POST_TYPE !== $screen->post_type ) {
            return;
        }

        wp_enqueue_style(
            'wsd-admin',
            WSD_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WSD_VERSION
        );

        wp_enqueue_script(
            'wsd-admin',
            WSD_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            WSD_VERSION,
            true
        );
    }
}
