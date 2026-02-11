<?php
function hjm_floorcare_calculate_item_duration( $cart_item ) {

    if ( empty( $cart_item['data'] ) ) {
        return 0;
    }

    $product = $cart_item['data'];

    // Base duration per unit (minutes)
    $base = (int) $product->get_meta('_floorcare_base_duration');

    if ( $base <= 0 ) {
        return 0;
    }

    // Quantity = rooms / items
    $qty = max( 1, (int) $cart_item['quantity'] );

    // Variation multiplier
    $multiplier = 1;

    if ( $product->is_type('variation') ) {
        $m = get_post_meta( $product->get_id(), '_floorcare_duration_multiplier', true );
        if ( is_numeric( $m ) && $m > 0 ) {
            $multiplier = (float) $m;
        }
    }

    $duration = $base * $qty * $multiplier;

    // Add-ons
    if ( ! empty( $cart_item['floorcare_addons'] ) ) {

        foreach ( $cart_item['floorcare_addons'] as $addon_id ) {

            $addon_minutes = (int) get_post_meta( $addon_id, '_addon_duration', true );
            $per_unit      = get_post_meta( $addon_id, '_addon_per_unit', true ) === 'yes';

            if ( $addon_minutes <= 0 ) {
                continue;
            }

            $duration += $per_unit
                ? $addon_minutes * $qty
                : $addon_minutes;
        }
    }

    return (int) ceil( $duration );
}

function hjm_floorcare_calculate_cart_duration() {

    if ( ! WC()->cart ) {
        return 0;
    }

    $total = 0;

    foreach ( WC()->cart->get_cart() as $cart_item ) {
        $total += hjm_floorcare_calculate_item_duration( $cart_item );
    }

    return (int) $total;
}

add_action( 'woocommerce_cart_calculate_fees', function () {

    $duration = hjm_floorcare_calculate_cart_duration();

    WC()->session->set( 'floorcare_total_duration', $duration );

});

add_action( 'woocommerce_cart_totals_before_order_total', function () {

    $duration = WC()->session->get( 'floorcare_total_duration' );

    if ( ! $duration ) return;

    echo '<tr class="floorcare-duration">';
    echo '<th>Estimated Service Time</th>';
    echo '<td>' . esc_html( ceil( $duration / 60 * 2 ) / 2 ) . ' hours</td>';
    echo '</tr>';
});

add_action( 'woocommerce_checkout_create_order', function ( $order ) {

    $duration = WC()->session->get( 'floorcare_total_duration' );

    if ( $duration ) {
        $order->update_meta_data(
            '_floorcare_total_duration',
            $duration
        );
    }
});
