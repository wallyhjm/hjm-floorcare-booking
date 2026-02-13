(function ($) {
    function syncTierRulesVisibility() {
        var model = $('[name="addon_price_model"]').val() || '';
        $('.hjm-addon-tier-rules-row').toggle(model === 'tiered_flat');
    }

    $(document).on('change', '[name="addon_price_model"]', syncTierRulesVisibility);
    $(syncTierRulesVisibility);
})(jQuery);
