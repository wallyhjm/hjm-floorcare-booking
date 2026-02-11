<?php
if ( ! defined('ABSPATH') ) exit;

$date = WC()->session->get('floorcare_booking_date');
$time = WC()->session->get('floorcare_booking_time');
?>

<div class="floorcare-booking">

    <p>
        <label for="floorcare_booking_date">Service date</label><br>
        <input
                type="date"
                id="floorcare_booking_date"
                name="floorcare_booking_date"
                value="<?php echo esc_attr( $date ); ?>"
                class="input-text"
        >
    </p>

    <p>
        <label for="floorcare_booking_time">Service time</label><br>
        <select
                id="floorcare_booking_time"
                name="floorcare_booking_time"
                class="input-text"
        >
            <option value="">— Select a time —</option>

            <?php if ( $time ) : ?>
                <option value="<?php echo esc_attr($time); ?>" selected>
                    <?php echo esc_html($time); ?>
                </option>
            <?php endif; ?>
        </select>
    </p>

    <!-- REQUIRED for availability messaging -->
    <p class="floorcare-availability-message" style="display:none;"></p>

</div>
