<?php

add_action('wp_enqueue_scripts', function () {

    if (!is_cart() && !is_checkout()) {
        return;
    }

    $key = defined('HJM_GOOGLE_PLACES_API_KEY') ? HJM_GOOGLE_PLACES_API_KEY : '';
    if (empty($key)) {
        return;
    }

    wp_enqueue_script(
        'google-places',
        "https://maps.googleapis.com/maps/api/js?key={$key}&libraries=places",
        [],
        null,
        true
    );

});
