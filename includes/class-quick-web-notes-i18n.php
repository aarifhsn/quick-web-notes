<?php

if (!defined('ABSPATH')) {
	exit;
}
class Quick_Web_Notes_i18n
{

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	private $domain;

	public function ahqwn_set_domain($domain)
	{
		$this->domain = $domain;
	}
}
