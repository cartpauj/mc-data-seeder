<?php
/**
 * Plugin Name: MemberCore Data Seeder
 * Plugin URI: https://caseproof.com
 * Description: A comprehensive data seeder for MemberCore with batch processing and progress tracking
 * Version: 1.0.0
 * Author: Caseproof
 * Author URI: https://caseproof.com
 * Text Domain: membercore-data-seeder
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MCDS_VERSION', '1.0.0');
define('MCDS_PLUGIN_FILE', __FILE__);
define('MCDS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MCDS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'MCDS\\';
    $base_dir = MCDS_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize the plugin
add_action('plugins_loaded', function() {
    \MCDS\Plugin::instance();
});

// Load seeder registrations
require_once MCDS_PLUGIN_DIR . 'includes/register-seeders.php';

// Activation hook
register_activation_hook(__FILE__, function() {
    \MCDS\Installer::activate();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    \MCDS\Installer::deactivate();
});
