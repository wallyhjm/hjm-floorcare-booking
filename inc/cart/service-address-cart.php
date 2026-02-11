<?php

/**
 * Show service address fields on cart page
 */
add_action('woocommerce_before_cart_totals', function () {

    $address = WC()->session->get('floorcare_service_address_parts', []);

    ?>
    <div class="floorcare-service-address">
        <h3>Service Location</h3>

        <p><input type="text" name="floorcare_address" placeholder="Street address" value="<?php echo esc_attr($address['address'] ?? ''); ?>" class="input-text" /></p>
        <p><input type="text" name="floorcare_apt" placeholder="Apartment, unit, suite (optional)" value="<?php echo esc_attr($address['apt'] ?? ''); ?>" class="input-text" /></p>
        <p><input type="text" name="floorcare_city" placeholder="City" value="<?php echo esc_attr($address['city'] ?? ''); ?>" class="input-text" /></p>
        <p><input type="text" name="floorcare_state" placeholder="State" value="<?php echo esc_attr($address['state'] ?? ''); ?>" class="input-text" /></p>
        <p><input type="text" name="floorcare_zip" placeholder="ZIP Code" value="<?php echo esc_attr($address['zip'] ?? ''); ?>" class="input-text" /></p>

        <p class="floorcare-note">
            Trip charge will calculate automatically once address is entered.
        </p>
    </div>
    <?php
});

/**
 * Update service address via AJAX
 */
add_action('wp_ajax_floorcare_update_address', 'hjm_floorcare_update_address');
add_action('wp_ajax_nopriv_floorcare_update_address', 'hjm_floorcare_update_address');

function hjm_floorcare_update_address()
{

    $address = [
        'address' => sanitize_text_field($_POST['address'] ?? ''),
        'apt'     => sanitize_text_field($_POST['apt'] ?? ''),
        'city'    => sanitize_text_field($_POST['city'] ?? ''),
        'state'   => sanitize_text_field($_POST['state'] ?? ''),
        'zip'     => sanitize_text_field($_POST['zip'] ?? ''),
    ];

    // Save structured parts
    WC()->session->set('floorcare_service_address_parts', $address);

    // Only build full address when complete
    if (
        $address['address'] &&
        $address['city'] &&
        $address['state'] &&
        $address['zip']
    ) {

        $full = $address['address'];

        if (!empty($address['apt'])) {
            $full .= ', ' . $address['apt'];
        }

        $full .= ', ' . $address['city'] . ', ' . $address['state'] . ' ' . $address['zip'];

        WC()->session->set('floorcare_service_address', $full);

    } else {
        WC()->session->__unset('floorcare_service_address');
    }

    // Recalculate cart safely
    if (WC()->cart) {
        WC()->cart->calculate_totals();
    }

    wp_send_json_success();
}