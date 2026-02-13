<?php

add_action('rest_api_init', function () {

    register_rest_route(
        'wc/store',
        '/floorcare/service-address',
        [
            [
                'methods'  => 'GET',
                'callback' => 'hjm_floorcare_get_service_address',
                'permission_callback' => 'hjm_floorcare_store_api_permission',
            ],
            [
                'methods'  => 'POST',
                'callback' => 'hjm_floorcare_set_service_address',
                'permission_callback' => 'hjm_floorcare_store_api_permission',
            ],
        ]
    );

});

function hjm_floorcare_store_api_permission( $request ) {
    if ( ! is_user_logged_in() ) {
        return true;
    }

    $nonce = $request->get_header( 'X-WP-Nonce' );

    return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
}

/**
 * GET service address
 */
function hjm_floorcare_get_service_address()
{
    $parts = WC()->session->get('floorcare_service_address_parts', []);

    return [
        'address' => $parts['address'] ?? '',
        'city'    => $parts['city'] ?? '',
        'state'   => $parts['state'] ?? '',
        'zip'     => $parts['zip'] ?? '',
        'full'    => WC()->session->get('floorcare_service_address', ''),
    ];
}

/**
 * POST service address
 */
function hjm_floorcare_set_service_address($request)
{
    $data = $request->get_json_params();

    $address = [
        'address' => sanitize_text_field($data['address'] ?? ''),
        'city'    => sanitize_text_field($data['city'] ?? ''),
        'state'   => sanitize_text_field($data['state'] ?? ''),
        'zip'     => sanitize_text_field($data['zip'] ?? ''),
    ];

    WC()->session->set('floorcare_service_address_parts', $address);

    if (
        !empty($address['address']) &&
        !empty($address['city']) &&
        !empty($address['state']) &&
        !empty($address['zip'])
    ) {
        WC()->session->set(
            'floorcare_service_address',
            implode(', ', $address)
        );
    } else {
        WC()->session->__unset('floorcare_service_address');
    }

    return [
        'success' => true,
        'address' => $address,
    ];
}
