jQuery(document).ready(function ($) {
  // Edit note in admin dashboard
  $(document).on("click", ".edit-note-admin", function () {
    const row = $(this).closest("tr");
    const titleSpan = row.find(".note-title");
    const contentSpan = row.find(".note-content");

    // Store original values
    const originalTitle = titleSpan.text();
    const originalContent = contentSpan.text();

    // Create input fields
    titleSpan.html(
      `<input type="text" class="regular-text" value="${originalTitle.replace(
        /"/g,
        "&quot;"
      )}">`
    );
    contentSpan.html(
      `<textarea class="large-text" rows="3">${originalContent}</textarea>`
    );

    // Change button to Save and add Cancel
    $(this).after(
      `<button type="button" class="button button-small cancel-edit-admin">Cancel</button>`
    );
    $(this)
      .text("Save")
      .removeClass("edit-note-admin")
      .addClass("save-note-admin");
  });

  // Cancel edit
  $(document).on("click", ".cancel-edit-admin", function () {
    const row = $(this).closest("tr");
    const titleSpan = row.find(".note-title");
    const contentSpan = row.find(".note-content");
    const saveButton = row.find(".save-note-admin");

    // Get original text from input/textarea
    const originalTitle = titleSpan.find("input").data("original");
    const originalContent = contentSpan.find("textarea").data("original");

    // Restore text content
    titleSpan.text(originalTitle);
    contentSpan.text(originalContent);

    // Restore edit button
    saveButton
      .text("Edit")
      .removeClass("save-note-admin")
      .addClass("edit-note-admin");

    // Remove cancel button
    $(this).remove();
  });

  // Save edited note
  $(document).on("click", ".save-note-admin", function () {
    const row = $(this).closest("tr");
    const titleInput = row.find(".note-title input");
    const contentTextarea = row.find(".note-content textarea");

    const id = row.data("id");
    const title = titleInput.val();
    const content = contentTextarea.val();

    // Validate fields
    if (!title || !content) {
      alert("Both title and content are required!");
      return;
    }

    $.ajax({
      url: simpleNotesAdmin.ajaxurl,
      type: "POST",
      data: {
        action: "admin_edit_note",
        nonce: simpleNotesAdmin.nonce,
        id: id,
        title: title,
        content: content,
      },
      success: function (response) {
        if (response.success) {
          // Update the display
          titleInput.parent().text(title);
          contentTextarea.parent().text(content);

          // Reset the buttons
          row
            .find(".save-note-admin")
            .text("Edit")
            .removeClass("save-note-admin")
            .addClass("edit-note-admin");

          // Remove cancel button
          row.find(".cancel-edit-admin").remove();
        } else {
          alert("Error: " + response.data);
        }
      },
      error: function (xhr, status, error) {
        alert("Error occurred while updating note: " + error);
      },
    });
  });

  // Handle "Select All" checkboxes
  $("#cb-select-all-1, #cb-select-all-2").click(function () {
    $('input[name="note_ids[]"]').prop("checked", $(this).prop("checked"));
  });

  // Confirm bulk delete
  $("#notes-list").submit(function (e) {
    if ($('select[name="action"]').val() === "bulk-delete") {
      if (!confirm("Are you sure you want to delete these notes?")) {
        e.preventDefault();
      }
    }
  });

  // Keep select all checkboxes in sync
  $("#cb-select-all-1").change(function () {
    $("#cb-select-all-2").prop("checked", $(this).prop("checked"));
  });
  $("#cb-select-all-2").change(function () {
    $("#cb-select-all-1").prop("checked", $(this).prop("checked"));
  });

  // when clicked the button, show the form

  $(".admin_add_new_note_form").hide(); // Hide form initially

  $(".admin_add_new_note_button").click(function () {
    $(".admin_add_new_note_form").show(300);
  });
  $(".admin_add_new_note_close_button").click(function () {
    $(".admin_add_new_note_form").hide(300);
  });
});
