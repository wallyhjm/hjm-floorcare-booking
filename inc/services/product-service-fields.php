<?php

/**
 * Floorcare service product fields
 */
add_action('woocommerce_product_options_general_product_data', function () {

    echo '<div class="options_group">';
    echo '<h4>Floor Care Service</h4>';

    // Service type
    woocommerce_wp_select([
        'id' => '_floorcare_service_type',
        'label' => 'Service type',
        'options' => [
            ''           => '— Select —',
            'carpet'     => 'Carpet Cleaning',
            'rug'        => 'Area Rug',
            'upholstery' => 'Furniture / Upholstery',
        ],
    ]);

    // Unit label
    woocommerce_wp_text_input([
        'id'          => '_floorcare_unit_label',
        'label'       => 'Unit label',
        'placeholder' => 'room / item / rug',
        'desc_tip'    => true,
        'description' => 'How this service is counted.',
    ]);

    // Base duration
    woocommerce_wp_text_input([
        'id'          => '_floorcare_base_duration',
        'label'       => 'Base duration (minutes)',
        'type'        => 'number',
        'custom_attributes' => [
            'min'  => '0',
            'step' => '5',
        ],
        'desc_tip'    => true,
        'description' => 'Estimated time per unit.',
    ]);

    echo '</div>';
});

add_action('woocommerce_admin_process_product_object', function ($product) {

    $product->update_meta_data(
        '_floorcare_service_type',
        sanitize_text_field($_POST['_floorcare_service_type'] ?? '')
    );

    $product->update_meta_data(
        '_floorcare_unit_label',
        sanitize_text_field($_POST['_floorcare_unit_label'] ?? '')
    );

    $product->update_meta_data(
        '_floorcare_base_duration',
        intval($_POST['_floorcare_base_duration'] ?? 0)
    );

});

/**
 * Variation duration multiplier
 */
add_action('woocommerce_variation_options_pricing', function ($loop, $variation_data, $variation) {

    woocommerce_wp_text_input([
        'id'            => "_floorcare_duration_multiplier[$loop]",
        'label'         => 'Duration multiplier',
        'type'          => 'number',
        'wrapper_class' => 'form-row form-row-full',
        'custom_attributes' => [
            'step' => '0.25',
            'min'  => '0',
        ],
        'value' => get_post_meta($variation->ID, '_floorcare_duration_multiplier', true),
        'description' => 'Multiplies base service duration (e.g. 1.5 = 150%)',
    ]);
}, 10, 3);

add_action('woocommerce_save_product_variation', function ($variation_id, $i) {

    if (isset($_POST['_floorcare_duration_multiplier'][$i])) {
        update_post_meta(
            $variation_id,
            '_floorcare_duration_multiplier',
            floatval($_POST['_floorcare_duration_multiplier'][$i])
        );
    }
}, 10, 2);

function hjm_floorcare_get_product_service_type($product)
{
    if (!$product) {
        return '';
    }

    $service_type = (string) $product->get_meta('_floorcare_service_type');

    if ($service_type !== '') {
        return $service_type;
    }

    if ($product->is_type('variation')) {
        $parent_id = (int) $product->get_parent_id();
        if ($parent_id > 0) {
            return (string) get_post_meta($parent_id, '_floorcare_service_type', true);
        }
    }

    return '';
}

function hjm_floorcare_addon_applies_to_service($addon_id, $service_type)
{
    $applies = (array) get_post_meta((int) $addon_id, '_addon_applies_to', true);

    if (empty($applies)) {
        return false;
    }

    return in_array('all', $applies, true) || in_array($service_type, $applies, true);
}

function hjm_floorcare_get_valid_addons_for_product($product, $addon_ids)
{
    $service_type = hjm_floorcare_get_product_service_type($product);

    if (!$service_type) {
        return [];
    }

    $validated = [];

    foreach ((array) $addon_ids as $addon_id) {
        $addon_id = (int) $addon_id;

        if ($addon_id <= 0 || get_post_type($addon_id) !== 'floorcare_addon') {
            continue;
        }

        if (get_post_status($addon_id) !== 'publish') {
            continue;
        }

        if (!hjm_floorcare_addon_applies_to_service($addon_id, $service_type)) {
            continue;
        }

        $validated[] = $addon_id;
    }

    return array_values(array_unique($validated));
}

function hjm_floorcare_get_addon_price_for_qty($addon_id, $qty)
{
    $addon_id = (int) $addon_id;
    $qty = max(1, (int) $qty);

    $base_price  = (float) get_post_meta($addon_id, '_addon_price', true);
    $price_model = (string) get_post_meta($addon_id, '_addon_price_model', true);
    $tiers       = get_post_meta($addon_id, '_addon_price_tiers', true);

    if (!in_array($price_model, ['flat', 'per_unit', 'tiered_flat'], true)) {
        $legacy_per_unit = get_post_meta($addon_id, '_addon_per_unit', true) === 'yes';
        $price_model = $legacy_per_unit ? 'per_unit' : 'flat';
    }

    if ($price_model === 'per_unit') {
        return $base_price * $qty;
    }

    if ($price_model === 'tiered_flat' && is_array($tiers) && !empty($tiers)) {
        foreach ($tiers as $tier) {
            $min = isset($tier['min']) ? (int) $tier['min'] : 1;
            $max = isset($tier['max']) && $tier['max'] !== '' ? (int) $tier['max'] : 0;
            $price = isset($tier['price']) ? (float) $tier['price'] : 0.0;

            if ($qty < $min) {
                continue;
            }

            if ($max > 0 && $qty > $max) {
                continue;
            }

            return max(0, $price);
        }
    }

    return max(0, $base_price);
}

