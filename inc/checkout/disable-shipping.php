<?php

// Disable all shipping for floor care
add_filter( 'woocommerce_cart_needs_shipping', '__return_false' );
add_filter( 'woocommerce_checkout_fields', function ( $fields ) {

    unset( $fields['shipping'] );

    return $fields;
});

