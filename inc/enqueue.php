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
            'nonce'      => wp_create_nonce( 'hjm_floorcare_ajax' ),
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

        $booking_js = HJM_FLOORCARE_PATH . 'inc/assets/js/booking.js';
        $booking_ver = file_exists( $booking_js ) ? filemtime( $booking_js ) : '1.0.0';

        wp_enqueue_script(
            'hjm-floorcare-booking',
            HJM_FLOORCARE_URL . 'inc/assets/js/booking.js',
            ['jquery'],
            $booking_ver,
            true
        );

        wp_localize_script(
            'hjm-floorcare-booking',
            'floorcareBookingContext',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce( 'hjm_floorcare_ajax' ),
            ]
        );
    }
});


