<?php
if ( ! defined('ABSPATH') ) exit;

add_action('admin_menu', function () {
    add_menu_page(
        'Floorcare Capacity',
        'Floorcare Capacity',
        'manage_woocommerce',
        'hjm-floorcare-capacity',
        'hjm_floorcare_render_capacity_page',
        'dashicons-calendar-alt',
        56
    );
});

add_action('admin_enqueue_scripts', function () {

    wp_enqueue_script(
        'hjm-capacity-modal',
        HJM_FLOORCARE_URL . 'inc/admin/capacity/capacity-modal.js',
        ['jquery'],
        time(),
        true
    );

    wp_enqueue_style(
        'hjm-modal-css',
        HJM_FLOORCARE_URL . 'inc/admin/capacity/capacity-modal.css',
        [],
        '1.0.0'
    );
});

require_once HJM_FLOORCARE_PATH . 'inc/admin/capacity/holiday-presets.php';
require_once HJM_FLOORCARE_PATH . 'inc/admin/capacity/capacity-actions.php';


function hjm_floorcare_render_capacity_page() {

    require_once __DIR__ . '/capacity-list-table.php';

    $table = new HJM_Floorcare_Capacity_List_Table();
    $table->prepare_items();
    ?>
    <div class="wrap">
        <h1>Daily Capacity</h1>

    <div style="margin:15px 0;">
        <button
                class="button button-secondary"
                id="hjm-apply-holidays"
                data-year="<?php echo esc_attr( date('Y') ); ?>"
        >
            Apply Holidays (<?php echo esc_html( date('Y') ); ?>)
        </button>

        <button
                class="button"
                id="hjm-apply-holidays-next"
                data-year="<?php echo esc_attr( date('Y') + 1 ); ?>"
        >
            Apply Holidays (<?php echo esc_html( date('Y') + 1 ); ?>)
        </button>
    </div>

    <hr>

    <h2>Bulk Close Date Range</h2>

    <form id="hjm-bulk-close-form">
        <?php wp_nonce_field( 'hjm_bulk_close', 'nonce' ); ?>

        <p>
            <label>
                Start date<br>
                <input type="date" name="start_date" required>
            </label>
        </p>

        <p>
            <label>
                End date<br>
                <input type="date" name="end_date" required>
            </label>
        </p>

        <p>
            <button type="submit" class="button button-secondary">
                Close Date Range
            </button>
        </p>
    </form>

    <h2>Holiday Presets</h2>

    <form id="hjm-apply-holidays">
        <?php wp_nonce_field('hjm_capacity_save', 'nonce'); ?>

        <p>
            <label>
                Year<br>
                <input type="number" name="year" value="<?php echo date('Y'); ?>">
            </label>
        </p>

        <p>
            <button class="button">
                Apply Federal Holidays
            </button>
        </p>
    </form>

        <form method="post">
            <?php $table->display(); ?>
        </form>

        <!-- Modal container -->
    <div id="hjm-capacity-modal" style="display:none;">
        <div class="hjm-capacity-modal-inner">
            <h2>Edit Daily Capacity</h2>
            <form id="hjm-capacity-form">
                <?php wp_nonce_field('hjm_capacity_save', 'nonce'); ?>
                <input type="hidden" name="service_date" id="hjm-capacity-date">
                <p>
                    <label>
                        <input type="checkbox" name="is_closed" id="hjm-capacity-closed">
                        Closed
                    </label>
                </p>
                <p>
                    <label for="hjm-capacity-minutes">
                        Available minutes
                    </label><br>
                    <input
                        type="number"
                        name="total_minutes"
                        id="hjm-capacity-minutes"
                        min="0"
                        step="15"
                    >
                </p>
                <p class="submit">
                    <button type="submit" class="button button-primary">
                        Save
                    </button>
                    <button type="button" class="button hjm-capacity-cancel">
                        Cancel
                    </button>
                </p>
            </form>

        </div>
    </div>
    <?php
}

