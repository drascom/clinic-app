document.addEventListener("DOMContentLoaded", function () {
  // currentUserId is set globally in index.php
  const leadsList = document.getElementById("leadsList");
  const chatHeader = document.getElementById("chatHeader");
  const chatName = document.getElementById("chatName");
  const chatAvatar = document.getElementById("chatAvatar");
  const messagesContainer = document.getElementById("messages");
  const chatBox = document.getElementById("chatBox");
  const initialChatState = document.getElementById("initial-state");
  const messageInput = document.getElementById("messageInput");
  const chatForm = document.getElementById("chatForm");
  const searchLeadsInput = document.getElementById("searchLeads");
  const leadStatusDropdown = document.getElementById("leadStatus");

  let activeLeadId = null;

  loadLeadsList();

  searchLeadsInput.addEventListener("input", function () {
    loadLeadsList(this.value);
  });

  function resetChatArea() {
    chatHeader.style.display = "none";
    chatBox.style.display = "none";
    messagesContainer.innerHTML = ""; // Clear messages
    initialChatState.style.display = "flex"; // Show initial state
    activeLeadId = null;
  }

  async function loadLeadsList(searchTerm = "") {
    leadsList.innerHTML = ""; // Clear current list
    let data = {};
    if (searchTerm) {
      data.search = searchTerm;
    }

    try {
      // Changed from list_leeds to list_local_leeds to reflect the new architecture
      const response = await apiRequest("leeds", "list_leeds", data);
      if (response.success) {
        response.leeds.forEach((lead) => {
          const listItem = document.createElement("li");
          listItem.classList.add(
            "list-group-item",
            "list-group-item-action",
            "d-flex",
            "align-items-center",
            "py-3",
            "px-3",
            "border-bottom"
          );
          listItem.style.cursor = "pointer";
          listItem.dataset.leadId = lead.id;

          listItem.innerHTML = `
                        <img src="../assets/avatar.png" class="rounded-circle me-3" alt="Avatar" width="48" height="48" />
                        <div class="flex-grow-1">
                            <h6 class="mb-0">${lead.name}</h6>
                            <small class="text-muted text-truncate d-block" style="max-width: 150px;">${
                              lead.email || lead.phone
                            }</small>
                        </div>
                    `;
          leadsList.appendChild(listItem);

          listItem.addEventListener("click", () => {
            document
              .querySelectorAll("#leadsList .list-group-item")
              .forEach((li) => li.classList.remove("active"));
            listItem.classList.add("active");
            activeLeadId = lead.id;
            loadLeadNotes(lead.id, lead.name, lead.status);
          });
        });
      } else {
        console.error("Error fetching leads list:", response.message);
      }
    } catch (error) {
      console.error("API request failed:", error);
    }
  }

  async function loadLeadNotes(leadId, name, status) {
    initialChatState.style.display = "none";
    messagesContainer.innerHTML = ""; // Clear previous messages
    chatHeader.style.display = "flex";
    chatBox.style.display = "none";
    chatName.textContent = name;
    chatAvatar.src = "../assets/avatar.png"; // Default avatar
    leadStatusDropdown.value = status;

    try {
      const response = await apiRequest("leeds", "get_lead_details", {
        id: leadId,
      });
      if (response.success) {
        if (response.notes) {
            response.notes.forEach((note) => {
                displayNote(note);
            });
        }
        leadStatusDropdown.value = response.lead.status;
        messagesContainer.scrollTop = messagesContainer.scrollHeight; // Scroll to bottom
      } else {
        // Even if there are no notes, show the chat box
        console.log("No notes for this lead yet.");
      }
    } catch (error) {
      console.error("API request failed:", error);
    }
  }

  function displayNote(note) {
    const noteElement = document.createElement("div");
    const isSender = note.user_id == currentUserId;
    noteElement.classList.add(
      "d-flex",
      "mb-3",
      isSender ? "justify-content-end" : "justify-content-start"
    );

    noteElement.innerHTML = `
            <div class="message-bubble d-flex align-items-center" style="max-width: 75%; padding: 1rem; border-radius: 0.5rem; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); background-color: ${
              isSender ? "#e0f2f7" : "#f8f9fa"
            };">
                <p class="mb-1 me-2">${note.note}</p>
                <small class="message-timestamp d-block text-end text-muted flex-grow-1">${new Date(
                  note.created_at
                ).toLocaleString()}</small>
                <button class="btn btn-sm btn-outline-danger ms-2 delete-note-btn" data-note-id="${note.id}">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
    messagesContainer.appendChild(noteElement);

    // Add event listener for the delete button
    const deleteButton = noteElement.querySelector(".delete-note-btn");
    if (deleteButton) {
      deleteButton.addEventListener("click", async () => {
        const noteId = deleteButton.dataset.noteId;
        if (confirm("Are you sure you want to delete this note?")) {
          try {
            const response = await apiRequest("leeds", "delete_note", { id: noteId });
            if (response.success) {
              loadLeadNotes(activeLeadId, chatName.textContent, leadStatusDropdown.value); // Reload notes
            } else {
              alert("Error deleting note: " + response.message);
            }
          } catch (error) {
            console.error("API request failed:", error);
            alert("Failed to delete note due to API error.");
          }
        }
      });
    }
  }


  leadStatusDropdown.addEventListener("change", async function () {
    if (!activeLeadId) return;

    const newStatus = this.value;
    try {
      const response = await apiRequest("leeds", "update_status", {
        lead_id: activeLeadId,
        status: newStatus,
      });
      if (!response.success) {
        alert("Failed to update status: " + response.message);
        // Optionally, revert the dropdown if the update fails
        loadLeadNotes(activeLeadId, chatName.textContent);
      }
    } catch (error) {
      console.error("API request failed:", error);
      alert("Failed to update status due to an API error.");
    }
  });
});
