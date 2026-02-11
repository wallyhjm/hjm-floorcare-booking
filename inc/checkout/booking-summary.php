<?php
if ( ! defined('ABSPATH') ) exit;

add_action( 'woocommerce_checkout_before_customer_details', function () {

    $date     = WC()->session->get( 'floorcare_booking_date' );
    $time     = WC()->session->get( 'floorcare_booking_time' );
    $duration = WC()->session->get( 'floorcare_total_duration' );

    if ( ! $date || ! $time ) {
        return;
    }

    $hours = ceil( ( $duration / 60 ) * 2 ) / 2;

    ?>
    <div class="floorcare-booking-summary">

        <h3>Service Appointment</h3>
        <p class="floorcare-availability-message" style="display:none;"></p>
        <p><strong>Date:</strong> <?php echo esc_html( date( 'F j, Y', strtotime( $date ) ) ); ?></p>
        <p><strong>Time:</strong> <?php echo esc_html( $time ); ?></p>
        <p><strong>Estimated duration:</strong> <?php echo esc_html( $hours ); ?> hours</p>

        <label class="floorcare-edit-toggle">
            <input type="checkbox" id="floorcare-edit-booking">
            Edit appointment
        </label>

        <div id="floorcare-booking-editor" style="display:none;">
            <div class="floorcare-booking floorcare-booking-checkout">
                <?php require HJM_FLOORCARE_PATH . 'inc/booking/booking-ui.php'; ?>
            </div>
        </div>

    </div>
    <?php
});

add_action( 'wp_footer', function () {
    if ( ! is_checkout() ) return;
    ?>
    <script>
        (function ($) {

            $(document).on('change', '#floorcare-edit-booking', function () {
                $('#floorcare-booking-editor').toggle(this.checked);
            });

        })(jQuery);
    </script>
    <?php
});

