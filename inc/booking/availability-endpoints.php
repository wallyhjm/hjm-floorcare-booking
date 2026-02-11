<?php

add_action('wp_ajax_floorcare_get_date_availability', 'hjm_floorcare_get_date_availability_ajax');
add_action('wp_ajax_nopriv_floorcare_get_date_availability', 'hjm_floorcare_get_date_availability_ajax');

function hjm_floorcare_get_date_availability_ajax() {

    $date = sanitize_text_field($_POST['date'] ?? '');

    if ( empty($date) ) {
        wp_send_json_error(['message' => 'Missing date'], 400);
    }

    $availability = hjm_floorcare_get_daily_availability($date);

    if ( $availability['is_closed'] ) {
        wp_send_json_success([
            'status'    => 'none',
            'message'   => 'No availability on this date.',
            'remaining' => 0,
        ]);
    }

    // Threshold can be tuned later
    if ( $availability['remaining'] <= 120 ) {
        wp_send_json_success([
            'status'    => 'limited',
            'message'   => 'Limited availability. Some time slots may be unavailable.',
            'remaining' => $availability['remaining'],
        ]);
    }

    wp_send_json_success([
        'status'    => 'available',
        'message'   => 'Availability available.',
        'remaining' => $availability['remaining'],
    ]);
}
