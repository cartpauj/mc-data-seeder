<?php

namespace MCDS;

/**
 * Plugin Configuration Manager
 * Manages configurations for different target plugins (MemberCore, MemberPress, WishList LMS, etc.)
 */
class PluginConfig {

    /**
     * Supported plugins configuration
     */
    private static $plugins = [
        'membercore' => [
            'name' => 'MemberCore',
            'class' => 'MecoUser',
            'prefix' => 'meco',
            'short_prefix' => 'mc',
            'post_types' => [
                'product' => 'membercoreproduct',
                'group' => 'membercoregroup'
            ],
            'meta_keys' => [
                'address_one' => 'meco-address-one',
                'address_two' => 'meco-address-two',
                'address_city' => 'meco-address-city',
                'address_state' => 'meco-address-state',
                'address_zip' => 'meco-address-zip',
                'address_country' => 'meco-address-country',
                'product_price' => '_meco_product_price',
                'product_period' => '_meco_product_period',
                'product_period_type' => '_meco_product_period_type',
                'product_signup_button_text' => '_meco_product_signup_button_text',
                'group_id' => '_meco_group_id',
                'group_order' => '_meco_group_order',
                'group_is_upgrade_path' => '_meco_group_is_upgrade_path',
                'group_upgrade_path_reset_period' => '_meco_group_upgrade_path_reset_period',
                'group_pricing_page_disabled' => '_meco_group_pricing_page_disabled',
                'group_disable_change_plan_popup' => '_meco_group_disable_change_plan_popup',
                'group_theme' => '_meco_group_theme',
            ],
            'table_prefix' => 'mc',
            'directory_table' => 'mcdir_profile_images'
        ],
        'memberpress' => [
            'name' => 'MemberPress',
            'class' => 'MeprUser',
            'prefix' => 'mepr',
            'short_prefix' => 'mepr',
            'post_types' => [
                'product' => 'memberpressproduct',
                'group' => 'memberpressgroup'
            ],
            'meta_keys' => [
                'address_one' => 'mepr-address-one',
                'address_two' => 'mepr-address-two',
                'address_city' => 'mepr-address-city',
                'address_state' => 'mepr-address-state',
                'address_zip' => 'mepr-address-zip',
                'address_country' => 'mepr-address-country',
                'product_price' => '_mepr_product_price',
                'product_period' => '_mepr_product_period',
                'product_period_type' => '_mepr_product_period_type',
                'product_signup_button_text' => '_mepr_product_signup_button_text',
                'group_id' => '_mepr_group_id',
                'group_order' => '_mepr_group_order',
                'group_is_upgrade_path' => '_mepr_group_is_upgrade_path',
                'group_upgrade_path_reset_period' => '_mepr_group_upgrade_path_reset_period',
                'group_pricing_page_disabled' => '_mepr_group_pricing_page_disabled',
                'group_disable_change_plan_popup' => '_mepr_group_disable_change_plan_popup',
                'group_theme' => '_mepr_group_theme',
            ],
            'table_prefix' => 'mepr',
            'directory_table' => 'meprdir_profile_images'
        ],
        'wishlist' => [
            'name' => 'WishList LMS',
            'class' => 'WlmsUser',
            'prefix' => 'wlms',
            'short_prefix' => 'wlms',
            'post_types' => [
                'product' => 'wishlistlmsproduct',
                'group' => 'wishlistlmsgroup'
            ],
            'meta_keys' => [
                'address_one' => 'wlms-address-one',
                'address_two' => 'wlms-address-two',
                'address_city' => 'wlms-address-city',
                'address_state' => 'wlms-address-state',
                'address_zip' => 'wlms-address-zip',
                'address_country' => 'wlms-address-country',
                'product_price' => '_wlms_product_price',
                'product_period' => '_wlms_product_period',
                'product_period_type' => '_wlms_product_period_type',
                'product_signup_button_text' => '_wlms_product_signup_button_text',
                'group_id' => '_wlms_group_id',
                'group_order' => '_wlms_group_order',
                'group_is_upgrade_path' => '_wlms_group_is_upgrade_path',
                'group_upgrade_path_reset_period' => '_wlms_group_upgrade_path_reset_period',
                'group_pricing_page_disabled' => '_wlms_group_pricing_page_disabled',
                'group_disable_change_plan_popup' => '_wlms_group_disable_change_plan_popup',
                'group_theme' => '_wlms_group_theme',
            ],
            'table_prefix' => 'wlms',
            'directory_table' => 'wlmsdir_profile_images'
        ]
    ];

