<?php
// Check if this file is called directly
if (!defined('ABSPATH')) {
    exit;
}

// Verify nonce for sorting
$sort_nonce = wp_create_nonce('quick_web_notes_sort');

// Check if the nonce is set and valid before processing GET parameters
if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash(($_GET['_wpnonce']))), 'quick_web_notes_sort')) {
    $orderby = isset($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : 'created_at';
    $order = isset($_GET['order']) ? sanitize_text_field(wp_unslash($_GET['order'])) : 'DESC';
} else {
    // If nonce verification fails, use default values
    $orderby = 'created_at';
    $order = 'DESC';
}


// Validate orderby parameter
$allowed_orderby = array('title', 'created_at');
$orderby = in_array($orderby, $allowed_orderby) ? $orderby : 'created_at';

// Validate order parameter
$allowed_order = array('ASC', 'DESC');
$order = in_array(strtoupper($order), $allowed_order) ? $order : 'DESC';

$reverse_order = $order === 'DESC' ? 'ASC' : 'DESC';

// Generate sorting URLs with nonce
$title_sort_url = add_query_arg(array(
    'orderby' => 'title',
    'order' => ($orderby === 'title' ? $reverse_order : 'ASC'),
    '_wpnonce' => $sort_nonce
), admin_url(
    'admin.php?page=quick-web-notes-manager'
));

$date_sort_url = add_query_arg(array(
    'orderby' => 'created_at',
    'order' => ($orderby === 'created_at' ? $reverse_order : 'DESC'),
    '_wpnonce' => $sort_nonce
), admin_url(
    'admin.php?page=quick-web-notes-manager'
));
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Notes Manager', 'quick-web-notes'); ?></h1>

    <form id="notes-list" method="post">
        <input type="hidden" name="page" value="quick-web-notes-manager">
        <?php wp_nonce_field('bulk-notes'); ?>

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-top" class="screen-reader-text">
                    <?php esc_html_e('Select bulk action', 'quick-web-notes'); ?>
                </label>
                <select name="action" id="bulk-action-selector-top">
                    <option value="-1"><?php esc_html_e('Bulk Actions', 'quick-web-notes'); ?></option>
                    <option value="bulk-delete"><?php esc_html_e('Delete', 'quick-web-notes'); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'quick-web-notes'); ?>">
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">
                            <?php esc_html_e('Select All', 'quick-web-notes'); ?>
                        </label>
                        <input type="checkbox" id="cb-select-all-1">
                    </td>
                    <th scope="col"
                        class="manage-column column-title sortable <?php echo esc_attr($orderby === 'title' ? 'sorted' : ''); ?> <?php echo esc_attr($order); ?>">
                        <a href="<?php echo esc_url($title_sort_url); ?>">
                            <span><?php esc_html_e('Title', 'quick-web-notes'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php esc_html_e('Content', 'quick-web-notes'); ?>
                    </th>
                    <th scope="col"
                        class="manage-column column-date sortable <?php echo esc_attr($orderby === 'created_at' ? 'sorted' : ''); ?> <?php echo esc_attr($order); ?>">
                        <a href="<?php echo esc_url($date_sort_url); ?>">
                            <span><?php esc_html_e('Created At', 'quick-web-notes'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php esc_html_e('Actions', 'quick-web-notes'); ?>
                    </th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($notes)): ?>
                    <tr>
                        <td colspan="5"><?php esc_html_e('No notes found.', 'quick-web-notes'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($notes as $note): ?>
                        <tr data-id="<?php echo esc_attr($note->id); ?>">
                            <th scope="row" class="check-column">
                                <label class="screen-reader-text" for="cb-select-<?php echo esc_attr($note->id); ?>">
                                    <?php
                                    /* translators: %s: note title */
                                    printf(esc_html__('Select note %s', 'quick-web-notes'), esc_html($note->title));
                                    ?>
                                </label>
                                <input type="checkbox" id="cb-select-<?php echo esc_attr($note->id); ?>" name="note_ids[]"
                                    value="<?php echo esc_attr($note->id); ?>">
                            </th>
                            <td>
                                <span class="note-title"><strong><?php echo esc_html($note->title); ?></strong></span>
                            </td>
                            <td>
                                <span class="note-content"><?php echo esc_html($note->content); ?></span>
                            </td>
                            <td>
                                <?php echo esc_html(mysql2date(get_option('date_format'), $note->created_at)); ?>
                            </td>
                            <td class="actions">
                                <button type="button" class="button button-small edit-note-admin"
                                    data-id="<?php echo esc_attr($note->id); ?>" aria-label="<?php
                                       /* translators: %s: note title */
                                       printf(esc_attr__('Edit note %s', 'quick-web-notes'), esc_attr($note->title));
                                       ?>">
                                    <?php esc_html_e('Edit', 'quick-web-notes'); ?>
                                </button>

                                <?php
                                $delete_url = wp_nonce_url(
                                    add_query_arg(
                                        array(
                                            'page' => 'quick-web-notes-manager',
                                            'action' => 'delete',
                                            'id' => $note->id,
                                            'delete_nonce' => wp_create_nonce('delete_note_' . $note->id)
                                        ),
                                        admin_url('admin.php')
                                    ),
                                    'delete_note_' . $note->id,
                                    'delete_nonce'
                                );
                                ?>

                                <a href="<?php echo esc_url($delete_url); ?>" class="button button-small delete-note"
                                    onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this note?', 'quick-web-notes'); ?>')"
                                    aria-label="<?php
                                    /* translators: %s: note title */
                                    printf(esc_attr__('Delete note %s', 'quick-web-notes'), esc_attr($note->title));
                                    ?>">
                                    <?php esc_html_e('Delete', 'quick-web-notes'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>

            <tfoot>
                <tr>
                    <?php // Duplicate of header row for large tables ?>
                    <td class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-2">
                            <?php esc_html_e('Select All', 'quick-web-notes'); ?>
                        </label>
                        <input type="checkbox" id="cb-select-all-2">
                    </td>
                    <th scope="col"
                        class="manage-column column-title sortable <?php echo esc_attr($orderby === 'title' ? 'sorted' : ''); ?> <?php echo esc_attr($order); ?>">
                        <a href="<?php echo esc_url($title_sort_url); ?>">
                            <span><?php esc_html_e('Title', 'quick-web-notes'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php esc_html_e('Content', 'quick-web-notes'); ?>
                    </th>
                    <th scope="col"
                        class="manage-column column-date sortable <?php echo esc_attr($orderby === 'created_at' ? 'sorted' : ''); ?> <?php echo esc_attr($order); ?>">
                        <a href="<?php echo esc_url($date_sort_url); ?>">
                            <span><?php esc_html_e('Created At', 'quick-web-notes'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php esc_html_e('Actions', 'quick-web-notes'); ?>
                    </th>
                </tr>
            </tfoot>
        </table>

        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-bottom" class="screen-reader-text">
                    <?php esc_html_e('Select bulk action', 'quick-web-notes'); ?>
                </label>
                <select name="action2" id="bulk-action-selector-bottom">
                    <option value="-1"><?php esc_html_e('Bulk Actions', 'quick-web-notes'); ?></option>
                    <option value="bulk-delete"><?php esc_html_e('Delete', 'quick-web-notes'); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'quick-web-notes'); ?>">
            </div>
        </div>
    </form>
</div>