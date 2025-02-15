<?php

class Quick_Web_Notes_i18n
{

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	private $domain;

	public function set_domain($domain)
	{
		$this->domain = $domain;
	}

	public function load_plugin_textdomain()
	{
		load_plugin_textdomain(
			'quick-web-notes',
			false,
			dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
		);

	}



}
