<?php
// File: public/app-msg/add_edit_message.php

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

$message_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$page_title = $message_id > 0 ? 'Edit Message' : 'Add New Message';

// Placeholder for current user ID - replace with actual session/auth logic
$current_user_id = 1;
?>

<div class="container mt-4">
    <h2><?php echo $page_title; ?></h2>

    <form id="messageForm">
        <input type="hidden" id="messageId" value="<?php echo $message_id; ?>">
        <input type="hidden" id="senderId" value="<?php echo $current_user_id; ?>">

        <div class="mb-3">
            <label for="receiverId" class="form-label">Receiver ID (Optional):</label>
            <input type="number" class="form-control" id="receiverId" placeholder="Enter receiver user ID">
        </div>

        <div class="mb-3">
            <label for="relatedTable" class="form-label">Related Entity Table:</label>
            <select class="form-control" id="relatedTable" required>
                <option value="">Select Entity</option>

            </select>
            <div class="invalid-feedback">Please select a related entity.</div>
        </div>

        <div class="mb-3">
            <label for="relatedId" class="form-label">Related Entity ID:</label>
            <select class="form-control" id="relatedId" required>
                <option value="">Select ID</option>
            </select>
            <div class="invalid-feedback">Please select a related entity ID.</div>
        </div>

        <div class="mb-3">
            <label for="messageContent" class="form-label">Message:</label>
            <textarea class="form-control" id="messageContent" rows="5" required placeholder="Enter your message"></textarea>
            <div class="invalid-feedback">Message content cannot be empty.</div>
        </div>

        <button type="submit" class="btn btn-primary" id="submitMessageBtn">
            <?php echo $message_id > 0 ? 'Update Message' : 'Create Message'; ?>
        </button>
        <a href="index.php" class="btn btn-secondary">Back to Messages</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script src="../assets/js/api-helper.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', async function() {
        const messageId = document.getElementById('messageId').value;
        const senderId = document.getElementById('senderId').value;
        const receiverIdInput = document.getElementById('receiverId');
        const relatedTableSelect = document.getElementById('relatedTable');
        const relatedIdInput = document.getElementById('relatedId');
        const messageContentTextarea = document.getElementById('messageContent');
        const submitMessageBtn = document.getElementById('submitMessageBtn');
        const messageForm = document.getElementById('messageForm');


        // Function to validate a single field
        function validateField(fieldElement) {
            if (fieldElement.hasAttribute('required') && !fieldElement.value.trim()) {
                fieldElement.classList.add('is-invalid');
                return false;
            } else {
                fieldElement.classList.remove('is-invalid');
                return true;
            }
        }

        // Function to validate the entire form
        function validateForm() {
            let isValid = true;
            isValid = validateField(relatedTableSelect) && isValid;
            isValid = validateField(relatedIdInput) && isValid;
            isValid = validateField(messageContentTextarea) && isValid;
            return isValid;
        }

        // Update submit button state based on form validity
        function updateSubmitButtonState() {
            submitMessageBtn.disabled = !validateForm();
        }

        // Add event listeners for real-time validation
        relatedTableSelect.addEventListener('change', async function() {
            validateField(this);
            await populateRelatedIdOptions(this.value);
            updateSubmitButtonState();
        });
        relatedIdInput.addEventListener('change', function() {
            validateField(this);
            updateSubmitButtonState();
        });
        messageContentTextarea.addEventListener('input', function() {
            validateField(this);
            updateSubmitButtonState();
        });

        // Function to populate relatedId dropdown based on selected table
        async function populateRelatedIdOptions(tableName) {
            // Clear existing options first, except the default "Select ID"
            relatedIdInput.innerHTML = '<option value="">Select ID</option>';
            if (!tableName) {
                return;
            }

            try {
                const response = await apiRequest('entities', 'list', {
                    table: tableName
                });
                if (response.success && response.records) {
                    response.records.forEach(record => {
                        const option = document.createElement('option');
                        option.value = record.id;
                        option.textContent = `${record.label} (ID: ${record.id})`;
                        relatedIdInput.appendChild(option);
                    });
                } else {
                    console.error('Error fetching related IDs:', response.message);
                }
            } catch (error) {
                console.error('API request failed:', error);
            }
        }

        // Initial state of the submit button
        updateSubmitButtonState();

        // Fetch entities to populate relatedTable dropdown
        try {
            const entitiesResponse = await apiRequest('entities', 'tables');
            if (entitiesResponse.success && entitiesResponse.tables) {
                entitiesResponse.tables.forEach(table => {
                    const option = document.createElement('option');
                    option.value = table.name;
                    option.textContent = table.name?.replace(/^./, c => c.toUpperCase());
                    relatedTableSelect.appendChild(option);
                });
            } else {
                console.error('Error fetching entities:', entitiesResponse.message);
            }
        } catch (error) {
            console.error('API request failed:', error);
        }

        if (messageId > 0) {
            // Fetch message data for editing
            const response = await apiRequest('messages', 'get', {
                message_id: messageId
            });
            if (response.success && response.message_data) {
                const message = response.message_data;
                receiverIdInput.value = message.receiver_id || '';
                relatedTableSelect.value = message.related_table;
                // Populate relatedId options and then set the value
                await populateRelatedIdOptions(message.related_table);
                relatedIdInput.value = message.related_id;
                messageContentTextarea.value = message.message;
                updateSubmitButtonState(); // Update button state after populating
            } else {
                alert('Error fetching message: ' + response.message);
                // Redirect or handle error
            }
        }

        messageForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            if (!validateForm()) {
                alert('Please fill in all required fields.');
                return;
            }

            const action = messageId > 0 ? 'update' : 'create';
            const payload = {
                sender_id: senderId,
                receiver_id: receiverIdInput.value || null,
                related_table: relatedTableSelect.value,
                related_id: relatedIdInput.value,
                message: messageContentTextarea.value
            };

            // Sample insert/update request:
            // To create a new message:
            // apiRequest('messages', 'create', {
            //     sender_id: 1,
            //     receiver_id: null, // Optional
            //     related_table: 'patients',
            //     related_id: 123,
            //     message: 'This is a new message related to patient 123.'
            // });
            //
            // To update an existing message (assuming messageId is available):
            // apiRequest('messages', 'update', {
            //     message_id: messageId,
            //     sender_id: 1,
            //     receiver_id: 456, // Optional
            //     related_table: 'staff',
            //     related_id: 789,
            //     message: 'Updated message content for staff 789.'
            // });

            if (messageId > 0) {
                payload.message_id = messageId;
            }

            const response = await apiRequest('messages', action, payload);

            if (response.success) {
                alert('Message ' + (action === 'create' ? 'created' : 'updated') + ' successfully!');
                window.location.href = 'index.php'; // Redirect to message list
            } else {
                alert('Error ' + (action === 'create' ? 'creating' : 'updating') + ' message: ' + response.message);
            }
        });
    });
</script>