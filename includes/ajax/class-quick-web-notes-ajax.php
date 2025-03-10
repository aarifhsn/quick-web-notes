<?php

/** 
 * Class Quick_Web_Notes_Ajax
 * 
 * @package Quick_Web_Notes
 * 
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 */

if (!defined('ABSPATH')) {
    exit;
}

class Quick_Web_Notes_Ajax
{
    private $notes_service;

    private $table_name;

    private $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'quick_web_notes';

        // $this->notes_service = $notes_service;
        $this->ahqwn_init_hooks();
    }

    /**
     * Initialize hooks for the ajax area.
     *
     * @since    1.0.0
     */
    private function ahqwn_init_hooks()
    {
        // Public Actions
        add_action('wp_ajax_add_note', array($this, 'ahqwn_ajax_add_note'));
        add_action('wp_ajax_nopriv_add_note', array($this, 'ahqwn_ajax_add_note'));
        add_action('wp_ajax_get_notes', array($this, 'ahqwn_ajax_get_notes'));
        add_action('wp_ajax_nopriv_get_notes', array($this, 'ahqwn_ajax_get_notes'));

        // Admin Actions
        add_action('wp_ajax_edit_note', array($this, 'ahqwn_ajax_edit_note'));
        add_action('wp_ajax_delete_note', array($this, 'ahqwn_ajax_delete_note'));
        add_action('wp_ajax_get_note_by_id', array($this, 'ahqwn_ajax_get_note_by_id'));
    }

    /**
     * Validate title and content
     *
     * @param string $title
     * @param string $content
     * @return bool
     */
    private function ahqwn_validate_title_content($title, $content)
    {
        if (empty($title) && !empty($content)) {
            wp_send_json_error('Title is required');
            return false;
        }
        return true;
    }

    /**
     * Add a new note
     *
     * @since 1.0.0
     */
    public function ahqwn_ajax_add_note()
    {
        check_ajax_referer('quick-web-notes-nonce', 'nonce');

        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $content = isset($_POST['content']) ? sanitize_textarea_field(wp_unslash($_POST['content'])) : '';

        if (!$this->ahqwn_validate_title_content($title, $content)) {
            return;
        }

        $result = $this->wpdb->insert(
            $this->table_name,
            array(
                'title' => $title,
                'content' => $content,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );

        if ($result) {
            wp_cache_delete('quick_web_notes_all', 'quick-web-notes');
            wp_send_json_success(array(
                'message' => 'Note added successfully',
                'note_id' => $this->wpdb->insert_id
            ));
        } else {
            wp_send_json_error('Failed to add note');
        }
    }

    /**
     * Get all notes
     *
     * @since 1.0.0
     */
    public function ahqwn_ajax_get_notes()
    {
        global $wpdb;
        check_ajax_referer('quick-web-notes-nonce', 'nonce');

        $cache_key = 'quick_web_notes_all';
        $notes = wp_cache_get($cache_key, 'quick-web-notes');

        if (false === $notes) {

            $table_name = $wpdb->prefix . 'quick_web_notes';

            // Remove wpdb::prepare since there are no variables to escape
            $notes = $wpdb->get_results(
                "SELECT * FROM {$table_name} ORDER BY created_at DESC"
            );

            if ($notes) {
                wp_cache_set($cache_key, $notes, 'quick-web-notes', HOUR_IN_SECONDS);
            }
        }

        wp_send_json_success($notes);
    }

    /**
     * Edit a note
     *
     * @since 1.0.0
     */
    public function ahqwn_ajax_edit_note()
    {
        check_ajax_referer('quick-web-notes-nonce', 'nonce');

        if (!isset($_POST['id'])) {
            wp_send_json_error('Invalid Note ID');
            return;
        }

        $id = absint(wp_unslash($_POST['id']));
        if ($id <= 0) {
            wp_send_json_error('Invalid Note ID');
            return;
        }

        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $content = isset($_POST['content']) ? sanitize_textarea_field(wp_unslash($_POST['content'])) : '';

        if (!$this->ahqwn_validate_title_content($title, $content)) {
            return;
        }

        $result = $this->wpdb->update(
            $this->table_name,
            array(
                'title' => $title,
                'content' => $content
            ),
            array('id' => $id),
            array('%s', '%s'),
            array('%d')
        );

        if ($result !== false) {
            wp_cache_delete('quick_web_notes_' . $id, 'quick-web-notes');
            wp_cache_delete('quick_web_notes_all', 'quick-web-notes');
            wp_send_json_success('Note updated successfully');
        } else {
            wp_send_json_error('Failed to update note');
        }
    }

    /**
     * Get a note by ID
     *
     * @since 1.0.0
     */
    public function ahqwn_ajax_get_note_by_id()
    {
        global $wpdb;
        check_ajax_referer('quick-web-notes-nonce', 'nonce');

        if (!isset($_POST['id'])) {
            wp_send_json_error('Invalid Note ID');
            return;
        }

        $id = absint(wp_unslash($_POST['id']));
        if ($id <= 0) {
            wp_send_json_error('Invalid Note ID');
            return;
        }

        $cache_key = 'quick_web_notes_' . $id;
        $note = wp_cache_get($cache_key, 'quick-web-notes');

        if (false === $note) {
            $note = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}quick_web_notes WHERE id = %d",
                    $id
                )
            );

            if ($note) {
                wp_cache_set($cache_key, $note, 'quick-web-notes', HOUR_IN_SECONDS);
            }
        }

        if ($note) {
            wp_send_json_success($note);
        } else {
            wp_send_json_error('Note not found');
        }
    }

    /**
     * Delete a note
     *
     * @since 1.0.0
     */
    public function ahqwn_ajax_delete_note()
    {
        global $wpdb;

        check_ajax_referer('quick-web-notes-nonce', 'nonce');

        if (!isset($_POST['id'])) {
            wp_send_json_error('Invalid Note ID');
            return;
        }

        $id = absint(wp_unslash($_POST['id']));

        if ($id <= 0) {
            wp_send_json_error('Invalid Note ID');
            return;
        }

        $cache_key = 'quick_web_notes_' . $id;
        $note = wp_cache_get($cache_key, 'quick-web-notes');

        if (false === $note) {
            $note = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}quick_web_notes WHERE id = %d",
                    $id
                )
            );

            if ($note) {
                wp_cache_set($cache_key, $note, 'quick-web-notes', HOUR_IN_SECONDS);
            }
        }

        if (!$note) {
            wp_send_json_error('Note not found');
            return;
        }

        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );

        if ($result !== false) {
            wp_cache_delete('quick_web_notes_' . $id, 'quick-web-notes');
            wp_cache_delete('quick_web_notes_all', 'quick-web-notes');
            wp_send_json_success('Note deleted successfully');
        } else {
            wp_send_json_error('Failed to delete note');
        }
    }
}
