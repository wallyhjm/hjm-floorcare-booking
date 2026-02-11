<?php

add_action('woocommerce_cart_calculate_fees', function ($cart) {

    // Never run in admin unless AJAX
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    // Prevent duplicate execution
    if (did_action('woocommerce_cart_calculate_fees') > 1) {
        return;
    }

    // Service address must exist
    $address = WC()->session->get('floorcare_service_address');

    if (empty($address)) {
        return;
    }

    // Distance lookup
    $miles = hjm_floorcare_get_job_distance();

    if ($miles === false || $miles <= 0) {
        return;
    }

    // Pricing table lookup
    $fee = hjm_floorcare_calculate_trip_charge($miles);

    if ($fee <= 0) {
        return;
    }

    // Add fee
    $cart->add_fee(
        sprintf('Trip Charge (%.1f miles)', $miles),
        $fee,
        false
    );

    // Store for later use (checkout + order meta)
    WC()->session->set('floorcare_trip_miles', $miles);
    WC()->session->set('floorcare_trip_fee', $fee);
});
