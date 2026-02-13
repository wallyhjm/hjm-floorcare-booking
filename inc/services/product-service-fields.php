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

function hjm_floorcare_is_addon_cart_item($cart_item)
{
    return !empty($cart_item['floorcare_is_addon_line']) && !empty($cart_item['floorcare_addon_id']);
}

function hjm_floorcare_sync_addon_cart_lines($cart)
{
    if (!($cart instanceof WC_Cart)) {
        return;
    }

    static $sync_in_progress = false;

    if ($sync_in_progress) {
        return;
    }

    $sync_in_progress = true;
    $did_change = false;

    try {
        $items = $cart->get_cart();
        $addon_children = [];

        foreach ($items as $cart_item_key => $cart_item) {
            if (!hjm_floorcare_is_addon_cart_item($cart_item)) {
                continue;
            }

            $parent_key = sanitize_text_field($cart_item['floorcare_parent_key'] ?? '');
            $addon_id = (int) ($cart_item['floorcare_addon_id'] ?? 0);

            if ($parent_key === '' || $addon_id <= 0) {
                $cart->remove_cart_item($cart_item_key);
                $did_change = true;
                continue;
            }

            if (!isset($items[$parent_key])) {
                $cart->remove_cart_item($cart_item_key);
                $did_change = true;
                continue;
            }

            if (!isset($addon_children[$parent_key])) {
                $addon_children[$parent_key] = [];
            }

            $addon_children[$parent_key][$addon_id] = $cart_item_key;

            $parent_qty = max(1, (int) ($items[$parent_key]['quantity'] ?? 1));
            $child_qty = max(1, (int) ($cart_item['quantity'] ?? 1));

            if ($child_qty !== $parent_qty) {
                $cart->set_quantity($cart_item_key, $parent_qty, false);
                $did_change = true;
            }
        }

        foreach ($items as $parent_key => $cart_item) {
            if (hjm_floorcare_is_addon_cart_item($cart_item)) {
                continue;
            }

            $addon_ids = array_values(array_unique(array_filter(array_map('intval', (array) ($cart_item['floorcare_addons'] ?? [])))));

            if (empty($addon_ids)) {
                continue;
            }

            $product_id = (int) ($cart_item['product_id'] ?? 0);
            $variation_id = (int) ($cart_item['variation_id'] ?? 0);
            $variation = isset($cart_item['variation']) && is_array($cart_item['variation']) ? $cart_item['variation'] : [];
            $qty = max(1, (int) ($cart_item['quantity'] ?? 1));

            foreach ($addon_ids as $addon_id) {
                if (isset($addon_children[$parent_key][$addon_id])) {
                    continue;
                }

                $cart->add_to_cart(
                    $product_id,
                    $qty,
                    $variation_id,
                    $variation,
                    [
                        'floorcare_is_addon_line' => true,
                        'floorcare_addon_id'      => (int) $addon_id,
                        'floorcare_parent_key'    => $parent_key,
                        'floorcare_addon_key'     => md5($parent_key . '|' . (int) $addon_id),
                    ]
                );
                $did_change = true;
            }

            if (isset($cart->cart_contents[$parent_key]['floorcare_addons'])) {
                unset($cart->cart_contents[$parent_key]['floorcare_addons']);
                $did_change = true;
            }
        }
    } finally {
        $sync_in_progress = false;
    }

    if ($did_change) {
        $cart->set_session();
    }
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
        $image_url = get_the_post_thumbnail_url($addon->ID, 'thumbnail');

        echo '<label class="floorcare-addon">';
        if ($image_url) {
            echo '<span class="floorcare-addon-image-wrap">';
            echo '<img class="floorcare-addon-image" src="' . esc_url($image_url) . '" alt="' . esc_attr($addon->post_title) . '" width="64" height="64" loading="lazy" decoding="async">';
            echo '</span> ';
        }

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

    if (!empty($cart_item_data['floorcare_is_addon_line'])) {
        return $cart_item_data;
    }

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

    if (!hjm_floorcare_is_addon_cart_item($cart_item)) {
        return $item_data;
    }

    $parent_key = sanitize_text_field($cart_item['floorcare_parent_key'] ?? '');
    $addon_id = (int) ($cart_item['floorcare_addon_id'] ?? 0);

    if ($addon_id > 0) {
        $item_data[] = [
            'name'  => 'Add-on',
            'value' => get_the_title($addon_id),
        ];
    }

    if ($parent_key !== '' && WC()->cart) {
        $parent_item = WC()->cart->get_cart_item($parent_key);
        if (!empty($parent_item['data']) && is_object($parent_item['data'])) {
            $item_data[] = [
                'name'  => 'For',
                'value' => $parent_item['data']->get_name(),
            ];
        }
    }

    return $item_data;
}, 10, 2);

add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values) {

    if (!hjm_floorcare_is_addon_cart_item($values)) {
        return;
    }

    $addon_id = (int) ($values['floorcare_addon_id'] ?? 0);
    if ($addon_id <= 0) {
        return;
    }

    $addon_name = get_the_title($addon_id);
    if ($addon_name !== '') {
        $item->set_name($addon_name);
    }

    $item->add_meta_data('_floorcare_addon_id', $addon_id, true);
}, 10, 3);

add_action('woocommerce_before_calculate_totals', function ($cart) {
    hjm_floorcare_sync_addon_cart_lines($cart);
}, 5);

add_filter('woocommerce_cart_item_name', function ($name, $cart_item) {
    if (!hjm_floorcare_is_addon_cart_item($cart_item)) {
        return $name;
    }

    $addon_name = get_the_title((int) ($cart_item['floorcare_addon_id'] ?? 0));
    if ($addon_name === '') {
        return esc_html__('Service Add-on', 'hjm-floorcare');
    }

    return esc_html($addon_name);
}, 10, 2);

add_filter('woocommerce_cart_item_quantity', function ($product_quantity, $cart_item_key, $cart_item) {
    if (!hjm_floorcare_is_addon_cart_item($cart_item)) {
        return $product_quantity;
    }

    $qty = max(1, (int) ($cart_item['quantity'] ?? 1));

    return sprintf(
        '<span class="quantity">%1$s</span><input type="hidden" name="cart[%2$s][qty]" value="%3$d">',
        esc_html($qty),
        esc_attr($cart_item_key),
        $qty
    );
}, 10, 3);
