<?php
class Quick_Web_Notes_Ajax
{
    private $notes_service;

    private $table_name;

    private $wpdb;

    public function __construct($notes_service)
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'quick_web_notes';

        $this->notes_service = $notes_service;
        $this->init_hooks();
    }

    private function init_hooks()
    {
        add_action('wp_ajax_add_note', array($this, 'ajax_add_note'));
        add_action('wp_ajax_nopriv_add_note', array($this, 'ajax_add_note'));
        add_action('wp_ajax_get_notes', array($this, 'ajax_get_notes'));
        add_action('wp_ajax_nopriv_get_notes', array($this, 'ajax_get_notes'));
        add_action('wp_ajax_edit_note', array($this, 'ajax_edit_note'));
        add_action('wp_ajax_delete_note', array($this, 'ajax_delete_note'));
        add_action('wp_ajax_get_note_by_id', array($this, 'ajax_get_note_by_id'));
    }

    public function ajax_add_note()
    {
        check_ajax_referer('quick-web-notes-nonce', 'nonce');

        $title = sanitize_text_field($_POST['title']);
        $content = sanitize_textarea_field($_POST['content']);

        if (empty($title) && empty($content)) {
            wp_send_json_error('Title or Content is required');
            return;
        }

        $result = $this->wpdb->insert(
            $this->table_name,
            array(
                'title' => $title,
                'content' => $content
            )
        );

        if ($result) {
            wp_send_json_success('Note added successfully');
        } else {
            wp_send_json_error('Failed to add note');
        }
    }

    public function ajax_get_notes()
    {
        check_ajax_referer('quick-web-notes-nonce', 'nonce');
        $notes = $this->get_all_notes();
        wp_send_json_success($notes);
    }
    private function get_all_notes()
    {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY created_at DESC"
        );
    }

    public function ajax_edit_note()
    {
        check_ajax_referer('quick-web-notes-nonce', 'nonce');

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

    public function ajax_get_note_by_id()
    {
        check_ajax_referer('quick-web-notes-nonce', 'nonce');

        $id = intval($_POST['id']);
        $note = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id)
        );

        if ($note) {
            wp_send_json_success($note);
        } else {
            wp_send_json_error('Note not found');
        }
    }

    public function ajax_delete_note()
    {
        check_ajax_referer('quick-web-notes-nonce', 'nonce');

        $id = intval($_POST['id']);
        if (!$id) {
            wp_send_json_error('Invalid Note id');
            return;
        }

        // Check if note exists before trying to delete
        $note = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT id FROM {$this->table_name} WHERE id = %d", $id)
        );

        if (!$note) {
            wp_send_json_error('Note not found');
            return;
        }
        $result = $this->wpdb->delete($this->table_name, array('id' => $id));

        if ($result) {
            wp_send_json_success('Note deleted successfully');
        } else {
            wp_send_json_error('Failed to delete note');
        }
    }
}
