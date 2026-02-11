<?php

function hjm_floorcare_get_job_distance()
{
    $origin = '320 South Military Avenue, Green Bay, WI 54303';
    $parts = WC()->session->get('floorcare_service_address_parts');

    if (empty($parts)) {
        return false;
    }

    $destination = implode(', ', $parts);
    if (empty($destination)) {
        return false;
    }

    // Check cache first
    $cached = hjm_floorcare_get_cached_distance($origin, $destination);

    if ($cached !== false) {
        return $cached;
    }

    // Call Google
    $miles = hjm_floorcare_google_drive_distance($origin, $destination);

    if ($miles === false) {
        return false;
    }

    // Store result
    hjm_floorcare_store_distance($origin, $destination, $miles);

    return $miles;
}

