<?php

/**
 * Floorcare Add-ons CPT
 */
add_action('after_setup_theme', function () {
    add_theme_support('post-thumbnails', ['floorcare_addon']);
});

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
        'supports'      => ['title', 'thumbnail'],
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
    $price_model = get_post_meta($post->ID, '_addon_price_model', true);
    $price_tiers = get_post_meta($post->ID, '_addon_price_tiers', true);

    if (!in_array($price_model, ['flat', 'per_unit', 'tiered_flat'], true)) {
        $price_model = $per_unit === 'yes' ? 'per_unit' : 'flat';
    }

    if (!is_array($price_tiers)) {
        $price_tiers = [];
    }

    $tiers_text = '';
    foreach ($price_tiers as $tier) {
        $min = isset($tier['min']) ? (int) $tier['min'] : 0;
        $max = isset($tier['max']) && $tier['max'] !== '' ? (int) $tier['max'] : '';
        $tier_price = isset($tier['price']) ? (float) $tier['price'] : 0;
        $tiers_text .= $min . ',' . ($max === '' ? '*' : $max) . ',' . $tier_price . "\n";
    }

    wp_nonce_field('floorcare_addon_save', 'floorcare_addon_nonce');
    ?>

    <p>
        <label><strong>Price increase ($)</strong></label><br>
        <input type="number" step="0.01" name="addon_price" value="<?php echo esc_attr($price); ?>">
    </p>

    <p>
        <label><strong>Price model</strong></label><br>
        <select name="addon_price_model">
            <option value="flat" <?php selected($price_model, 'flat'); ?>>Flat (once per line item)</option>
            <option value="per_unit" <?php selected($price_model, 'per_unit'); ?>>Per unit (per room/item/rug)</option>
            <option value="tiered_flat" <?php selected($price_model, 'tiered_flat'); ?>>Tiered flat (qty ranges)</option>
        </select>
    </p>

    <p class="hjm-addon-tier-rules-row" <?php if ($price_model !== 'tiered_flat') { echo 'style="display:none;"'; } ?>>
        <label><strong>Tiered pricing rules</strong></label><br>
        <textarea name="addon_price_tiers" rows="5" cols="60" placeholder="1,5,30&#10;6,*,60"><?php echo esc_textarea(trim($tiers_text)); ?></textarea><br>
        <small>One tier per line: <code>min,max,price</code>. Use <code>*</code> for no max. Example: <code>1,5,30</code> then <code>6,*,60</code>.</small>
    </p>

    <p>
        <label><strong>Duration increase (minutes)</strong></label><br>
        <input type="number" step="5" name="addon_duration" value="<?php echo esc_attr($duration); ?>">
    </p>

    <p>
        <strong>Applies to services:</strong><br>

        <?php
        $types = [
            'all'        => 'All Services',
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

    <p>
        <small>Use the Featured Image panel to upload/select an image for this add-on.</small>
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

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $price_model = sanitize_text_field($_POST['addon_price_model'] ?? 'flat');
    if (!in_array($price_model, ['flat', 'per_unit', 'tiered_flat'], true)) {
        $price_model = 'flat';
    }

    update_post_meta($post_id, '_addon_price', floatval($_POST['addon_price'] ?? 0));
    update_post_meta($post_id, '_addon_duration', intval($_POST['addon_duration'] ?? 0));
    update_post_meta($post_id, '_addon_price_model', $price_model);

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

    $raw_tiers = sanitize_textarea_field($_POST['addon_price_tiers'] ?? '');
    $lines = preg_split('/\r\n|\r|\n/', $raw_tiers);
    $tiers = [];

    foreach ((array) $lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $parts = array_map('trim', explode(',', $line));
        if (count($parts) !== 3) {
            continue;
        }

        $min = max(1, (int) $parts[0]);
        $max = $parts[1] === '*' ? '' : max($min, (int) $parts[1]);
        $tier_price = (float) $parts[2];

        if ($tier_price < 0) {
            $tier_price = 0;
        }

        $tiers[] = [
            'min' => $min,
            'max' => $max,
            'price' => $tier_price,
        ];
    }

    if (!empty($tiers)) {
        usort($tiers, static function ($a, $b) {
            return (int) $a['min'] <=> (int) $b['min'];
        });
    }

    update_post_meta($post_id, '_addon_price_tiers', $tiers);
});

add_action('admin_enqueue_scripts', function ($hook) {

    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }

    if (!function_exists('get_current_screen')) {
        return;
    }

    $screen = get_current_screen();

    if (!$screen || $screen->post_type !== 'floorcare_addon') {
        return;
    }

    $script_path = HJM_FLOORCARE_PATH . 'inc/assets/js/addon-admin.js';
    $script_ver  = file_exists($script_path) ? (string) filemtime($script_path) : '1.0.0';

    wp_enqueue_script(
        'hjm-floorcare-addon-admin',
        HJM_FLOORCARE_URL . 'inc/assets/js/addon-admin.js',
        ['jquery'],
        $script_ver,
        true
    );
});


