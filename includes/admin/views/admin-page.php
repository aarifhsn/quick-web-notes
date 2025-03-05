<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
?>

<div class="wrap">

    <h1 class="page_title">Quick Web Notes</h1>

    <h2 class="button admin_add_new_note_button">Add New Note</h2>
    <form method="post" id="add-note-form" class="admin_add_new_note_form">
        <?php wp_nonce_field('add_note', 'note_nonce'); ?>

        <div class="admin_add_new_note_close_button">&times;</div>
        <p>
            <label for="note_title">Title:</label><br>
            <input type="text" name="note_title" id="note_title" required class="regular-text">
        </p>
        <p>
            <label for="note_content">Content:</label><br>
            <textarea name="note_content" id="note_content" class="large-text" rows="3"></textarea>
        </p>
        <p>
            <input type="submit" name="submit_note" class="button button-primary" value="Add Note">
        </p>
    </form>

    <div id="notes-table-container" class="notes_table_container">
        <h2>All Notes</h2>
        <?php $this->ahqwn_render_notes_table($notes, $orderby, $order); ?>
    </div>
</div>