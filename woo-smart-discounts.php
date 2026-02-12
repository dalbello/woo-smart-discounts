<?php
/**
 * Plugin Name: Smart Discount Rules for WooCommerce
 * Plugin URI: https://tinyship.ai/plugins/woo-smart-discounts/
 * Description: BOGO deals, bulk pricing, cart discounts, and more. Free forever — no premium upsell.
 * Version: 1.0.0
 * Author: tinyship.ai
 * Author URI: https://tinyship.ai
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-smart-discounts
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.7
 * WC requires at least: 7.0
 * WC tested up to: 9.5
 * Requires PHP: 7.4
 *
 * @package Woo_Smart_Discounts
 */

defined( 'ABSPATH' ) || exit;

define( 'WSD_VERSION', '1.0.0' );
define( 'WSD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WSD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WSD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if WooCommerce is active.
 */
function wsd_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'wsd_woocommerce_missing_notice' );
        return false;
    }
    return true;
}

/**
 * Admin notice when WooCommerce is missing.
 */
function wsd_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e( 'Smart Discount Rules requires WooCommerce to be installed and active.', 'woo-smart-discounts' ); ?></p>
    </div>
    <?php
}

/**
 * Initialize plugin.
 */
function wsd_init() {
    if ( ! wsd_check_woocommerce() ) {
        return;
    }

    require_once WSD_PLUGIN_DIR . 'includes/class-rule-types.php';
    require_once WSD_PLUGIN_DIR . 'includes/class-discount-rules.php';
    require_once WSD_PLUGIN_DIR . 'includes/class-admin-page.php';
    require_once WSD_PLUGIN_DIR . 'includes/class-cart-handler.php';

    new WSD_Discount_Rules();
    new WSD_Admin_Page();
    new WSD_Cart_Handler();
}
add_action( 'plugins_loaded', 'wsd_init' );

/**
 * Load text domain.
 */
function wsd_load_textdomain() {
    load_plugin_textdomain( 'woo-smart-discounts', false, dirname( WSD_PLUGIN_BASENAME ) . '/languages' );
}
add_action( 'init', 'wsd_load_textdomain' );

/**
 * Settings link on plugins page.
 *
 * @param array $links Plugin action links.
 * @return array
 */
function wsd_plugin_action_links( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'edit.php?post_type=wsd_discount_rule' ) ) . '">' . esc_html__( 'Discount Rules', 'woo-smart-discounts' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . WSD_PLUGIN_BASENAME, 'wsd_plugin_action_links' );

/**
 * Declare HPOS compatibility.
 */
add_action(
    'before_woocommerce_init',
    function () {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    }
);
