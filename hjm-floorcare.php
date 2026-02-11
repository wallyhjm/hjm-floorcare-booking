<?php
/**
 * Plugin Name: HJM Floor Care
 */

if (!defined('ABSPATH')) exit;

define('HJM_FLOORCARE_PATH', plugin_dir_path(__FILE__));
define('HJM_FLOORCARE_URL', plugin_dir_url(__FILE__));
define('HJM_GOOGLE_API_KEY', 'AIzaSyAG8EQ5RGfB82K9yw0je93kKHyCYrtkQTg');
define('HJM_GOOGLE_PLACES_API_KEY', 'AIzaSyAHRv-ZWbUpRQyCOlxE6-ZK0dcAHVsPO6Q');

require_once HJM_FLOORCARE_PATH . 'inc/activation.php';
require_once HJM_FLOORCARE_PATH . 'inc/enqueue.php';

register_activation_hook( __FILE__, 'hjm_floorcare_activate' );

/**
 * -------------------------------------------------
 * Core logic (no Woo hooks, no side effects)
 * -------------------------------------------------
 */
require_once HJM_FLOORCARE_PATH . 'inc/pricing/pricing-engine.php';
require_once HJM_FLOORCARE_PATH . 'inc/pricing/duration-calculator.php';
require_once HJM_FLOORCARE_PATH . 'inc/availability.php';

/**
 * -------------------------------------------------
 * Data / schema
 * -------------------------------------------------
 */
require_once HJM_FLOORCARE_PATH . 'inc/booking/bookings-table.php';

/**
 * -------------------------------------------------
 * Booking engines (depend on core logic + data)
 * -------------------------------------------------
 */
require_once HJM_FLOORCARE_PATH . 'inc/booking/slot-engine.php';
require_once HJM_FLOORCARE_PATH . 'inc/booking/booking-create.php';
require_once HJM_FLOORCARE_PATH . 'inc/booking/write-booking-from-order.php';

/**
 * -------------------------------------------------
 * Cart layer
 * -------------------------------------------------
 */
require_once HJM_FLOORCARE_PATH . 'inc/cart/cart-hooks.php';
require_once HJM_FLOORCARE_PATH . 'inc/cart/service-address-cart.php';
require_once HJM_FLOORCARE_PATH . 'inc/cart/booking-cart.php';

/**
 * -------------------------------------------------
 * Checkout layer
 * -------------------------------------------------
 */
require_once HJM_FLOORCARE_PATH . 'inc/checkout/disable-shipping.php';
require_once HJM_FLOORCARE_PATH . 'inc/checkout/service-address-checkout.php';
require_once HJM_FLOORCARE_PATH . 'inc/checkout/billing-same-as-service.php';
require_once HJM_FLOORCARE_PATH . 'inc/checkout/booking-validation.php';
require_once HJM_FLOORCARE_PATH . 'inc/checkout/booking-summary.php';

/**
 * -------------------------------------------------
 * Orders
 * -------------------------------------------------
 */
require_once HJM_FLOORCARE_PATH . 'inc/orders/save-service-meta.php';
require_once HJM_FLOORCARE_PATH . 'inc/orders/save-booking-meta.php';

/**
 * -------------------------------------------------
 * Supporting systems (loosely coupled)
 * -------------------------------------------------
 */
require_once HJM_FLOORCARE_PATH . 'inc/geocoding/address-autocomplete.php';
require_once HJM_FLOORCARE_PATH . 'inc/services/product-service-fields.php';
require_once HJM_FLOORCARE_PATH . 'inc/addons/addon-cpt.php';
require_once HJM_FLOORCARE_PATH . 'inc/admin/admin-loader.php';


register_activation_hook(
    __FILE__,
    'hjm_floorcare_create_bookings_table'
);

/**
 * Hook registration must wait until Woo exists
 */
add_action('plugins_loaded', function () {

    if (!class_exists('WooCommerce')) {
        return;
    }

    do_action('hjm_floorcare_woocommerce_ready');

});


// Force classic cart and checkout (disable blocks)
add_filter('woocommerce_should_load_cart_block', '__return_false');
add_filter('woocommerce_should_load_checkout_block', '__return_false');
