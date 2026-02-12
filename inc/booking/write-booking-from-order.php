<?php

/**
 * Persist booking record from WooCommerce order
 */
add_action('woocommerce_checkout_create_order', function ($order) {

    global $wpdb;

    $table = $wpdb->prefix . 'hjm_floorcare_bookings';

    $order_id = $order->get_id();

    // Prevent duplicates (idempotency)
    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$table} WHERE order_id = %d LIMIT 1",
            $order_id
        )
    );

    if ($exists) {
        return;
    }

    // Pull required order meta
    $date     = $order->get_meta('_floorcare_booking_date');
    $time     = $order->get_meta('_floorcare_booking_time');
    $duration = (int) $order->get_meta('_floorcare_total_duration');
    $address  = $order->get_meta('_floorcare_service_address');
    $miles    = $order->get_meta('_floorcare_trip_miles');
    $fee      = $order->get_meta('_floorcare_trip_fee');

    // Hard stop if required data is missing
    if (!$date || !$time || !$duration) {
        return;
    }

    $start_minutes = hjm_floorcare_time_to_minutes( $time );
    $end_minutes   = $start_minutes + $duration;

    $wpdb->insert(
        $table,
        [
            'order_id'        => $order_id,
            'booking_date'    => $date,
            'start_time'      => hjm_floorcare_minutes_to_time( $start_minutes ),
            'end_time'        => hjm_floorcare_minutes_to_time( $end_minutes ),
            'duration_minutes'=> $duration,
            'service_address' => $address,
            'trip_miles'      => $miles,
            'trip_fee'        => $fee,
            'status'          => 'confirmed',
            'created_at'      => current_time('mysql'),
        ],
        [
            '%d',
            '%s',
            '%s',
            '%s',
            '%d',
            '%s',
            '%f',
            '%f',
            '%s',
            '%s',
        ]
    );

}, 20);


