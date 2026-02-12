<?php

/**
 * Get driving distance in miles using Google Distance Matrix API
 *
 * @param string $origin
 * @param string $destination
 * @return float|false
 */
function hjm_floorcare_google_drive_distance($origin, $destination)
{
    if (empty($origin) || empty($destination)) {
        return false;
    }

    $api_key = defined('HJM_GOOGLE_API_KEY') ? HJM_GOOGLE_API_KEY : '';

    if (!$api_key) {
        return false;
    }

    $url = add_query_arg([
        'origins'      => $origin,
        'destinations' => $destination,
        'units'        => 'imperial',
        'key'          => $api_key,
    ], 'https://maps.googleapis.com/maps/api/distancematrix/json');

    $response = wp_remote_get($url, [
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (
        empty($body['rows'][0]['elements'][0]['distance']['value'])
    ) {
        return false;
    }

    // meters -> miles
    $meters = $body['rows'][0]['elements'][0]['distance']['value'];
    $miles  = round($meters / 1609.34, 1);

    return $miles;
}
