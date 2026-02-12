=== Smart Discount Rules for WooCommerce ===
Contributors: tinyshipai
Tags: woocommerce, discounts, bogo, bulk pricing, cart discount, coupons, deals, pricing rules
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

BOGO deals, bulk pricing, cart discounts, and more. Free forever — no premium upsell.

== Description ==

**Smart Discount Rules for WooCommerce** is a free alternative to paid discount plugins like Flycart Discount Rules ($59/yr). Create powerful, flexible pricing rules without spending a dime.

= Rule Types =

* **Buy One Get One (BOGO)** — Buy X get Y free or at a discount
* **Bulk Pricing** — Quantity-based tiered pricing (buy 5+ get 10% off, 10+ get 20% off)
* **Cart Total Discount** — Spend $100+ get $10 off
* **Category Discount** — All items in a category get X% off
* **User Role Discount** — Special pricing for wholesale, VIP, or any custom role

= Features =

* **Simple Admin UI** — Custom post type with intuitive meta boxes
* **Enable/Disable Rules** — Toggle rules on and off instantly
* **Date Scheduling** — Set start and end dates for promotions
* **Priority Ordering** — Control which rules apply first
* **Product Targeting** — Apply to all products or specific ones
* **Coupon Compatible** — Works alongside existing WooCommerce coupons
* **Cart Breakdown** — Customers see discount details in their cart
* **HPOS Compatible** — Works with WooCommerce High-Performance Order Storage
* **No Premium Upsell** — Every feature is free. Period.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/woo-smart-discounts/`, or install through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **WooCommerce → Discount Rules** to create your first rule.

== Frequently Asked Questions ==

= Is this really free? No pro version? =

Correct. 100% free. No premium tier. No upsell. We built it because the alternatives are overpriced for what they do.

= Can I run multiple rules at once? =

Yes. Rules are processed in priority order. You can have BOGO, bulk, and cart rules all active simultaneously.

= Does it work with existing coupons? =

Yes. Smart Discount Rules applies discounts as price adjustments and cart fees, which stack with standard WooCommerce coupons.

= How do I set up bulk pricing? =

Create a new Discount Rule, select "Bulk Pricing", and enter tiers as JSON:
`[{"min_qty": 5, "discount": 10}, {"min_qty": 10, "discount": 20}]`

This gives 10% off for 5+ items and 20% off for 10+ items.

== Screenshots ==

1. Discount rules list with type, status, and schedule
2. BOGO rule configuration
3. Bulk pricing tiers setup
4. Cart showing applied discounts

== Changelog ==

= 1.0.0 =
* Initial release
* BOGO (Buy One Get One) rules
* Bulk/tiered pricing rules
* Cart total discount rules
* Category-based discount rules
* User role-based discount rules
* Date range scheduling
* Priority ordering
* HPOS compatibility

== Upgrade Notice ==

= 1.0.0 =
Initial release of Smart Discount Rules for WooCommerce.
