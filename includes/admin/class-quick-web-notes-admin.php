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


    public function render_admin_page()
    {
        $this->handle_note_edit();

        // Handle sorting
        $orderby = isset($_GET['orderby']) ? $_GET['orderby'] : 'created_at';
        $order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
        $notes = $this->get_all_notes($orderby, $order);

        require_once QUICK_WEB_NOTES_PLUGIN_PATH . 'includes/admin/views/admin-page.php';
    }


    public function render_notes_table($notes)
    {
        include QUICK_WEB_NOTES_PLUGIN_PATH . 'includes/admin/views/notes-table.php';
    }

    public function process_note_submission()
    {
        if (isset($_POST['submit_note'])) {
            if (!isset($_POST['note_nonce']) || !wp_verify_nonce($_POST['note_nonce'], 'add_note')) {
                wp_die('Security check failed');
            }
            $title = sanitize_text_field($_POST['note_title']);
            $content = sanitize_textarea_field($_POST['note_content']);

            $result = $this->wpdb->insert(
                $this->table_name,
                array(
                    'title' => $title,
                    'content' => $content
                )
            );
            // Add a transient to display a success message
            set_transient('quick_web_notes_message', [
                'type' => $result ? 'success' : 'error',
                'message' => $result ? 'Note added successfully!' : 'Failed to add note. Please try again.'
            ], 30);

            wp_redirect(admin_url('admin.php?page=quick-web-notes-manager'));
            exit;
        }
    }


    private function handle_note_edit()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
            $this->wpdb->update(
                $this->table_name,
                array(
                    'title' => sanitize_text_field($_GET['title']),
                    'content' => sanitize_textarea_field($_GET['content']),
                ),
                array('id' => $_GET['id'])
            );
        }
    }

    public function process_note_deletion()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
            if (!isset($_GET['delete_nonce']) || !wp_verify_nonce($_GET['delete_nonce'], 'delete_note_' . $_GET['id'])) {
                wp_die('Security check failed');
            }

            $result = $this->wpdb->delete($this->table_name, array('id' => $_GET['id']));

            // Add a transient for a success message
            set_transient('quick_web_notes_message', [
                'type' => $result ? 'success' : 'error',
                'message' => $result ? 'Note deleted successfully!' : 'Failed to delete note. Please try again.'
            ], 30);
        }
    }

    private function get_all_notes($orderby = 'created_at', $order = 'DESC')
    {
        // Whitelist of allowed columns for ordering
        $allowed_orderby = array('title', 'content', 'created_at');

        // Sanitize orderby
        $orderby = in_array($orderby, $allowed_orderby) ? $orderby : 'created_at';

        // Sanitize order
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        // Use prepare with %s placeholders for dynamic column names
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} ORDER BY %s %s",
                $orderby,
                $order
            )
        );
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

                $result = $this->wpdb->query(
                    $this->wpdb->prepare(
                        "DELETE FROM {$this->table_name} WHERE id IN ($placeholder_string)",
                        $ids
                    )
                );

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

        if (empty($title) || empty($content)) {
            wp_send_json_error('Title and content are required');
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
            plugins_url('assets/css/quick-web-notes-style.css', dirname(dirname(__FILE__))),
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