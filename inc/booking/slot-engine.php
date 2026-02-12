<?php
/**
 * Floorcare booking availability engine
 *
 * Responsibility:
 * - Given a date, return valid start times
 * - Uses total cart duration
 * - Does NOT render UI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Booking configuration
 * (can later be moved to wp_options)
 */
function hjm_floorcare_get_booking_config() {

    return [
        // Fixed start slots (HH:MM)
        'fixed_slots' => [
            '08:00',
            '11:00',
            '14:00',
        ],

        // Business day hard stop (job must END by this time)
        'day_end' => '17:00',
    ];
}

/**
 * Get total job duration in minutes
 */
function hjm_floorcare_get_job_duration_minutes() {
    return (int) hjm_floorcare_calculate_cart_duration();
}

/**
 * Get existing bookings for a date
 *
 * Format:
 * [
 *   ['start' => '09:00', 'end' => '11:00'],
 * ]
 */
function hjm_floorcare_get_bookings_for_date( $date ) {

    global $wpdb;

    if ( ! $date ) {
        return [];
    }

    $table = $wpdb->prefix . 'hjm_floorcare_bookings';

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "
            SELECT start_time, end_time
            FROM {$table}
            WHERE booking_date = %s
              AND status IN ('pending', 'confirmed')
            ",
            $date
        ),
        ARRAY_A
    );

    if ( empty( $rows ) ) {
        return [];
    }

    // Normalize to expected format
    return array_map( function ( $row ) {
        return [
            'start' => substr( $row['start_time'], 0, 5 ),
            'end'   => substr( $row['end_time'], 0, 5 ),
        ];
    }, $rows );
}

/**
 * Convert HH:MM to minutes since midnight
 */
function hjm_floorcare_time_to_minutes( $time ) {
    [ $h, $m ] = array_map( 'intval', explode( ':', $time ) );
    return ( $h * 60 ) + $m;
}

/**
 * Convert minutes since midnight to HH:MM
 */
function hjm_floorcare_minutes_to_time( $minutes ) {
    $h = floor( $minutes / 60 );
    $m = $minutes % 60;
    return sprintf( '%02d:%02d', $h, $m );
}

/**
 * Check if a proposed time slot overlaps existing bookings
 */
function hjm_floorcare_slot_conflicts( $start, $end, $bookings ) {

    foreach ( $bookings as $booking ) {

        $b_start = hjm_floorcare_time_to_minutes( $booking['start'] );
        $b_end   = hjm_floorcare_time_to_minutes( $booking['end'] );

        // Overlap check
        if ( $start < $b_end && $end > $b_start ) {
            return true;
        }
    }

    return false;
}

/**
 * Core function:
 * Get available booking start times for a date
 */
function hjm_floorcare_get_available_slots( $date ) {

    $duration = hjm_floorcare_get_job_duration_minutes();
    if ( $duration <= 0 ) return [];

    $availability = hjm_floorcare_get_daily_availability( $date );

    if (
        $availability['is_closed'] ||
        $availability['remaining'] < $duration
    ) {
        return [];
    }

    $config   = hjm_floorcare_get_booking_config();
    $day_end  = hjm_floorcare_time_to_minutes( $config['day_end'] );
    $bookings = hjm_floorcare_get_bookings_for_date( $date );

    $slots = [];

    foreach ( $config['fixed_slots'] as $time ) {

        $start = hjm_floorcare_time_to_minutes( $time );
        $end   = $start + $duration;

        if ( $end > $day_end ) continue;

        if ( hjm_floorcare_slot_conflicts( $start, $end, $bookings ) ) continue;

        $slots[] = $time;
    }

    return $slots;
}
