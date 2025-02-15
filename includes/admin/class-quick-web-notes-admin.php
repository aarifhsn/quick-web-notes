<?php
class Quick_Web_Notes_Admin
{
    private $wpdb;
    private $table_name;
    private $settings;

    public function __construct($wpdb, $table_name)
    {
        $this->wpdb = $wpdb;
        $this->table_name = $table_name;
        $this->init_hooks();

        // Include the settings class
        require_once QUICK_WEB_NOTES_PLUGIN_PATH . 'includes/admin/class-quick-web-notes-admin-settings.php';
        $this->settings = new Quick_Web_Notes_Admin_Settings();

    }

    private function init_hooks()
    {
        add_action('admin_menu', array($this, 'register_admin_menu'));

        // Admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Ajax actions
        add_action('wp_ajax_admin_edit_note', array($this, 'ajax_admin_edit_note'));

        add_action('admin_init', array($this, 'process_bulk_actions'));
        add_action('admin_init', array($this, 'process_note_submission'));
        add_action('admin_init', array($this, 'process_note_deletion'));

        // Admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
    }

    public function register_admin_menu()
    {
        add_menu_page(
            'Notes Manager',
            'Notes Manager',
            'manage_options',
            'quick-web-notes-manager',
            array($this, 'render_admin_page'),
            'dashicons-sticky'
        );

        add_submenu_page(
            'quick-web-notes-manager',
            'Notes Manager',
            'Manage Notes',
            'manage_options',
            'quick-web-notes-manager',
            array($this, 'render_admin_page')
        );
    }


    public function render_admin_page($orderby = 'created_at', $order = 'DESC')
    {
        $this->handle_note_edit();

        // Allowed order by columns to prevent SQL injection
        $allowed_orderby = array('title', 'content', 'created_at');
        $allowed_order = array('ASC', 'DESC');

        // Sanitize orderby
        $orderby = in_array($orderby, $allowed_orderby) ? $orderby : 'created_at';

        // Sanitize order
        $order = in_array(strtoupper($order), $allowed_order) ? strtoupper($order) : 'ASC';

        // Handle sorting
        $orderby = esc_sql($orderby);
        $order = esc_sql($order);

        $notes = $this->get_all_notes($orderby, $order);

        require_once QUICK_WEB_NOTES_PLUGIN_PATH . 'includes/admin/views/admin-page.php';
    }


    public function render_notes_table($notes)
    {
        include QUICK_WEB_NOTES_PLUGIN_PATH . 'includes/admin/views/notes-table.php';
    }

    public function process_note_submission()
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


    private function handle_note_edit()
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

    public function process_note_deletion()
    {
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
            wp_die('Security check failed');
        }

        // Delete the note
        $result = $this->wpdb->delete(
            $this->table_name,
            array('id' => $note_id)
        );

        // Set result message
        set_transient('quick_web_notes_message', [
            'type' => $result ? 'success' : 'error',
            'message' => $result ? 'Note deleted successfully!' : 'Failed to delete note. Please try again.'
        ], 30);
    }

    private function get_all_notes($orderby = 'created_at', $order = 'DESC')
    {
        global $wpdb;

        // Allowed order by columns to prevent SQL injection
        $allowed_orderby = array('title', 'content', 'created_at');
        $allowed_order = array('ASC', 'DESC');

        // Sanitize orderby
        $orderby = in_array($orderby, $allowed_orderby) ? $orderby : 'created_at';

        // Sanitize order
        $order = in_array(strtoupper($order), $allowed_order) ? strtoupper($order) : 'ASC';

        // Use $wpdb->prepare with a placeholder that won't be escaped
        $sql = $wpdb->prepare(
            "SELECT * FROM $this->table_name ORDER BY `%1s` %2s",
            $orderby,
            $order
        );

        // Get all notes
        return $wpdb->get_results($sql);
    }

    public function process_bulk_actions()
    {
        // Only process if we're on our plugin page and have a bulk action
        if (!isset($_POST['page']) || $_POST['page'] !== 'quick-web-notes-manager') {
            return;
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'bulk-notes')) {
            return;
        }

        $action = '-1';
        if (isset($_POST['action']) && $_POST['action'] !== '-1') {
            $action = $_POST['action'];
        } elseif (isset($_POST['action2']) && $_POST['action2'] !== '-1') {
            $action = $_POST['action2'];
        }

        if ($action === 'bulk-delete' && isset($_POST['note_ids']) && is_array($_POST['note_ids'])) {
            $ids = array_map('intval', $_POST['note_ids']);

            if (!empty($ids)) {
                $placeholders = array_fill(0, count($ids), '%d');
                $placeholder_string = implode(',', $placeholders);

                // Escape table name
                $table = esc_sql($this->table_name);

                // Prepare and execute the query
                $query = $this->wpdb->prepare(
                    "DELETE FROM {$table} WHERE id IN ($placeholder_string)",
                    $ids
                );
                $result = $this->wpdb->query($query);

                // Store result in transient
                set_transient('quick_web_notes_bulk_delete_result', [
                    'status' => $result ? 'success' : 'error',
                    'count' => $result
                ], 30);

                // Redirect back to the admin page
                wp_safe_redirect(add_query_arg(
                    array('page' => 'quick-web-notes-manager'),
                    admin_url('admin.php')
                ));
                exit;
            }
        }
    }

    public function admin_notices()
    {
        $result = get_transient('quick_web_notes_bulk_delete_result');
        if ($result) {
            if ($result['status'] === 'success') {
                $count = $result['count'];
                /* translators: %s: number of notes deleted */
                $message = sprintf(
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

    public function ajax_admin_edit_note()
    {
        check_ajax_referer('quick-web-notes-admin-nonce', 'nonce');

        $id = intval($_POST['id']);
        $title = sanitize_text_field($_POST['title']);
        $content = sanitize_textarea_field($_POST['content']);

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

    public function enqueue_admin_assets($hook)
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