function hjm_floorcare_calculate_addons_total_for_cart_item($cart_item)
{
    if (empty($cart_item['floorcare_addons'])) {
        return 0.0;
    }

    $qty = max(1, (int) ($cart_item['quantity'] ?? 1));
    $total = 0.0;

    foreach ((array) $cart_item['floorcare_addons'] as $addon_id) {
        $total += hjm_floorcare_get_addon_price_for_qty((int) $addon_id, $qty);
    }

    return (float) $total;
}

function hjm_floorcare_get_addon_price_label($addon_id)
{
    $addon_id = (int) $addon_id;
    $base_price  = (float) get_post_meta($addon_id, '_addon_price', true);
    $price_model = (string) get_post_meta($addon_id, '_addon_price_model', true);
    $tiers       = get_post_meta($addon_id, '_addon_price_tiers', true);

    if (!in_array($price_model, ['flat', 'per_unit', 'tiered_flat'], true)) {
        $legacy_per_unit = get_post_meta($addon_id, '_addon_per_unit', true) === 'yes';
        $price_model = $legacy_per_unit ? 'per_unit' : 'flat';
    }

    if ($price_model === 'per_unit') {
        return sprintf('$%s per item/room', number_format($base_price, 2));
    }

    if ($price_model === 'tiered_flat' && is_array($tiers) && !empty($tiers)) {
        $parts = [];

        foreach ($tiers as $tier) {
            $min = isset($tier['min']) ? (int) $tier['min'] : 1;
            $max = isset($tier['max']) && $tier['max'] !== '' ? (int) $tier['max'] : 0;
            $price = isset($tier['price']) ? (float) $tier['price'] : 0.0;

            if ($max > 0) {
                $parts[] = sprintf('$%s (%d-%d)', number_format($price, 2), $min, $max);
            } else {
                $parts[] = sprintf('$%s (%d+)', number_format($price, 2), $min);
            }
        }

        return implode(', ', $parts);
    }

    return sprintf('$%s', number_format($base_price, 2));
}

function hjm_floorcare_get_addons_for_service($service_type)
{
    if (!$service_type) return [];

    $addons = get_posts([
        'post_type'  => 'floorcare_addon',
        'post_status'=> 'publish',
        'numberposts'=> -1,
        'meta_query' => [
            'relation' => 'OR',
            [
                'key'     => '_addon_applies_to',
                'value'   => 'all',
                'compare' => 'LIKE'
            ],
            [
                'key'     => '_addon_applies_to',
                'value'   => $service_type,
                'compare' => 'LIKE'
            ],
        ]
    ]);

    return $addons;
}

add_action('woocommerce_before_add_to_cart_button', function () {

    global $product;

    $service_type = $product->get_meta('_floorcare_service_type');

    if (!$service_type) {
        return;
    }

    $addons = hjm_floorcare_get_addons_for_service($service_type);

    if (!$addons) {
        return;
    }

    echo '<div class="floorcare-addons">';
    echo '<h3>Optional Add-ons</h3>';

    foreach ($addons as $addon) {

        $price_label = hjm_floorcare_get_addon_price_label($addon->ID);
        echo '<label class="floorcare-addon">';
        echo '<input type="checkbox" name="floorcare_addons[]" value="' . esc_attr($addon->ID) . '"> ';
        echo esc_html($addon->post_title);

        if ($price_label !== '') {
            echo ' (+' . esc_html($price_label) . ')';
        }

        echo '</label><br>';
    }

    echo '</div>';
});

add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id) {

    if (!empty($_POST['floorcare_addons'])) {
        $product = wc_get_product($product_id);
        if (isset($_POST['variation_id']) && (int) $_POST['variation_id'] > 0) {
            $variation_product = wc_get_product((int) $_POST['variation_id']);
            if ($variation_product) {
                $product = $variation_product;
            }
        }

        $cart_item_data['floorcare_addons'] = hjm_floorcare_get_valid_addons_for_product(
            $product,
            (array) $_POST['floorcare_addons']
        );
    }

    return $cart_item_data;

}, 10, 2);

add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {

    if (empty($cart_item['floorcare_addons'])) {
        return $item_data;
    }

    foreach ($cart_item['floorcare_addons'] as $addon_id) {
        $item_data[] = [
            'name'  => 'Add-on',
            'value' => get_the_title($addon_id),
        ];
    }

    return $item_data;
}, 10, 2);

add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values) {

    if (empty($values['floorcare_addons'])) {
        return;
    }

    $qty = max(1, (int) ($values['quantity'] ?? 1));
    $labels = [];
    $total = 0.0;

    foreach ((array) $values['floorcare_addons'] as $addon_id) {
        $addon_id = (int) $addon_id;
        if ($addon_id <= 0) {
            continue;
        }

        $name = get_the_title($addon_id);
        if ($name === '') {
            continue;
        }

        $price = hjm_floorcare_get_addon_price_for_qty($addon_id, $qty);
        $total += $price;

        $labels[] = sprintf('%s ($%s)', $name, wc_format_decimal($price, 2));
    }

    if (!empty($labels)) {
        $item->add_meta_data('Add-ons', implode(', ', $labels), true);
        $item->add_meta_data('_floorcare_addons', wp_json_encode(array_values($values['floorcare_addons'])), true);
    }

    if ($total > 0) {
        $item->add_meta_data('_floorcare_addons_total', wc_format_decimal($total, 2), true);
    }
}, 10, 3);
