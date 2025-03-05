<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/aarifhsn/quick-web-notes/
 * @since             1.0.0
 * @package           Quick_Web_Notes
 */

if (!defined('ABSPATH')) {
	exit;
}
class Quick_Web_Notes
{
	protected $quick_web_notes;

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version 
	 */
	protected $version;

	private $wpdb;
	private $table_name;

	public function __construct()
	{
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->table_name = $wpdb->prefix . 'quick_web_notes';

		$this->quick_web_notes = 'quick-web-notes';
		$this->version = QUICK_WEB_NOTES_VERSION;

		$this->ahqwn_load_dependencies();

		// Initialize Database tables
		$db = new Quick_Web_Notes_DB();
		$db->ahqwn_create_tables();

		$this->ahqwn_set_locale();
		$this->ahqwn_define_settings_hooks();

		// $notes_service = new Quick_Web_Notes_Service($wpdb, $this->table_name);
		$admin = new Quick_Web_Notes_Admin($wpdb, $this->table_name);
		$frontend = new Quick_Web_Notes_Frontend();
		$ajax = new Quick_Web_Notes_Ajax();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Quick_Web_Notes_DB. Defines the custom database table.
	 * - Quick_Web_Notes_i18n. Defines internationalization functionality.
	 * - Quick_Web_Notes_Settings_Page. Defines all hooks for the settings page.
	 * - Quick_Web_Notes_Admin. Defines all hooks for the admin area.
	 * - Quick_Web_Notes_Frontend. Defines all hooks for the frontend area.
	 * - Quick_Web_Notes_Ajax. Defines all hooks for the ajax area.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function ahqwn_load_dependencies()
	{
		/**
		 * Load required files here
		 * 
		 * @since    1.0.0
		 * @access   private
		 * 
		 */

		require_once QUICK_WEB_NOTES_PLUGIN_PATH . 'includes/class-quick-web-notes-db.php';

		require_once QUICK_WEB_NOTES_PLUGIN_PATH . 'includes/class-quick-web-notes-i18n.php';

		require_once QUICK_WEB_NOTES_PLUGIN_PATH . 'includes/class-quick-web-notes-settings-page.php';

		require_once QUICK_WEB_NOTES_PLUGIN_PATH . 'includes/admin/class-quick-web-notes-admin.php';

		require_once QUICK_WEB_NOTES_PLUGIN_PATH . 'includes/frontend/class-quick-web-notes-frontend.php';

		require_once QUICK_WEB_NOTES_PLUGIN_PATH . 'includes/ajax/class-quick-web-notes-ajax.php';
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the quick_web_notes_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function ahqwn_set_locale()
	{

		$plugin_i18n = new quick_web_notes_i18n();

		$plugin_i18n->ahqwn_set_domain($this->quick_web_notes);

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function ahqwn_define_settings_hooks()
	{
		new Quick_Web_Notes_Settings_Page();
	}

}
