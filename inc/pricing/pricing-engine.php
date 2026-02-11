<?php

require_once HJM_FLOORCARE_PATH . 'inc/pricing/trip-charge-rates.php';
require_once HJM_FLOORCARE_PATH . 'inc/pricing/trip-charge-calculator.php';
require_once HJM_FLOORCARE_PATH . 'inc/geocoding/google-distance-matrix.php';
require_once HJM_FLOORCARE_PATH . 'inc/geocoding/distance-cache.php';
require_once HJM_FLOORCARE_PATH . 'inc/geocoding/distance-provider.php';
require_once HJM_FLOORCARE_PATH . 'inc/pricing/trip-charge.php';

add_action('hjm_floorcare_woocommerce_ready', function () {

    add_action('woocommerce_before_calculate_totals', function ($cart) {

        error_log('HJM FLOORCARE: pricing hook fired');

        foreach ($cart->get_cart() as $cart_item) {

            $product = $cart_item['data'];

            if (!$product || $product->get_slug() !== 'floor-care-service') {
                continue;
            }

            $cart_item['data']->set_price(160);
        }
    });
});
