<?php
// Safety check
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Defaults for capacity seeding (define here so activation never depends on other includes)
if ( ! defined('HJM_FLOORCARE_SEED_DAYS') ) {
    define('HJM_FLOORCARE_SEED_DAYS', 180);
}

if ( ! defined('HJM_FLOORCARE_DEFAULT_WEEKDAY_MINUTES') ) {
    define('HJM_FLOORCARE_DEFAULT_WEEKDAY_MINUTES', 480 * 2); // 2 crews x 8 hrs
}


/**
 * Plugin activation entry point
 */
function hjm_floorcare_activate() {
    hjm_floorcare_create_distance_cache_table();
    hjm_floorcare_create_daily_capacity_table();
    hjm_floorcare_seed_capacity_table();
}

/**
 * Distance cache table
 */
function hjm_floorcare_create_distance_cache_table() {
    global $wpdb;

    $table   = $wpdb->prefix . 'hjm_floorcare_distance_cache';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        origin_hash CHAR(64) NOT NULL,
        destination_hash CHAR(64) NOT NULL,
        origin TEXT NOT NULL,
        destination TEXT NOT NULL,
        miles DECIMAL(6,2) NOT NULL,
        updated DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY route (origin_hash, destination_hash)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

function hjm_floorcare_create_daily_capacity_table() {
    global $wpdb;

    if ( HJM_FLOORCARE_SEED_DAYS <= 0 ) {
        return;
    }

    $table = $wpdb->prefix . 'hjm_floorcare_daily_capacity';
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "
        CREATE TABLE $table (
            service_date DATE NOT NULL,
            total_minutes INT NOT NULL,
            is_closed TINYINT(1) NOT NULL DEFAULT 0,
            is_override TINYINT(1) NOT NULL DEFAULT 0,
            notes VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (service_date)
        ) $charset_collate;
    ";

    dbDelta( $sql );
}

function hjm_floorcare_seed_capacity_table() {
    global $wpdb;

    $table = $wpdb->prefix . 'hjm_floorcare_daily_capacity';
    $today = new DateTimeImmutable( 'today', wp_timezone() );

    for ( $i = 0; $i < HJM_FLOORCARE_SEED_DAYS; $i++ ) {

        $date = $today->modify( "+$i days" );
        $date_str = $date->format( 'Y-m-d' );
        $weekday = (int) $date->format( 'N' ); // 1 (Mon) -> 7 (Sun)

        // Skip if already exists (preserve overrides)
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT service_date FROM $table WHERE service_date = %s",
                $date_str
            )
        );

        if ( $exists ) {
            continue;
        }

        $is_closed = ( $weekday >= 6 ); // Sat/Sun
        $minutes   = $is_closed ? 0 : HJM_FLOORCARE_DEFAULT_WEEKDAY_MINUTES;

        $wpdb->insert(
            $table,
            [
                'service_date'  => $date_str,
                'total_minutes' => $minutes,
                'is_closed'     => $is_closed ? 1 : 0,
                'is_override'   => 0,
                'notes'         => null,
                'created_at'    => current_time( 'mysql' ),
                'updated_at'    => current_time( 'mysql' ),
            ],
            [ '%s', '%d', '%d', '%d', '%s', '%s', '%s' ]
        );
    }
}
