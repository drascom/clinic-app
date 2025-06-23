<?php
require_once __DIR__ . '/../includes/header.php';
// Assuming user_id is stored in session after login
$current_user_id = $_SESSION['user_id'] ?? null;
?>
<div class="container-fluid g-0">
    <div class="row g-0">
        <!-- People / Chat list column -->
        <aside class="col-12 col-sm-4 col-md-3 col-lg-3 border-end d-flex flex-column vh-100 bg-body">
            <!-- Search -->
            <div class="p-3 border-bottom">
                <div class="input-group">
                    <span class="input-group-text bg-body border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchPeople" class="form-control border-start-0" placeholder="Search or start new chat" />
                </div>
            </div>

            <!-- People list -->
            <ul id="peopleList" class="list-group list-group-flush flex-grow-1 overflow-auto">
                <!-- Contacts will be loaded here dynamically -->
            </ul>
        </aside>

        <!-- Conversation column -->
        <main class="col-12 col-sm-8 col-md-9 col-lg-9 d-flex flex-column vh-100">
            <!-- Header (selected person) -->
            <header id="chatHeader" class="align-items-center gap-2 p-2 border-bottom bg-body " style="display: none;">
                <img id="chatAvatar" src="" class="rounded-circle" alt="Selected person avatar" width="40" height="40" />
                <strong id="chatName"></strong>
                <div class="ms-auto">
                    <button id="moreOptionsBtn" class="btn btn-text p-2 text-danger" data-bs-toggle="modal" data-bs-target="#deleteConversationModal">
                        <i class="fas fa-trash"></i> <span class="d-none d-lg-inline ms-1">Delete</span>
                    </button>
                </div>
            </header>

            <!-- Messages area -->
            <div id="messages" class="flex-grow-1 overflow-auto p-4  d-flex flex-column">
                <div id="initial-state" class="flex-grow-1 d-flex align-items-center justify-content-center" style="display: flex;">
                    <div class="text-center">
                        <div class="w-32-h-32 bg-gradient-cyan-blue-br rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4 shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-send text-body-emphasis">
                                <path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"></path>
                                <path d="m21.854 2.147-10.94 10.939"></path>
                            </svg>
                        </div>
                        <h2 class="fs-2 fw-bold text-secondary mb-2">Welcome to Messages</h2>
                        <p class="text-muted">Select a conversation to start messaging</p>
                    </div>
                </div>
            </div>

            <!-- Chatbox -->
            <div id="chatBox" class="border-top p-2 mt-4 sticky-bottom bg-body" style="display: none;">
                <form id="chatForm" class="d-flex gap-2">
                    <!-- Add / attachment -->
                    <label for="fileInput" class="btn btn-outline-secondary mb-0 border">
                        <i class="bi bi-paperclip"></i>
                    </label>
                    <input type="file" id="fileInput" />
                    <!-- Text input -->
                    <input type="text" id="messageInput" class="form-control" placeholder="Type a message" autocomplete="off" required />
                    <!-- Send -->
                    <button class="btn btn-success d-flex align-items-center" type="submit">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </form>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<!-- Delete Conversation Confirmation Modal -->
