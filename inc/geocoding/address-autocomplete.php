<?php

add_action('wp_enqueue_scripts', function () {

    if (!is_cart() && !is_checkout()) {
        return;
    }

    $key = HJM_GOOGLE_PLACES_API_KEY;

    wp_enqueue_script(
        'google-places',
        "https://maps.googleapis.com/maps/api/js?key={$key}&libraries=places",
        [],
        null,
        true
    );

});

add_action('wp_footer', function () {

    if (!is_cart() && !is_checkout()) return;
    ?>
    <script>
        (function () {

            function initFloorcareAutocomplete() {

                const input = document.querySelector('input[name="floorcare_address"]');
                if (!input || !window.google || !google.maps || !google.maps.places) return;

                // Prevent double-binding if Woo re-renders without replacing the input
                if (input.dataset.floorcareAutocomplete === '1') return;
                input.dataset.floorcareAutocomplete = '1';

                const autocomplete = new google.maps.places.Autocomplete(input, {
                    types: ['address'],
                    componentRestrictions: { country: 'us' }
                });

                autocomplete.addListener('place_changed', function () {

                    const place = autocomplete.getPlace();
                    if (!place || !place.address_components) return;

                    let streetNumber = '';
                    let route = '';
                    let city = '';
                    let state = '';
                    let zip = '';

                    place.address_components.forEach(c => {
                        if (c.types.includes('street_number')) streetNumber = c.long_name;
                        if (c.types.includes('route')) route = c.long_name;
                        if (c.types.includes('locality')) city = c.long_name;
                        if (c.types.includes('administrative_area_level_1')) state = c.short_name;
                        if (c.types.includes('postal_code')) zip = c.long_name;
                    });

                    const street = [streetNumber, route].filter(Boolean).join(' ');

                    const setVal = (name, value) => {
                        const el = document.querySelector(`input[name="${name}"]`);
                        if (!el) return;
                        el.value = value || '';
                        // Fire input so your existing debounce/AJAX runs
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                    };

                    // Fill address fields
                    if (street) setVal('floorcare_address', street);
                    if (city)   setVal('floorcare_city', city);
                    if (state)  setVal('floorcare_state', state);
                    if (zip)    setVal('floorcare_zip', zip);

                    // Apt must be manual; clear it when address changes
                    setVal('floorcare_apt', '');

                });
            }

            // Initial bind
            document.addEventListener('DOMContentLoaded', initFloorcareAutocomplete);

            // Re-bind after Woo updates cart fragments / checkout fields (inputs get replaced)
            if (window.jQuery) {
                jQuery(function ($) {
                    $(document.body).on(
                        'updated_wc_div wc_fragments_loaded updated_checkout',
                        function () {
                            initFloorcareAutocomplete();
                        }
                    );
                });
            }

        })();
    </script>
    <?php
});


