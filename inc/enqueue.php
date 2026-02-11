<?php
add_action('wp_enqueue_scripts', function () {

    if ( ! is_cart() && ! is_checkout() ) {
        return;
    }

    wp_enqueue_script(
        'hjm-floorcare-service-address',
        HJM_FLOORCARE_URL . 'inc/assets/js/service-address.js',
        ['jquery'],
        '1.0.0',
        true
    );

    wp_localize_script(
        'hjm-floorcare-service-address',
        'floorcareAddressContext',
        [
            'ajaxUrl'    => admin_url('admin-ajax.php'),
            'isCart'     => is_cart(),
            'isCheckout' => is_checkout(),
        ]
    );

    wp_enqueue_style(
        'hjm-floorcare-checkout',
        HJM_FLOORCARE_URL . 'inc/assets/css/checkout.css',
        [],
        '1.0.0'
    );
});

add_action('wp_enqueue_scripts', function () {

    if ( is_cart() || is_checkout() ) {

        wp_enqueue_script(
            'hjm-floorcare-booking',
            HJM_FLOORCARE_URL . 'inc/assets/js/booking.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script(
            'hjm-floorcare-booking',
            'floorcareBookingContext',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
            ]
        );
    }
});


