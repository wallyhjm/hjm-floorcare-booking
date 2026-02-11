<?php

add_action('woocommerce_checkout_create_order', function ($order) {

    // Service address (string)
    $service_address = WC()->session->get('floorcare_service_address');

    // Address parts
    $parts = WC()->session->get('floorcare_service_address_parts', []);

    // Distance snapshot
    $miles = WC()->session->get('floorcare_trip_miles');
    $fee   = WC()->session->get('floorcare_trip_fee');

    if ($service_address) {
        $order->update_meta_data('_floorcare_service_address', $service_address);
    }

    if (!empty($parts)) {
        $order->update_meta_data('_floorcare_service_address_parts', $parts);
    }

    if ($miles !== null) {
        $order->update_meta_data('_floorcare_trip_miles', $miles);
    }

    if ($fee !== null) {
        $order->update_meta_data('_floorcare_trip_fee', $fee);
    }

});

add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {

    $address = $order->get_meta('_floorcare_service_address');
    $miles   = $order->get_meta('_floorcare_trip_miles');
    $fee     = $order->get_meta('_floorcare_trip_fee');

    if (!$address) return;

    echo '<h4>Service Location</h4>';
    echo '<p>' . esc_html($address) . '</p>';

    if ($miles) {
        echo '<p><strong>Distance:</strong> ' . esc_html($miles) . ' miles</p>';
    }

    if ($fee) {
        echo '<p><strong>Trip Charge:</strong> $' . wc_format_decimal($fee, 2) . '</p>';
    }
});
