<?php

// Optional test hook to seed cart for development only.
// Enable by defining HJM_FLOORCARE_ENABLE_TEST_HOOK to true.
add_action('wp_loaded', function () {

    if ( ! defined('HJM_FLOORCARE_ENABLE_TEST_HOOK') || ! HJM_FLOORCARE_ENABLE_TEST_HOOK ) {
        return;
    }

    if (!isset($_GET['floorcare_test'])) {
        return;
    }

    if (!class_exists('WooCommerce')) {
        return;
    }

    if (!WC()->cart) {
        return;
    }

    // Resolve product by slug
    $product = get_page_by_path('floor-care-service', OBJECT, 'product');

    if (!$product) {
        return;
    }

    // Clear cart first
    WC()->cart->empty_cart();

    // Add item
    WC()->cart->add_to_cart(
        $product->ID,
        1,
        0,
        [],
        [
            'floorcare' => [
                'rooms' => 3,
                'pet_treatment' => true,
            ]
        ]
    );

    // Force session save BEFORE redirect
    WC()->cart->set_session();

    wp_safe_redirect(wc_get_cart_url());
    exit;
});
