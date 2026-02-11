<?php

add_action('woocommerce_before_checkout_billing_form', function () {
    ?>
    <p class="form-row form-row-wide floorcare-billing-same-as-service">
        <label>
            <input type="checkbox" id="billing-same-as-service">
            Billing details same as service address
        </label>
    </p>
    <?php
});

add_action('wp_footer', function () {
    if (!is_checkout()) return;
    ?>
    <script>
        (function ($) {

            const service = <?php
                echo wp_json_encode(
                    WC()->session->get('floorcare_service_address_parts', [])
                );
                ?>;

            $('#billing-same-as-service').on('change', function () {

                if (!this.checked) return;

                if (!service || !service.address) return;

                // Parse street number + name for Woo fields
                const street = service.address;

                $('#billing_address_1').val(street).trigger('change');
                $('#billing_city').val(service.city || '').trigger('change');
                $('#billing_state').val(service.state || '').trigger('change');
                $('#billing_postcode').val(service.zip || '').trigger('change');
            });

        })(jQuery);
    </script>
    <?php
});
