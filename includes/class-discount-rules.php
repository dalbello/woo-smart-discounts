<?php
/**
 * Core discount rules engine — registers custom post type and manages rule storage.
 *
 * @package Woo_Smart_Discounts
 */

defined( 'ABSPATH' ) || exit;

class WSD_Discount_Rules {

    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_wsd_discount_rule', array( $this, 'save_meta' ) );
        add_filter( 'manage_wsd_discount_rule_posts_columns', array( $this, 'admin_columns' ) );
        add_action( 'manage_wsd_discount_rule_posts_custom_column', array( $this, 'admin_column_content' ), 10, 2 );
    }

    /**
     * Register custom post type for discount rules.
     */
    public function register_post_type() {
        register_post_type( 'wsd_discount_rule', array(
            'labels'       => array(
                'name'               => __( 'Discount Rules', 'woo-smart-discounts' ),
                'singular_name'      => __( 'Discount Rule', 'woo-smart-discounts' ),
                'add_new_item'       => __( 'Add New Discount Rule', 'woo-smart-discounts' ),
                'edit_item'          => __( 'Edit Discount Rule', 'woo-smart-discounts' ),
                'menu_name'          => __( 'Discount Rules', 'woo-smart-discounts' ),
            ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => 'woocommerce',
            'supports'     => array( 'title' ),
            'map_meta_cap' => true,
            'capability_type' => 'post',
        ) );
    }

    /**
     * Add meta boxes to discount rule editor.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'wsd_rule_config',
            __( 'Rule Configuration', 'woo-smart-discounts' ),
            array( $this, 'render_meta_box' ),
            'wsd_discount_rule',
            'normal',
            'high'
        );
    }

    /**
     * Render the rule configuration meta box.
     *
     * @param WP_Post $post Current post.
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'wsd_save_rule', 'wsd_rule_nonce' );

        $rule_type      = get_post_meta( $post->ID, '_wsd_rule_type', true );
        $discount_type  = get_post_meta( $post->ID, '_wsd_discount_type', true );
        $discount_value = get_post_meta( $post->ID, '_wsd_discount_value', true );
        $min_qty        = get_post_meta( $post->ID, '_wsd_min_qty', true );
        $max_qty        = get_post_meta( $post->ID, '_wsd_max_qty', true );
        $min_cart_total  = get_post_meta( $post->ID, '_wsd_min_cart_total', true );
        $buy_qty        = get_post_meta( $post->ID, '_wsd_buy_qty', true );
        $get_qty        = get_post_meta( $post->ID, '_wsd_get_qty', true );
        $applies_to     = get_post_meta( $post->ID, '_wsd_applies_to', true );
        $category_ids   = get_post_meta( $post->ID, '_wsd_category_ids', true );
        $product_ids    = get_post_meta( $post->ID, '_wsd_product_ids', true );
        $user_roles     = get_post_meta( $post->ID, '_wsd_user_roles', true );
        $date_from      = get_post_meta( $post->ID, '_wsd_date_from', true );
        $date_to        = get_post_meta( $post->ID, '_wsd_date_to', true );
        $priority       = get_post_meta( $post->ID, '_wsd_priority', true );
        $enabled        = get_post_meta( $post->ID, '_wsd_enabled', true );

        if ( '' === $enabled ) {
            $enabled = '1';
        }
        if ( '' === $priority ) {
            $priority = '10';
        }

        $rule_types = WSD_Rule_Types::get_types();
        $categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
        $wp_roles   = wp_roles()->roles;
        ?>
        <style>
            .wsd-field { margin-bottom: 15px; }
            .wsd-field label { display: block; font-weight: 600; margin-bottom: 5px; }
            .wsd-field input, .wsd-field select { width: 100%; max-width: 400px; }
            .wsd-field select[multiple] { height: 120px; }
            .wsd-row { display: flex; gap: 20px; flex-wrap: wrap; }
            .wsd-row .wsd-field { flex: 1; min-width: 200px; }
            .wsd-section { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #ddd; }
            .wsd-section h4 { margin: 0 0 10px; }
        </style>

        <div class="wsd-field">
            <label for="wsd_enabled">
                <input type="checkbox" id="wsd_enabled" name="wsd_enabled" value="1" <?php checked( $enabled, '1' ); ?>>
                <?php esc_html_e( 'Enable this rule', 'woo-smart-discounts' ); ?>
            </label>
        </div>

        <div class="wsd-row">
            <div class="wsd-field">
                <label for="wsd_rule_type"><?php esc_html_e( 'Rule Type', 'woo-smart-discounts' ); ?></label>
                <select id="wsd_rule_type" name="wsd_rule_type">
                    <?php foreach ( $rule_types as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $rule_type, $key ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="wsd-field">
                <label for="wsd_priority"><?php esc_html_e( 'Priority (lower = first)', 'woo-smart-discounts' ); ?></label>
                <input type="number" id="wsd_priority" name="wsd_priority" value="<?php echo esc_attr( $priority ); ?>" min="1" max="100">
            </div>
        </div>

        <div class="wsd-section">
            <h4><?php esc_html_e( 'Discount', 'woo-smart-discounts' ); ?></h4>
            <div class="wsd-row">
                <div class="wsd-field">
                    <label for="wsd_discount_type"><?php esc_html_e( 'Discount Type', 'woo-smart-discounts' ); ?></label>
                    <select id="wsd_discount_type" name="wsd_discount_type">
                        <option value="percentage" <?php selected( $discount_type, 'percentage' ); ?>><?php esc_html_e( 'Percentage (%)', 'woo-smart-discounts' ); ?></option>
                        <option value="fixed" <?php selected( $discount_type, 'fixed' ); ?>><?php esc_html_e( 'Fixed Amount ($)', 'woo-smart-discounts' ); ?></option>
                        <option value="free" <?php selected( $discount_type, 'free' ); ?>><?php esc_html_e( 'Free (100% off)', 'woo-smart-discounts' ); ?></option>
                    </select>
                </div>
                <div class="wsd-field">
                    <label for="wsd_discount_value"><?php esc_html_e( 'Discount Value', 'woo-smart-discounts' ); ?></label>
                    <input type="number" id="wsd_discount_value" name="wsd_discount_value" value="<?php echo esc_attr( $discount_value ); ?>" step="0.01" min="0">
                </div>
            </div>
        </div>

        <div class="wsd-section" id="wsd_bogo_section">
            <h4><?php esc_html_e( 'BOGO Settings', 'woo-smart-discounts' ); ?></h4>
            <div class="wsd-row">
                <div class="wsd-field">
                    <label for="wsd_buy_qty"><?php esc_html_e( 'Buy Quantity', 'woo-smart-discounts' ); ?></label>
                    <input type="number" id="wsd_buy_qty" name="wsd_buy_qty" value="<?php echo esc_attr( $buy_qty ); ?>" min="1">
                </div>
                <div class="wsd-field">
                    <label for="wsd_get_qty"><?php esc_html_e( 'Get Quantity (discounted)', 'woo-smart-discounts' ); ?></label>
                    <input type="number" id="wsd_get_qty" name="wsd_get_qty" value="<?php echo esc_attr( $get_qty ); ?>" min="1">
                </div>
            </div>
        </div>

        <div class="wsd-section" id="wsd_bulk_section">
            <h4><?php esc_html_e( 'Quantity Range', 'woo-smart-discounts' ); ?></h4>
            <div class="wsd-row">
                <div class="wsd-field">
                    <label for="wsd_min_qty"><?php esc_html_e( 'Minimum Quantity', 'woo-smart-discounts' ); ?></label>
                    <input type="number" id="wsd_min_qty" name="wsd_min_qty" value="<?php echo esc_attr( $min_qty ); ?>" min="1">
                </div>
                <div class="wsd-field">
                    <label for="wsd_max_qty"><?php esc_html_e( 'Maximum Quantity (0 = no limit)', 'woo-smart-discounts' ); ?></label>
                    <input type="number" id="wsd_max_qty" name="wsd_max_qty" value="<?php echo esc_attr( $max_qty ); ?>" min="0">
                </div>
            </div>
        </div>

        <div class="wsd-section" id="wsd_cart_section">
            <h4><?php esc_html_e( 'Cart Total', 'woo-smart-discounts' ); ?></h4>
            <div class="wsd-field">
                <label for="wsd_min_cart_total"><?php esc_html_e( 'Minimum Cart Total', 'woo-smart-discounts' ); ?></label>
                <input type="number" id="wsd_min_cart_total" name="wsd_min_cart_total" value="<?php echo esc_attr( $min_cart_total ); ?>" step="0.01" min="0">
            </div>
        </div>

        <div class="wsd-section">
            <h4><?php esc_html_e( 'Applies To', 'woo-smart-discounts' ); ?></h4>
            <div class="wsd-field">
                <label for="wsd_applies_to"><?php esc_html_e( 'Apply Discount To', 'woo-smart-discounts' ); ?></label>
                <select id="wsd_applies_to" name="wsd_applies_to">
                    <option value="all" <?php selected( $applies_to, 'all' ); ?>><?php esc_html_e( 'All Products', 'woo-smart-discounts' ); ?></option>
                    <option value="categories" <?php selected( $applies_to, 'categories' ); ?>><?php esc_html_e( 'Specific Categories', 'woo-smart-discounts' ); ?></option>
                    <option value="products" <?php selected( $applies_to, 'products' ); ?>><?php esc_html_e( 'Specific Products', 'woo-smart-discounts' ); ?></option>
                </select>
            </div>
            <div class="wsd-field" id="wsd_categories_field">
                <label for="wsd_category_ids"><?php esc_html_e( 'Categories', 'woo-smart-discounts' ); ?></label>
                <select id="wsd_category_ids" name="wsd_category_ids[]" multiple>
                    <?php
                    $selected_cats = is_array( $category_ids ) ? $category_ids : array();
                    if ( ! is_wp_error( $categories ) ) :
                        foreach ( $categories as $cat ) :
                            ?>
                            <option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php echo in_array( (string) $cat->term_id, $selected_cats, true ) ? 'selected' : ''; ?>>
                                <?php echo esc_html( $cat->name ); ?>
                            </option>
                        <?php endforeach; endif; ?>
                </select>
            </div>
            <div class="wsd-field" id="wsd_products_field">
                <label for="wsd_product_ids"><?php esc_html_e( 'Product IDs (comma-separated)', 'woo-smart-discounts' ); ?></label>
                <input type="text" id="wsd_product_ids" name="wsd_product_ids" value="<?php echo esc_attr( is_array( $product_ids ) ? implode( ',', $product_ids ) : $product_ids ); ?>">
            </div>
        </div>

        <div class="wsd-section" id="wsd_role_section">
            <h4><?php esc_html_e( 'User Role Restriction', 'woo-smart-discounts' ); ?></h4>
            <div class="wsd-field">
                <label for="wsd_user_roles"><?php esc_html_e( 'Restrict to Roles (leave empty for all)', 'woo-smart-discounts' ); ?></label>
                <select id="wsd_user_roles" name="wsd_user_roles[]" multiple>
                    <?php
                    $selected_roles = is_array( $user_roles ) ? $user_roles : array();
                    foreach ( $wp_roles as $role_key => $role_data ) :
                        ?>
                        <option value="<?php echo esc_attr( $role_key ); ?>" <?php echo in_array( $role_key, $selected_roles, true ) ? 'selected' : ''; ?>>
                            <?php echo esc_html( $role_data['name'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="wsd-section">
            <h4><?php esc_html_e( 'Schedule', 'woo-smart-discounts' ); ?></h4>
            <div class="wsd-row">
                <div class="wsd-field">
                    <label for="wsd_date_from"><?php esc_html_e( 'Start Date', 'woo-smart-discounts' ); ?></label>
                    <input type="date" id="wsd_date_from" name="wsd_date_from" value="<?php echo esc_attr( $date_from ); ?>">
                </div>
                <div class="wsd-field">
                    <label for="wsd_date_to"><?php esc_html_e( 'End Date', 'woo-smart-discounts' ); ?></label>
                    <input type="date" id="wsd_date_to" name="wsd_date_to" value="<?php echo esc_attr( $date_to ); ?>">
                </div>
            </div>
        </div>

        <script>
        jQuery(function($) {
            function toggleSections() {
                var type = $('#wsd_rule_type').val();
                $('#wsd_bogo_section').toggle(type === 'bogo');
                $('#wsd_bulk_section').toggle(type === 'bulk');
                $('#wsd_cart_section').toggle(type === 'cart_total');
                $('#wsd_role_section').toggle(type === 'user_role');
            }
            function toggleAppliesTo() {
                var val = $('#wsd_applies_to').val();
                $('#wsd_categories_field').toggle(val === 'categories');
                $('#wsd_products_field').toggle(val === 'products');
            }
            $('#wsd_rule_type').on('change', toggleSections);
            $('#wsd_applies_to').on('change', toggleAppliesTo);
            toggleSections();
            toggleAppliesTo();
        });
        </script>
        <?php
    }

    /**
     * Save rule meta data.
     *
     * @param int $post_id Post ID.
     */
    public function save_meta( $post_id ) {
        if ( ! isset( $_POST['wsd_rule_nonce'] ) || ! wp_verify_nonce( $_POST['wsd_rule_nonce'], 'wsd_save_rule' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = array(
            'wsd_rule_type'      => '_wsd_rule_type',
            'wsd_discount_type'  => '_wsd_discount_type',
            'wsd_discount_value' => '_wsd_discount_value',
            'wsd_min_qty'        => '_wsd_min_qty',
            'wsd_max_qty'        => '_wsd_max_qty',
            'wsd_min_cart_total' => '_wsd_min_cart_total',
            'wsd_buy_qty'        => '_wsd_buy_qty',
            'wsd_get_qty'        => '_wsd_get_qty',
            'wsd_applies_to'    => '_wsd_applies_to',
            'wsd_date_from'      => '_wsd_date_from',
            'wsd_date_to'        => '_wsd_date_to',
            'wsd_priority'       => '_wsd_priority',
        );

        foreach ( $fields as $field_name => $meta_key ) {
            if ( isset( $_POST[ $field_name ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) ) );
            }
        }

        // Checkbox.
        update_post_meta( $post_id, '_wsd_enabled', isset( $_POST['wsd_enabled'] ) ? '1' : '0' );

        // Arrays.
        if ( isset( $_POST['wsd_category_ids'] ) && is_array( $_POST['wsd_category_ids'] ) ) {
            update_post_meta( $post_id, '_wsd_category_ids', array_map( 'sanitize_text_field', wp_unslash( $_POST['wsd_category_ids'] ) ) );
        } else {
            delete_post_meta( $post_id, '_wsd_category_ids' );
        }

        if ( isset( $_POST['wsd_product_ids'] ) ) {
            $ids = array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['wsd_product_ids'] ) ) ) ) );
            update_post_meta( $post_id, '_wsd_product_ids', $ids );
        }

        if ( isset( $_POST['wsd_user_roles'] ) && is_array( $_POST['wsd_user_roles'] ) ) {
            update_post_meta( $post_id, '_wsd_user_roles', array_map( 'sanitize_text_field', wp_unslash( $_POST['wsd_user_roles'] ) ) );
        } else {
            delete_post_meta( $post_id, '_wsd_user_roles' );
        }
    }

    /**
     * Custom admin columns.
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function admin_columns( $columns ) {
        $new = array();
        $new['cb']        = $columns['cb'];
        $new['title']     = $columns['title'];
        $new['rule_type'] = __( 'Type', 'woo-smart-discounts' );
        $new['discount']  = __( 'Discount', 'woo-smart-discounts' );
        $new['enabled']   = __( 'Enabled', 'woo-smart-discounts' );
        $new['priority']  = __( 'Priority', 'woo-smart-discounts' );
        $new['date']      = $columns['date'];
        return $new;
    }

    /**
     * Custom column content.
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     */
    public function admin_column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'rule_type':
                $types = WSD_Rule_Types::get_types();
                $type  = get_post_meta( $post_id, '_wsd_rule_type', true );
                echo esc_html( isset( $types[ $type ] ) ? $types[ $type ] : '—' );
                break;
            case 'discount':
                $dtype = get_post_meta( $post_id, '_wsd_discount_type', true );
                $dval  = get_post_meta( $post_id, '_wsd_discount_value', true );
                if ( 'free' === $dtype ) {
                    echo esc_html__( 'Free', 'woo-smart-discounts' );
                } elseif ( 'percentage' === $dtype ) {
                    echo esc_html( $dval . '%' );
                } else {
                    echo esc_html( wc_price( $dval ) );
                }
                break;
            case 'enabled':
                $enabled = get_post_meta( $post_id, '_wsd_enabled', true );
                echo '1' === $enabled ? '<span style="color:green;">✓</span>' : '<span style="color:red;">✗</span>';
                break;
            case 'priority':
                echo esc_html( get_post_meta( $post_id, '_wsd_priority', true ) );
                break;
        }
    }

    /**
     * Get all active rules, sorted by priority.
     *
     * @return array Array of rule data.
     */
    public static function get_active_rules() {
        $posts = get_posts( array(
            'post_type'      => 'wsd_discount_rule',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_key'       => '_wsd_priority',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ) );

        $rules = array();
        $now   = current_time( 'Y-m-d' );

        foreach ( $posts as $post ) {
            $enabled = get_post_meta( $post->ID, '_wsd_enabled', true );
            if ( '1' !== $enabled ) {
                continue;
            }

            $date_from = get_post_meta( $post->ID, '_wsd_date_from', true );
            $date_to   = get_post_meta( $post->ID, '_wsd_date_to', true );

            if ( $date_from && $now < $date_from ) {
                continue;
            }
            if ( $date_to && $now > $date_to ) {
                continue;
            }

            $rules[] = array(
                'id'             => $post->ID,
                'title'          => $post->post_title,
                'rule_type'      => get_post_meta( $post->ID, '_wsd_rule_type', true ),
                'discount_type'  => get_post_meta( $post->ID, '_wsd_discount_type', true ),
                'discount_value' => floatval( get_post_meta( $post->ID, '_wsd_discount_value', true ) ),
                'min_qty'        => absint( get_post_meta( $post->ID, '_wsd_min_qty', true ) ),
                'max_qty'        => absint( get_post_meta( $post->ID, '_wsd_max_qty', true ) ),
                'min_cart_total' => floatval( get_post_meta( $post->ID, '_wsd_min_cart_total', true ) ),
                'buy_qty'        => absint( get_post_meta( $post->ID, '_wsd_buy_qty', true ) ),
                'get_qty'        => absint( get_post_meta( $post->ID, '_wsd_get_qty', true ) ),
                'applies_to'    => get_post_meta( $post->ID, '_wsd_applies_to', true ),
                'category_ids'   => get_post_meta( $post->ID, '_wsd_category_ids', true ) ?: array(),
                'product_ids'    => get_post_meta( $post->ID, '_wsd_product_ids', true ) ?: array(),
                'user_roles'     => get_post_meta( $post->ID, '_wsd_user_roles', true ) ?: array(),
            );
        }

        return $rules;
    }
}
