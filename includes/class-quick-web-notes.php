<?php

class Quick_Web_Notes
{
	protected $quick_web_notes;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
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

		$this->load_dependencies();

		// Initialize Database tables
		$db = new Quick_Web_Notes_DB();
		$db->create_tables();

		$this->set_locale();
		$this->define_settings_hooks();

		// $notes_service = new Quick_Web_Notes_Service($wpdb, $this->table_name);
		$admin = new Quick_Web_Notes_Admin($wpdb, $this->table_name);
		$frontend = new Quick_Web_Notes_Frontend();
		$ajax = new Quick_Web_Notes_Ajax();

	}

	private function load_dependencies()
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

		// require_once QUICK_WEB_NOTES_PLUGIN_PATH . 'includes/services/class-quick-web-notes-service.php';

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
	private function set_locale()
	{

		$plugin_i18n = new quick_web_notes_i18n();

		$plugin_i18n->set_domain($this->quick_web_notes);

		$plugin_i18n->load_plugin_textdomain();

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_settings_hooks()
	{

		new Quick_Web_Notes_Settings_Page();

	}

}
