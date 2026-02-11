<?php
if ( ! defined('ABSPATH') ) exit;

add_action('wp_ajax_hjm_save_capacity', function () {

    check_ajax_referer('hjm_capacity_save', 'nonce');

    if ( empty($_POST['service_date']) ) {
        wp_send_json_error('Missing service date');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'hjm_floorcare_daily_capacity';

    $service_date = sanitize_text_field( $_POST['service_date'] );
    $is_closed    = ! empty( $_POST['is_closed'] ) ? 1 : 0;
    $total_minutes = isset($_POST['total_minutes'])
        ? (int) $_POST['total_minutes']
        : 0;

    // Closed day always forces 0 minutes
    if ( $is_closed ) {
        $total_minutes = 0;
    }

    $wpdb->update(
        $table,
        [
            'total_minutes' => $total_minutes,
            'is_closed'     => $is_closed,
            'is_override'   => 1,
        ],
        [
            'service_date' => $service_date,
        ],
        [ '%d', '%d', '%d' ],
        [ '%s' ]
    );

    wp_send_json_success();
});

add_action( 'wp_ajax_hjm_bulk_close_dates', function () {

    check_ajax_referer( 'hjm_bulk_close', 'nonce' );

    global $wpdb;
    $table = $wpdb->prefix . 'hjm_floorcare_daily_capacity';

    $start = sanitize_text_field( $_POST['start_date'] ?? '' );
    $end   = sanitize_text_field( $_POST['end_date'] ?? '' );

    if ( ! $start || ! $end || $start > $end ) {
        wp_send_json_error( 'Invalid date range' );
    }

    $wpdb->query(
        $wpdb->prepare(
            "
            UPDATE {$table}
            SET
                total_minutes = 0,
                is_closed = 1,
                is_override = 1
            WHERE service_date BETWEEN %s AND %s
            ",
            $start,
            $end
        )
    );

    wp_send_json_success();
});