    /**
     * Current active plugin key
     */
    private static $active_plugin = null;

    /**
     * Get all supported plugins
     */
    public static function get_plugins() {
        return self::$plugins;
    }

    /**
     * Get configuration for a specific plugin
     */
    public static function get_config($plugin_key) {
        return self::$plugins[$plugin_key] ?? null;
    }

    /**
     * Set the active plugin
     */
    public static function set_active_plugin($plugin_key) {
        if (isset(self::$plugins[$plugin_key])) {
            self::$active_plugin = $plugin_key;
            update_option('mcds_active_plugin', $plugin_key);
        }
    }

    /**
     * Get the active plugin key
     */
    public static function get_active_plugin() {
        if (self::$active_plugin === null) {
            self::$active_plugin = get_option('mcds_active_plugin', 'membercore');
        }
        return self::$active_plugin;
    }

    /**
     * Get active plugin configuration
     */
    public static function get_active_config() {
        $plugin_key = self::get_active_plugin();
        return self::get_config($plugin_key);
    }

    /**
     * Detect which plugins are installed
     */
    public static function detect_installed_plugins() {
        $installed = [];

        foreach (self::$plugins as $key => $plugin) {
            if (class_exists($plugin['class'])) {
                $installed[] = $key;
            }
        }

        return $installed;
    }

    /**
     * Auto-detect and set the active plugin
     * Returns the detected plugin key or null if none found
     */
    public static function auto_detect_plugin() {
        $installed = self::detect_installed_plugins();

        if (!empty($installed)) {
            // Use the first installed plugin
            $plugin_key = $installed[0];
            self::set_active_plugin($plugin_key);
            return $plugin_key;
        }

        return null;
    }

    /**
     * Check if active plugin is installed
     */
    public static function is_active_plugin_installed() {
        $config = self::get_active_config();
        return $config && class_exists($config['class']);
    }

    /**
     * Get a meta key for the active plugin
     */
    public static function get_meta_key($key) {
        $config = self::get_active_config();
        return $config['meta_keys'][$key] ?? null;
    }

    /**
     * Get a post type for the active plugin
     */
    public static function get_post_type($type) {
        $config = self::get_active_config();
        return $config['post_types'][$type] ?? null;
    }

    /**
     * Get the prefix for the active plugin
     */
    public static function get_prefix() {
        $config = self::get_active_config();
        return $config['prefix'] ?? 'meco';
    }

    /**
     * Get the short prefix for the active plugin
     */
    public static function get_short_prefix() {
        $config = self::get_active_config();
        return $config['short_prefix'] ?? 'mc';
    }

    /**
     * Get the table prefix for the active plugin
     */
    public static function get_table_prefix() {
        $config = self::get_active_config();
        return $config['table_prefix'] ?? 'mc';
    }

    /**
     * Get the directory table name for the active plugin
     */
    public static function get_directory_table() {
        global $wpdb;
        $config = self::get_active_config();
        return $wpdb->prefix . ($config['directory_table'] ?? 'mcdir_profile_images');
    }

    /**
     * Get active plugin name
     */
    public static function get_active_plugin_name() {
        $config = self::get_active_config();
        return $config['name'] ?? 'MemberCore';
    }
}