<div class="modal fade" id="deleteConversationModal" tabindex="-1" aria-labelledby="deleteConversationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConversationModalLabel">Delete Conversation?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete all messages in this conversation? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteConversationBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/api-helper.js"></script>
<script>
    console.log('Current theme:', document.body.classList.contains('dark-mode') ? 'Dark Mode' : 'Light Mode');
    document.addEventListener('DOMContentLoaded', function() {
        const currentUserId = <?php echo json_encode($current_user_id); ?>;
        if (!currentUserId) {
            document.getElementById('peopleList').innerHTML = '<p class="text-center text-danger p-3">Error: User not logged in.</p>';
            return;
        }

        const peopleList = document.getElementById('peopleList');
        const searchInput = document.getElementById('searchPeople');
        const chatHeader = document.getElementById('chatHeader');
        const chatAvatar = document.getElementById('chatAvatar');
        const chatName = document.getElementById('chatName');
        const messagesContainer = document.getElementById('messages');
        const initialState = document.getElementById('initial-state');
        const chatBox = document.getElementById('chatBox');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const deleteConversationModal = new bootstrap.Modal(document.getElementById('deleteConversationModal'));
        const confirmDeleteConversationBtn = document.getElementById('confirmDeleteConversationBtn');

        let activeParticipantId = null;
        let allContacts = [];

        async function fetchAndDisplayContacts() {
            peopleList.innerHTML = '<p class="text-center p-3">Loading contacts...</p>';
            try {
                const response = await apiRequest('conversations', 'list_senders', {
                    user_id: currentUserId
                });
                if (response.success && response.senders) {
                    allContacts = response.senders;
                    renderContacts(allContacts);
                } else {
                    peopleList.innerHTML = `<p class="text-center text-danger p-3">Error: ${response.message || 'Could not fetch contacts.'}</p>`;
                }
            } catch (error) {
                console.error('Error fetching contacts:', error);
                peopleList.innerHTML = '<p class="text-center text-danger p-3">An error occurred while fetching contacts.</p>';
            }
        }

        function renderContacts(contacts) {
            peopleList.innerHTML = '';
            if (contacts.length === 0) {
                peopleList.innerHTML = '<p class="text-center p-3">No conversations found.</p>';
                return;
            }
            contacts.forEach(contact => {
                const listItem = document.createElement('li');
                listItem.className = 'list-group-item d-flex align-items-center gap-2';
                listItem.dataset.participantId = contact.participant_id;
                listItem.dataset.participantName = contact.participant_name !== null ? contact.participant_name : contact.participant_username;
                listItem.dataset.participantEmail = contact.participant_email;
                let name = contact.participant_name !== null ? contact.participant_name : contact.participant_username
                listItem.innerHTML = `
                        <img src="https://i.pravatar.cc/40?u=${contact.participant_email}" class="rounded-circle" alt="${name}" width="40" height="40" />
                        <div>
                            <strong>${name}</strong>
                            <div class="small text-muted text-truncate" style="max-width: 200px;">${contact.latest_message}</div>
                        </div>
                        ${contact.unread_count > 0 ? `<span class="badge bg-success ms-auto">${contact.unread_count}</span>` : ''}
                    `;
                peopleList.appendChild(listItem);
            });
        }

        searchInput.addEventListener("input", () => {
            const term = searchInput.value.toLowerCase();
            const filteredContacts = allContacts.filter(contact =>
                name.toLowerCase().includes(term)
            );
            renderContacts(filteredContacts);
        });

        peopleList.addEventListener("click", (e) => {
            const li = e.target.closest("li[data-participant-id]");
            if (!li) return;

            const participantId = li.dataset.participantId;
            if (activeParticipantId === participantId) return;

            document.querySelectorAll("#peopleList > li").forEach(c => c.classList.remove("active"));
            li.classList.add("active");

            activeParticipantId = participantId;
            const participantName = li.dataset.participantName;
            const participantEmail = li.dataset.participantEmail;

            loadConversation(participantId, participantName, participantEmail);
        });

        confirmDeleteConversationBtn.addEventListener('click', async () => {
            if (!activeParticipantId) return;

            try {
                const response = await apiRequest('conversations', 'delete_conversation', {
                    user_id: currentUserId,
                    participant_id: activeParticipantId
                });

                if (response.success) {
                    deleteConversationModal.hide();
                    activeParticipantId = null; // Clear active participant
                    messagesContainer.innerHTML = ''; // Clear messages
                    chatHeader.style.display = 'none'; // Hide chat header
                    chatBox.style.display = 'none'; // Hide chat box
                    initialState.style.display = 'flex'; // Show initial state
                    fetchAndDisplayContacts(); // Refresh contact list
                } else {
                    showToast(`Failed to delete conversation: ${response.message}`, 'danger');
                }
            } catch (error) {
                console.error('Error deleting conversation:', error);
                showToast('An error occurred while deleting the conversation.', 'danger');
            }
        });


        async function loadConversation(participantId, participantName, participantEmail) {
            initialState.style.display = 'none';
            chatHeader.style.display = 'flex';
            chatBox.style.display = 'block';
            messagesContainer.innerHTML = '<div class="m-auto"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

            chatAvatar.src = `https://i.pravatar.cc/40?u=${participantEmail}`;
            chatName.textContent = participantName;

            try {
                await apiRequest('conversations', 'mark_conversation_as_read', {
                    user_id: currentUserId,
                    participant_id: participantId
                });
                fetchAndDisplayContacts(); // Refresh list to remove unread badge

                const response = await apiRequest('conversations', 'get_conversation', {
                    user_id: currentUserId,
                    participant_id: participantId
                });

                messagesContainer.innerHTML = '';
                if (response.success && response.conversation) {
                    if (response.conversation.length === 0) {
                        messagesContainer.innerHTML = '<p class="text-center text-muted my-auto">No messages yet. Start the conversation!</p>';
                    } else {
                        let currentGroup = null;

                        response.conversation.forEach(msg => {
                            const groupId = (msg.related_entity_id && msg.related_table && msg.related_entity_label) ? `${msg.related_table}-${msg.related_entity_id}` : null;

                            if (groupId && currentGroup && currentGroup.id === groupId) {
                                // This message belongs to the current active group
                                currentGroup.messages.push(msg);
                            } else {
                                // This message is different or the first one. End the previous group.
                                if (currentGroup) {
                                    const bubble = createConversationBubble(currentGroup, currentGroup.id, currentUserId);
                                    messagesContainer.appendChild(bubble);
                                }
                                currentGroup = null; // Reset the group

                                if (groupId) {
                                    // Start a new group for the current message
                                    currentGroup = {
                                        id: groupId,
                                        label: msg.related_entity_label,
                                        table: msg.related_table,
                                        messages: [msg]
                                    };
                                } else {
                                    // This is a standalone message, render it directly
                                    const messageElement = createMessageElement(
                                        msg.message,
                                        msg.sender_id == currentUserId ? 'sent' : 'received',
                                        msg.timestamp,
                                        msg.related_entity_label,
                                        msg.related_table
                                    );
                                    messagesContainer.appendChild(messageElement);
                                }
                            }
                        });

                        // After the loop, if there's an open group, render it
                        if (currentGroup) {
                            const bubble = createConversationBubble(currentGroup, currentGroup.id, currentUserId);
                            messagesContainer.appendChild(bubble);
                        }
                    }
                    messagesContainer.innerHTML += '<p class="p-4" ></p>';
                } else {
                    messagesContainer.innerHTML = `<p class="text-center text-danger my-auto">Error: ${response.message}</p>`;
                }
            } catch (error) {
                console.error('Error loading conversation:', error);
                messagesContainer.innerHTML = '<p class="text-center text-danger my-auto">An error occurred.</p>';
            }
            scrollToBottom();
        }

        function createConversationBubble(group, groupId, currentUserId) {
            const uniqueId = `collapse-${groupId}-${Math.random().toString(36).substr(2, 9)}`;
            const bubbleWrapper = document.createElement('div');
            bubbleWrapper.className = 'conversation-bubble my-3';

            const bubbleHeader = document.createElement('a');
            bubbleHeader.className = 'conversation-bubble-header d-block text-center p-2 bg-light border rounded text-decoration-none text-secondary';
            bubbleHeader.href = `#${uniqueId}`;
            bubbleHeader.dataset.bsToggle = 'collapse';
            bubbleHeader.setAttribute('role', 'button');
            bubbleHeader.setAttribute('aria-expanded', 'false');
            bubbleHeader.setAttribute('aria-controls', uniqueId);
            bubbleHeader.innerHTML = `
                <i class="bi bi-chat-dots me-2"></i>
                Conversation about <strong>${group.label}</strong>
                <span class="badge bg-secondary ms-2">${group.messages.length}</span>
            `;

            const collapseContainer = document.createElement('div');
            collapseContainer.className = 'collapse';
            collapseContainer.id = uniqueId;

            const messageList = document.createElement('div');
            messageList.className = 'p-3 border border-top-0 rounded-bottom';

            group.messages.forEach(msg => {
                const messageElement = createMessageElement(
                    msg.message,
                    msg.sender_id == currentUserId ? 'sent' : 'received',
                    msg.timestamp,
                    null, // No related info inside bubble
                    null
                );
                messageList.appendChild(messageElement);
            });

            collapseContainer.appendChild(messageList);
            bubbleWrapper.appendChild(bubbleHeader);
            bubbleWrapper.appendChild(collapseContainer);

            return bubbleWrapper;
        }

        function createMessageElement(text, type, timestamp, relatedEntityLabel, relatedTable) {
            const outerDiv = document.createElement("div");
            outerDiv.className = `d-flex flex-column mb-3 ${type === 'sent' ? 'align-items-end' : 'align-items-start'}`;

            const messageBubble = document.createElement("div");
            messageBubble.className = `message ${type}`;
            messageBubble.textContent = text;
            outerDiv.appendChild(messageBubble);


            if (relatedEntityLabel && relatedTable) {
                const relatedInfo = document.createElement("div");
                relatedInfo.className = "small text-muted mt-1 px-2";
                relatedInfo.innerHTML = `Related to: <strong>${relatedEntityLabel}</strong> (${relatedTable})`;
                outerDiv.appendChild(relatedInfo);
            }

            const timeDiv = document.createElement("div");
            timeDiv.className = "small text-muted mt-1 px-2";
            timeDiv.textContent = moment(timestamp, "YYYY-MM-DD HH:mm:ss").fromNow();
            outerDiv.appendChild(timeDiv);
            return outerDiv;
        }

        function scrollToBottom() {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        chatForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const text = messageInput.value.trim();
            if (!text || !activeParticipantId) return;

            // Optimistic UI update
            const optimisticTimestamp = Math.floor(Date.now() / 1000);
            messagesContainer.appendChild(createMessageElement(text, "sent", optimisticTimestamp));
            scrollToBottom();
            const originalMessage = messageInput.value;
            messageInput.value = "";

            try {
                const response = await apiRequest('conversations', 'send_message', {
                    sender_id: currentUserId,
                    receiver_id: activeParticipantId,
                    message: text
                });

                if (!response.success) {
                    showToast(`Failed to send message: ${response.message}`, 'danger');
                    messageInput.value = originalMessage; // Restore on failure
                    // Optionally remove the optimistic message
                } else {
                    fetchAndDisplayContacts(); // Refresh contact list for latest message preview
                }
            } catch (error) {
                console.error('Error sending message:', error);
                showToast('An error occurred while sending the message.', 'danger');
                messageInput.value = originalMessage;
            }
        });

        // Initial load
        fetchAndDisplayContacts();
    });
</script>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>