// Conditional display of "Related to" information:
// This information is displayed only in "private" conversation mode.
// In "patient" mode, it is not displayed as the conversation is already specific to a patient.
document.addEventListener("DOMContentLoaded", function () {
  // currentUserId is set globally in index.php
  const peopleList = document.getElementById("peopleList");
  const chatHeader = document.getElementById("chatHeader");
  const chatName = document.getElementById("chatName");
  const chatAvatar = document.getElementById("chatAvatar");
  const messagesContainer = document.getElementById("messages");
  const chatBox = document.getElementById("chatBox");
  const initialChatState = document.getElementById("initial-state");
  const messageInput = document.getElementById("messageInput");
  const chatForm = document.getElementById("chatForm");
  const receiverIdInput = document.getElementById("receiverIdInput");
  const patientIdInput = document.getElementById("patientIdInput");
  const deleteConversationModal = new bootstrap.Modal(
    document.getElementById("deleteConversationModal")
  );
  const confirmDeleteConversationBtn = document.getElementById(
    "confirmDeleteConversationBtn"
  );
  const btnradioPrivate = document.getElementById("btnradioPrivate");
  const btnradioPatients = document.getElementById("btnradioPatients");
  const searchPeopleInput = document.getElementById("searchPeople");

  let activeConversationType = "private"; // 'private' or 'patient'
  let activeParticipantId = null; // For private chats
  let activePatientId = null; // For patient chats

  // Set default radio button to Private and load private conversations
  btnradioPrivate.checked = true;
  loadPeopleList("private");

  btnradioPrivate.addEventListener("change", () => {
    activeConversationType = "private";
    loadPeopleList("private");
    resetChatArea();
  });

  btnradioPatients.addEventListener("change", () => {
    activeConversationType = "patient";
    loadPeopleList("patient");
    resetChatArea();
  });

  searchPeopleInput.addEventListener("input", function () {
    loadPeopleList(activeConversationType, this.value);
  });

  function resetChatArea() {
    chatHeader.style.display = "none";
    chatBox.style.display = "none";
    messagesContainer.innerHTML = ""; // Clear messages
    initialChatState.style.display = "flex"; // Show initial state
    activeParticipantId = null;
    activePatientId = null;
  }

  async function loadPeopleList(type, searchTerm = "") {
    peopleList.innerHTML = ""; // Clear current list
    let endpoint = "";
    let action = "";
    let data = {
      user_id: currentUserId,
    };

    if (type === "private") {
      endpoint = "conversations";
      action = "list_senders";
    } else if (type === "patient") {
      endpoint = "conversations";
      action = "list_patients_with_messages";
    }

    if (searchTerm) {
      data.search = searchTerm;
    }

    try {
      const response = await apiRequest(endpoint, action, data);
      if (response.success) {
        const items = type === "private" ? response.senders : response.patients;
        items.forEach((item) => {
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

          let name = "";
          let id = "";
          let unreadCount = 0;

          if (type === "private") {
            name =
              item.participant_username ||
              item.participant_name ||
              item.participant_email;
            id = item.participant_id;
            unreadCount = item.unread_count;
            listItem.dataset.participantId = id;
          } else {
            // patient
            name = item.patient_name || item.patient_email;
            id = item.patient_id;
            // For patients, we might need a separate API call or logic to get unread count
            // For now, assuming no unread count for patient list directly
            listItem.dataset.patientId = id;
          }

          listItem.innerHTML = `
                        <img src="../assets/avatar.png" class="rounded-circle me-3" alt="Avatar" width="48" height="48" />
                        <div class="flex-grow-1">
                            <h6 class="mb-0">${name}</h6>
                            <small class="text-muted text-truncate d-block" style="max-width: 150px;">${
                              item.latest_message || "No messages yet."
                            }</small>
                        </div>
                        <div class="d-flex flex-column align-items-end">
                            <small class="text-muted">${
                              item.latest_message_timestamp
                                ? new Date(
                                    item.latest_message_timestamp
                                  ).toLocaleTimeString([], {
                                    hour: "2-digit",
                                    minute: "2-digit",
                                  })
                                : ""
                            }</small>
                            ${
                              unreadCount > 0
                                ? `<span class="badge bg-primary rounded-pill mt-1">${unreadCount}</span>`
                                : ""
                            }
                        </div>
                    `;
          peopleList.appendChild(listItem);

          listItem.addEventListener("click", () => {
            document
              .querySelectorAll("#peopleList .list-group-item")
              .forEach((li) => li.classList.remove("active"));
            listItem.classList.add("active");
            if (type === "private") {
              activeParticipantId = id;
              activePatientId = null;
              loadConversation(activeParticipantId, null, name);
            } else {
              // patient
              activePatientId = id;
              activeParticipantId = null;
              loadConversation(null, activePatientId, name);
            }
          });
        });
      } else {
        console.error("Error fetching people list:", response.message);
      }
    } catch (error) {
      console.error("API request failed:", error);
    }
  }

  async function loadConversation(participantId, patientId, name) {
    initialChatState.style.display = "none";
    messagesContainer.innerHTML = ""; // Clear previous messages
    chatHeader.style.display = "flex";
    chatBox.style.display = "block";
    chatName.textContent = name;
    chatAvatar.src = "../assets/avatar.png"; // Default avatar

    receiverIdInput.value = participantId;
    patientIdInput.value = patientId;

    let endpoint = "conversations";
    let action = "";
    let data = {
      user_id: currentUserId,
    };

    if (participantId) {
      action = "get_conversation";
      data.participant_id = participantId;
    } else if (patientId) {
      action = "get_patient_conversation";
      data.patient_id = patientId;
    } else {
      console.error("No participantId or patientId provided for conversation.");
      return;
    }

    try {
      const response = await apiRequest(endpoint, action, data);
      if (response.success && response.conversation) {
        // Mark conversation as read
        if (participantId) {
          await apiRequest("conversations", "mark_conversation_as_read", {
            user_id: currentUserId,
            participant_id: participantId,
          });
        }
        // No mark as read for patient conversations yet, as they are not direct user-to-user
        // If needed, implement a separate 'mark_patient_conversation_as_read' action

        response.conversation.forEach((msg) => {
          displayMessage(msg);
        });
        messagesContainer.scrollTop = messagesContainer.scrollHeight; // Scroll to bottom
      } else {
        console.error("Error fetching conversation:", response.message);
      }
    } catch (error) {
      console.error("API request failed:", error);
    }
  }

  function displayMessage(msg) {
    const messageElement = document.createElement("div");
    const isSender = msg.sender_id == currentUserId;
    messageElement.classList.add(
      "d-flex",
      "mb-3",
      isSender ? "justify-content-start" : "justify-content-end"
    );

    let relatedEntityHtml = "";
    if (
      msg.related_table &&
      Array.isArray(msg.related_table) &&
      msg.related_table.length > 0
    ) {
      const entity = msg.related_table[0];
      if (activeConversationType === "private" && entity.label) {
        // Only display related entity information in private mode,
        // as in patient mode, the conversation is already specific to a patient.
        relatedEntityHtml = `<small class="text-muted-darker">Related to: ${entity.label} (${entity.table_name} ID: ${entity.id})</small>`;
      }
    }

    messageElement.innerHTML = `
            <div class="message-bubble" style="max-width: 75%; padding: 1rem; border-radius: 0.5rem; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); background-color: ${
              isSender ? "#e0f2f7" : "#e0f7f7"
            }; color: #333;">
                <small class="message-sender fw-bold">${
                  isSender ? "" : msg.sender_name || "Unknown"
                }</small>
                <p class="mb-1">${msg.message}</p>
                ${relatedEntityHtml}
            </div> 
            <small class="message-timestamp d-block text-end ${
              isSender ? "text-white-50" : "text-muted"
            }">${new Date(msg.timestamp).toLocaleTimeString([], {
      hour: "2-digit",
      minute: "2-digit",
    })}</small>
        `;
    messagesContainer.appendChild(messageElement);
  }

  chatForm.addEventListener("submit", async function (event) {
    event.preventDefault();
    const messageContent = messageInput.value.trim();
    if (!messageContent) return;

    const payload = {
      sender_id: currentUserId,
      message: messageContent,
    };

    if (activeConversationType === "private") {
      payload.receiver_id = activeParticipantId;
    } else if (activeConversationType === "patient") {
      payload.patient_id = activePatientId;
      // For patient messages, the receiver might be the agent/admin (currentUserId's role)
      // Or it could be a general message related to the patient.
      // For simplicity, let's assume the receiver is the currentUserId if it's a patient message
      // and the sender is not the currentUserId.
      // Or, if it's a patient-specific message, it might not have a direct receiver_id in the messages table.
      // Based on the schema, patient_id is a separate column.
      // If the message is from the current user about a patient, it might be a broadcast or to a specific agent.
      // For now, let's keep receiver_id null for patient messages unless explicitly set.
      payload.receiver_id = null; // Or set to a default agent/admin ID if applicable
    }

    try {
      const response = await apiRequest(
        "conversations",
        "send_message",
        payload
      );
      if (response.success) {
        messageInput.value = ""; // Clear input
        // Reload conversation to show new message
        loadConversation(
          activeParticipantId,
          activePatientId,
          chatName.textContent
        );
      } else {
        alert("Error sending message: " + response.message);
      }
    } catch (error) {
      console.error("API request failed:", error);
      alert("Failed to send message due to API error.");
    }
  });

  confirmDeleteConversationBtn.addEventListener("click", async function () {
    let success = false;
    if (activeConversationType === "private" && activeParticipantId) {
      const response = await apiRequest(
        "conversations",
        "delete_conversation",
        {
          user_id: currentUserId,
          participant_id: activeParticipantId,
        }
      );
      success = response.success;
    } else if (activeConversationType === "patient" && activePatientId) {
      // Implement patient conversation deletion if needed
      // For now, this action is not directly supported by the API for patient_id based conversations
      alert("Deleting patient-related conversations is not yet supported.");
      success = false;
    }

    if (success) {
      alert("Conversation deleted successfully!");
      deleteConversationModal.hide();
      loadPeopleList(activeConversationType); // Reload the list
      resetChatArea(); // Reset chat display
    } else {
      alert("Failed to delete conversation.");
    }
  });
});
