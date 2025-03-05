<?php
/**
 * Quick Web Notes Frontend Class
 * 
 * This class handles the frontend functionality of the plugin
 * 
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

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

        add_action('init', array($this, 'ahqwn_init_hooks'));
    }

    /**
     * Initialize hooks for the frontend area.
     *
     * @since    1.0.0
     */
    public function ahqwn_init_hooks()
    {
        add_shortcode('ahqwn_notes', array($this, 'ahqwn_render_frontend_notes'));
        add_action('wp_enqueue_scripts', array($this, 'ahqwn_enqueue_scripts'));
        add_action('wp_footer', array($this, 'ahqwn_render_frontend_modal'));
    }

    /**
     * Enqueue scripts and styles for the frontend area.
     *
     * @since    1.0.0
     */
    public function ahqwn_enqueue_scripts()
    {
        if (is_user_logged_in() && current_user_can('manage_options')) {
            wp_enqueue_style(
                'quick-web-notes-style',
                plugin_dir_url(__FILE__) . '../../assets/css/quick-web-notes.css',
                array(),
                '1.0.0'
            );

            // Then add the dynamic positioning CSS
            $dynamic_css = $this->ahqwn_get_position_css();
            wp_add_inline_style('quick-web-notes-style', esc_html($dynamic_css));

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
    }

    /**
     * Get the dynamic CSS for the notes position.
     *
     * @since    1.0.0
     */
    private function ahqwn_get_position_css()
    {
        // Try to get cached CSS first
        $cached_css = get_transient('quick_web_notes_position_css');
        if ($cached_css !== false) {
            return $cached_css;
        }

        // Generate new CSS
        $css = $this->ahqwn_generate_notes_position_css();

        // Cache the CSS for 12 hours
        set_transient('quick_web_notes_position_css', $css, 12 * HOUR_IN_SECONDS);

        return $css;
    }

    /**
     * Generate the CSS for the notes position.
     *
     * @since    1.0.0
     */
    private function ahqwn_generate_notes_position_css()
    {
        $options = get_option($this->options_name);

        // Default values if settings aren't saved
        $defaults = array(
            'vertical_position' => 'bottom',
            'vertical_offset' => '20',
            'horizontal_position' => 'right',
            'horizontal_offset' => '30',
            'z_index' => '9998',
            'background_color' => '#0073aa',
            'icon_url' => plugins_url('assets/icons/note.png', dirname(dirname(__FILE__)))
        );

        // Merge saved options with defaults
        $options = wp_parse_args($options, $defaults);

        // Use esc_url to properly escape the URL
        $icon_url = esc_url($options['icon_url']);

        return "
        .simple-notes-fixed-button {
            position: fixed !important;
            {$options['vertical_position']}: {$options['vertical_offset']}px !important;
            {$options['horizontal_position']}: {$options['horizontal_offset']}px !important;
            z-index: {$options['z_index']} !important;
            background: {$options['background_color']} !important;
        }
            
        .simple-notes-fixed-button .button {
        background-image: url({$icon_url}) !important;
        background-repeat: no-repeat !important;
        background-position: center !important;
        background-size: contain !important;
        width: 20px !important;
        height: 20px !important;
        border: none;
        background-color: transparent;
        color: transparent;
        }";
    }

    /**
     * Render the frontend modal.
     *
     * @since    1.0.0
     */
    public function ahqwn_render_frontend_modal()
    {
        if (is_user_logged_in() && current_user_can('manage_options')) {
            ?>
            <!-- Fixed Button -->
            <div id="simple-notes-fixed-btn" class="simple-notes-fixed-button note-button">
                <button class="button" aria-label="<?php esc_attr_e('Notes', 'quick-web-notes'); ?>">
                    <span class="screen-reader-text"><?php esc_html_e('Notes', 'quick-web-notes'); ?></span>
                </button>
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
    }

    /**
     * Render the frontend notes using shortcode.
     *
     * @since    1.0.0
     */
    public function ahqwn_render_frontend_notes($atts = [], $content = null)
    {
        // Start output buffering
        ob_start();

        $notes = $this->ahqwn_get_all_notes();
        ?>
        <div class="notes-container">
            <div class="notes-content">
                <?php if (!empty($notes)): ?>
                    <?php foreach ($notes as $note): ?>
                        <div class="note-item">
                            <h3><?php echo esc_html($note->title); ?></h3>
                            <p><?php echo esc_html($note->content); ?></p>
                            <small>Created: <?php echo esc_html($note->created_at); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No notes found.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php

        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Get all notes from the database.
     *
     * @since    1.0.0
     */
    private function ahqwn_get_all_notes()
    {
        $table_name = esc_sql($this->table_name);
        return $this->wpdb->get_results(
            "SELECT * FROM {$table_name} ORDER BY created_at DESC"
        );
    }
}