<?php

namespace MCDS;

/**
 * Plugin installer class
 */
class Installer {

    /**
     * Create the progress table
     */
    public static function create_progress_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'mcds_progress';

        // Create progress tracking table
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            seeder_key varchar(191) NOT NULL,
            total int(11) NOT NULL DEFAULT 0,
            processed int(11) NOT NULL DEFAULT 0,
            batch_size int(11) NOT NULL DEFAULT 50,
            status varchar(20) NOT NULL DEFAULT 'pending',
            settings longtext,
            started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            error_message text,
            PRIMARY KEY (id),
            UNIQUE KEY seeder_key (seeder_key),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Run on plugin activation
     */
    public static function activate() {
        self::create_progress_table();

        // Set plugin version
        update_option('mcds_version', MCDS_VERSION);
        update_option('mcds_installed', true);

        // Clear the tables verified transient to force a recheck
        delete_transient('mcds_tables_verified');
    }

    /**
     * Run on plugin deactivation
     */
    public static function deactivate() {
        // Clean up any running processes
        global $wpdb;
        $table_name = $wpdb->prefix . 'mcds_progress';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            $wpdb->update(
                $table_name,
                ['status' => 'cancelled'],
                ['status' => 'running']
            );
        }
    }
}
