<?php
/**
 * Quick Web Notes Database Class
 * 
 * This class is used to create the database tables for the plugin
 * 
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Quick_Web_Notes_DB
{
    private $table_name;
    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'quick_web_notes';
    }

    /**
     * Create the database tables
     * 
     * @since 1.0.0
     */
    public function qwn_create_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        content text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}