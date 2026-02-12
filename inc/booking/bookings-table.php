<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function hjm_floorcare_create_bookings_table() {

    global $wpdb;

    $table = $wpdb->prefix . 'hjm_floorcare_bookings';
    $charset = $wpdb->get_charset_collate();

    $sql = "
        CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NOT NULL,
            booking_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            duration_minutes INT NOT NULL,
            service_address TEXT NOT NULL,
            trip_miles DECIMAL(6,2) NULL,
            trip_fee DECIMAL(10,2) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_date (booking_date),
            KEY order_id (order_id),
            KEY status (status)
        ) {$charset};
    ";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

