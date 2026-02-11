<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Block checkout if booking date/time missing
 */
add_action( 'woocommerce_checkout_process', function () {

    $date = WC()->session->get( 'floorcare_booking_date' );
    $time = WC()->session->get( 'floorcare_booking_time' );

    if ( empty( $date ) || empty( $time ) ) {
        wc_add_notice(
            'Please select a service date and time before completing checkout.',
            'error'
        );
    }
});

/**
 * Validate booking slot availability before order is created
 */
add_action( 'woocommerce_checkout_process', function () {

    $date = WC()->session->get( 'floorcare_booking_date' );
    $time = WC()->session->get( 'floorcare_booking_time' );

    if ( ! $date || ! $time ) return;

    $slots = hjm_floorcare_get_available_slots( $date );

    if ( ! in_array( $time, $slots, true ) ) {
        wc_add_notice(
            'The selected service time is no longer available. Please choose a different time.',
            'error'
        );

        // Clear invalid selection
        WC()->session->__unset( 'floorcare_booking_time' );
    }
});
