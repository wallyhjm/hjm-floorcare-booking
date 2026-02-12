(function ($) {

    let saveTimer = null;
    let recalcTimer = null;
    let autocomplete = null;

    let isTypingAddress = false;
    let isAutocompleteActive = false;

    const context = window.floorcareAddressContext || {};
    const isCheckout = !!context.isCheckout;

    const recalcEvent = isCheckout
        ? 'update_checkout'
        : 'wc_update_cart';

    const recalcTrigger = function () {
        $('body').trigger(recalcEvent);
    };

    /* ------------------------------------
     * Checkout: toggle edit address
     * ------------------------------------ */
    if (isCheckout) {
        $(document).on('change', '#edit-service-address', function () {
            const $edit = $('#service-address-edit');
            $edit.toggle(this.checked);

            if (this.checked) {
                setTimeout(initAutocomplete, 50);
            }
        });
    }

    function syncServiceToBilling() {

        // Only on checkout
        if (!isCheckout) return;

        const $same = $('#billing_same_as_service_address');
        if (!$same.length || !$same.is(':checked')) return;

        $('#billing_address_1').val($('input[name="floorcare_address"]').val()).trigger('change');
        $('#billing_address_2').val($('input[name="floorcare_apt"]').val()).trigger('change');
        $('#billing_city').val($('input[name="floorcare_city"]').val()).trigger('change');
        $('#billing_state').val($('input[name="floorcare_state"]').val()).trigger('change');
        $('#billing_postcode').val($('input[name="floorcare_zip"]').val()).trigger('change');
    }

    /* ------------------------------------
     * Address input handling (manual typing)
     * ------------------------------------ */
    $(document).on(
        'input',
        'input[name="floorcare_address"], ' +
        'input[name="floorcare_apt"], ' +
        'input[name="floorcare_city"], ' +
        'input[name="floorcare_state"], ' +
        'input[name="floorcare_zip"]',
        function () {

            isTypingAddress = true;

            clearTimeout(saveTimer);
            saveTimer = setTimeout(function () {

                const data = {
                    action: 'floorcare_update_address',
                    nonce: context.nonce || '',
                    address: $('input[name="floorcare_address"]').val(),
                    apt: $('input[name="floorcare_apt"]').val(),
                    city: $('input[name="floorcare_city"]').val(),
                    state: $('input[name="floorcare_state"]').val(),
                    zip: $('input[name="floorcare_zip"]').val()
                };

                $.post(context.ajaxUrl, data, function () {

                    // Checkout: update visible address immediately
                    if (!isCheckout) return;

                    if (data.address && data.city && data.state && data.zip) {
                        const full = [
                            data.address,
                            data.apt,
                            data.city,
                            data.state + ' ' + data.zip
                        ].filter(Boolean).join(', ');

                        $('.service-address-display').text(full);
                        syncServiceToBilling();
                    }
                });

            }, 300);

            clearTimeout(recalcTimer);
            recalcTimer = setTimeout(function () {

                if (isTypingAddress || isAutocompleteActive) return;

                recalcTrigger();
                isTypingAddress = false;

            }, 1600);
        }
    );

    /* ------------------------------------
     * Google Places helper (NEW)
     * ------------------------------------ */
    function fillAddressFromPlace(place) {

        let streetNumber = '';
        let route = '';
        let city = '';
        let state = '';
        let zip = '';

        (place.address_components || []).forEach(component => {
            const types = component.types;

            if (types.includes('street_number')) {
                streetNumber = component.long_name;
            }
            if (types.includes('route')) {
                route = component.long_name;
            }
            if (types.includes('locality')) {
                city = component.long_name;
            }
            if (types.includes('administrative_area_level_1')) {
                state = component.short_name;
            }
            if (types.includes('postal_code')) {
                zip = component.long_name;
            }
        });

        if (streetNumber || route) {
            $('input[name="floorcare_address"]').val(
                [streetNumber, route].filter(Boolean).join(' ')
            );
        }

        if (city) $('input[name="floorcare_city"]').val(city);
        if (state) $('input[name="floorcare_state"]').val(state);
        if (zip) $('input[name="floorcare_zip"]').val(zip);
    }

    /* ------------------------------------
     * Google Places Autocomplete
     * ------------------------------------ */
    function initAutocomplete() {

        if (
            typeof google === 'undefined' ||
            !google.maps ||
            !google.maps.places
        ) {
            return;
        }

        const input = isCheckout
            ? document.querySelector('#service-address-edit input[name="floorcare_address"]')
            : document.querySelector('input[name="floorcare_address"]');

        if (!input || autocomplete) return;

        autocomplete = new google.maps.places.Autocomplete(input, {
            types: ['address'],
            componentRestrictions: { country: 'us' }
        });

        input.addEventListener('focus', function () {
            isAutocompleteActive = true;
        });

        autocomplete.addListener('place_changed', function () {

            const place = autocomplete.getPlace();
            if (!place || !place.address_components) return;

            fillAddressFromPlace(place);

            isTypingAddress = false;
            isAutocompleteActive = false;

            input.blur();

            // Google does not trigger input events
            // We must explicitly save + recalc here
            const data = {
                action: 'floorcare_update_address',
                nonce: context.nonce || '',
                address: $('input[name="floorcare_address"]').val(),
                apt: $('input[name="floorcare_apt"]').val(),
                city: $('input[name="floorcare_city"]').val(),
                state: $('input[name="floorcare_state"]').val(),
                zip: $('input[name="floorcare_zip"]').val()
            };

            $.post(context.ajaxUrl, data, function () {

                // Update checkout display immediately
                if (isCheckout && data.address && data.city && data.state && data.zip) {
                    const full = [
                        data.address,
                        data.apt,
                        data.city,
                        data.state + ' ' + data.zip
                    ].filter(Boolean).join(', ');

                    $('.service-address-display').text(full);
                    syncServiceToBilling();
                }

                // Now force totals refresh
                recalcTrigger();
            });

            setTimeout(function () {
                document.querySelectorAll('.pac-container').forEach(el => el.remove());
            }, 100);
        });

    }

    /* ------------------------------------
     * Init + survive Woo refreshes
     * ------------------------------------ */
    $(document).ready(function () {
        if (!isCheckout || $('#service-address-edit').is(':visible')) {
            initAutocomplete();
        }
    });

    $(document.body).on(
        'updated_wc_div updated_cart_totals updated_checkout',
        function () {
            document.querySelectorAll('.pac-container').forEach(el => el.remove());
            autocomplete = null;

            if (!isCheckout || $('#service-address-edit').is(':visible')) {
                initAutocomplete();
            }
        }
    );

    /* ------------------------------------
     * Click-outside failsafe
     * ------------------------------------ */
    document.addEventListener('click', function (e) {
        if (
            !e.target.closest('.pac-container') &&
            !e.target.closest('input[name="floorcare_address"]')
        ) {
            document.querySelectorAll('.pac-container').forEach(el => el.remove());
        }
    });

})(jQuery);
