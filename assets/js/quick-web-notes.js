jQuery(document).ready(function ($) {
  // Toggle notes in shortcode
  $("#toggleNotes").on("click", function () {
    var $content = $("#notesContent");
    if ($content.is(":hidden")) {
      $content.slideDown();
      $(this).text("Hide Notes");
    } else {
      $content.slideUp();
      $(this).text("Show Notes");
    }
  });

  // Show/hide action menu
  $(document).on("click", ".action_icon", function (event) {
    event.stopPropagation();
    var $menu = $(this).next(".action_list_container");
    $(".action_list_container").not($menu).hide();
    $menu.toggle(200);
  });

  // Hide menu when clicking outside
  $(document).on("click", function () {
    $(".action_list_container").hide();
  });

  // Open main modal
  $("#simple-notes-fixed-btn").on("click", function () {
    $("#simple-notes-modal").fadeIn();
    loadNotes();
  });

  // Open add note modal
  $("#simple-notes-add-btn").on("click", function () {
    $("#simple-notes-modal").hide();
    $("#simple-notes-add-modal").fadeIn();
  });

  // Close modals
  $(".simple-notes-close").on("click", function () {
    $(this).closest(".simple-notes-modal").fadeOut();
  });

  // Close modal when clicking outside
  $(window).on("click", function (event) {
    if ($(event.target).hasClass("simple-notes-modal")) {
      $(".simple-notes-modal").fadeOut();
    }
  });

  // Handle note submission
  $("#simple-notes-add-form").on("submit", function (e) {
    e.preventDefault();

    const title = $("#modal_note_title").val();
    const content = $("#modal_note_content").val();

    if (!title || !content) {
      alert("Please fill in all required fields");
      return;
    }

    $.ajax({
      url: simpleNotes.ajaxurl,
      type: "POST",
      data: {
        action: "add_note",
        nonce: simpleNotes.nonce,
        title: title,
        content: content,
      },
      success: function (response) {
        if (response.success) {
          $("#simple-notes-add-form")[0].reset();
          $("#simple-notes-add-modal").hide();
          $("#simple-notes-modal").show();
          loadNotes();
        } else {
          alert("Error: " + response.data);
        }
      },
      error: function () {
        alert("Error occurred while adding note");
      },
    });
  });

  function loadNotes() {
    $.ajax({
      url: simpleNotes.ajaxurl,
      type: "POST",
      data: {
        action: "get_notes",
        nonce: simpleNotes.nonce,
      },
      success: function (response) {
        if (response.success) {
          const notes = response.data;
          let html = "";

          notes.forEach(function (note) {
            html += `
              <div class="note-item" data-id="${note.id}">
                  <h3 class="note_tile_container">${note.title}
                    <div class="action_list_wrapper">
                      <button class="action_icon">&#8942;</button>
                      <div class="action_list_container">
                        <button class="button edit-note">Edit</button>
                        <button class="button delete-note">Delete</button>
                      </div>
                    </div>
                  </h3>
                  <p>${note.content}</p>
                  <small>Created: ${note.created_at}</small>
                  
              </div>
          `;
          });

          $("#simple-notes-list").html(html);
        }
      },
    });
  }
  // Edit note button click
  $(document).on("click", ".edit-note", function () {
    const noteId = $(this).closest(".note-item").data("id");

    $.ajax({
      url: simpleNotes.ajaxurl,
      type: "POST",
      data: {
        action: "get_note_by_id",
        nonce: simpleNotes.nonce,
        id: noteId,
      },
      success: function (response) {
        if (response.success) {
          const note = response.data;
          $("#edit_note_id").val(note.id);
          $("#edit_note_title").val(note.title);
          $("#edit_note_content").val(note.content);
          $("#simple-notes-modal").hide();
          $("#simple-notes-edit-modal").fadeIn();
        }
      },
    });
  });

  // Handle edit form submission
  $("#simple-notes-edit-form").on("submit", function (e) {
    e.preventDefault();

    const id = $("#edit_note_id").val();
    const title = $("#edit_note_title").val();
    const content = $("#edit_note_content").val();

    if (!title || !content) {
      alert("Please fill in all required fields");
      return;
    }

    $.ajax({
      url: simpleNotes.ajaxurl,
      type: "POST",
      data: {
        action: "edit_note",
        nonce: simpleNotes.nonce,
        id: id,
        title: title,
        content: content,
      },
      success: function (response) {
        if (response.success) {
          $("#simple-notes-edit-form")[0].reset();
          $("#simple-notes-edit-modal").hide();
          $("#simple-notes-modal").show();
          loadNotes();
        } else {
          alert("Error: " + response.data);
        }
      },
    });
  });

  // Delete note button click
  $(document).on("click", ".delete-note", function () {
    if (!confirm("Are you sure you want to delete this note?")) {
      return;
    }

    const noteItem = $(this).closest(".note-item");
    const noteId = $(this).closest(".note-item").data("id");

    console.log("Attempting to delete note with ID:", noteId);
    if (!noteId) {
      console.error("No note ID found");
      return;
    }

    $.ajax({
      url: simpleNotes.ajaxurl,
      type: "POST",
      data: {
        action: "delete_note",
        nonce: simpleNotes.nonce,
        id: noteId,
      },
      success: function (response) {
        if (response.success) {
          loadNotes();
        } else {
          alert("Error: " + response.data);
        }
      },
    });
  });
});
