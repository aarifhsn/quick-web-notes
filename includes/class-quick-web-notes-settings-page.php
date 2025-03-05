<?php

if (!defined('ABSPATH')) {
    exit;
}
class Quick_Web_Notes_Settings_Page
{
    private $wpdb;
    private $table_name;

    /**
     * package Quick_Web_Notes_Settings_Page constructor.
     * @since 1.0.0
     * @access public
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'quick_web_notes';
    }

}