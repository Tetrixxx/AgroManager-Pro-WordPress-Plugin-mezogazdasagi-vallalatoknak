<?php
/**
 * AgroManager Pro – Database Activator
 *
 * Creates all required database tables on plugin activation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AgroManager_Activator {

    /**
     * Run on plugin activation.
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        flush_rewrite_rules();
    }

    /**
     * Create all database tables.
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Parcels table
        $table_parcels = $wpdb->prefix . 'agro_parcels';
        $sql_parcels = "CREATE TABLE $table_parcels (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            size_ha decimal(10,2) NOT NULL DEFAULT 0,
            location varchar(255) DEFAULT '',
            gps_lat decimal(10,7) DEFAULT NULL,
            gps_lng decimal(10,7) DEFAULT NULL,
            soil_quality tinyint(2) DEFAULT 5,
            cultivation_type varchar(100) DEFAULT '',
            status varchar(50) DEFAULT 'active',
            notes text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta( $sql_parcels );

        // Crops table
        $table_crops = $wpdb->prefix . 'agro_crops';
        $sql_crops = "CREATE TABLE $table_crops (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            parcel_id bigint(20) unsigned DEFAULT NULL,
            crop_name varchar(255) NOT NULL,
            sowing_date date DEFAULT NULL,
            expected_harvest date DEFAULT NULL,
            sown_area_ha decimal(10,2) DEFAULT 0,
            expected_yield decimal(10,2) DEFAULT 0,
            actual_yield decimal(10,2) DEFAULT 0,
            status varchar(50) DEFAULT 'planned',
            notes text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY parcel_id (parcel_id)
        ) $charset_collate;";
        dbDelta( $sql_crops );

        // Machines table
        $table_machines = $wpdb->prefix . 'agro_machines';
        $sql_machines = "CREATE TABLE $table_machines (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            type varchar(100) DEFAULT '',
            manufacturer varchar(255) DEFAULT '',
            license_plate varchar(50) DEFAULT '',
            operating_hours int(11) DEFAULT 0,
            condition_status varchar(50) DEFAULT 'operational',
            last_service date DEFAULT NULL,
            next_service date DEFAULT NULL,
            notes text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta( $sql_machines );

        // Finances table
        $table_finances = $wpdb->prefix . 'agro_finances';
        $sql_finances = "CREATE TABLE $table_finances (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            type varchar(20) NOT NULL DEFAULT 'expense',
            category varchar(100) DEFAULT '',
            amount decimal(12,2) NOT NULL DEFAULT 0,
            date date NOT NULL,
            parcel_id bigint(20) unsigned DEFAULT NULL,
            crop_id bigint(20) unsigned DEFAULT NULL,
            notes text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY parcel_id (parcel_id),
            KEY crop_id (crop_id),
            KEY type_date (type, date)
        ) $charset_collate;";
        dbDelta( $sql_finances );

        // Workers table
        $table_workers = $wpdb->prefix . 'agro_workers';
        $sql_workers = "CREATE TABLE $table_workers (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            position varchar(255) DEFAULT '',
            phone varchar(50) DEFAULT '',
            email varchar(255) DEFAULT '',
            status varchar(50) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta( $sql_workers );

        // Work logs table
        $table_work_logs = $wpdb->prefix . 'agro_work_logs';
        $sql_work_logs = "CREATE TABLE $table_work_logs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            worker_id bigint(20) unsigned NOT NULL,
            date date NOT NULL,
            start_time time DEFAULT NULL,
            end_time time DEFAULT NULL,
            activity varchar(255) DEFAULT '',
            parcel_id bigint(20) unsigned DEFAULT NULL,
            notes text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY worker_id (worker_id),
            KEY parcel_id (parcel_id)
        ) $charset_collate;";
        dbDelta( $sql_work_logs );
    }

    /**
     * Set default options.
     */
    private static function set_default_options() {
        add_option( 'agromanager_currency', 'HUF' );
        add_option( 'agromanager_area_unit', 'ha' );
        add_option( 'agromanager_default_location', 'Budapest, Magyarország' );
        add_option( 'agromanager_default_lat', '47.4979' );
        add_option( 'agromanager_default_lng', '19.0402' );
        add_option( 'agromanager_db_version', AGROMANAGER_VERSION );
    }
}
