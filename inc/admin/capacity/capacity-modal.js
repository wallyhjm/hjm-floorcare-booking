window.hjmCapacityLoaded = true;
console.log('HJM Capacity JS loaded');

/**
 * OPEN MODAL — capture click before WP swallows it
 */
document.addEventListener('click', function (e) {

    const btn = e.target.closest('.hjm-edit-capacity');
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    console.log('CAPACITY EDIT CLICK', btn);

    // Populate modal fields
    document.getElementById('hjm-capacity-date').value =
        btn.getAttribute('data-date') || '';

    document.getElementById('hjm-capacity-minutes').value =
        btn.getAttribute('data-minutes') || '';

    document.getElementById('hjm-capacity-closed').checked =
        btn.getAttribute('data-closed') == 1;

    document.getElementById('hjm-capacity-closed').addEventListener('change', function () {
        document.getElementById('hjm-capacity-minutes').disabled = this.checked;
    });

    // Show modal
    const modal = document.getElementById('hjm-capacity-modal');
    modal.style.display = 'block';
});

/**
 * CLOSE MODAL
 */
document.addEventListener('click', function (e) {

    if (!e.target.closest('.hjm-capacity-cancel')) return;

    e.preventDefault();

    const modal = document.getElementById('hjm-capacity-modal');
    modal.style.display = 'none';
});

/**
 * SAVE CAPACITY (AJAX)
 */
jQuery(function ($) {

    $(document).on('submit', '#hjm-capacity-form', function (e) {
        e.preventDefault();

        $.post(
            ajaxurl,
            $(this).serialize() + '&action=hjm_save_capacity',
            function () {
                location.reload();
            }
        );
    });

});

document.addEventListener('click', function (e) {

    const btn = e.target.closest('#hjm-apply-holidays, #hjm-apply-holidays-next');
    if (!btn) return;

    e.preventDefault();

    if (!confirm('Apply holiday closures for this year?')) return;

    const year = btn.getAttribute('data-year');

    jQuery.post(
        ajaxurl,
        {
            action: 'hjm_apply_holiday_presets',
            year: year,
            nonce: document.querySelector('#hjm-capacity-form input[name="nonce"]').value
        },
        function () {
            location.reload();
        }
    );
});

jQuery(function ($) {

    $('#hjm-bulk-close-form').on('submit', function (e) {
        e.preventDefault();

        if (!confirm('This will CLOSE all dates in the selected range. Continue?')) {
            return;
        }

        $.post(
            ajaxurl,
            $(this).serialize() + '&action=hjm_bulk_close_dates',
            function (resp) {
                if (resp.success) {
                    location.reload();
                } else {
                    alert(resp.data || 'Failed to close dates');
                }
            }
        );
    });

});

jQuery(function ($) {

    $('#hjm-apply-holidays').on('submit', function (e) {
        e.preventDefault();

        if (!confirm('Apply holiday closures for this year?')) return;

        $.post(
            ajaxurl,
            $(this).serialize() + '&action=hjm_apply_holiday_presets',
            function () {
                location.reload();
            }
        );
    });

});
