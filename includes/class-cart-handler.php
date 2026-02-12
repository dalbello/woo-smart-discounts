<?php
/**
 * Cart handler — applies discount rules to WooCommerce cart.
 *
 * @package Woo_Smart_Discounts
 */

defined( 'ABSPATH' ) || exit;

class WSD_Cart_Handler {

    public function __construct() {
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply_discounts' ), 20 );
        add_filter( 'woocommerce_cart_item_price', array( $this, 'show_original_price' ), 10, 3 );
        add_action( 'woocommerce_cart_totals_before_order_total', array( $this, 'show_discount_summary' ) );
        add_action( 'woocommerce_before_cart', array( $this, 'show_discount_notices' ) );
    }

    /**
     * Apply all active discount rules to cart items.
     *
     * @param WC_Cart $cart Cart instance.
     */
    public function apply_discounts( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }
        if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
            return;
        }

        $rules = WSD_Discount_Rules::get_active_rules();
        if ( empty( $rules ) ) {
            return;
        }

        // Reset any previous discount data.
        foreach ( $cart->get_cart() as $key => $item ) {
            unset( $cart->cart_contents[ $key ]['wsd_discount'] );
            unset( $cart->cart_contents[ $key ]['wsd_original_price'] );
        }

        foreach ( $rules as $rule ) {
            // Check user role restriction.
            if ( ! empty( $rule['user_roles'] ) && is_array( $rule['user_roles'] ) ) {
                $user = wp_get_current_user();
                if ( ! array_intersect( $user->roles, $rule['user_roles'] ) ) {
                    continue;
                }
            }

            switch ( $rule['rule_type'] ) {
                case 'bogo':
                    $this->apply_bogo( $cart, $rule );
                    break;
                case 'bulk':
                    $this->apply_bulk( $cart, $rule );
                    break;
                case 'cart_total':
                    $this->apply_cart_total( $cart, $rule );
                    break;
                case 'category':
                    $this->apply_category( $cart, $rule );
                    break;
                case 'user_role':
                    $this->apply_role_discount( $cart, $rule );
                    break;
            }
        }
    }

    /**
     * Check if a product matches the rule's target.
     *
     * @param array $rule Rule data.
     * @param int   $product_id Product ID.
     * @return bool
     */
    private function product_matches_rule( $rule, $product_id ) {
        $applies_to = isset( $rule['applies_to'] ) ? $rule['applies_to'] : 'all';

        if ( 'all' === $applies_to ) {
            return true;
        }

        if ( 'products' === $applies_to && ! empty( $rule['product_ids'] ) ) {
            return in_array( $product_id, array_map( 'absint', $rule['product_ids'] ), true );
        }

        if ( 'categories' === $applies_to && ! empty( $rule['category_ids'] ) ) {
            $product_cats = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
            return ! empty( array_intersect( $product_cats, array_map( 'absint', $rule['category_ids'] ) ) );
        }

        return true;
    }

    /**
     * Calculate discounted price.
     *
     * @param float $price Original price.
     * @param array $rule  Rule data.
     * @return float
     */
    private function calculate_discount( $price, $rule ) {
        if ( 'free' === $rule['discount_type'] ) {
            return 0;
        }
        if ( 'percentage' === $rule['discount_type'] ) {
            return $price * ( 1 - $rule['discount_value'] / 100 );
        }
        // Fixed amount.
        return max( 0, $price - $rule['discount_value'] );
    }

    /**
     * Apply BOGO discount.
     */
    private function apply_bogo( $cart, $rule ) {
        $buy_qty = max( 1, $rule['buy_qty'] );
        $get_qty = max( 1, $rule['get_qty'] );

        foreach ( $cart->get_cart() as $key => $item ) {
            $product_id = $item['variation_id'] ? $item['variation_id'] : $item['product_id'];
            if ( ! $this->product_matches_rule( $rule, $item['product_id'] ) ) {
                continue;
            }

            $qty           = $item['quantity'];
            $sets          = floor( $qty / ( $buy_qty + $get_qty ) );
            $discounted_qty = $sets * $get_qty;

            if ( $discounted_qty > 0 ) {
                $original_price = floatval( $item['data']->get_price() );
                $free_price     = $this->calculate_discount( $original_price, $rule );
                $paid_qty       = $qty - $discounted_qty;
                $new_price      = ( ( $paid_qty * $original_price ) + ( $discounted_qty * $free_price ) ) / $qty;

                $cart->cart_contents[ $key ]['data']->set_price( $new_price );
                $cart->cart_contents[ $key ]['wsd_discount']       = $rule['title'];
                $cart->cart_contents[ $key ]['wsd_original_price'] = $original_price;
            }
        }
    }

    /**
     * Apply bulk quantity discount.
     */
    private function apply_bulk( $cart, $rule ) {
        foreach ( $cart->get_cart() as $key => $item ) {
            if ( ! $this->product_matches_rule( $rule, $item['product_id'] ) ) {
                continue;
            }

            $qty = $item['quantity'];
            if ( $rule['min_qty'] > 0 && $qty < $rule['min_qty'] ) {
                continue;
            }
            if ( $rule['max_qty'] > 0 && $qty > $rule['max_qty'] ) {
                continue;
            }

            $original_price = floatval( $item['data']->get_price() );
            $new_price      = $this->calculate_discount( $original_price, $rule );

            $cart->cart_contents[ $key ]['data']->set_price( $new_price );
            $cart->cart_contents[ $key ]['wsd_discount']       = $rule['title'];
            $cart->cart_contents[ $key ]['wsd_original_price'] = $original_price;
        }
    }

    /**
     * Apply cart total discount.
     */
    private function apply_cart_total( $cart, $rule ) {
        $subtotal = 0;
        foreach ( $cart->get_cart() as $item ) {
            $subtotal += floatval( $item['data']->get_price() ) * $item['quantity'];
        }

        if ( $subtotal < $rule['min_cart_total'] ) {
            return;
        }

        foreach ( $cart->get_cart() as $key => $item ) {
            if ( ! $this->product_matches_rule( $rule, $item['product_id'] ) ) {
                continue;
            }

            $original_price = floatval( $item['data']->get_price() );
            $new_price      = $this->calculate_discount( $original_price, $rule );

            $cart->cart_contents[ $key ]['data']->set_price( $new_price );
            $cart->cart_contents[ $key ]['wsd_discount']       = $rule['title'];
            $cart->cart_contents[ $key ]['wsd_original_price'] = $original_price;
        }
    }

    /**
     * Apply category discount.
     */
    private function apply_category( $cart, $rule ) {
        if ( empty( $rule['category_ids'] ) ) {
            return;
        }

        foreach ( $cart->get_cart() as $key => $item ) {
            $product_cats = wp_get_post_terms( $item['product_id'], 'product_cat', array( 'fields' => 'ids' ) );
            if ( empty( array_intersect( $product_cats, array_map( 'absint', $rule['category_ids'] ) ) ) ) {
                continue;
            }

            $original_price = floatval( $item['data']->get_price() );
            $new_price      = $this->calculate_discount( $original_price, $rule );

            $cart->cart_contents[ $key ]['data']->set_price( $new_price );
            $cart->cart_contents[ $key ]['wsd_discount']       = $rule['title'];
            $cart->cart_contents[ $key ]['wsd_original_price'] = $original_price;
        }
    }

    /**
     * Apply user role-based discount.
     */
    private function apply_role_discount( $cart, $rule ) {
        if ( empty( $rule['user_roles'] ) ) {
            return;
        }

        $user = wp_get_current_user();
        if ( ! array_intersect( $user->roles, $rule['user_roles'] ) ) {
            return;
        }

        foreach ( $cart->get_cart() as $key => $item ) {
            if ( ! $this->product_matches_rule( $rule, $item['product_id'] ) ) {
                continue;
            }

            $original_price = floatval( $item['data']->get_price() );
            $new_price      = $this->calculate_discount( $original_price, $rule );

            $cart->cart_contents[ $key ]['data']->set_price( $new_price );
            $cart->cart_contents[ $key ]['wsd_discount']       = $rule['title'];
            $cart->cart_contents[ $key ]['wsd_original_price'] = $original_price;
        }
    }

    /**
     * Show original price with strikethrough in cart.
     *
     * @param string $price     Formatted price HTML.
     * @param array  $cart_item Cart item data.
     * @param string $cart_item_key Cart item key.
     * @return string
     */
    public function show_original_price( $price, $cart_item, $cart_item_key ) {
        if ( isset( $cart_item['wsd_original_price'] ) ) {
            $original = wc_price( $cart_item['wsd_original_price'] );
            $discounted = wc_price( $cart_item['data']->get_price() );
            return '<del>' . $original . '</del> <ins>' . $discounted . '</ins>';
        }
        return $price;
    }

    /**
     * Show discount summary above cart totals.
     */
    public function show_discount_summary() {
        $discounts = array();
        foreach ( WC()->cart->get_cart() as $item ) {
            if ( isset( $item['wsd_discount'] ) && isset( $item['wsd_original_price'] ) ) {
                $name   = $item['wsd_discount'];
                $saving = ( $item['wsd_original_price'] - $item['data']->get_price() ) * $item['quantity'];
                if ( ! isset( $discounts[ $name ] ) ) {
                    $discounts[ $name ] = 0;
                }
                $discounts[ $name ] += $saving;
            }
        }

        foreach ( $discounts as $name => $saving ) {
            if ( $saving > 0 ) {
                ?>
                <tr class="wsd-discount-row">
                    <th><?php echo esc_html( $name ); ?></th>
                    <td data-title="<?php echo esc_attr( $name ); ?>">-<?php echo wp_kses_post( wc_price( $saving ) ); ?></td>
                </tr>
                <?php
            }
        }
    }

    /**
     * Show active discount notices on cart page.
     */
    public function show_discount_notices() {
        $has_discounts = false;
        foreach ( WC()->cart->get_cart() as $item ) {
            if ( isset( $item['wsd_discount'] ) ) {
                $has_discounts = true;
                break;
            }
        }

        if ( $has_discounts ) {
            wc_print_notice(
                __( '🎉 Automatic discounts have been applied to your cart!', 'woo-smart-discounts' ),
                'success'
            );
        }
    }
}
