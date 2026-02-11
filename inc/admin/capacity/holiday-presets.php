<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * US Federal Holidays (fixed + computed)
 */
function hjm_floorcare_get_holiday_presets( $year ) {

    return [
        // Fixed-date holidays
        "$year-01-01" => 'New Year’s Day',
        "$year-07-04" => 'Independence Day',
        "$year-11-11" => 'Veterans Day',
        "$year-12-25" => 'Christmas Day',
        "$year-12-24" => 'Christmas Eve Day',

        // Computed holidays
        date('Y-m-d', strtotime("third monday of january $year")) => 'MLK Day',
        date('Y-m-d', strtotime("third monday of february $year")) => 'Presidents’ Day',
        date('Y-m-d', strtotime("last monday of may $year")) => 'Memorial Day',
        date('Y-m-d', strtotime("first monday of september $year")) => 'Labor Day',
        date('Y-m-d', strtotime("fourth thursday of november $year")) => 'Thanksgiving',
    ];
}

add_action('wp_ajax_hjm_apply_holiday_presets', function () {

    check_ajax_referer('hjm_capacity_save', 'nonce');

    $year = isset($_POST['year'])
        ? (int) $_POST['year']
        : (int) date('Y');

    global $wpdb;
    $table = $wpdb->prefix . 'hjm_floorcare_daily_capacity';

    $holidays = hjm_floorcare_get_holiday_presets( $year );

    foreach ( $holidays as $date => $label ) {

        $wpdb->update(
            $table,
            [
                'is_closed'     => 1,
                'total_minutes' => 0,
                'is_override'   => 1,
            ],
            [ 'service_date' => $date ],
            [ '%d', '%d', '%d' ],
            [ '%s' ]
        );
    }

    wp_send_json_success([
        'applied' => count($holidays),
        'year'    => $year,
    ]);
});

