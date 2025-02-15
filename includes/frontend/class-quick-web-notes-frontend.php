<?php
class Quick_Web_Notes_Frontend
{
    private $options_name = 'quick_web_notes_settings';
    private $wpdb;
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'quick_web_notes';

        $this->init_hooks();
    }

    private function init_hooks()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'render_frontend_modal'));
        add_shortcode('notes', array($this, 'render_frontend_notes'));
    }

    public function enqueue_scripts()
    {
        wp_enqueue_style(
            'quick-web-notes-style',
            plugin_dir_url(__FILE__) . '../../assets/css/quick-web-notes.css',
            array(),
            '1.0.0'
        );

        // Then add the dynamic positioning CSS
        $dynamic_css = $this->get_position_css();
        wp_add_inline_style('quick-web-notes-style', $dynamic_css);

        wp_enqueue_script(
            'quick-web-notes-script',
            plugin_dir_url(__FILE__) . '../../assets/js/quick-web-notes.js',
            array('jquery'),
            '1.0.0',
            array('in_footer' => true)
        );

        wp_localize_script(
            'quick-web-notes-script',
            'simpleNotes',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('quick-web-notes-nonce')
            )
        );
    }

    private function get_position_css()
    {
        // Try to get cached CSS first
        $cached_css = get_transient('quick_web_notes_position_css');
        if ($cached_css !== false) {
            return $cached_css;
        }

        // Generate new CSS
        $css = $this->generate_notes_position_css();

        // Cache the CSS for 12 hours
        set_transient('quick_web_notes_position_css', $css, 12 * HOUR_IN_SECONDS);

        return $css;
    }
    private function generate_notes_position_css()
    {
        $options = get_option($this->options_name);

        // Default values if settings aren't saved
        $defaults = array(
            'vertical_position' => 'bottom',
            'vertical_offset' => '20',
            'horizontal_position' => 'right',
            'horizontal_offset' => '30',
            'z_index' => '9998'
        );

        // Merge saved options with defaults
        $options = wp_parse_args($options, $defaults);

        return "
        .simple-notes-fixed-button {
            position: fixed !important;
            {$options['vertical_position']}: {$options['vertical_offset']}px !important;
            {$options['horizontal_position']}: {$options['horizontal_offset']}px !important;
            z-index: {$options['z_index']} !important;
        }";
    }

    public function render_frontend_modal()
    {
        ?>
        <!-- Fixed Button -->
        <div id="simple-notes-fixed-btn" class="simple-notes-fixed-button note-button">
            <button class="button"><img src="<?php echo plugins_url('../../assets/icons/note.png', __FILE__); ?>"
                    alt="Note Icon"></button>
        </div>

        <!-- Notes Modal -->
        <div id="simple-notes-modal" class="simple-notes-modal" style="display: none;">
            <div class="simple-notes-modal-content">
                <div class="simple-notes-modal-header">
                    <h2>Notes</h2>
                    <button class="simple-notes-close">&times;</button>
                </div>
                <div class="simple-notes-modal-body">
                    <button id="simple-notes-add-btn" class="button button-primary">Add New Note</button>
                    <div id="simple-notes-list"></div>
                </div>
            </div>
        </div>

        <!-- Add Note Modal -->
        <div id="simple-notes-add-modal" class="simple-notes-modal" style="display: none;">
            <div class="simple-notes-modal-content">
                <div class="simple-notes-modal-header">
                    <h2>Add New Note</h2>
                    <button class="simple-notes-close">&times;</button>
                </div>
                <div class="simple-notes-modal-body">
                    <form id="simple-notes-add-form">
                        <p>
                            <label for="modal_note_title">Title: <span class="required">*</span></label><br>
                            <input type="text" id="modal_note_title" required class="regular-text">
                        </p>
                        <p>
                            <label for="modal_note_content">Content: </label><br>
                            <textarea id="modal_note_content" class="large-text" rows="4"></textarea>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary">Save Note</button>
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Note Modal -->
        <div id="simple-notes-edit-modal" class="simple-notes-modal" style="display: none;">
            <div class="simple-notes-modal-content">
                <div class="simple-notes-modal-header">
                    <h2>Edit Note</h2>
                    <button class="simple-notes-close">&times;</button>
                </div>
                <div class="simple-notes-modal-body">
                    <form id="simple-notes-edit-form">
                        <input type="hidden" id="edit_note_id">
                        <p>
                            <label for="edit_note_title">Title: <span class="required">*</span></label><br>
                            <input type="text" id="edit_note_title" required class="regular-text">
                        </p>
                        <p>
                            <label for="edit_note_content">Content: </label><br>
                            <textarea id="edit_note_content" class="large-text" rows="4"></textarea>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary">Update Note</button>
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <?php
    }

    public function render_frontend_notes()
    {
        $notes = $this->get_all_notes();

        ob_start();
        ?>
        <div class="notes-container">
            <button id="toggleNotes" class="button">Show Notes</button>
            <div id="notesContent" style="display: none;">
                <?php foreach ($notes as $note): ?>
                    <div class="note-item">
                        <h3><?php echo esc_html($note->title); ?></h3>
                        <p><?php echo esc_html($note->content); ?></p>
                        <small>Created: <?php echo esc_html($note->created_at); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_all_notes()
    {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY created_at DESC"
        );
    }
}