(function ($) {
    function syncTierRulesVisibility() {
        var model = $('[name="addon_price_model"]').val() || '';
        var isTiered = model === 'tiered_flat';

        $('.hjm-addon-tier-rules-row').toggle(isTiered);
        $('.hjm-addon-base-price-row').toggle(!isTiered);
    }

    $(document).on('change', '[name="addon_price_model"]', syncTierRulesVisibility);
    $(syncTierRulesVisibility);
})(jQuery);
