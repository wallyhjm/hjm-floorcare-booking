<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Create booking record when order is placed
 */
add_action( 'woocommerce_checkout_create_order', function ( $order ) {

    global $wpdb;

    $date     = WC()->session->get( 'floorcare_booking_date' );
    $time     = WC()->session->get( 'floorcare_booking_time' );
    $duration = (int) WC()->session->get( 'floorcare_total_duration' );
    $address  = WC()->session->get( 'floorcare_service_address' );

    if ( ! $date || ! $time || ! $duration ) {
        return;
    }

    $start_minutes = hjm_floorcare_time_to_minutes( $time );
    $end_minutes   = $start_minutes + $duration;

    $table = $wpdb->prefix . 'hjm_floorcare_bookings';

    $wpdb->insert(
        $table,
        [
            'order_id'         => $order->get_id(),
            'booking_date'     => $date,
            'start_time'       => hjm_floorcare_minutes_to_time( $start_minutes ),
            'end_time'         => hjm_floorcare_minutes_to_time( $end_minutes ),
            'duration_minutes' => $duration,
            'service_address'  => $address,
            'status'           => 'confirmed',
        ],
        [
            '%d','%s','%s','%s','%d','%s','%s'
        ]
    );

    $booking_id = $wpdb->insert_id;

    if ( $booking_id ) {
        $order->update_meta_data( '_floorcare_booking_id', $booking_id );
        $order->update_meta_data( '_floorcare_booking_date', $date );
        $order->update_meta_data( '_floorcare_booking_time', $time );
    }

});

