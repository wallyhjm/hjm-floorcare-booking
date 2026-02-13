(function ($) {

    const ctx = window.floorcareBookingContext || {};
    if (!ctx.ajaxUrl) return;

    let timer = null;

    function fetchSlots(date) {
        return $.post(
            ctx.ajaxUrl,
            {
                action: 'floorcare_get_slots',
                nonce: ctx.nonce || '',
                date: date
            },
            null,
            'json'
        ).then(
            resp => {
                if (!resp || !resp.success) return [];
                return resp.data || [];
            },
            () => []
        );
    }

    function setBooking(date, time) {
        return $.post(ctx.ajaxUrl, {
            action: 'floorcare_set_booking',
            nonce: ctx.nonce || '',
            date: date,
            time: time
        }, null, 'json');
    }

    function syncDateState($container, date, preserveSelection) {
        const $time = $container.find('[name="floorcare_booking_time"]');
        const $msg = $container.find('.floorcare-availability-message');
        const selectedBeforeRefresh = preserveSelection ? ($time.val() || '') : '';

        populateTimes($container, [], '');

        if (!date) {
            setBooking('', '');
            return;
        }

        return fetchAvailability(date).then(state => {

            if (!state) {
                $msg.hide();
                $time.prop('disabled', false);
                // Still attempt to load slots if availability endpoint fails.
                return fetchSlots(date).then(times => {
                    populateTimes($container, times, selectedBeforeRefresh);
                });
            }

            if (state.status === 'none') {
                setBooking('', '');
                $time.prop('disabled', true);
            } else {
                $time.prop('disabled', false);
            }

            $msg
                .removeClass('available limited none')
                .addClass(state.status)
                .text(state.message)
                .show();

            // Only fetch slots if date is usable
            if (state.status !== 'none') {
                return fetchSlots(date).then(times => {
                    populateTimes($container, times, selectedBeforeRefresh);
                });
            }
        });
    }

    function populateTimes($container, times, preferredValue) {
        const $sel = $container.find('[name="floorcare_booking_time"]');
        $sel.prop('disabled', true);

        if (times.length > 0) {
            $sel.prop('disabled', false);
        }

        $container.find('.no-slots').remove();

        $sel.empty().append(
            $('<option>', { value: '', text: '-- Select a time --' })
        );

        times.forEach(t => {
            $sel.append($('<option>', { value: t, text: t }));
        });

        const shouldKeep = preferredValue && times.indexOf(preferredValue) !== -1;
        $sel.val(shouldKeep ? preferredValue : '');

        if (times.length === 0) {
            $sel.after(
                //'<p class="floorcare-note no-slots">No available start times for this date.</p>'
            );
        }
    }

    function fetchAvailability(date) {
        return $.post(
            ctx.ajaxUrl,
            {
                action: 'floorcare_get_date_availability',
                nonce: ctx.nonce || '',
                date: date
            },
            null,
            'json'
        ).then(
            resp => resp?.success ? resp.data : null,
            () => null
        );
    }

    // Date change
    jQuery(document).on('change', '[name="floorcare_booking_date"]', function () {

        const $container = jQuery(this).closest('.floorcare-booking');
        const date = jQuery(this).val();
        syncDateState($container, date, false);
    });


    // Time change
    $(document).on('change', '[name="floorcare_booking_time"]', function () {

        const $container = $(this).closest('.floorcare-booking');
        const date = $container.find('[name="floorcare_booking_date"]').val();
        const time = $(this).val();

        clearTimeout(timer);
        timer = setTimeout(function () {
            setBooking(date, time).then(resp => {
                if (!resp || !resp.success) {
                    // Server rejected slot (stale/invalid). Reset UI to avoid phantom selection.
                    $container.find('[name="floorcare_booking_time"]').val('');
                }
            }, () => {
                $container.find('[name="floorcare_booking_time"]').val('');
            });
        }, 250);
    });

    // Cart recalculation: keep selected time if still valid for selected date.
    $(document.body).on('updated_cart_totals', function () {
        $('.floorcare-booking').each(function () {
            const $container = $(this);
            const date = $container.find('[name="floorcare_booking_date"]').val();
            const selected = $container.find('[name="floorcare_booking_time"]').val();

            if (!date || !selected) return;

            fetchSlots(date).then(times => {
                if (times.indexOf(selected) === -1) {
                    $container.find('[name="floorcare_booking_time"]').val('');
                    setBooking(date, '');
                }
            });
        });
    });

    function refreshBookingUi(preserveSelection) {
        $('.floorcare-booking').each(function () {
            const $container = $(this);
            const date = $container.find('[name="floorcare_booking_date"]').val();
            if (date) {
                syncDateState($container, date, !!preserveSelection);
            }
        });
    }

    // Initial load: if date already selected in session/UI, fetch available times immediately.
    refreshBookingUi(true);

    // Woo can re-render cart/checkout fragments; re-hydrate time options after refresh.
    $(document.body).on('updated_wc_div updated_cart_totals updated_checkout', function () {
        refreshBookingUi(true);
    });

})(jQuery);
