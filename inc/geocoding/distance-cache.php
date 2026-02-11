<?php

/**
 * Create consistent hash for address strings
 */
function hjm_floorcare_distance_hash($address)
{
    return hash('sha256', strtolower(trim($address)));
}

/**
 * Get cached distance if available
 *
 * @return float|false
 */
function hjm_floorcare_get_cached_distance($origin, $destination)
{
    global $wpdb;

    $table = $wpdb->prefix . 'hjm_floorcare_distance_cache';

    $origin_hash      = hjm_floorcare_distance_hash($origin);
    $destination_hash = hjm_floorcare_distance_hash($destination);

    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT miles FROM {$table}
             WHERE origin_hash = %s
               AND destination_hash = %s
             LIMIT 1",
            $origin_hash,
            $destination_hash
        )
    );

    if ($row && isset($row->miles)) {
        return (float) $row->miles;
    }

    return false;
}

/**
 * Store distance result
 */
function hjm_floorcare_store_distance($origin, $destination, $miles)
{
    global $wpdb;

    $table = $wpdb->prefix . 'hjm_floorcare_distance_cache';

    $wpdb->replace($table, [
        'origin_hash'      => hjm_floorcare_distance_hash($origin),
        'destination_hash' => hjm_floorcare_distance_hash($destination),
        'origin'           => $origin,
        'destination'      => $destination,
        'miles'            => $miles,
        'updated'          => current_time('mysql'),
    ]);
}

