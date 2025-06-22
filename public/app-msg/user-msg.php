<?php
require_once '../includes/header.php';
$page_title = "Private Messages";
?>
<style>
    .h-screen-chat {
        height: calc(100vh - 5rem);
        /* Adjust based on your header/footer height */
    }

    .active-chat {
        background-color: #e9ecef;
        /* Light gray for active chat in Bootstrap */
    }

    /* Custom styles for gradient backgrounds and specific sizing not directly in Bootstrap */
    .bg-gradient-cyan-blue {
        background: linear-gradient(to right, #0ea5e9, #007bff);
        /* Adjusted to Bootstrap primary blue */
    }

    .bg-gradient-cyan-blue-br {
        background: linear-gradient(to bottom right, #0ea5e9, #007bff);
        /* Adjusted to Bootstrap primary blue */
    }

    .w-10-h-10 {
        width: 2.5rem;
        height: 2.5rem;
    }

    .w-32-h-32 {
        width: 8rem;
        height: 8rem;
    }

    .min-w-0 {
        min-width: 0;
    }

    .min-w-20px {
        min-width: 20px;
    }

    .h-5 {
        height: 1.25rem;
    }

    .rounded-2xl {
        border-radius: 1rem;
        /* Custom border-radius for message bubbles */
    }

    #message-content {
        min-height: 0;
        /* Allows the flex item to shrink and enable scrolling */
    }
</style>
<div class="h-screen-chat bg-light d-flex text-dark">
    <div class="d-flex w-100 mx-auto bg-white rounded shadow-lg" style="height: 100%;">
        <!-- Sidebar -->
        <div class="col-4 bg-white border-end border-light d-flex flex-column">
            <!-- Sidebar Header -->
            <div class="p-4 border-bottom border-light d-flex justify-content-between align-items-center bg-light">
                <div class="d-flex align-items-center gap-3">
                    <div class="w-10-h-10 bg-gradient-cyan-blue rounded-circle d-flex align-items-center justify-content-center text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle">
                            <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"></path>
                        </svg>
                    </div>
                    <h1 class="fs-5 fw-semibold text-secondary">Messages</h1>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-light rounded-circle p-2 text-muted">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </button>
                    <button class="btn btn-light rounded-circle p-2 text-muted">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings">
                            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 0 2l-.15.08a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1 0-2l.15-.08a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </button>
                </div>
            </div>
            <!-- Search -->
            <div class="p-4 border-bottom border-light">
                <div class="position-relative">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search position-absolute start-0 ms-3 top-50 translate-middle-y text-muted">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>
                    <input type="text" id="search-input" placeholder="Search conversations..." class="form-control w-100 ps-5 pe-4 py-2 bg-light border border-light rounded-pill">
                </div>
            </div>
            <!-- Conversation List -->
            <div id="sender-list" class="flex-grow-1 overflow-auto">
                <!-- Senders will be loaded here -->
            </div>
        </div>

        <!-- Message Content -->
        <div class="col-8 d-flex flex-column bg-light">
            <!-- Initial State -->
            <div id="initial-state" class="flex-grow-1 d-flex align-items-center justify-content-center">
                <div class="text-center">
                    <div class="w-32-h-32 bg-gradient-cyan-blue-br rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4 shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-send text-white">
                            <path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"></path>
                            <path d="m21.854 2.147-10.94 10.939"></path>
                        </svg>
                    </div>
                    <h2 class="fs-2 fw-bold text-secondary mb-2">Welcome to Messages</h2>
                    <p class="text-muted">Select a conversation to start messaging</p>
                </div>
            </div>
            <!-- Conversation View (hidden by default) -->
            <div id="conversation-view" class="d-none d-flex flex-column" style="height: 100%;">
                <!-- Chat Header -->
                <div id="chat-header" class="p-3 border-bottom border-light d-flex justify-content-between align-items-center bg-white shadow-sm">
                    <!-- Participant info loaded here -->
                </div>
                <!-- Messages -->
                <div id="message-content" class="flex-grow-1 p-4" style="overflow-y: auto;">
                    <!-- Messages loaded here -->
                </div>
                <!-- Message Input -->
                <div class="p-4 bg-white border-top border-light">
                    <div class="d-flex align-items-center gap-3">
                        <input type="text" id="message-input" placeholder="Type a message..." class="form-control flex-grow-1 px-4 py-2 bg-light border border-light rounded-pill">
                        <button class="btn btn-light rounded-circle p-2 text-muted">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-smile">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M8 14s1.5 2 4 2 4-2 4-2" />
                                <line x1="9" x2="9.01" y1="9" y2="9" />
                                <line x1="15" x2="15.01" y1="9" y2="9" />
                            </svg>
                        </button>
                        <button class="btn btn-light rounded-circle p-2 text-muted">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-paperclip">
                                <path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.59a2 2 0 0 1-2.83-2.83l8.49-8.48" />
                            </svg>
                        </button>
                        <button id="send-button" class="btn btn-primary px-4 py-2 bg-gradient-cyan-blue text-white fw-semibold rounded-pill shadow hover:shadow-lg transition-shadow">
                            Send
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<script src="../assets/js/api-helper.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const senderList = document.getElementById('sender-list');
        const messageContainer = document.getElementById('message-container');
        const initialState = document.getElementById('initial-state');
        const conversationView = document.getElementById('conversation-view');
        const chatHeader = document.getElementById('chat-header');
        const messageContent = document.getElementById('message-content');
        const messageInput = document.getElementById('message-input');
        const sendButton = document.getElementById('send-button');
        const searchInput = document.getElementById('search-input');

        let currentUserId = <?php echo $_SESSION['user_id'] ?? 'null'; ?>;
        let activeParticipantId = null;

        async function fetchAndDisplaySenders() {
            senderList.innerHTML = `<div class="p-4 text-center text-muted">Loading...</div>`;
            try {
                const response = await apiRequest('messages', 'list_senders', {
                    user_id: currentUserId
                });
                if (response.success) {
                    senderList.innerHTML = '';
                    const senders = response.senders;
                    if (senders.length === 0) {
                        senderList.innerHTML = `<p class="p-4 text-center text-muted">No conversations found.</p>`;
                        return;
                    }
                    senders.forEach(sender => {
                        const unreadBadge = sender.unread_count > 0 ?
                            `<span class="badge bg-primary text-white rounded-pill px-2 py-1 min-w-20px h-5 d-flex align-items-center justify-content-center fw-medium shadow-sm">${sender.unread_count}</span>` :
                            '';
                        const listItem = document.createElement('div');
                        listItem.className = 'p-3 border-bottom border-light cursor-pointer transition-all hover:bg-light d-flex align-items-center gap-3';
                        listItem.dataset.participantId = sender.participant_id;
                        listItem.dataset.participantName = sender.participant_name;
                        listItem.dataset.participantEmail = sender.participant_email;

                        listItem.innerHTML = `
                        <div class="position-relative">
                            <img src="https://i.pravatar.cc/150?u=${sender.participant_email}" alt="${sender.participant_name}" class="rounded-circle object-fit-cover" style="width: 3rem; height: 3rem;">
                            <span class="position-absolute bottom-0 end-0 d-block h-3 w-3 bg-success rounded-circle border border-white border-2"></span>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <h3 class="fw-semibold small text-dark text-truncate">${sender.participant_name}</h3>
                                <span class="small text-muted">${new Date(sender.latest_message_timestamp * 1000).toLocaleDateString()}</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mt-1">
                                <p class="small text-secondary text-truncate">${sender.latest_message}</p>
                                ${unreadBadge}
                            </div>
                        </div>
                    `;
                        senderList.appendChild(listItem);
                    });
                } else {
                    senderList.innerHTML = `<p class="p-4 text-center text-danger">Error: ${response.message}</p>`;
                }
            } catch (error) {
                console.error('Error fetching senders:', error);
                senderList.innerHTML = `<p class="p-4 text-center text-danger">An error occurred.</p>`;
            }
        }

        function filterSenders() {
            const searchTerm = searchInput.value.toLowerCase();
            const items = senderList.querySelectorAll('.p-3.border-bottom');
            items.forEach(item => {
                const participantName = item.dataset.participantName.toLowerCase();
                const participantEmail = item.dataset.participantEmail.toLowerCase();
                if (participantName.includes(searchTerm) || participantEmail.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('input', filterSenders);

        senderList.addEventListener('click', function(event) {
            const listItem = event.target.closest('.p-3.border-bottom');
            if (!listItem) return;

            const participantId = listItem.dataset.participantId;
            if (activeParticipantId === participantId) return;

            const currentlyActive = senderList.querySelector('.active-chat');
            if (currentlyActive) {
                currentlyActive.classList.remove('active-chat');
            }
            listItem.classList.add('active-chat');
            activeParticipantId = participantId;
            loadConversation(participantId, listItem.dataset.participantName, listItem.dataset.participantEmail);
        });

        async function loadConversation(participantId, participantName, participantEmail) {
            initialState.classList.remove('d-flex');
            initialState.classList.add('d-none');
            conversationView.classList.remove('d-none');
            conversationView.classList.add('d-flex');
            messageContent.innerHTML = `<div class="flex-grow-1 d-flex align-items-center justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>`;

            // Update chat header
            chatHeader.innerHTML = `
            <div class="d-flex align-items-center gap-3">
                <img src="https://i.pravatar.cc/150?u=${participantEmail}" alt="${participantName}" class="rounded-circle object-fit-cover" style="width: 2.5rem; height: 2.5rem;">
                <div>
                    <h2 class="fs-5 fw-semibold text-dark">${participantName}</h2>
                    <p class="small text-success">Online</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-light rounded-circle p-2 text-muted"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></button>
                <button class="btn btn-light rounded-circle p-2 text-muted"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-video"><path d="m22 8-6 4 6 4V8Z"/><rect width="14" height="12" x="2" y="6" rx="2" ry="2"/></svg></button>
                <button class="btn btn-light rounded-circle p-2 text-muted"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-more-vertical"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg></button>
            </div>
        `;

            try {
                await apiRequest('messages', 'mark_conversation_as_read', {
                    user_id: currentUserId,
                    participant_id: participantId
                });
                fetchAndDisplaySenders(); // Refresh sender list to remove unread badge

                const response = await apiRequest('messages', 'get_conversation', {
                    user_id: currentUserId,
                    participant_id: participantId
                });
                if (response.success) {
                    messageContent.innerHTML = '';
                    const conversation = response.conversation;
                    if (conversation.length === 0) {
                        messageContent.innerHTML = '<p class="text-center text-muted">No messages yet. Start the conversation!</p>';
                        return;
                    }
                    conversation.forEach(msg => {
                        const isSent = msg.sender_id == currentUserId;
                        const alignClass = isSent ? 'justify-content-end' : 'justify-content-start';
                        const bubbleClass = isSent ? 'bg-primary text-white' : 'bg-white text-dark shadow-sm';

                        const messageElement = document.createElement('div');
                        messageElement.className = `d-flex ${alignClass} mb-3`;
                        messageElement.innerHTML = `
                        <div class="col-md-6">
                            <div class="px-3 py-2 rounded-2xl ${bubbleClass}">
                                <p class="mb-0">${msg.message}</p>
                            </div>
                            <div class="small text-muted mt-1 px-2 ${isSent ? 'text-end' : 'text-start'}">${new Date(msg.timestamp * 1000).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                        </div>
                    `;
                        messageContent.appendChild(messageElement);
                    });
                    messageContent.scrollTop = messageContent.scrollHeight;
                } else {
                    messageContent.innerHTML = `<p class="p-4 text-center text-danger">Error: ${response.message}</p>`;
                }
            } catch (error) {
                console.error('Error fetching conversation:', error);
                messageContent.innerHTML = `<p class="p-4 text-center text-danger">An error occurred.</p>`;
            }
        }

        async function sendMessage() {
            const messageText = messageInput.value.trim();
            if (!messageText || !activeParticipantId) return;

            const tempId = `temp_${Date.now()}`;
            // Add message to UI immediately for responsiveness
            const messageElement = document.createElement('div');
            messageElement.className = 'd-flex justify-content-end';
            messageElement.dataset.tempId = tempId;
            messageElement.innerHTML = `
            <div class="col-md-6">
                <div class="px-3 py-2 rounded-2xl bg-primary text-white">
                    <p class="mb-0">${messageText}</p>
                </div>
                <div class="small text-muted mt-1 px-2 text-end">Sending...</div>
            </div>
        `;
            messageContent.appendChild(messageElement);
            messageContent.scrollTop = messageContent.scrollHeight;

            const originalMessage = messageInput.value;
            messageInput.value = '';

            try {
                const response = await apiRequest('messages', 'send_message', {
                    sender_id: currentUserId,
                    receiver_id: activeParticipantId,
                    message: messageText
                });

                if (response.success) {
                    // Update the message with the real ID and timestamp from server
                    const sentMessage = messageContent.querySelector(`[data-temp-id="${tempId}"]`);
                    if (sentMessage) {
                        sentMessage.querySelector('.small').textContent = new Date().toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }
                    fetchAndDisplaySenders(); // Refresh list to show new latest message
                } else {
                    alert(`Failed to send message: ${response.message}`);
                    messageInput.value = originalMessage; // Restore message on failure
                    messageContent.querySelector(`[data-temp-id="${tempId}"]`).remove();
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('An error occurred while sending the message.');
                messageInput.value = originalMessage;
                messageContent.querySelector(`[data-temp-id="${tempId}"]`).remove();
            }
        }

        sendButton.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        if (currentUserId) {
            fetchAndDisplaySenders();
        } else {
            senderList.innerHTML = `<p class="p-4 text-center text-danger">User not logged in.</p>`;
            messageContainer.innerHTML = `<div class="flex-grow-1 d-flex align-items-center justify-content-center"><p class="text-danger">Please log in to view messages.</p></div>`;
        }
    });
</script>