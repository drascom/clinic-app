<?php
// File: public/app-msg/add_edit_message.php

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

$message_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$page_title = $message_id > 0 ? 'Edit Message' : 'Add New Message';

// Placeholder for current user ID - replace with actual session/auth logic
$current_user_id = 1;
?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<div class="container mt-4">
    <h2><?php echo $page_title; ?></h2>

    <form id="messageForm">
        <input type="hidden" id="messageId" value="<?php echo $message_id; ?>">
        <input type="hidden" id="senderId" value="<?php echo $current_user_id; ?>">

        <div class="mb-3">
            <label for="receiverId" class="form-label">Receiver (Optional):</label>
            <select class="form-control" id="receiverId" style="width: 100%;">
                <option value="">Select Receiver</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="patientId" class="form-label">Patient (Optional):</label>
            <select class="form-control" id="patientId" style="width: 100%;">
                <option value="">Select Patient</option>
            </select>
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
            <textarea class="form-control" id="messageContent" rows="5" required
                placeholder="Enter your message"></textarea>
            <div class="invalid-feedback">Message content cannot be empty.</div>
        </div>

        <button type="submit" class="btn btn-primary" id="submitMessageBtn">
            <?php echo $message_id > 0 ? 'Update Message' : 'Create Message'; ?>
        </button>
        <a href="index.php" class="btn btn-secondary">Back to Messages</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="../assets/js/api-helper.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', async function () {
        const messageId = document.getElementById('messageId').value;
        const senderId = document.getElementById('senderId').value;
        const receiverIdSelect = document.getElementById('receiverId'); // Renamed for clarity
        const patientIdSelect = document.getElementById('patientId'); // New patientId select
        const relatedTableSelect = document.getElementById('relatedTable');
        const relatedIdInput = document.getElementById('relatedId');
        const messageContentTextarea = document.getElementById('messageContent');
        const submitMessageBtn = document.getElementById('submitMessageBtn');
        const messageForm = document.getElementById('messageForm');

        // Initialize Select2 for receiverId
        $('#receiverId').select2({
            placeholder: 'Search for a receiver',
            allowClear: true,
            ajax: {
                delay: 250,
                transport: function (params, success, failure) {
                    const requestData = {
                        search: params.data.term,
                        page: params.data.page || 1
                    };
                    apiRequest('users', 'list', requestData)
                        .then(success)
                        .catch(failure);
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    if (data.success && data.data) { // Assuming the handler returns data in a 'data' property
                        return {
                            results: data.data.map(user => ({
                                id: user.id,
                                text: `${user.username} (ID: ${user.id})`
                            })),
                            pagination: {
                                more: (params.page * 10) < data.total // Adjust based on API response
                            }
                        };
                    }
                    return {
                        results: []
                    };
                },
                cache: true
            },
            minimumInputLength: 1
        });


        // Initialize Select2 for patientId
        $('#patientId').select2({
            placeholder: 'Search for a patient',
            allowClear: true,
            ajax: {
                delay: 250,
                transport: function (params, success, failure) {
                    const requestData = {
                        search: params.data.term,
                        page: params.data.page || 1
                    };
                    apiRequest('patients', 'list', requestData)
                        .then(success)
                        .catch(failure);
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    if (data.success && data.data) {
                        return {
                            results: data.data.map(patient => ({
                                id: patient.id,
                                text: `${patient.name} (ID: ${patient.id})`
                            })),
                            pagination: {
                                more: (params.page * 10) < data.total
                            }
                        };
                    }
                    return {
                        results: []
                    };
                },
                cache: true
            },
            minimumInputLength: 1
        });


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
        relatedTableSelect.addEventListener('change', async function () {
            validateField(this);
            await populateRelatedIdOptions(this.value);
            updateSubmitButtonState();
        });
        relatedIdInput.addEventListener('change', function () {
            validateField(this);
            updateSubmitButtonState();
        });
        messageContentTextarea.addEventListener('input', function () {
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
                // Set receiverIdSelect value for Select2
                if (message.receiver_id) {
                    const receiverOption = new Option(message.receiver_username + ' (ID: ' + message.receiver_id + ')', message.receiver_id, true, true);
                    receiverIdSelect.append(receiverOption).trigger('change');
                }

                // Set patientIdSelect value for Select2
                if (message.patient_id) {
                    // You might need to fetch patient name if not available in message_data
                    // For now, assuming message_data might contain patient_name or similar
                    const patientName = message.patient_name || 'Patient'; // Placeholder
                    const patientOption = new Option(patientName + ' (ID: ' + message.patient_id + ')', message.patient_id, true, true);
                    patientIdSelect.append(patientOption).trigger('change');
                }

                // Handle related_tables which is now a JSON array
                if (message.related_tables) {
                    try {
                        const relatedEntities = JSON.parse(message.related_tables);
                        if (Array.isArray(relatedEntities) && relatedEntities.length > 0) {
                            const firstEntity = relatedEntities[0]; // Assuming only one related entity for now
                            relatedTableSelect.value = firstEntity.table_name;
                            await populateRelatedIdOptions(firstEntity.table_name);
                            relatedIdInput.value = firstEntity.id;
                        }
                    } catch (e) {
                        console.error("Error parsing related_tables JSON:", e);
                    }
                }
                messageContentTextarea.value = message.message;
                updateSubmitButtonState(); // Update button state after populating
            } else {
                alert('Error fetching message: ' + response.message);
                // Redirect or handle error
            }
        }

        messageForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (!validateForm()) {
                alert('Please fill in all required fields.');
                return;
            }

            const action = messageId > 0 ? 'update' : 'create';

            const relatedTableValue = relatedTableSelect.value;
            const relatedIdValue = relatedIdInput.value;
            let relatedTablesPayload = null;

            if (relatedTableValue && relatedIdValue) {
                // Find the display text from the selected option to get the field_name logic
                const selectedOption = Array.from(relatedIdInput.options).find(opt => opt.value === relatedIdValue);
                const selectedText = selectedOption ? selectedOption.textContent : '';

                // This map should ideally come from a centralized config or API
                const tableFieldMap = {
                    'patients': 'name',
                    'staff': 'name',
                    'users': 'username',
                    'appointments': 'title',
                    'rooms': 'name',
                    'agencies': 'name',
                    'procedures': 'name',
                    'surgeries': 'title',
                    'candidates': 'name'
                };
                const fieldName = tableFieldMap[relatedTableValue] || 'id';

                relatedTablesPayload = JSON.stringify([{
                    table_name: relatedTableValue,
                    field_name: fieldName,
                    id: parseInt(relatedIdValue, 10)
                }]);
            }

            const payload = {
                sender_id: senderId,
                receiver_id: receiverIdSelect.value || null,
                patient_id: patientIdSelect.value || null, // Add patient_id to payload
                related_tables: relatedTablesPayload,
                message: messageContentTextarea.value,
            };

            // Sample insert/update request:
            // To create a new message:
            // apiRequest('messages', 'create', {
            //     sender_id: 1,
            //     receiver_id: null, // Optional
            //     related_table: [{"table_name":"patients","field_name":"name","id":1}],
            //     patient_id: 1,
            //     message: 'This is a new message related to patient 123.'
            // });
            //
            // To update an existing message (assuming messageId is available):
            // apiRequest('messages', 'update', {
            //     message_id: messageId,
            //     sender_id: 1,
            //     receiver_id: 2, // Optional
            //     related_table: [{"table_name":"staff","field_name":"name","id":6}]
            //     patient_id: null,
            //     message: 'Updated message content for staff 6.'
            // });

            if (messageId > 0) {
                payload.message_id = messageId;
            }

            const response = await apiRequest('messages', action, payload);

            if (response.success) {
                alert('Message ' + (action === 'create' ? 'created' : 'updated') + ' successfully!');
                // window.location.href = 'index.php'; // Redirect to message list
            } else {
                alert('Error ' + (action === 'create' ? 'creating' : 'updating') + ' message: ' + response.message);
            }
        });
    });
</script>