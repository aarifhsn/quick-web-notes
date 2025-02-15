<?php
// Check if this file is called directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current sort parameters
$orderby = isset($_GET['orderby']) ? $_GET['orderby'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$reverse_order = $order === 'DESC' ? 'ASC' : 'DESC';

// Generate sorting URLs
$title_sort_url = add_query_arg(array(
    'orderby' => 'title',
    'order' => ($orderby === 'title' ? $reverse_order : 'ASC')
));
$date_sort_url = add_query_arg(array(
    'orderby' => 'created_at',
    'order' => ($orderby === 'created_at' ? $reverse_order : 'DESC')
));
?>

<form id="notes-list" method="post">
    <?php wp_nonce_field('bulk-notes'); ?>
    <input type="hidden" name="page" value="simple-notes-manager">

    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <select name="action">
                <option value="-1"><?php _e('Bulk Actions', 'quick-web-notes'); ?></option>
                <option value="bulk-delete"><?php _e('Delete', 'quick-web-notes'); ?></option>
            </select>
            <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'quick-web-notes'); ?>">
        </div>
    </div>
    <table class=" wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <td class="manage-column column-cb check-column">
                    <input type="checkbox" id="cb-select-all-1">
                </td>
                <th scope="col"
                    class="manage-column column-title sortable <?php echo $orderby === 'title' ? 'sorted' : ''; ?> <?php echo $order; ?>">
                    <a href="<?php echo esc_url($title_sort_url); ?>">
                        <span>Title</span>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th>Content</th>
                <th scope="col"
                    class="manage-column column-date sortable <?php echo $orderby === 'created_at' ? 'sorted' : ''; ?> <?php echo $order; ?>">
                    <a href="<?php echo esc_url($date_sort_url); ?>">
                        <span>Created At</span>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($notes)): ?>
                <tr>
                    <td colspan="5">No notes found.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($notes as $note): ?>
                <tr data-id="<?php echo esc_attr($note->id); ?>">
                    <th scope="row" class="check-column">
                        <input type="checkbox" name="note_ids[]" value="<?php echo esc_attr($note->id); ?>">
                    </th>
                    <td><span class="note-title"><?php echo esc_html($note->title); ?></span></td>
                    <td><span class="note-content"><?php echo esc_html($note->content); ?></span></td>
                    <td><?php echo esc_html($note->created_at); ?></td>

                    <td>
                        <button type="button" class="button button-small edit-note-admin">Edit</button>

                        <a href="<?php echo wp_nonce_url(
                            admin_url('admin.php?page=simple-notes-manager&action=delete&id=' . $note->id),
                            'delete_note_' . $note->id,
                            'delete_nonce'
                        ); ?>" onclick="return confirm('Are you sure?')" class="button button-small">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td class="manage-column column-cb check-column">
                    <input type="checkbox" id="cb-select-all-2">
                </td>
                <th scope="col"
                    class="manage-column column-title sortable <?php echo $orderby === 'title' ? 'sorted' : ''; ?> <?php echo $order; ?>">
                    <a href="<?php echo esc_url($title_sort_url); ?>">
                        <span>Title</span>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th>Content</th>
                <th scope="col"
                    class="manage-column column-date sortable <?php echo $orderby === 'created_at' ? 'sorted' : ''; ?> <?php echo $order; ?>">
                    <a href="<?php echo esc_url($date_sort_url); ?>">
                        <span>Created At</span>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th>Actions</th>
            </tr>
        </tfoot>
    </table>

    <div class="tablenav bottom">
        <div class="alignleft actions bulkactions">
            <select name="action2">
                <option value="-1"><?php _e('Bulk Actions', 'quick-web-notes'); ?></option>
                <option value="bulk-delete"><?php _e('Delete', 'quick-web-notes'); ?></option>
            </select>
            <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'quick-web-notes'); ?>">
        </div>
    </div>
</form>