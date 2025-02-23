<?php
/**
 * Get all notes with proper preparation and caching
 * 
 * @param string $orderby Column to order by
 * @param string $order Order direction
 * @return array Array of note objects
 * 
 * @phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * @phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Quick_Web_Notes_Admin
{
    private $wpdb;
    private $table_name;
    private $settings;

    /**
     * package Quick_Web_Notes_Admin constructor.
     * @since 1.0.0
     * @access public
     * @param $wpdb
     * @param $table_name
     */
    public function __construct($wpdb, $table_name)
    {
        $this->wpdb = $wpdb;
        $this->table_name = $table_name;
        $this->qwn_init_hooks();

        // Include the settings class
        require_once QUICK_WEB_NOTES_PLUGIN_PATH . 'includes/admin/class-quick-web-notes-admin-settings.php';
        $this->settings = new Quick_Web_Notes_Admin_Settings();

    }

    /**
     * Initialize hooks for the admin area.
     *
     * @since    1.0.0
     */
    private function qwn_init_hooks()
    {
        add_action('admin_menu', array($this, 'qwn_register_admin_menu'));

        // Admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'qwn_enqueue_admin_assets'));

        // Ajax actions
        add_action('wp_ajax_admin_edit_note', array($this, 'qwn_ajax_admin_edit_note'));

        add_action('admin_init', array($this, 'qwn_process_bulk_actions'));
        add_action('admin_init', array($this, 'qwn_process_note_submission'));
        add_action('admin_init', array($this, 'qwn_process_note_deletion'));

        // Admin notices
        add_action('admin_notices', array($this, 'qwn_admin_notices'));
    }

    /**
     * Register the admin menu
     * 
     * @since 1.0.0
     */
    public function qwn_register_admin_menu()
    {
        add_menu_page(
            'Quick Web Notes',
            'Quick Web Notes',
            'manage_options',
            'quick-web-notes-manager',
            array($this, 'qwn_render_admin_page'),
            'dashicons-sticky'
        );

        add_submenu_page(
            'quick-web-notes-manager',
            'Quick Web Notes',
            'Manage Notes',
            'manage_options',
            'quick-web-notes-manager',
            array($this, 'qwn_render_admin_page')
        );
    }

    /**
     * Render the admin page
     * 
     * @since 1.0.0
     */
    public function qwn_render_admin_page()
    {
        $this->qwn_handle_note_edit();

        // Create nonce for sorting
        $sort_nonce = wp_create_nonce('quick_web_notes_sort');

        // Check if the nonce is set and valid before processing GET parameters
        if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'quick_web_notes_sort')) {
            $orderby = isset($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : 'created_at';
            $order = isset($_GET['order']) ? sanitize_text_field(wp_unslash($_GET['order'])) : 'DESC';
        } else {
            // If nonce verification fails, use default values
            $orderby = 'created_at';
            $order = 'DESC';
        }

        // Allowed order by columns to prevent SQL injection
        $allowed_orderby = array('title', 'content', 'created_at');
        $allowed_order = array('ASC', 'DESC');

        // Sanitize orderby
        $orderby = in_array($orderby, $allowed_orderby, true) ? $orderby : 'created_at';

        // Sanitize order
        $order = in_array(strtoupper($order), $allowed_order, true) ? strtoupper($order) : 'ASC';

        // Handle sorting
        $orderby = esc_sql($orderby);
        $order = esc_sql($order);

        $notes = $this->qwn_get_all_notes($orderby, $order);

        require_once QUICK_WEB_NOTES_PLUGIN_PATH . 'includes/admin/views/admin-page.php';
    }

    /**
     * Render the notes table
     * 
     * @since 1.0.0
     * @param array $notes Array of note objects
     */
    public function qwn_render_notes_table($notes)
    {
        include QUICK_WEB_NOTES_PLUGIN_PATH . 'includes/admin/views/notes-table.php';
    }

    /**
     * Process note submission
     * 
     * @since 1.0.0
     */
    public function qwn_process_note_submission()
    {
        // First check if the form was submitted
        if (!isset($_POST['submit_note'])) {
            return;
        }

        // Verify nonce exists and is valid
        if (
            !isset($_POST['note_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['note_nonce'])), 'add_note')
        ) {
            wp_die('Security check failed');
        }

        // Validate and sanitize title
        $title = '';
        if (isset($_POST['note_title'])) {
            $title = sanitize_text_field(wp_unslash($_POST['note_title']));
        }

        // Validate and sanitize content
        $content = '';
        if (isset($_POST['note_content'])) {
            $content = sanitize_textarea_field(wp_unslash($_POST['note_content']));
        }

        // Verify we have at least a title or content
        if (empty($title)) {
            set_transient('quick_web_notes_message', [
                'type' => 'error',
                'message' => 'Please enter a title for the note.'
            ], 30);
            wp_redirect(admin_url('admin.php?page=quick-web-notes-manager'));
            exit;
        }

        // Insert the note
        $result = $this->wpdb->insert(
            $this->table_name,
            array(
                'title' => $title,
                'content' => $content
            )
        );

        // Set message transient
        set_transient('quick_web_notes_message', [
            'type' => $result ? 'success' : 'error',
            'message' => $result ? 'Note added successfully!' : 'Failed to add note. Please try again.'
        ], 30);

        wp_redirect(admin_url('admin.php?page=quick-web-notes-manager'));
        exit;
    }

    /**
     * Handle note edit
     * 
     * @since 1.0.0
     */
    private function qwn_handle_note_edit()
    {
        // Check if this is an edit action
        if (!isset($_GET['action']) || $_GET['action'] !== 'edit' || !isset($_GET['id'])) {
            return;
        }

        // Verify nonce
        if (
            !isset($_GET['edit_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['edit_nonce'])), 'edit_note_' . absint($_GET['id']))
        ) {
            wp_die('Security check failed');
        }

        // Validate and sanitize ID
        $note_id = absint($_GET['id']);
        if (!$note_id) {
            return;
        }

        // Validate and sanitize title and content
        $title = '';
        if (isset($_GET['title'])) {
            $title = sanitize_text_field(wp_unslash($_GET['title']));
        }

        $content = '';
        if (isset($_GET['content'])) {
            $content = sanitize_textarea_field(wp_unslash($_GET['content']));
        }

        // Verify we have data to update
        if (empty($title) && empty($content)) {
            return;
        }

        // Prepare update data
        $update_data = array();
        if (!empty($title)) {
            $update_data['title'] = $title;
        }
        if (!empty($content)) {
            $update_data['content'] = $content;
        }

        // Update the note
        $result = $this->wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $note_id)
        );

        // Set result message
        set_transient('quick_web_notes_message', [
            'type' => $result ? 'success' : 'error',
            'message' => $result ? 'Note updated successfully!' : 'Failed to update note. Please try again.'
        ], 30);
    }

    /**
     * Process note deletion
     * 
     * @since 1.0.0
     */
    public function qwn_process_note_deletion()
    {
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_die(esc_html_e('You are not allowed to perform this action', 'quick-web-notes'));
        }

        // Check if this is a delete action
        if (!isset($_GET['action']) || $_GET['action'] !== 'delete' || !isset($_GET['id'])) {
            return;
        }

        // Validate and sanitize ID
        $note_id = absint($_GET['id']);
        if (!$note_id) {
            return;
        }

        // Verify nonce
        if (
            !isset($_GET['delete_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['delete_nonce'])), 'delete_note_' . $note_id)
        ) {
            wp_die(esc_html_e('Security check failed', 'quick-web-notes'));
        }

        // Delete the note
        $result = $this->wpdb->delete(
            $this->table_name,
            array('id' => $note_id),
            array('%d')
        );

        // Set result message
        set_transient('quick_web_notes_message', [
            'type' => $result ? 'success' : 'error',
            'message' => $result !== false ?
                __('Note deleted successfully!', 'quick-web-notes') :
                __('Failed to delete note. Please try again.', 'quick-web-notes')
        ], 30);

        // Redirect back to notes manager page
        // Redirect back to the notes page
        wp_safe_redirect(
            add_query_arg(
                ['page' => 'quick-web-notes-manager'],
                admin_url('admin.php')
            )
        );
        exit;
    }

    /**
     * Get all notes with proper preparation and caching
     * 
     * @param string $orderby Column to order by
     * @param string $order Order direction
     * @return array Array of note objects
     * 
     * @phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
     * @phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
     */
    private function qwn_get_all_notes($orderby = 'created_at', $order = 'DESC')
    {
        global $wpdb;

        // Allowed columns to prevent SQL injection
        $allowed_orderby = ['title', 'content', 'created_at'];
        $allowed_order = ['ASC', 'DESC'];

        // Validate and sanitize ORDER BY column
        $orderby = in_array($orderby, $allowed_orderby, true) ? $orderby : 'created_at';

        // Validate and sanitize ORDER direction
        $order = in_array(strtoupper($order), $allowed_order, true) ? strtoupper($order) : 'DESC';

        // Cache key for caching results
        $cache_key = "quick_web_notes_{$orderby}_{$order}";
        $notes = wp_cache_get($cache_key, 'quick_web_notes');

        if (false === $notes) {
            $table_name = $wpdb->prefix . 'quick_web_notes';

            // Create the base query with a placeholder for the table name
            $query = "SELECT * FROM `" . esc_sql($table_name) . "`";

            // Add ORDER BY clause
            $query .= " ORDER BY " . esc_sql($orderby) . " " . esc_sql($order);

            // Execute the query
            $notes = $wpdb->get_results($query);

            // Store results in cache
            wp_cache_set($cache_key, $notes, 'quick_web_notes', HOUR_IN_SECONDS);
        }

        return $notes;
    }

    /**
     * Process bulk actions
     * 
     * @since 1.0.0
     */
    public function qwn_process_bulk_actions()
    {
        // Check if we're on the correct page
        if (!isset($_POST['page']) || $_POST['page'] !== 'quick-web-notes-manager') {
            return;
        }

        // Verify nonce
        if (
            !isset($_POST['_wpnonce']) || !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['_wpnonce'])),
                'bulk-notes'
            )
        ) {
            wp_die('Security check failed');
        }

        // Get the action
        $action = '-1';
        if (isset($_POST['action']) && $_POST['action'] !== '-1') {
            $action = sanitize_text_field(wp_unslash($_POST['action']));
        } elseif (isset($_POST['action2']) && $_POST['action2'] !== '-1') {
            $action = sanitize_text_field(wp_unslash($_POST['action2']));
        }

        // Process bulk delete
        if ($action === 'bulk-delete' && isset($_POST['note_ids']) && is_array($_POST['note_ids'])) {
            global $wpdb;

            // Sanitize and validate IDs
            $ids = array_map('absint', wp_unslash($_POST['note_ids']));
            $ids = array_filter($ids); // Remove any zero values

            if (!empty($ids)) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'quick_web_notes';

                // Cache key for bulk operations
                $cache_key = 'quick_web_notes_bulk_operation';
                $notes_cache_group = 'quick_web_notes';

                // Delete individual note caches
                foreach ($ids as $note_id) {
                    wp_cache_delete($note_id, $notes_cache_group);
                }

                // Delete any bulk listing caches
                wp_cache_delete('all_notes', $notes_cache_group);
                wp_cache_delete($cache_key, $notes_cache_group);

                // Execute the properly prepared query directly
                $affected_rows = $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM `" . esc_sql($table_name) . "` WHERE id IN (" .
                        implode(',', array_fill(0, count($ids), '%d')) .
                        ")",
                        $ids
                    )
                );

                // Cache the operation result briefly to prevent duplicate operations
                wp_cache_set(
                    $cache_key,
                    [
                        'status' => $affected_rows ? 'success' : 'error',
                        'count' => $affected_rows,
                        'timestamp' => time()
                    ],
                    $notes_cache_group,
                    30
                );

                // Store result in transient (for UI feedback)
                set_transient('quick_web_notes_bulk_delete_result', [
                    'status' => $affected_rows ? 'success' : 'error',
                    'count' => $affected_rows
                ], 30);

                // Redirect back to the admin page
                wp_safe_redirect(add_query_arg(
                    ['page' => 'quick-web-notes-manager'],
                    admin_url('admin.php')
                ));
                exit;
            }
        }
    }

    /**
     * Display admin notices
     * 
     * @since 1.0.0
     */
    public function qwn_admin_notices()
    {
        $result = get_transient('quick_web_notes_bulk_delete_result');
        if ($result) {
            if ($result['status'] === 'success') {
                $count = $result['count'];

                $message = sprintf(
                    // translators: %s: number of notes deleted
                    _n(
                        '%s note deleted successfully.',
                        '%s notes deleted successfully.',
                        $count,
                        'quick-web-notes'
                    ),
                    number_format_i18n($count)
                );
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' .
                    esc_html__('Error deleting notes. Please try again.', 'quick-web-notes') .
                    '</p></div>';
            }
            delete_transient('quick_web_notes_bulk_delete_result');
        }

        // Check for add note message
        $note_message = get_transient('quick_web_notes_message');
        if ($note_message) {
            $class = ($note_message['type'] === 'success') ? 'notice-success' : 'notice-error';
            echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' .
                esc_html($note_message['message']) .
                '</p></div>';
            delete_transient('quick_web_notes_message');
        }
    }

    /**
     * AJAX: Edit a note
     * 
     * @since 1.0.0
     */
    public function qwn_ajax_admin_edit_note()
    {
        check_ajax_referer('quick-web-notes-admin-nonce', 'nonce');

        // Validate and sanitize id
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        // Validate and sanitize title
        $title = '';
        if (isset($_POST['title'])) {
            $title = sanitize_text_field(wp_unslash($_POST['title']));
        }

        // Validate and sanitize content
        $content = '';
        if (isset($_POST['content'])) {
            $content = sanitize_textarea_field(wp_unslash($_POST['content']));
        }

        // Check if at least one field is filled
        if (empty($title) && empty($content)) {
            wp_send_json_error('Title or Content is required');
            return;
        }

        $result = $this->wpdb->update(
            $this->table_name,
            array(
                'title' => $title,
                'content' => $content
            ),
            array('id' => $id)
        );

        if ($result !== false) {
            wp_send_json_success('Note updated successfully');
        } else {
            wp_send_json_error('Failed to update note');
        }
    }

    /**
     * Enqueue admin assets
     * 
     * @since 1.0.0
     * @param string $hook The current admin page
     */
    public function qwn_enqueue_admin_assets($hook)
    {

        // Only load on our plugin's page
        if ('toplevel_page_quick-web-notes-manager' !== $hook) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'quick-web-notes-admin-style',
            plugins_url('assets/css/quick-web-notes-admin-style.css', dirname(dirname(__FILE__))),
            array(),
            '1.0.0'
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'quick-web-notes-admin',
            plugins_url('assets/js/quick-web-notes-admin.js', dirname(dirname(__FILE__))),
            array('jquery'),
            '1.0.0',
            array('in_footer' => true)
        );

        // Localize script
        wp_localize_script(
            'quick-web-notes-admin',
            'quickWebNotesAdmin',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('quick-web-notes-admin-nonce')
            )
        );
    }


}