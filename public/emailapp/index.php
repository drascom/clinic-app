<?php
require_once '../includes/header.php';
$page_title = "Email Dashboard";
?>
<style>
    #email-list {
        max-height: 100vh;
        /* Do not exceed viewport height */
        overflow-y: auto;
        /* Scroll if too many items */
        flex-grow: 0;
        /* Prevent growing unnecessarily */
        flex-shrink: 1;
        /* Allow shrinking */
    }

    .list-group-item.active {
        background-color: #d0e2ff !important;
        color: black;
    }

    .card-body {
        overflow: visible;
        /* Don't restrict overflow here */
        flex-grow: 0;
        /* Prevents forcing content to stretch */
        flex-shrink: 1;
    }

    .email-body table,
    .email-body img {
        max-width: 100% !important;
        /* height: auto !important; */
        /* keep images inside the box */
    }

    .email-body [width] {
        width: auto !important;
        /* neutralise fixed widths coming from email */
    }
</style>
<div class="container-fluid emp p-4">
    <div class="row">
        <!-- Left Column: Email List -->
        <div class="col-md-4 mb-3">
            <div class="card h-100 d-flex flex-column border shadow-sm ">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" id="folder-title">
                            <i class="fas fa-inbox me-2 text-primary"></i>
                            Inbox
                        </h5>
                        <div class="d-flex justify-content-between px-2">
                            <button class="btn btn-sm btn-outline-primary" id="check-emails-btn">
                                <i class="fas fa-sync-alt me-1"></i>
                                Check
                            </button>
                            <a href="#" class="btn btn-sm btn-outline-success ms-2" id="nav-send">
                                <i class="fas fa-envelope me-2"></i> Send
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0 d-flex flex-column">
                    <!-- Progress Bar for Email Fetching -->
                    <div id="email-fetch-progress" class="p-3 border-bottom" style="display:none;">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"
                                id="progress-bar"></div>
                        </div>
                        <small class="text-muted mt-1 d-block" id="progress-text"></small>
                    </div>
                    <!-- Search Bar -->
                    <div class="p-3 border-top">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="search-input" placeholder="Search emails...">
                            <button class="btn btn-outline-secondary" type="button" id="clear-search"
                                title="Clear search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <small><a href="#" id="show-deactivated-emails" class="text-decoration-none text-muted">Show
                                <span id="folder-text">deleted mails</span></a></small>
                    </div>
                    <!-- Email Sender List -->
                    <div id="email-list" class="list-group list-group-flush flex-grow-1 overflow-auto">
                        <!-- Senders will be loaded here via JavaScript -->
                        <div class="text-center p-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <!-- Toggle Message -->
                    <div id="toggle-message-container" class="alert alert-info d-flex align-items-center mt-3"
                        role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <small class="text-muted">To see all conversations, click person name again.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Email Content -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-envelope-open-text me-2 text-primary"></i>
                        Conversation
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div id="email-content" class="accordion accordion-flush">
                        <!-- Email conversation will be loaded here -->
                        <div class="text-center p-4">
                            <p>Select an email from the list to view the conversation.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const emailList = document.getElementById('email-list');
        const emailContent = document.getElementById('email-content');
        const searchInput = document.getElementById('search-input');
        const clearSearchBtn = document.getElementById('clear-search');
        const checkEmailsBtn = document.getElementById('check-emails-btn');
        const folderTitle = document.getElementById('folder-title');
        const emailFetchProgress = document.getElementById('email-fetch-progress');
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        const folderText = document.getElementById('folder-text');

        let currentFolder = 'inbox';
        let activeConversationEmail = null; // To keep track of the currently active conversation
        const toggleMessageContainer = document.getElementById('toggle-message-container');
        const showDeactivatedEmailsBtn = document.getElementById('show-deactivated-emails');

        // Function to fetch user-specific email settings
        async function fetchUserEmailSettings() {
            try {
                // Assuming the user ID is available globally or can be fetched
                // For this example, we'll assume a global PHP variable `currentUserId` is set.
                // In a real application, you might fetch this from a secure endpoint.
                const userId = <?php echo $_SESSION['user_id'] ?? 'null'; ?>;

                if (userId === null) {
                    console.warn('User ID not found. Cannot fetch personalized email settings.');
                    return;
                }

                const response = await apiRequest('email_settings', 'get', {
                    user_id: userId
                });
                if (response.success) {
                    // Store settings globally or pass to functions that need them
                    window.userEmailSettings = response.data;
                } else {
                    console.error('Failed to fetch user email settings:', response.message);
                    // Fallback to default settings or show an an error to the user
                }
            } catch (error) {
                console.error('Error fetching user email settings:', error);
                // Fallback to default settings or show an error to the user
            }
        }

        fetchUserEmailSettings(); // Call on page load

        function fetchAndDisplayConversations(folder) {
            let action;
            let title;
            let iconClass;

            if (folder === 'inbox') {
                action = 'list_senders';
                title = 'Inbox';
                iconClass = 'fas fa-inbox';
                folderText.innerHTML = 'deleted';
            } else if (folder === 'deactivated') {
                action = 'list_deactivated_senders'; // New action for deactivated emails
                title = 'Deleted Mails';
                iconClass = 'fas fa-trash-alt'; // Icon for deleted mails
                folderText.innerHTML = 'inbox';
            } else {
                // Default to inbox if an unknown folder is passed
                action = 'list_senders';
                title = 'Inbox';
                iconClass = 'fas fa-inbox';
            }

            folderTitle.innerHTML = `<i class="${iconClass} me-2 text-primary"></i> ${title}`;

            emailList.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>`;

            apiRequest('emails', action)
                .then(data => {
                    if (data.success) {
                        emailList.innerHTML = ''; // Clear spinner
                        const conversations = data.conversations;
                        if (conversations.length === 0) {
                            emailList.innerHTML =
                                `<p class="text-center p-4">No emails found in ${title}.</p>`;
                            return;
                        }
                        conversations.forEach(convo => {
                            const unreadBadge = convo.unread_count > 0 ?
                                `<span class="badge bg-warning rounded-pill ms-2 ps-2">${convo.unread_count}</span><small class="ms-2">new mail</small> ` :
                                '';
                            const listItem = document.createElement('a');
                            listItem.href = '#';
                            listItem.className = 'list-group-item list-group-item-action';
                            listItem.dataset.senderEmail = convo.sender_email;
                            listItem.innerHTML = `
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${convo.sender_name}</h6>
                                <small>${new Date(convo.latest_date * 1000).toLocaleDateString()}</small>
                            </div>
                             <span class="mb-1 d-flex justify-content-between"><small>${convo.sender_email} </small>
                               <span>
                                     <i class="fas fa-eye-slash deactivate-conversation-btn me-2" data-sender-email="${convo.sender_email}"></i>
                                    <!--   <i class="fas fa-trash-alt text-danger delete-conversation-btn" data-sender-email="${convo.sender_email}"></i> -->   </span>
                             </span>
                            ${unreadBadge}
                           
                        `;

                            emailList.appendChild(listItem);
                        });
                        // After loading, if there's an active conversation, hide others
                        if (activeConversationEmail) {
                            hideOtherListItems(activeConversationEmail);
                        }
                    } else {
                        emailList.innerHTML =
                            `<p class="text-center p-4 text-danger">Error: ${data.message}</p>`;
                    }
                })
                .catch(error => {
                    console.error(`Error fetching ${folder} emails:`, error);
                    emailList.innerHTML =
                        `<p class="text-center p-4 text-danger">An error occurred while fetching emails.</p>`;
                });
        }

        function hideOtherListItems(selectedEmail) {
            const items = emailList.querySelectorAll('.list-group-item');
            items.forEach(item => {
                if (item.dataset.senderEmail !== selectedEmail) {
                    item.style.display = 'none';
                }
            });
        }

        function showAllListItems() {
            const items = emailList.querySelectorAll('.list-group-item');
            items.forEach(item => {
                item.style.display = '';
            });
        }

        function addToggleMessage() {
            toggleMessageContainer.style.display = 'block';
        }

        function removeToggleMessage() {
            toggleMessageContainer.style.display = 'none';
        }

        // Search functionality
        function filterSenders() {
            const searchTerm = searchInput.value.toLowerCase();
            const items = emailList.querySelectorAll('.list-group-item');
            items.forEach(item => {
                const senderName = item.querySelector('h6').textContent.toLowerCase();
                const senderEmail = item.querySelector('small').textContent.toLowerCase();
                if (senderName.includes(searchTerm) || senderEmail.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('keyup', filterSenders);
        clearSearchBtn.addEventListener('click', () => {
            searchInput.value = '';
            filterSenders();
        });

        // Function to check for new emails from the server
        function checkNewEmails() {
            const btnIcon = checkEmailsBtn.querySelector('i');
            checkEmailsBtn.disabled = true;
            btnIcon.classList.add('fa-spin'); // Add spinner animation
            emailFetchProgress.style.display = 'block'; // Show progress bar
            progressBar.style.width = '0%';
            progressBar.setAttribute('aria-valuenow', '0');
            progressText.textContent = 'Starting email fetch...';

            // Use EventSource for real-time progress updates
            const eventSource = new EventSource('api.php?entity=emails&action=check_new_emails');

            eventSource.onmessage = function (event) {
                const data = JSON.parse(event.data);
                if (data.status === 'progress') {
                    const percentage = data.total > 0 ? (data.processed / data.total) * 100 : 0;
                    progressBar.style.width = `${percentage}%`;
                    progressBar.setAttribute('aria-valuenow', percentage);
                    progressText.textContent =
                        `${data.stage}: ${data.processed} of ${data.total} records processed.`;
                } else if (data.status === 'complete') {
                    console.log(`Found ${data.new_email_count} new emails.`);
                    progressText.textContent = `Completed: Found ${data.new_email_count} new emails.`;
                    progressBar.style.width = '100%';
                    progressBar.setAttribute('aria-valuenow', '100');
                    fetchAndDisplayConversations(currentFolder);
                    eventSource.close();
                    checkEmailsBtn.disabled = false;
                    btnIcon.classList.remove('fa-spin');
                    emailFetchProgress.style.display = 'none';
                } else if (data.status === 'error') {
                    console.error('Error checking for new emails:', data.message);
                    progressText.textContent = `Error: ${data.message}`;
                    progressBar.classList.add('bg-danger'); // Indicate error
                    eventSource.close();
                    checkEmailsBtn.disabled = false;
                    btnIcon.classList.remove('fa-spin');
                    emailFetchProgress.style.display = 'none';
                }
            };

            eventSource.onerror = function (err) {
                console.error('EventSource failed:', err);
                progressText.textContent = 'Error: Connection to server lost or failed.';
                progressBar.classList.add('bg-danger');
                eventSource.close();
                checkEmailsBtn.disabled = false;
                btnIcon.classList.remove('fa-spin');
                emailFetchProgress.style.display = 'none';
            };
        }


        checkEmailsBtn.addEventListener('click', checkNewEmails);

        // Function to delete a single email conversation
        function deleteConversation(senderEmail) {
            if (!confirm(`Are you sure you want to delete the conversation with ${senderEmail}?`)) {
                return;
            }

            apiRequest('emails', 'delete_conversation', {
                sender_email: senderEmail
            })
                .then(data => {
                    if (data.success) {
                        alert('Conversation deleted successfully!');
                        fetchAndDisplayConversations(currentFolder); // Refresh the list
                        emailContent.innerHTML =
                            '<p class="text-center p-4">Select an email from the list to view the conversation.</p>'; // Clear content
                    } else {
                        alert(`Error deleting conversation: ${data.message}`);
                    }
                })
                .catch(error => {
                    console.error('Error deleting conversation:', error);
                    alert('An error occurred while deleting the conversation.');
                });
        }

        // Function to deactivate a single email conversation
        function deactivateConversation(senderEmail) {
            if (!confirm(`Are you sure you want to hide the conversation with ${senderEmail}?`)) {
                return;
            }

            apiRequest('emails', 'deactivate_conversation', {
                sender_email: senderEmail
            })
                .then(data => {
                    if (data.success) {
                        // On success, find the specific list item and remove it from the DOM
                        const itemToRemove = emailList.querySelector(
                            `.list-group-item[data-sender-email="${senderEmail}"]`);
                        if (itemToRemove) {
                            itemToRemove.remove();
                        }
                        // If the deactivated conversation was being viewed, clear the content area
                        if (activeConversationEmail === senderEmail) {
                            emailContent.innerHTML =
                                '<p class="text-center p-4">Select an email from the list to view the conversation.</p>';
                            activeConversationEmail = null;
                        }
                        // Consider a more subtle notification than an alert in a real app
                        // For now, an alert provides clear feedback.
                        alert('Conversation hidden successfully.');
                    } else {
                        alert(`Error: ${data.message}`);
                    }
                })
                .catch(error => {
                    console.error('Error deactivating conversation:', error);
                    alert('An error occurred while hiding the conversation.');
                });
        }

        // Initial load
        fetchAndDisplayConversations(currentFolder);

        // Event listener for "Click to see deleted mails"
        showDeactivatedEmailsBtn.addEventListener('click', function (event) {
            event.preventDefault();
            currentFolder = currentFolder == 'deactivated' ? 'inbox' : 'deactivated';
            fetchAndDisplayConversations(currentFolder);
        });

        // Handle click on a sender to load the conversation or delete
        emailList.addEventListener('click', function (event) {
            const target = event.target;

            // Handle deactivate button click
            if (target.closest('.deactivate-conversation-btn')) {
                event.preventDefault();
                const senderEmail = target.closest('.deactivate-conversation-btn').dataset.senderEmail;
                deactivateConversation(senderEmail);
                return;
            }


            // Handle delete button click
            if (target.closest('.delete-conversation-btn')) {
                event.preventDefault();
                const senderEmail = target.closest('.delete-conversation-btn').dataset.senderEmail;
                deleteConversation(senderEmail);
                return;
            }

            // Handle conversation click
            const listItem = target.closest('.list-group-item');
            if (!listItem) return;

            event.preventDefault();

            const senderEmail = listItem.dataset.senderEmail;

            if (activeConversationEmail === senderEmail) {
                // If the same item is clicked again, clear content and show all list items
                emailContent.innerHTML =
                    '<p class="text-center p-4">Select an email from the list to view the conversation.</p>';
                showAllListItems();
                removeToggleMessage(); // Remove message when showing all
                listItem.classList.remove('active');
                activeConversationEmail = null;
            } else {
                // Highlight the selected item and hide others
                const currentlyActive = emailList.querySelector('.active');
                if (currentlyActive) {
                    currentlyActive.classList.remove('active');
                }
                listItem.classList.add('active');
                hideOtherListItems(senderEmail);
                addToggleMessage(); // Add message to newly active
                activeConversationEmail = senderEmail;
                loadConversation(senderEmail, listItem);
            }
        });

        // Function to load and display a conversation
        function loadConversation(senderEmail, targetElement) {
            emailContent.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>`;

            // Mark the conversation as read first
            apiRequest('emails', 'mark_as_read', {
                sender_email: senderEmail
            })
                .then(markData => {
                    if (markData.success && markData.updated_count > 0) {
                        // Remove the unread badge from the UI
                        const unreadBadge = targetElement.querySelector('.badge');
                        if (unreadBadge) {
                            unreadBadge.remove();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error marking conversation as read:', error);
                });


            apiRequest('emails', 'get_conversation', {
                sender_email: senderEmail
            })
                .then(data => {
                    if (data.success) {
                        emailContent.innerHTML = ''; // Clear spinner
                        const conversation = data.conversation;
                        if (conversation.emails.length === 0) {
                            emailContent.innerHTML =
                                '<p class="text-center p-4">No emails in this conversation.</p>';
                            return;
                        }

                        conversation.emails.forEach((email, index) => {
                            const accordionItem = `
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading-${index}">
                                    <button class="accordion-button ${index === 0 ? '' : 'collapsed'}"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#collapse-${index}"
                                            aria-expanded="${index === 0 ? 'true' : 'false'}"
                                            aria-controls="collapse-${index}">
                                        <div class="d-flex w-100 justify-content-between">
                                            <div class="fw-bold me-2"> <p>Subject: </br>
                                           <small class="text-muted">${email.subject}</small></p></div>
                                            <small >${new Date(email.date * 1000).toLocaleString()}</small>
                                        </div>
                                    </button>
                                </h2>

                                <!-- added data-bs-parent -->
                                <div id="collapse-${index}"
                                    class="accordion-collapse collapse ${index === 0 ? 'show' : ''}"
                                    aria-labelledby="heading-${index}"
                                    data-bs-parent="#email-content">

                                    <div class="accordion-body overflow-auto" style="max-height:65vh;">
                                        <small>From: ${email.from}</small><br>
                                        <small>To: ${email.to}</small><hr>
                                        <div class="email-body">${email.body}</div>

                                        ${email.attachments?.length
                                    ? `<hr><h6>Attachments:</h6><ul class="list-unstyled">
                                                ${email.attachments.map(a => `
                                                    <li>
                                                        <a href="download.php?file=${encodeURIComponent(a.file_path.replace(/^\//, ''))}&filename=${encodeURIComponent(a.filename)}"
                                                        class="download-attachment-btn" target="_blank">
                                                            <i class="fas fa-paperclip me-1"></i>
                                                            ${a.filename} (${(a.size / 1024).toFixed(2)} KB)
                                                        </a>
                                                    </li>`).join('')}
                                            </ul>`
                                    : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                            emailContent.innerHTML += accordionItem;
                        });
                    } else {
                        emailContent.innerHTML =
                            `<p class="text-center p-4 text-danger">Error: ${data.message}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching conversation:', error);
                    emailContent.innerHTML =
                        '<p class="text-center p-4 text-danger">An error occurred while fetching the conversation.</p>';
                });
        }
    });
</script>