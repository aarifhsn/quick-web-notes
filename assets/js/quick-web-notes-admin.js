jQuery(document).ready(function ($) {
  // Edit note in admin dashboard
  $(document).on("click", ".edit-note-admin", function () {
    const row = $(this).closest("tr");
    const titleSpan = row.find(".note-title");
    const contentSpan = row.find(".note-content");

    // Store original values
    const originalTitle = titleSpan.text().trim();
    const originalContent = contentSpan.text().trim();

    // Create input fields
    titleSpan.html(
      `<input type="text" class="regular-text" value="${originalTitle.replace(
        /"/g,
        "&quot;"
      )}" data-original="${originalTitle.replace(/"/g, "&quot;")}">`
    );
    contentSpan.html(
      `<textarea class="large-text" rows="3" data-original="${originalContent.replace(
        /"/g,
        "&quot;"
      )}">${originalContent}</textarea>`
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
    titleSpan.html(`<strong>${originalTitle}</strong>`);
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
    if (!title) {
      alert("Please fill the required field");
      return;
    }

    $.ajax({
      url: quickWebNotesAdmin.ajaxurl,
      type: "POST",
      data: {
        action: "admin_edit_note",
        nonce: quickWebNotesAdmin.nonce,
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

  // First, ensure the form is hidden on page load
  $(".admin_add_new_note_form").hide();

  // Button click handlers with error checking
  $(document).on("click", ".admin_add_new_note_button", function () {
    const $form = $(".admin_add_new_note_form");
    if ($form.length) {
      $form.show(300);
    }
  });

  $(document).on("click", ".admin_add_new_note_close_button", function () {
    const $form = $(".admin_add_new_note_form");
    if ($form.length) {
      $form.hide(300);
    }
  });

  // inline scripts

  // Media uploader
  let mediaUploader;

  $("#upload_icon_button").click(function (e) {
    e.preventDefault();
    if (mediaUploader) {
      mediaUploader.open();
      return;
    }
    mediaUploader = wp.media({
      title: "Select Icon",
      button: {
        text: "Use this image",
      },
      multiple: false,
    });

    mediaUploader.on("select", function () {
      let attachment = mediaUploader.state().get("selection").first().toJSON();
      $("#icon_url").val(attachment.url);
      $("#icon_attachment_id").val(attachment.id);

      // Update preview using background-image
      $("#icon_preview").css({
        "background-image": "url(" + attachment.url + ")",
      });
    });
    mediaUploader.open();
  });

  function updatePreview() {
    // Use more generic selectors based on the actual form structure
    var verticalPos = $('[name$="[vertical_position]"]').val();
    var horizontalPos = $('[name$="[horizontal_position]"]').val();
    var verticalOffset = $('[name$="[vertical_offset]"]').val();
    var horizontalOffset = $('[name$="[horizontal_offset]"]').val();
    var backgroundColor = $(".color-field").val();

    $("#preview-icon").css({
      top: verticalPos === "top" ? verticalOffset + "px" : "auto",
      bottom: verticalPos === "bottom" ? verticalOffset + "px" : "auto",
      left: horizontalPos === "left" ? horizontalOffset + "px" : "auto",
      right: horizontalPos === "right" ? horizontalOffset + "px" : "auto",
      background: backgroundColor || "#0073aa",
    });
  }

  // Initialize color picker
  $(".color-field").wpColorPicker({
    change: function (event, ui) {
      // Update preview when color changes
      updatePreview();
    },
  });

  // Bind events and initial update
  $("select, input").on("change input", updatePreview);
  updatePreview();
});
