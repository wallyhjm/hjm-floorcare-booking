<?php
/**
 * Cart booking date/time UI + AJAX persistence
 */

if ( ! defined('ABSPATH') ) exit;

/**
 * Render booking fields on cart page (below coupon area).
 * For classic cart: coupon is inside the cart form; this hook is reliably above totals.
 */
add_action('woocommerce_after_cart_table', function () {


    $booking_date = WC()->session->get('floorcare_booking_date', '');
    $booking_time = WC()->session->get('floorcare_booking_time', '');

    // If duration is 0, no reason to show booking fields yet.
    $duration = (int) WC()->session->get('floorcare_total_duration', 0);

    ?>
    <div class="floorcare-booking" style="margin: 20px 0; padding: 15px; border: 1px solid #ddd;">
        <h3>Schedule Your Service</h3>

        <?php if ( $duration <= 0 ) : ?>
            <p>Please add services to your cart to select a time.</p>
        <?php else : ?>

            <p>
                <label for="floorcare_booking_date"><strong>Service Date</strong></label><br>
                <input
                    type="date"
                    id="floorcare_booking_date"
                    name="floorcare_booking_date"
                    value="<?php echo esc_attr($booking_date); ?>"
                    class="input-text"
                    min="<?php echo esc_attr( date('Y-m-d', strtotime('+1 day')) ); ?>"
                >
            </p>

            <p>
                <label for="floorcare_booking_time"><strong>Start Time</strong></label><br>
                <select id="floorcare_booking_time" name="floorcare_booking_time" class="input-text">
                    <option value="">-- Select a time --</option>
                    <?php if ( $booking_time ) : ?>
                        <option value="<?php echo esc_attr($booking_time); ?>" selected>
                            <?php echo esc_html($booking_time); ?>
                        </option>
                    <?php endif; ?>
                </select>
            </p>
            <p class="floorcare-availability-message" style="display:none;"></p>
            <p class="floorcare-note" style="margin-top: 10px;">
                Available start times are based on your selected services and estimated duration.
            </p>

        <?php endif; ?>
    </div>
    <?php
});

/**
 * Fetch slots for a date (uses availability engine + cart duration)
 */
add_action('wp_ajax_floorcare_get_slots', 'hjm_floorcare_ajax_get_slots');
add_action('wp_ajax_nopriv_floorcare_get_slots', 'hjm_floorcare_ajax_get_slots');

function hjm_floorcare_ajax_get_slots() {

    check_ajax_referer( 'hjm_floorcare_ajax', 'nonce' );

    if ( ! function_exists('hjm_floorcare_get_available_slots') ) {
        wp_send_json_error(['message' => 'Availability engine not loaded.'], 500);
    }

    $date = sanitize_text_field($_POST['date'] ?? '');

    if ( empty($date) ) {
        wp_send_json_success([]);
    }

    $slots = hjm_floorcare_get_available_slots( $date );

    wp_send_json_success($slots);
}

/**
 * Persist booking selection into session
 */
add_action('wp_ajax_floorcare_set_booking', 'hjm_floorcare_ajax_set_booking');
add_action('wp_ajax_nopriv_floorcare_set_booking', 'hjm_floorcare_ajax_set_booking');

function hjm_floorcare_ajax_set_booking() {

    check_ajax_referer( 'hjm_floorcare_ajax', 'nonce' );

    $date = sanitize_text_field($_POST['date'] ?? '');
    $time = sanitize_text_field($_POST['time'] ?? '');

    // Allow clearing
    if ( empty($date) || empty($time) ) {
        WC()->session->__unset('floorcare_booking_date');
        WC()->session->__unset('floorcare_booking_time');
        wp_send_json_success();
    }

    // Validate against availability engine before saving
    $slots = hjm_floorcare_get_available_slots( $date );

    if ( ! in_array( $time, $slots, true ) ) {
        wp_send_json_error(['message' => 'Selected time is no longer available.'], 409);
    }

    WC()->session->set('floorcare_booking_date', $date);
    WC()->session->set('floorcare_booking_time', $time);

    wp_send_json_success();
}

