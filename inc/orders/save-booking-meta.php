<?php

add_action('woocommerce_checkout_create_order', function ($order) {

    // Booking date & time
    $date = WC()->session->get('floorcare_booking_date');
    $time = WC()->session->get('floorcare_booking_time');

    if ($date) {
        $order->update_meta_data('_floorcare_booking_date', $date);
    }

    if ($time) {
        $order->update_meta_data('_floorcare_booking_time', $time);
    }

    // Duration (minutes)
    $duration = WC()->session->get('floorcare_total_duration');
    if ($duration) {
        $order->update_meta_data('_floorcare_total_duration', (int) $duration);
    }

    // Service address
    $address = WC()->session->get('floorcare_service_address');
    if ($address) {
        $order->update_meta_data('_floorcare_service_address', $address);
    }

    // Address parts (future-proofing)
    $parts = WC()->session->get('floorcare_service_address_parts');
    if (is_array($parts)) {
        foreach ($parts as $key => $value) {
            if ($value !== '') {
                $order->update_meta_data('_floorcare_service_' . $key, $value);
            }
        }
    }

    // Trip charge metadata
    $miles = WC()->session->get('floorcare_trip_miles');
    $fee   = WC()->session->get('floorcare_trip_fee');

    if ($miles !== null) {
        $order->update_meta_data('_floorcare_trip_miles', (float) $miles);
    }

    if ($fee !== null) {
        $order->update_meta_data('_floorcare_trip_fee', (float) $fee);
    }

});

