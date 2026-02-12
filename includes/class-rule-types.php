<?php
/**
 * Discount rule type definitions.
 *
 * @package Woo_Smart_Discounts
 */

defined( 'ABSPATH' ) || exit;

class WSD_Rule_Types {

    /**
     * Get all available rule types.
     *
     * @return array
     */
    public static function get_types() {
        return array(
            'bogo'     => array(
                'label'       => __( 'Buy One Get One (BOGO)', 'woo-smart-discounts' ),
                'description' => __( 'Buy X items, get Y items free or discounted.', 'woo-smart-discounts' ),
            ),
            'bulk'     => array(
                'label'       => __( 'Bulk Pricing', 'woo-smart-discounts' ),
                'description' => __( 'Discount based on quantity purchased.', 'woo-smart-discounts' ),
            ),
            'cart'     => array(
                'label'       => __( 'Cart Total Discount', 'woo-smart-discounts' ),
                'description' => __( 'Discount when cart total exceeds a threshold.', 'woo-smart-discounts' ),
            ),
            'category' => array(
                'label'       => __( 'Category Discount', 'woo-smart-discounts' ),
                'description' => __( 'Percentage off all items in specific categories.', 'woo-smart-discounts' ),
            ),
            'role'     => array(
                'label'       => __( 'User Role Discount', 'woo-smart-discounts' ),
                'description' => __( 'Discount for specific user roles (e.g. wholesale).', 'woo-smart-discounts' ),
            ),
        );
    }

    /**
     * Get type label.
     *
     * @param string $type Rule type key.
     * @return string
     */
    public static function get_label( $type ) {
        $types = self::get_types();
        return $types[ $type ]['label'] ?? $type;
    }
}
