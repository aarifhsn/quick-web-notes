<?php
/** 
 * Quick Web Notes Admin Settings Class
 * 
 * This class is used to create the settings page for the plugin
 * 
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Quick_Web_Notes_Admin_Settings
{
    private $options_group = 'quick_web_notes_options';
    private $options_name = 'quick_web_notes_settings';
    private $page_name = 'quick-web-notes-settings';

    /**
     * Quick_Web_Notes_Admin_Settings constructor.
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct()
    {
        add_action('admin_init', array($this, 'qwn_register_settings'));
        add_action('admin_menu', array($this, 'qwn_add_settings_page'));

        // Admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'qwn_enqueue_admin_assets'));
    }

    /**
     * Enqueue admin assets
     *
     * @since 1.0.0
     * @access public
     */
    public function qwn_enqueue_admin_assets($hook)
    {

        // Only load on our plugin's settings page
        if ('quick-web-notes_page_quick-web-notes-settings' !== $hook) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'quick-web-notes-admin-style',
            plugins_url('assets/css/quick-web-notes-admin-style.css', dirname(dirname(__FILE__))),
            array(),
            '1.0.0'
        );
    }

    /**
     * Add settings page
     *
     * @since 1.0.0
     * @access public
     */
    public function qwn_add_settings_page()
    {
        add_submenu_page(
            'quick-web-notes-manager',
            'Settings',
            'Settings',
            'manage_options',
            'quick-web-notes-settings',
            array($this, 'qwn_render_settings_page')
        );
    }

    /**
     * Register settings
     *
     * @since 1.0.0
     * @access public
     */
    public function qwn_register_settings()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        register_setting(
            $this->options_group,
            $this->options_name,
            [
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'show_in_rest' => false,
                'default' => [
                    'vertical_position' => 'bottom',
                    'horizontal_position' => 'right',
                    'vertical_offset' => 20,
                    'horizontal_offset' => 30,
                    'z_index' => 9998
                ]
            ]
        );



        add_settings_section(
            'position_section',
            'Icon Position Settings',
            array($this, 'qwn_position_section_callback'),
            $this->page_name
        );

        // Settings Fields
        $this->qwn_add_settings_fields();
    }

    /**
     * Add settings fields
     *
     * @since 1.0.0
     * @access public
     */
    public function qwn_add_settings_fields()
    {
        $fields = [
            'vertical_position' => 'Vertical Position',
            'vertical_offset' => 'Vertical Offset (px)',
            'horizontal_position' => 'Horizontal Position',
            'horizontal_offset' => 'Horizontal Offset (px)',
            'z_index' => 'Z-Index'
        ];

        foreach ($fields as $key => $label) {
            add_settings_field(
                $key,
                $label,
                array($this, $key . '_callback'),
                $this->page_name,
                'position_section'
            );
        }
    }

    /**
     * Render settings page
     *
     * @since 1.0.0
     * @access public
     */
    public function qwn_render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        wp_nonce_field('quick_web_notes_settings_action', 'quick_web_notes_settings_nonce');

        $options = get_option($this->options_name);
        ?>

        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="settings_container">
                <form method="post" action="options.php">
                    <?php
                    settings_fields($this->options_group);
                    do_settings_sections($this->page_name);
                    submit_button();
                    ?>
                </form>

                <div class="position-preview" style="margin-top: 20px; padding: 20px; background: #f0f0f0; border-radius: 5px;">
                    <h3>Preview</h3>
                    <div id="position-preview-box"
                        style="position: relative; width: 300px; height: 200px; border: 1px solid #ccc; background: #fff; margin: 20px 0;">
                        <div id="preview-icon"
                            style="width: 30px; height: 30px; background: #0073aa; border-radius: 50%; position: absolute;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                function updatePreview() {
                    var verticalPos = $('select[name="<?php echo esc_attr($this->options_name); ?>[vertical_position]"]').val();
                    var horizontalPos = $('select[name="<?php echo esc_attr($this->options_name); ?>[horizontal_position]"]').val();
                    var verticalOffset = $('input[name="<?php echo esc_attr($this->options_name); ?>[vertical_offset]"]').val();
                    var horizontalOffset = $('input[name="<?php echo esc_attr($this->options_name); ?>[horizontal_offset]"]').val();

                    $('#preview-icon').css({
                        'top': verticalPos === 'top' ? verticalOffset + 'px' : 'auto',
                        'bottom': verticalPos === 'bottom' ? verticalOffset + 'px' : 'auto',
                        'left': horizontalPos === 'left' ? horizontalOffset + 'px' : 'auto',
                        'right': horizontalPos === 'right' ? horizontalOffset + 'px' : 'auto'
                    });
                }

                // Bind events and initial update
                $('select, input').on('change input', updatePreview);
                updatePreview();
            });
        </script>

        <?php
    }

    /**
     * Callbacks for settings fields
     */
    public function qwn_position_section_callback()
    {
        echo '<p>Configure the position of your notes icon on the page.</p>';
    }

    /**
     * Callbacks for settings fields
     * 
     * @since 1.0.0
     */
    public function vertical_position_callback()
    {
        $options = get_option($this->options_name);
        $value = isset($options['vertical_position']) ? $options['vertical_position'] : 'bottom';
        ?>
        <select name="<?php echo esc_attr($this->options_name); ?>[vertical_position]">
            <option value="top" <?php selected($value, 'top'); ?>>Top</option>
            <option value="bottom" <?php selected($value, 'bottom'); ?>>Bottom</option>
        </select>
        <?php
    }

    /**
     * Callbacks for settings fields
     * 
     * @since 1.0.0
     */
    public function vertical_offset_callback()
    {
        $options = get_option($this->options_name);
        $value = isset($options['vertical_offset']) ? $options['vertical_offset'] : '20';
        ?>
        <input type="number" name="<?php echo esc_attr($this->options_name); ?>[vertical_offset]"
            value="<?php echo esc_attr($value); ?>" min="0" max="1000">
        <?php
    }

    /**
     * Callbacks for settings fields
     * 
     * @since 1.0.0
     */
    public function horizontal_position_callback()
    {
        $options = get_option($this->options_name);
        $value = isset($options['horizontal_position']) ? $options['horizontal_position'] : 'right';
        ?>
        <select name="<?php echo esc_attr($this->options_name); ?>[horizontal_position]">
            <option value="left" <?php selected($value, 'left'); ?>>Left</option>
            <option value="right" <?php selected($value, 'right'); ?>>Right</option>
        </select>
        <?php
    }

    /**
     * Callbacks for settings fields
     * 
     * @since 1.0.0
     */
    public function horizontal_offset_callback()
    {
        $options = get_option($this->options_name);
        $value = isset($options['horizontal_offset']) ? $options['horizontal_offset'] : '30';
        ?>
        <input type="number" name="<?php echo esc_attr($this->options_name); ?>[horizontal_offset]"
            value="<?php echo esc_attr($value); ?>" min="0" max="1000">
        <?php
    }

    /**
     * Callbacks for settings fields
     * 
     * @since 1.0.0
     */
    public function z_index_callback()
    {
        $options = get_option($this->options_name);
        $value = isset($options['z_index']) ? $options['z_index'] : '9998';
        ?>
        <input type="number" name="<?php echo esc_attr($this->options_name); ?>[z_index]"
            value="<?php echo esc_attr($value); ?>" min="1" max="99999">
        <?php
    }

    /**
     * Sanitize settings
     * 
     * @since 1.0.0
     */
    public function sanitize_settings($input)
    {
        // If input is not an array, return defaults
        if (!is_array($input)) {
            return [
                'vertical_position' => 'bottom',
                'horizontal_position' => 'right',
                'vertical_offset' => 20,
                'horizontal_offset' => 30,
                'z_index' => 9998
            ];
        }

        $sanitized = [];

        $sanitized['vertical_position'] = isset($input['vertical_position']) && in_array($input['vertical_position'], array('top', 'bottom'), true) ? $input['vertical_position'] : 'bottom';

        $sanitized['horizontal_position'] = isset($input['horizontal_position']) && in_array($input['horizontal_position'], array('left', 'right'), true) ? $input['horizontal_position'] : 'right';

        // Offsets
        $sanitized['vertical_offset'] = isset($input['vertical_offset']) ?
            absint($input['vertical_offset']) : 20;
        $sanitized['horizontal_offset'] = isset($input['horizontal_offset']) ?
            absint($input['horizontal_offset']) : 30;

        $sanitized['z_index'] = isset($input['z_index']) && is_numeric($input['z_index']) ? $input['z_index'] : 9998; // Default 9998

        // Clear transient cache
        delete_transient('quick_web_notes_position_css');

        return $sanitized;
    }
}