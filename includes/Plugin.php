<?php

namespace MCDS;

/**
 * Main plugin class
 */
class Plugin {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Seeder registry
     */
    public $registry;

    /**
     * Admin page
     */
    public $admin;

    /**
     * AJAX handler
     */
    public $ajax;

    /**
     * Get singleton instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Initialize plugin
     */
    private function init() {
        // Load text domain
        add_action('init', [$this, 'load_textdomain']);

        // Initialize components
        $this->registry = new SeederRegistry();
        $this->ajax = new AjaxHandler($this->registry);

        // Initialize admin if in admin area
        if (is_admin()) {
            $this->admin = new Admin($this->registry);
        }

        // Hook for registering seeders
        do_action('mcds_register_seeders', $this->registry);

        // Hook into WordPress avatar system to display seeded avatars
        add_filter('get_avatar_url', [$this, 'get_seeded_avatar_url'], 10, 3);
        add_filter('get_avatar', [$this, 'get_seeded_avatar'], 10, 6);
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'membercore-data-seeder',
            false,
            dirname(plugin_basename(MCDS_PLUGIN_FILE)) . '/languages'
        );
    }

    /**
     * Filter avatar URL to use seeded avatar from membercore-directory if available
     */
    public function get_seeded_avatar_url($url, $id_or_email, $args) {
        global $wpdb;

        $user = false;

        if (is_numeric($id_or_email)) {
            $user = get_user_by('id', absint($id_or_email));
        } elseif (is_object($id_or_email)) {
            if (!empty($id_or_email->user_id)) {
                $user = get_user_by('id', absint($id_or_email->user_id));
            }
        } elseif (is_string($id_or_email)) {
            $user = get_user_by('email', $id_or_email);
        }

        if ($user && is_object($user)) {
            $mcdir_table = $wpdb->prefix . 'mcdir_profile_images';
            $avatar_url = $wpdb->get_var($wpdb->prepare(
                "SELECT url FROM {$mcdir_table} WHERE user_id = %d AND type = 'avatar' LIMIT 1",
                $user->ID
            ));

            if ($avatar_url) {
                return $avatar_url;
            }
        }

        return $url;
    }

    /**
     * Filter avatar HTML to use seeded avatar from membercore-directory if available
     */
    public function get_seeded_avatar($avatar, $id_or_email, $size, $default, $alt, $args) {
        global $wpdb;

        $user = false;

        if (is_numeric($id_or_email)) {
            $user = get_user_by('id', absint($id_or_email));
        } elseif (is_object($id_or_email)) {
            if (!empty($id_or_email->user_id)) {
                $user = get_user_by('id', absint($id_or_email->user_id));
            }
        } elseif (is_string($id_or_email)) {
            $user = get_user_by('email', $id_or_email);
        }

        if ($user && is_object($user)) {
            $mcdir_table = $wpdb->prefix . 'mcdir_profile_images';
            $avatar_url = $wpdb->get_var($wpdb->prepare(
                "SELECT url FROM {$mcdir_table} WHERE user_id = %d AND type = 'avatar' LIMIT 1",
                $user->ID
            ));

            if ($avatar_url) {
                $avatar = sprintf(
                    "<img alt='%s' src='%s' class='avatar avatar-%d photo' height='%d' width='%d' loading='lazy' decoding='async' />",
                    esc_attr($alt),
                    esc_url($avatar_url),
                    esc_attr($size),
                    esc_attr($size),
                    esc_attr($size)
                );
            }
        }

        return $avatar;
    }
}
