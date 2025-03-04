<?php
/*
 * Plugin Name:       Quick Web Notes
 * Plugin URI:        https://github.com/aarifhsn/quick-web-notes
 * Description:       Quick Web Notes Daily is a user-friendly WordPress plugin that helps you capture and manage your daily ideas effortlessly. Add notes directly from the frontend or use the comprehensive dashboard. Never lose track of your thoughts with easy editing, organizing, and deletion features.
 * Version:           1.0.3
 * Requires at least: 6.2
 * Requires PHP:      7.2
 * Author:            Arif Hassan
 * Author URI:        https://github.com/aarifhsn
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       quick-web-notes
 * Domain Path:       /languages
 */


// If this file is called directly, exit.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Currently plugin version.
 * Rename this for your plugin and update it as you release new versions.
 */
define('QUICK_WEB_NOTES_VERSION', '1.0.2');
define('QUICK_WEB_NOTES_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('QUICK_WEB_NOTES_PLUGIN_URL', plugin_dir_url(__FILE__));


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */

require QUICK_WEB_NOTES_PLUGIN_PATH . 'includes/class-quick-web-notes.php';


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_quick_web_notes()
{
    new Quick_Web_Notes();
}
run_quick_web_notes();