<?php

class Quick_Web_Notes_Admin_Settings
{
    private $options_group = 'notes_manager_options';
    private $options_name = 'quick_web_notes_settings';

    public function __construct()
    {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));

        // Admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function enqueue_admin_assets($hook)
    {
        // Only load on our plugin's settings page
        if ('notes-manager_page_quick-web-notes-settings' !== $hook) {
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

    public function add_settings_page()
    {
        add_submenu_page(
            'quick-web-notes-manager',
            'Settings',
            'Settings',
            'manage_options',
            'quick-web-notes-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings()
    {
        register_setting(
            $this->options_group,
            $this->options_name,
            array($this, 'sanitize_settings')
        );

        add_settings_section(
            'position_section',
            'Icon Position Settings',
            array($this, 'position_section_callback'),
            'notes_manager_settings'
        );

        // Vertical Position (top/bottom)
        add_settings_field(
            'vertical_position',
            'Vertical Position',
            array($this, 'vertical_position_callback'),
            'notes_manager_settings',
            'position_section'
        );

        // Vertical Offset
        add_settings_field(
            'vertical_offset',
            'Vertical Offset (px)',
            array($this, 'vertical_offset_callback'),
            'notes_manager_settings',
            'position_section'
        );

        // Horizontal Position (left/right)
        add_settings_field(
            'horizontal_position',
            'Horizontal Position',
            array($this, 'horizontal_position_callback'),
            'notes_manager_settings',
            'position_section'
        );

        // Horizontal Offset
        add_settings_field(
            'horizontal_offset',
            'Horizontal Offset (px)',
            array($this, 'horizontal_offset_callback'),
            'notes_manager_settings',
            'position_section'
        );

        // Z-index
        add_settings_field(
            'z_index',
            'Z-Index',
            array($this, 'z_index_callback'),
            'notes_manager_settings',
            'position_section'
        );
    }

    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>

        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="settings_container">
                <form method="post" action="options.php">
                    <?php
                    settings_fields($this->options_group);
                    do_settings_sections('notes_manager_settings');
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

                $('select, input').on('change input', updatePreview);
                updatePreview();
            });
        </script>

        <?php
    }

    public function position_section_callback()
    {
        echo '<p>Configure the position of your notes icon on the page.</p>';
    }

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

    public function vertical_offset_callback()
    {
        $options = get_option($this->options_name);
        $value = isset($options['vertical_offset']) ? $options['vertical_offset'] : '20';
        ?>
        <input type="number" name="<?php echo esc_attr($this->options_name); ?>[vertical_offset]"
            value="<?php echo esc_attr($value); ?>" min="0" max="1000">
        <?php
    }

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

    public function horizontal_offset_callback()
    {
        $options = get_option($this->options_name);
        $value = isset($options['horizontal_offset']) ? $options['horizontal_offset'] : '30';
        ?>
        <input type="number" name="<?php echo esc_attr($this->options_name); ?>[horizontal_offset]"
            value="<?php echo esc_attr($value); ?>" min="0" max="1000">
        <?php
    }

    public function z_index_callback()
    {
        $options = get_option($this->options_name);
        $value = isset($options['z_index']) ? $options['z_index'] : '9998';
        ?>
        <input type="number" name="<?php echo esc_attr($this->options_name); ?>[z_index]"
            value="<?php echo esc_attr($value); ?>" min="1" max="99999">
        <?php
    }

    public function sanitize_settings($input)
    {
        $sanitized = array();

        $sanitized['vertical_position'] = sanitize_text_field($input['vertical_position']);
        $sanitized['vertical_offset'] = absint($input['vertical_offset']);
        $sanitized['horizontal_position'] = sanitize_text_field($input['horizontal_position']);
        $sanitized['horizontal_offset'] = absint($input['horizontal_offset']);
        $sanitized['z_index'] = absint($input['z_index']);

        // Clear transient cache
        delete_transient('simple_notes_position_css');

        return $sanitized;
    }
}