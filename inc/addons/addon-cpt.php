<?php

/**
 * Floorcare Add-ons CPT
 */
add_action('init', function () {

    register_post_type('floorcare_addon', [
        'labels' => [
            'name'          => 'Service Add-ons',
            'singular_name' => 'Service Add-on',
            'add_new_item'  => 'Add New Add-on',
            'edit_item'     => 'Edit Add-on',
        ],
        'public'        => false,
        'show_ui'       => true,
        'menu_position' => 26,
        'menu_icon'     => 'dashicons-plus-alt',
        'supports'      => ['title'],
    ]);

});

add_action('add_meta_boxes', function () {

    add_meta_box(
        'floorcare_addon_details',
        'Add-on Details',
        'hjm_floorcare_addon_meta_box',
        'floorcare_addon',
        'normal',
        'default'
    );

});

function hjm_floorcare_addon_meta_box($post) {

    $price     = get_post_meta($post->ID, '_addon_price', true);
    $duration  = get_post_meta($post->ID, '_addon_duration', true);
    $applies   = (array) get_post_meta($post->ID, '_addon_applies_to', true);
    $per_unit  = get_post_meta($post->ID, '_addon_per_unit', true);

    wp_nonce_field('floorcare_addon_save', 'floorcare_addon_nonce');
    ?>

    <p>
        <label><strong>Price increase ($)</strong></label><br>
        <input type="number" step="0.01" name="addon_price" value="<?php echo esc_attr($price); ?>">
    </p>

    <p>
        <label><strong>Duration increase (minutes)</strong></label><br>
        <input type="number" step="5" name="addon_duration" value="<?php echo esc_attr($duration); ?>">
    </p>

    <p>
        <strong>Applies to services:</strong><br>

        <?php
        $types = [
            'carpet'     => 'Carpet Cleaning',
            'rug'        => 'Area Rugs',
            'upholstery' => 'Furniture / Upholstery',
        ];

        foreach ($types as $key => $label) :
            ?>
            <label>
                <input type="checkbox"
                       name="addon_applies_to[]"
                       value="<?php echo esc_attr($key); ?>"
                    <?php checked(in_array($key, $applies)); ?>>
                <?php echo esc_html($label); ?>
            </label><br>
        <?php endforeach; ?>
    </p>

    <p>
        <label>
            <input type="checkbox" name="addon_per_unit" value="yes" <?php checked($per_unit, 'yes'); ?>>
            Applies per unit (per room / item / rug)
        </label>
    </p>

    <?php
}

add_action('save_post_floorcare_addon', function ($post_id) {

    if (
        !isset($_POST['floorcare_addon_nonce']) ||
        !wp_verify_nonce($_POST['floorcare_addon_nonce'], 'floorcare_addon_save')
    ) {
        return;
    }

    update_post_meta($post_id, '_addon_price', floatval($_POST['addon_price'] ?? 0));
    update_post_meta($post_id, '_addon_duration', intval($_POST['addon_duration'] ?? 0));

    update_post_meta(
        $post_id,
        '_addon_applies_to',
        array_map('sanitize_text_field', $_POST['addon_applies_to'] ?? [])
    );

    update_post_meta(
        $post_id,
        '_addon_per_unit',
        isset($_POST['addon_per_unit']) ? 'yes' : 'no'
    );
});


