<?php

require_once HJM_FLOORCARE_PATH . 'inc/pricing/trip-charge-rates.php';
require_once HJM_FLOORCARE_PATH . 'inc/pricing/trip-charge-calculator.php';
require_once HJM_FLOORCARE_PATH . 'inc/geocoding/google-distance-matrix.php';
require_once HJM_FLOORCARE_PATH . 'inc/geocoding/distance-cache.php';
require_once HJM_FLOORCARE_PATH . 'inc/geocoding/distance-provider.php';
require_once HJM_FLOORCARE_PATH . 'inc/pricing/trip-charge.php';

add_action('hjm_floorcare_woocommerce_ready', function () {

    add_action('woocommerce_before_calculate_totals', function ($cart) {

        foreach ($cart->get_cart() as $cart_item) {

            $product = $cart_item['data'];

            if (!$product) {
                continue;
            }

            $service_type = function_exists('hjm_floorcare_get_product_service_type')
                ? hjm_floorcare_get_product_service_type($product)
                : '';

            if ($service_type === '') {
                continue;
            }

            $qty = max(1, (int) ($cart_item['quantity'] ?? 1));
            $base_price = 160.0;
            $addons_total = function_exists('hjm_floorcare_calculate_addons_total_for_cart_item')
                ? (float) hjm_floorcare_calculate_addons_total_for_cart_item($cart_item)
                : 0.0;

            $unit_price = $base_price + ($addons_total / $qty);
            $cart_item['data']->set_price(max(0, (float) $unit_price));
        }
    });
});
