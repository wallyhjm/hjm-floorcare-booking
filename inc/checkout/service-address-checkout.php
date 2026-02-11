<?php

add_action('woocommerce_checkout_before_customer_details', function () {

    $address = WC()->session->get('floorcare_service_address');
    $parts   = WC()->session->get('floorcare_service_address_parts', []);

    if (empty($address)) {
        return;
    }
    ?>

    <div class="floorcare-checkout-service-address">
        <h3>Service Location</h3>

        <p class="service-address-display">
            <?php echo esc_html($address); ?>
        </p>

        <label>
            <input type="checkbox" id="edit-service-address" />
            Edit service address
        </label>

        <div id="service-address-edit" style="display:none;">

            <p>
                <input type="text"
                       name="floorcare_address"
                       placeholder="Street address"
                       value="<?php echo esc_attr($parts['address'] ?? ''); ?>">
            </p>

            <p>
                <input type="text"
                       name="floorcare_apt"
                       placeholder="Apartment, unit, suite (optional)"
                       value="<?php echo esc_attr($parts['apt'] ?? ''); ?>">
            </p>

            <p>
                <input type="text"
                       name="floorcare_city"
                       placeholder="City"
                       value="<?php echo esc_attr($parts['city'] ?? ''); ?>">
            </p>

            <p>
                <input type="text"
                       name="floorcare_state"
                       placeholder="State"
                       value="<?php echo esc_attr($parts['state'] ?? ''); ?>">
            </p>

            <p>
                <input type="text"
                       name="floorcare_zip"
                       placeholder="ZIP Code"
                       value="<?php echo esc_attr($parts['zip'] ?? ''); ?>">
            </p>

        </div>
    </div>
    <?php
});

