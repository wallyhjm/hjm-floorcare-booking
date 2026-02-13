<?php
/**
 * Floorcare Availability Engine
 *
 * Handles daily capacity, booked minutes, and availability checks.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// inc/availability.php (near top or bottom)
if ( ! defined( 'HJM_FLOORCARE_LIMITED_THRESHOLD_MINUTES' ) ) {
    define( 'HJM_FLOORCARE_LIMITED_THRESHOLD_MINUTES', 120 ); // 2 hours
}

/**
 * Get daily capacity data
 */
function hjm_floorcare_get_daily_capacity( $service_date ) {
    global $wpdb;

    $table = $wpdb->prefix . 'hjm_floorcare_daily_capacity';

    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT total_minutes, is_closed
             FROM {$table}
             WHERE service_date = %s",
            $service_date
        ),
        ARRAY_A
    );

    if ( ! $row ) {
        return [
            'capacity'  => 0,
            'is_closed' => true,
        ];
    }

    return [
        'capacity'  => (int) $row['total_minutes'],
        'is_closed' => (bool) $row['is_closed'],
    ];
}

/**
 * Get booked minutes for a date
 */
function hjm_floorcare_get_booked_minutes( $service_date ) {
    global $wpdb;

    $table = $wpdb->prefix . 'hjm_floorcare_bookings';

    $valid_statuses = [ 'pending', 'confirmed' ];

    $placeholders = implode(
        ',',
        array_fill( 0, count( $valid_statuses ), '%s' )
    );

    $sql = "
        SELECT COALESCE( SUM(duration_minutes), 0 )
        FROM {$table}
        WHERE booking_date = %s
          AND status IN ({$placeholders})
    ";

    $params = array_merge( [ $service_date ], $valid_statuses );

    return (int) $wpdb->get_var(
        $wpdb->prepare( $sql, $params )
    );
}

/**
 * Get availability summary for a date
 */
function hjm_floorcare_get_daily_availability( $service_date ) {

    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $service_date ) ) {
        return [
            'date'       => $service_date,
            'capacity'   => 0,
            'booked'     => 0,
            'remaining'  => 0,
            'is_closed'  => true,
        ];
    }

    $capacity_data = hjm_floorcare_get_daily_capacity( $service_date );

    if ( $capacity_data['is_closed'] ) {
        return [
            'date'       => $service_date,
            'capacity'   => 0,
            'booked'     => 0,
            'remaining'  => 0,
            'is_closed'  => true,
        ];
    }

    $capacity  = $capacity_data['capacity'];
    $booked    = hjm_floorcare_get_booked_minutes( $service_date );
    $remaining = max( 0, $capacity - $booked );

    return [
        'date'       => $service_date,
        'capacity'   => $capacity,
        'booked'     => $booked,
        'remaining'  => $remaining,
        'is_closed'  => false,
    ];
}

/**
 * Check if a job can fit on a date
 */
function hjm_floorcare_can_fit_job( $service_date, $job_minutes ) {
    $availability = hjm_floorcare_get_daily_availability( $service_date );

    if ( $availability['is_closed'] ) {
        return false;
    }

    return $availability['remaining'] >= (int) $job_minutes;
}

/**
 * Get availability UI state for a date
 *
 * @param string $service_date Y-m-d
 * @return array
 */
function hjm_floorcare_get_availability_ui_state( $service_date ) {

    $availability = hjm_floorcare_get_daily_availability( $service_date );

    if ( $availability['is_closed'] || $availability['remaining'] <= 0 ) {
        return [
            'status'  => 'none',
            'message' => 'No availability for this date.',
        ];
    }

    if ( $availability['remaining'] <= HJM_FLOORCARE_LIMITED_THRESHOLD_MINUTES ) {
        return [
            'status'  => 'limited',
            'message' => 'Limited availability - fewer time slots remain.',
        ];
    }

    return [
        'status'  => 'available',
        'message' => '',
    ];
}

add_action( 'wp_ajax_floorcare_get_date_availability', 'hjm_floorcare_ajax_get_date_availability' );
add_action( 'wp_ajax_nopriv_floorcare_get_date_availability', 'hjm_floorcare_ajax_get_date_availability' );

function hjm_floorcare_ajax_get_date_availability() {

    if ( ! check_ajax_referer( 'hjm_floorcare_ajax', 'nonce', false ) ) {
        wp_send_json_error([ 'message' => 'Invalid nonce.' ], 403);
    }

    $date = sanitize_text_field( $_POST['date'] ?? '' );

    if ( empty( $date ) ) {
        wp_send_json_error();
    }

    $availability = hjm_floorcare_get_daily_availability( $date );

    // Closed day
    if ( $availability['is_closed'] ) {
        wp_send_json_success([
            'date'      => $date,
            'status'    => 'none',
            'capacity'  => 0,
            'booked'    => 0,
            'remaining' => 0,
            'message'   => 'No availability on this date',
        ]);
    }

    // Fully booked
    if ( $availability['remaining'] <= 0 ) {
        wp_send_json_success([
            'date'      => $date,
            'status'    => 'none',
            'capacity'  => $availability['capacity'],
            'booked'    => $availability['booked'],
            'remaining' => 0,
            'message'   => 'This date is fully booked',
        ]);
    }

    // Limited threshold
    $status  = 'available';
    $message = 'Availability available';

    if ( $availability['remaining'] <= HJM_FLOORCARE_LIMITED_THRESHOLD_MINUTES ) {
        $status  = 'limited';
        $message = 'Limited availability remaining';
    }

    wp_send_json_success([
        'date'      => $date,
        'status'    => $status,
        'capacity'  => $availability['capacity'],
        'booked'    => $availability['booked'],
        'remaining' => $availability['remaining'],
        'message'   => $message,
    ]);
}
