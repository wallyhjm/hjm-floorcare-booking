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

            if (function_exists('hjm_floorcare_is_addon_cart_item') && hjm_floorcare_is_addon_cart_item($cart_item)) {
                $addon_id = (int) ($cart_item['floorcare_addon_id'] ?? 0);

                if ($addon_id > 0 && function_exists('hjm_floorcare_get_addon_price_for_qty')) {
                    $addon_total = (float) hjm_floorcare_get_addon_price_for_qty($addon_id, $qty);
                    $unit_price = $addon_total / $qty;
                } else {
                    $unit_price = 0.0;
                }
            } else {
                $unit_price = $base_price;
            }

            $cart_item['data']->set_price(max(0, (float) $unit_price));
        }
    });
});
