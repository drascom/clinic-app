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
                <div class="btn-group w-100 mb-3" role="group" aria-label="Message Type">
                    <input type="radio" class="btn-check" name="message-type" id="btnradioPrivate" autocomplete="off">
                    <label class="btn btn-outline-primary" for="btnradioPrivate">Private</label>

                    <input type="radio" class="btn-check" name="message-type" id="btnradioPatients" autocomplete="off">
                    <label class="btn btn-outline-primary" for="btnradioPatients">Patients</label>
                </div>
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
                    <input type="hidden" id="receiverIdInput" />
                    <input type="hidden" id="patientIdInput" />
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
    const currentUserId = <?php echo json_encode($current_user_id); ?>;
</script>
<script src="page.js"></script>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>