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

function hjm_floorcare_get_addons_for_service($service_type)
{
    if (!$service_type) return [];

    $addons = get_posts([
        'post_type'  => 'floorcare_addon',
        'numberposts'=> -1,
        'meta_query' => [[
            'key'     => '_addon_applies_to',
            'value'   => $service_type,
            'compare' => 'LIKE'
        ]]
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

        $price    = get_post_meta($addon->ID, '_addon_price', true);
        $duration = get_post_meta($addon->ID, '_addon_duration', true);
        $per_unit = get_post_meta($addon->ID, '_addon_per_unit', true);

        echo '<label class="floorcare-addon">';
        echo '<input type="checkbox" name="floorcare_addons[]" value="' . esc_attr($addon->ID) . '"> ';
        echo esc_html($addon->post_title);

        if ($price) {
            echo ' (+$' . number_format($price, 2) . ')';
        }

        if ($per_unit === 'yes') {
            echo ' per item';
        }

        echo '</label><br>';
    }

    echo '</div>';
});

add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id) {

    if (!empty($_POST['floorcare_addons'])) {
        $cart_item_data['floorcare_addons'] = array_map(
            'intval',
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
