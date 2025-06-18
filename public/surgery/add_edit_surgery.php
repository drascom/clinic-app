<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header('Location: /login.php');
    exit();
}

$surgery = null;
$errors = [];
$is_editing = false;

// Fetch surgery data if ID is provided (for editing)

$surgery_id = $_GET['id'] ?? null;
$patient_id_from_url = $_GET['patient_id'] ?? null; // Get patient_id if passed in URL
$room_id_from_url = $_GET['room_id'] ?? null; // Get room_id if passed in URL
$date_from_url = $_GET['date'] ?? null; // Get date if passed in URL
$is_editing = $surgery_id !== null;

$page_title = $is_editing ? 'Edit Surgery' : 'Add New Surgery';
require_once __DIR__ . '/../includes/header.php';
?>


<div class="container emp">
    <!-- Surgery Form -->
    <div class="card">
        <!-- Page Header -->
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <a href="/surgery/surgeries.php" class="btn btn-outline-dark btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>
                    <span class="d-none d-sm-inline">Back to Surgeries</span>
                </a>
                <h4 class="mb-0">
                    <i class="far fa-hospital me-2"></i>
                    <?php echo $page_title; ?>
                </h4>
                <div class="d-flex align-items-center">
                    <!-- Status Display -->
                    <div class="me-3">
                        <span class="text-muted small">Status:</span>
                        <span id="status-display" class="badge bg-secondary ms-1">Loading...</span>
                        <?php if (is_admin()): ?>
                            <button type="button" class="btn btn-sm btn-link text-primary ms-1" id="edit-status-btn"
                                title="Edit Status">
                                <i class="fas fa-pen fa-xs"></i>
                            </button>
                        <?php endif; ?>
                        <!-- Inline Status Edit (Hidden by default) -->
                        <div id="status-edit-container" class="ms-1" style="display: none;">
                            <select class="form-select form-select-sm d-inline-block" id="status-inline"
                                style="width: auto;">
                                <option value="scheduled">Scheduled</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <button type="button" class="btn  btn-text text-success ms-1" id="save-status-btn"
                                title="Save">
                                <i class="fas fa-check fa-xs"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-text teext-danger ms-1" id="cancel-status-btn"
                                title="Cancel">
                                <i class="fas fa-times fa-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form id="surgery-form" novalidate>
                <?php if ($is_editing): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($surgery_id); ?>">
                <?php endif; ?>
                <input type="hidden" name="entity" value="surgeries">
                <input type="hidden" name="action" value="<?php echo $is_editing ? 'update' : 'add'; ?>">

                <!-- General Error Alert -->
                <div class="alert alert-danger d-none" id="form-error-alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="form-error-message"></span>
                </div>

                <div class="row">
                    <!-- Left Column -->
                    <div class="col-md-5">
                        <!-- Date and Room Section -->
                        <fieldset class="border rounded p-3 mb-4">
                            <legend class="w-auto px-2 mb-3" style="font-size: 1rem;">
                                <i class="far fa-calendar-alt me-2"></i>Date & Room Selection
                            </legend>
                            <?php if (!empty($date_from_url) && !empty($room_id_from_url)): ?>
                                <div class="align-items-center">
                                    <div class="alert alert-info mb-2">
                                        <i class="far fa-calendar me-2"></i> Date:
                                        <span class="text-primary">
                                            <?php echo date('F j, Y', strtotime($date_from_url)); ?></span>
                                        <input type="hidden" name="date" value="<?php echo $date_from_url; ?>">
                                    </div>
                                    <div class="alert alert-info mb-2">
                                        <i class="far fa-door-open me-2"></i> Room: <span class="text-primary"
                                            id="selected-room-name">Loading...</span>
                                    </div>
                                    <input type="hidden" name="room_id" value="<?php echo $room_id_from_url; ?>">
                                </div>
                            <?php endif; ?>

                            <!-- Date Field -->
                            <div class="mb-3" <?php echo !empty($date_from_url) ? 'style="display: none;"' : ''; ?>>
                                <label for="date" class="form-label">Surgery Date <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="date" name="date"
                                    value="<?php echo $date_from_url; ?>" required>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Room Field -->
                            <div class="mb-3" <?php echo !empty($room_id_from_url) ? 'style="display: none;"' : ''; ?>>
                                <label for="room_id" class="form-label">Room <span class="text-danger">*</span></label>
                                <select class="form-select" id="room_id" name="room_id" required>
                                    <option value="">Select Room</option>
                                    <!-- Room options will be loaded via JavaScript -->
                                </select>
                                <div class="invalid-feedback"></div>
                                <div class="form-text" id="room-availability-text"></div>
                            </div>
                        </fieldset>
                        <!-- Notes Section -->
                        <fieldset class="border rounded p-3 mb-3 shadow-sm">
                            <legend class="w-auto px-2 mb-3" style="font-size: 1rem;">
                                <i class="far fa-sticky-note me-2"></i>Notes
                            </legend>

                            <textarea class="form-control" id="notes" name="notes" rows="6"
                                placeholder="Enter any additional notes about the surgery..."></textarea>
                            <div class="invalid-feedback"></div>
                        </fieldset>

                    </div>

                    <!-- Right Column -->
                    <div class="col-md-7">
                        <!-- Patient Selection -->
                        <?php if (!$is_editing): ?>
                            <fieldset class="border rounded p-3 mb-3 shadow-sm">
                                <legend class="w-auto px-2 mb-3" style="font-size: 1rem;">
                                    <i class="far fa-user me-2"></i>Patient Selection
                                </legend>

                                <div class="mb-3">
                                    <label for="patient_id" class="form-label">Patient <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select class="form-select select2-enable" id="patient_id" name="patient_id" required>
                                            <option value="">Select Patient</option>
                                        </select>
                                        <button type="button" class="btn btn-text text-primary" data-bs-toggle="modal"
                                            data-bs-target="#newPatientModal">
                                            <i class="far fa-plus me-1"></i>
                                            <span class="d-none d-sm-inline">New Patient</span>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </fieldset>
                        <?php else: ?>
                            <input type="hidden" name="patient_id" id="patient_id">
                        <?php endif; ?>
                        <!-- Technicians Section -->
                        <fieldset class="border rounded p-3 mb-3 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center">
                                <legend class="w-auto " style="font-size: 1rem;">
                                    <i class="fas fa-user-md me-2"></i>Assigned Technicians
                                </legend>
                                <button type="button" class="btn p-0 btn-sm btn-link" id="add-technicians-btn">
                                    <i class="far fa-plus me-1"></i>Add Technicians
                                </button>
                            </div>

                            <div class="mb-3">
                                <select class="form-select" id="technicians" name="technicians[]" multiple
                                    style="display: none;"></select>
                                <div class="invalid-feedback"></div>
                                <div id="assigned-technicians" class="mt-2"></div>
                            </div>
                        </fieldset>
                        <!-- Surgery Details -->
                        <fieldset class="border rounded p-3 mb-3 shadow-sm">
                            <legend class="w-auto px-2 mb-3" style="font-size: 1rem;">
                                <i class="far fa-hospital me-2"></i>Graft Counts
                            </legend>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="predicted_grafts_count" class="form-label">Predicted Grafts</label>
                                    <input type="number" class="form-control" id="predicted_grafts_count"
                                        name="predicted_grafts_count" min="0" placeholder="Enter predicted grafts">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="current_grafts_count" class="form-label">Current Grafts</label>
                                    <input type="number" class="form-control" id="current_grafts_count"
                                        name="current_grafts_count" min="0" placeholder="Enter current grafts">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                    </div>

                    <input type="hidden" id="status" name="status" value="scheduled">
                    </fieldset>
                </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex align-items-center justify-content-end gap-2">
            <!-- Status Messages -->
            <div>
                <a href="<?php echo $is_editing ? '/surgery/surgeries.php' : '/surgery/surgeries.php'; ?>"
                    class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i>Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="far fa-save me-1"></i>
                    <span id="save-button-text"><?php echo $is_editing ? 'Update Surgery' : 'Add Surgery'; ?></span>
                </button>
            </div>
        </div>
        </form>
    </div>
</div>


<!-- New Patient Modal -->
<div class="modal fade" id="newPatientModal" tabindex="-1" aria-labelledby="newPatientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newPatientModalLabel">
                    <i class="fas fa-user-plus me-2"></i>
                    Create New Patient
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="new-patient-form" novalidate>
                    <?php if (is_admin() || is_editor()): ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="new_patient_agency_id" class="form-label">
                                        <i class="far fa-building me-1"></i>
                                        Agency
                                    </label>
                                    <select class="form-select" id="new_patient_agency_id" name="agency_id" required>
                                        <option value="">Select Agency*</option>
                                        <!-- Agency options will be loaded dynamically -->
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    <?php elseif (is_agent()): ?>
                        <!-- Hidden field for agents - their agency_id will be set via JavaScript -->
                        <input type="hidden" id="new_patient_agency_id" name="agency_id" value="">
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="new_patient_name" class="form-label">
                                    <i class="far fa-user me-1"></i>
                                    Patient Name *
                                </label>
                                <input type="text" class="form-control" id="new_patient_name" name="name"
                                    placeholder="Enter patient name" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="new_patient_dob" class="form-label">
                                    <i class="far fa-calendar me-1"></i>
                                    Date of Birth
                                </label>
                                <input type="date" class="form-control" id="new_patient_dob" name="dob">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </form>
                <div id="new-patient-status"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" id="save-new-patient">
                    <i class="far fa-save me-1"></i>Create Patient
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Technician Selection Modal -->
<div class="modal fade" id="technicianModal" tabindex="-1" aria-labelledby="technicianModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="technicianModalLabel">
                    <i class="far fa-user-md me-2"></i>
                    Select Available Technicians
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="technician-loading" class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading available technicians...</p>
                </div>
                <div id="technician-list" class="d-none">
                    <!-- Technicians will be loaded here -->
                </div>
                <div id="no-technicians" class="alert alert-warning d-none">
                    No technicians are available for the selected date. Please choose another date.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-technicians">Save Selection</button>
            </div>
        </div>
    </div>
</div>
</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const surgeryForm = document.getElementById('surgery-form');
        const surgeryIdInput = document.querySelector('#surgery-form input[name="id"]');
        const isEditing = surgeryIdInput !== null;
        const patientSelect = document.getElementById('patient_id'); // Get the patient select dropdown
        const patientIdHiddenInput = document.querySelector(
            '#surgery-form input[name="patient_id"]'); // Hidden patient_id for editing
        const roomSelect = document.getElementById('room_id');
        const dateInput = document.getElementById('date');
        const roomAvailabilityText = document.getElementById('room-availability-text');
        const validationState = { hasInteracted: false }; // Declare validationState

        const newPatientModal = document.getElementById('newPatientModal');
        const saveNewPatientButton = document.getElementById('save-new-patient');
        const newPatientForm = document.getElementById('new-patient-form');
        const newPatientStatusDiv = document.getElementById('new-patient-status');
        let allAgencies = []; // Store all agencies for modal dropdown

        // Global function to update status display (accessible from surgery data loading)
        window.updateStatusDisplayFromData = function (status) {
            const statusDisplay = document.getElementById('status-display');
            const statusColors = {
                'scheduled': 'bg-warning text-dark',
                'confirmed': 'bg-info',
                'completed': 'bg-success',
                'cancelled': 'bg-danger'
            };

            if (statusDisplay) {
                const statusText = status.charAt(0).toUpperCase() + status.slice(1);
                const colorClass = statusColors[status] || 'bg-secondary';
                statusDisplay.className = `badge ${colorClass} ms-1`;
                statusDisplay.textContent = statusText;
            }
        };


        // ===== FORM VALIDATION FUNCTIONS =====

        // Setup form validation
        setupFormValidation();

        /**
         * Setup form validation
         */
        function setupFormValidation() {
            const form = surgeryForm;
            const inputs = form.querySelectorAll('input, select, textarea');

            inputs.forEach(input => {
                // Real-time validation on blur
                input.addEventListener('blur', function () {
                    validateField(this);
                });

                // Clear validation on input
                input.addEventListener('input', function () {
                    if (this.classList.contains('is-invalid')) {
                        this.classList.remove('is-invalid');
                        const feedback = this.parentNode.querySelector('.invalid-feedback');
                        if (feedback) feedback.textContent = '';
                    }
                });
            });
        }

        /**
         * Validate individual field
         */
        function validateField(field) {
            const value = field.value.trim();
            let isValid = true;
            let message = '';

            // Required field validation
            if (field.hasAttribute('required') && !value) {
                isValid = false;
                message = 'This field is required.';
            }

            // Date validation (not in the past for non-admin users)
            else if (field.type === 'date' && value && field.name === 'date') {
                const userRole = '<?php echo get_user_role(); ?>';
                const isAdmin = userRole === 'admin';

                if (!isAdmin) {
                    const selectedDate = new Date(value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);

                    if (selectedDate < today) {
                        isValid = false;
                        message = 'Surgery date cannot be in the past.';
                    }
                }
            }

            // Number validation (graft count)
            else if (field.type === 'number' && value) {
                const numValue = parseInt(value);
                if (isNaN(numValue) || numValue < 0) {
                    isValid = false;
                    message = 'Please enter a valid number.';
                }
            }

            // Update field validation state
            if (isValid) {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            } else {
                field.classList.remove('is-valid');
                field.classList.add('is-invalid');
                const feedback = field.parentNode.querySelector('.invalid-feedback');
                if (feedback) feedback.textContent = message;
            }

            return isValid;
        }

        /**
         * Validate entire form
         */
        function validateForm() {
            const form = surgeryForm;
            const inputs = form.querySelectorAll('input[required], select[required]');
            let isValid = true;
            let firstInvalidField = null;

            // Validate all required fields
            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                    if (!firstInvalidField) {
                        firstInvalidField = input;
                    }
                }
            });

            // Validate technicians
            if (!validateTechniciansAssignment(true)) { // Pass true to show errors immediately
                isValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = document.getElementById('add-technicians-btn');
                }
            }

            if (!isValid && firstInvalidField) {
                firstInvalidField.focus();
                showFormError('Please correct the highlighted errors before submitting.');
            }

            return isValid;
        }

        /**
         * Show form error
         */
        function showFormError(message) {
            const errorAlert = document.getElementById('form-error-alert');
            const errorMessage = document.getElementById('form-error-message');
            errorMessage.textContent = message;
            errorAlert.classList.remove('d-none');
        }

        /**
         * Hide form error
         */
        function hideFormError() {
            const errorAlert = document.getElementById('form-error-alert');
            errorAlert.classList.add('d-none');
        }

        /**
         * Reset form validation
         */
        function resetForm() {
            surgeryForm.reset();
            surgeryForm.classList.remove('was-validated');

            // Clear validation states
            const inputs = surgeryForm.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.classList.remove('is-valid', 'is-invalid');
            });

            hideFormError();
        }

        // ===== END VALIDATION FUNCTIONS =====

        // Function to fetch agencies for the modal
        function fetchModalAgencies() {
            const userRole = '<?php echo get_user_role(); ?>';
            const userAgencyId = '<?php echo get_user_agency_id(); ?>';

            console.log('fetchModalAgencies called - User role:', userRole, 'Agency ID:', userAgencyId);

            if (userRole === 'agent') {
                // For agents, just set their agency_id
                console.log('Agent user - setting agency_id directly');
                populateModalAgencyDropdown();
            } else {
                // For admin and editor, fetch all agencies
                console.log('Admin/Editor user - fetching agencies from API');
                apiRequest('agencies', 'list')
                    .then(data => {
                        console.log('Agencies API response:', data);
                        if (data.success) {
                            allAgencies = data.agencies;
                            console.log('Agencies fetched successfully:', allAgencies.length, 'agencies');
                            populateModalAgencyDropdown();
                        } else {
                            console.error('Error fetching agencies:', data.message || data.error || 'Unknown error');
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching agencies:', error);
                    });
            }
        }

        // Function to populate agency dropdown in modal
        function populateModalAgencyDropdown() {
            const agencySelect = document.getElementById('new_patient_agency_id');
            const userRole = '<?php echo get_user_role(); ?>';
            const userAgencyId = '<?php echo get_user_agency_id(); ?>';

            console.log('populateModalAgencyDropdown called');
            console.log('Agency select element:', agencySelect);
            console.log('User role:', userRole, 'User agency ID:', userAgencyId);
            console.log('All agencies:', allAgencies);

            if (!agencySelect) {
                console.error('Agency select element not found!');
                return; // Field might not exist for all roles
            }

            // For agents, set their agency_id in the hidden field
            if (userRole === 'agent' && userAgencyId) {
                agencySelect.value = userAgencyId;
                console.log('Agent agency_id set to:', userAgencyId);
            } else {
                // For admin/editor, populate dropdown with all agencies
                console.log('Populating dropdown for admin/editor with', allAgencies.length, 'agencies');
                agencySelect.innerHTML = '<option value="">Select Agency*</option>';
                allAgencies.forEach(agency => {
                    const option = document.createElement('option');
                    option.value = agency.id;
                    option.textContent = agency.name;
                    agencySelect.appendChild(option);
                    console.log('Added agency option:', agency.name, 'ID:', agency.id);
                });
                console.log('Agency dropdown populated with', allAgencies.length, 'agencies');
            }
        }

        // Function to apply editor role restrictions
        function applyEditorRestrictions(surgery) {
            const userRole = '<?php echo get_user_role(); ?>';

            if (userRole === 'editor' && isEditing) {
                const isCompleted = surgery.status && surgery.status.toLowerCase() === 'completed';

                // Disable all fields except status
                document.getElementById('date').disabled = true;
                document.getElementById('predicted_grafts_count').disabled = true;
                document.getElementById('current_grafts_count').disabled = true;
                document.getElementById('room_id').disabled = true;
                document.getElementById('notes').disabled = true;

                // Disable save button if completed
                const saveButton = document.querySelector('button[type="submit"]');
                if (isCompleted && saveButton) {
                    saveButton.disabled = true;
                    saveButton.innerHTML = '<i class="far fa-lock me-1"></i>Surgery Completed';
                    saveButton.title = 'Cannot edit completed surgery';
                }

                // Add visual indication
                const form = document.getElementById('surgery-form');
                const editorNotice = document.createElement('div');
                editorNotice.className = 'alert alert-info mb-3';
                editorNotice.innerHTML = `
                <i class="far fa-info-circle me-2"></i>
                <strong>Editor Mode:</strong> You can only modify the status field.
                ${isCompleted ? 'This surgery is completed and cannot be edited further.' : ''}
            `;
                form.insertBefore(editorNotice, form.firstChild);
            }
        }

        // Function to load room options
        function loadRoomOptions(selectRoomId = null) { // Add parameter
            apiRequest('rooms', 'list')
                .then(data => {
                    if (data.success) {
                        roomSelect.innerHTML = '<option value="">Select Room</option>';
                        data.rooms.forEach(room => {
                            if (room.is_active) { // Only show active rooms
                                const option = document.createElement('option');
                                option.value = room.id;
                                option.textContent = room.name;
                                option.disabled = false; // Ensure options are not disabled initially

                                if (room.name.toLowerCase() === "consultation" || room.name.toLowerCase() === "cosmetology") {
                                    option.disabled = true; // Disable consultation and cosmetology rooms
                                } else {
                                    option.selected = true;
                                }
                                roomSelect.appendChild(option);

                            }
                        });

                        // Check availability if date is already selected
                        // Do this BEFORE setting the selected value
                        if (dateInput.value) {
                            checkRoomAvailability();
                        }

                        // Select the room if selectRoomId is provided
                        if (selectRoomId && roomSelect) {
                            roomSelect.value = String(selectRoomId); // Ensure type match
                        }

                        // Update the selected room name display if room is pre-selected from URL
                        const roomIdFromUrl = <?php echo json_encode($room_id_from_url); ?>;
                        if (roomIdFromUrl) {
                            const selectedRoomNameSpan = document.getElementById('selected-room-name');
                            if (selectedRoomNameSpan) {
                                const selectedRoom = data.rooms.find(room => room.id == roomIdFromUrl);
                                if (selectedRoom) {
                                    selectedRoomNameSpan.textContent = selectedRoom.name;
                                }
                            }
                        }
                    } else {
                        console.error('Error fetching room options:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error fetching room options:', error);
                });
        }

        // Function to check room availability for selected date
        function checkRoomAvailability() {
            const selectedDate = dateInput.value;
            if (!selectedDate) {
                roomAvailabilityText.innerHTML = '';
                return;
            }

            apiRequest('availability', 'byDate', {
                date: selectedDate
            })
                .then(data => {
                    if (data.success) {
                        const statistics = data.statistics;
                        const availableCount = statistics.available_rooms;
                        const totalActive = statistics.active_rooms;

                        // Update availability text with count
                        if (availableCount > 0) {
                            roomAvailabilityText.innerHTML = `<small class="text-success">
                            <i class="far fa-check me-1"></i>
                            ${availableCount} out of ${totalActive} rooms available for the selected date.
                        </small>`;
                            roomSelect.classList.remove('is-invalid');
                        } else {
                            roomAvailabilityText.innerHTML = `<small class="text-danger">
                            <i class="far fa-exclamation-triangle me-1"></i>
                            There is no available room. Please select another date.
                        </small>`;
                            roomSelect.classList.add('is-invalid');
                        }

                        updateRoomAvailability(data.rooms);
                    } else {
                        console.error('Error checking room availability:', data.error);
                        roomAvailabilityText.innerHTML = `<small class="text-danger">
                        <i class="far fa-exclamation-triangle me-1"></i>
                        Error checking room availability.
                    </small>`;
                    }
                })
                .catch(error => {
                    console.error('Error checking room availability:', error);
                    roomAvailabilityText.innerHTML = `<small class="text-danger">
                    <i class="far fa-exclamation-triangle me-1"></i>
                    Error checking room availability.
                </small>`;
                });
        }

        // Function to update room availability display
        function updateRoomAvailability(rooms) {
            const roomOptions = Array.from(roomSelect.options)
                .filter(opt => opt.value !== '');
            const currentSurgeryId = isEditing ? surgeryIdInput.value : null;

            roomOptions.forEach(option => {
                const roomId = parseInt(option.value);
                const room = rooms.find(r => r.id === roomId);

                if (room) {
                    // Don't disable room if it's booked by the current surgery being edited
                    const isBookedByCurrentSurgery = room.status === 'booked' &&
                        currentSurgeryId && room.surgery_id == currentSurgeryId;

                    option.disabled = room.status === 'booked' && !isBookedByCurrentSurgery;

                    if (room.status === 'booked' && !isBookedByCurrentSurgery) {
                        option.textContent = option.textContent.replace(' (Booked)', '') + ' (Booked)';
                    } else {
                        option.textContent = option.textContent.replace(' (Booked)', '');
                    }
                }
            });

            // Update availability text
            const bookedRooms = rooms.filter(r => r.status === 'booked' &&
                !(currentSurgeryId && r.surgery_id == currentSurgeryId));
            if (bookedRooms.length > 0) {
                roomAvailabilityText.innerHTML = `<small class="text-warning">
                <i class="far fa-exclamation-triangle me-1"></i>
                ${bookedRooms.length} room(s) already booked for this date
            </small>`;
            } else {
                roomAvailabilityText.innerHTML = `<small class="text-success">
                <i class="fas fa-check me-1"></i>
                All rooms available for this date
            </small>`;
            }
        }

        // Fetch patient options for the dropdown
        function fetchPatientOptions(selectPatientId = null) {
            const userRole = '<?php echo get_user_role(); ?>';
            const userAgencyId = '<?php echo get_user_agency_id(); ?>';

            // Build API request data with agency filter for agents
            let requestData = {};
            if (userRole === 'agent' && userAgencyId) {
                requestData.agency = userAgencyId;
            }

            apiRequest('patients', 'list', requestData)
                .then(data => {
                    if (data.success) {
                        patientSelect.innerHTML =
                            '<option value="">Select Patient</option>'; // Keep the default option
                        data.patients.forEach(patient => {
                            const option = document.createElement('option');
                            option.value = patient.id;
                            option.textContent = patient.name;
                            if (selectPatientId && patient.id == selectPatientId) {
                                option.selected = true;
                            }
                            patientSelect.appendChild(option);
                        });
                    } else {
                        console.error('Error fetching patient options:', data.error);
                        // Optionally display an error message for the dropdown
                    }
                })
                .catch(error => {
                    console.error('Error fetching patient options:', error);
                    // Optionally display an error message for the dropdown
                });
        }

        let roomToSelect = null; // Declare variable here

        // Fetch surgery data if editing
        if (isEditing) {
            const surgeryId = surgeryIdInput.value;
            apiRequest('surgeries', 'get', {
                id: surgeryId
            })
                .then(data => {
                    if (data.success) {
                        const surgery = data.surgery;

                        const dateInput = document.getElementById('date');
                        if (dateInput) {
                            dateInput.value = surgery.date;
                        }
                        document.getElementById('status').value = surgery.status;
                        document.getElementById('predicted_grafts_count').value = surgery.predicted_grafts_count;
                        document.getElementById('current_grafts_count').value = surgery.current_grafts_count;
                        document.getElementById('notes').value = surgery.notes;

                        // Update status display in header
                        updateStatusDisplayFromData(surgery.status);

                        // Set the hidden patient_id for editing
                        if (patientIdHiddenInput) {
                            patientIdHiddenInput.value = surgery.patient_id;
                        }

                        // Store room_id to select later
                        roomToSelect = surgery.room_id;
                        console.log("Fetched surgery room_id:", roomToSelect);

                        // Load existing technician assignments
                        if (surgery.technician_ids && surgery.technician_ids.length > 0) {
                            // Store technician data for display
                            if (surgery.technicians) {
                                surgery.technicians.forEach(tech => {
                                    technicianData.set(tech.id.toString(), {
                                        name: tech.name,
                                        specialty: tech.specialty,
                                        period: tech.period ||
                                            'full' // Default to full if not specified
                                    });
                                });
                            }

                            surgery.technician_ids.forEach(techId => {
                                selectedTechnicians.add(techId.toString());
                            });
                            updateAssignedTechnicians();
                        }

                        // Apply editor role restrictions
                        applyEditorRestrictions(surgery);

                        // Now that surgery data is loaded, load room options and check availability
                        loadRoomOptions(roomToSelect);

                    } else {
                        showToast(`Error loading surgery: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error fetching surgery:', error);
                    showToast('An error occurred while loading surgery data.', 'danger');
                });
        } else {
            // If adding a new surgery, fetch patient options
            const patientIdFromUrl = <?php echo json_encode($patient_id_from_url); ?>;
            const roomIdFromUrl = <?php echo json_encode($room_id_from_url); ?>;
            const dateFromUrl = <?php echo json_encode($date_from_url); ?>;

            fetchPatientOptions(
                patientIdFromUrl); // Fetch and populate patient dropdown, pre-selecting if patient_id is in URL

            // Pre-fill date if provided from URL
            if (dateFromUrl) {
                const dateInput = document.getElementById('date');
                if (dateInput) {
                    dateInput.value = dateFromUrl;
                }
            }

            // If adding, load room options and pre-select if provided
            loadRoomOptions(roomIdFromUrl);
        }

        // Fetch agencies for modal
        fetchModalAgencies();

        // Initialize status display and inline editing
        initializeStatusDisplay();

        // Add event listener for date changes to check room availability
        if (dateInput) {
            dateInput.addEventListener('change', checkRoomAvailability);
        }

        // Initialize technician selection
        const technicianModal = new bootstrap.Modal(document.getElementById('technicianModal'));
        const addTechniciansBtn = document.getElementById('add-technicians-btn');
        const technicianList = document.getElementById('technician-list');
        const saveTechniciansBtn = document.getElementById('save-technicians');
        const assignedTechniciansDiv = document.getElementById('assigned-technicians');
        const selectedTechnicians = new Set();
        const technicianData = new Map(); // Store technician info (id -> {name, specialty, period})

        // Load available technicians for the selected date
        function loadAvailableTechnicians() {
            // Get date from either visible input or hidden input (calendar flow)
            let selectedDate;
            if (dateInput) {
                selectedDate = dateInput.value;
            } else {
                // For calendar flow, get date from hidden input or URL parameter
                const hiddenDateInput = document.querySelector('input[name="date"]');
                selectedDate = hiddenDateInput ? hiddenDateInput.value : '<?php echo $date_from_url; ?>';
            }

            if (!selectedDate) {
                alert('Please select a surgery date first');
                // Focus on the date input field
                if (dateInput) {
                    dateInput.focus();
                }
                return false; // Return false to indicate validation failed
            }

            document.getElementById('technician-loading').classList.remove('d-none');
            document.getElementById('technician-list').classList.add('d-none');
            document.getElementById('no-technicians').classList.add('d-none');

            // For now, we'll load all available technicians for the date
            // In the future, we could add period selection to the UI
            apiRequest('staff_availability', 'byDate', {
                date: selectedDate
            })
                .then(data => {
                    if (data.success && data.technicians && data.technicians.length > 0) {
                        renderTechnicianList(data.technicians);
                        document.getElementById('technician-list').classList.remove('d-none');

                        // Update modal title with count
                        document.getElementById('technicianModalLabel').innerHTML = `
                        <i class="far fa-user-md me-2"></i>
                        Select Available Technicians (${data.count} available)
                    `;
                    } else {
                        document.getElementById('no-technicians').classList.remove('d-none');
                        document.getElementById('technicianModalLabel').innerHTML = `
                        <i class="far fa-user-md me-2"></i>
                        Select Available Technicians (0 available)
                    `;
                    }
                })
                .catch(error => {
                    console.error('Error loading technicians:', error);
                    document.getElementById('no-technicians').classList.remove('d-none');
                })
                .finally(() => {
                    document.getElementById('technician-loading').classList.add('d-none');
                });

            return true; // Return true to indicate successful validation and loading
        }

        // Render technician list in modal
        function renderTechnicianList(technicians) {
            // Store technician data for later use
            technicians.forEach(tech => {
                technicianData.set(tech.id.toString(), {
                    name: tech.name,
                    specialty: tech.specialty,
                    period: tech.period || 'full'
                });
            });

            technicianList.innerHTML = technicians.map(tech => {
                // Format period display
                let periodDisplay = '';
                if (tech.available_periods) {
                    const periods = tech.available_periods;
                    if (periods.includes('full')) {
                        periodDisplay = 'All day';
                    } else {
                        const periodNames = periods.map(p => p === 'am' ? 'Morning' : 'Afternoon');
                        periodDisplay = periodNames.join(', ');
                    }
                } else {
                    // Fallback for single period
                    periodDisplay = tech.period === 'full' ? 'All day' :
                        tech.period === 'am' ? 'Morning' : 'Afternoon';
                }

                return `
                <div class="form-check mb-2">
                    <input class="form-check-input technician-checkbox" type="checkbox"
                           value="${tech.id}" id="tech-${tech.id}"
                           ${selectedTechnicians.has(tech.id.toString()) ? 'checked' : ''}>
                    <label class="form-check-label" for="tech-${tech.id}">
                        <strong>${tech.name}</strong> ${tech.specialty ? `<span class="badge bg-secondary">${tech.specialty}</span>` : ''}
                        <small class="text-muted d-block">Available: ${periodDisplay}</small>
                        ${tech.phone ? `<small class="text-muted d-block">Phone: ${tech.phone}</small>` : ''}
                    </label>
                </div>
            `;
            }).join('');
        }

        // Update status based on room and technicians
        function updateSurgeryStatus() {
            const statusSelect = document.getElementById('status');
            const roomValue = roomSelect.value;

            if (roomValue && selectedTechnicians.size >= 2) {
                // Auto-update to confirmed when room and at least 2 technicians are selected
                statusSelect.value = 'confirmed';
            } else if (!isEditing) {
                // Reset to scheduled for new surgeries
                statusSelect.value = 'scheduled';
            }
        }

        // Function to validate technician assignment (minimum 2 required)
        function validateTechniciansAssignment(showErrors = true) {
            const assignedTechniciansDiv = document.getElementById('assigned-technicians');
            const feedback = assignedTechniciansDiv.parentNode.querySelector('.invalid-feedback');
            let isValid = true;

            if (selectedTechnicians.size < 2) {
                isValid = false;
                if (showErrors) {
                    assignedTechniciansDiv.classList.add('is-invalid');
                    if (feedback) feedback.textContent = 'At least 2 technicians must be assigned.';
                }
            } else {
                assignedTechniciansDiv.classList.remove('is-invalid');
                assignedTechniciansDiv.classList.add('is-valid');
                if (feedback) feedback.textContent = ''; // Clear feedback on valid
            }
            return isValid;
        }

        // Update assigned technicians display
        function updateAssignedTechnicians() {
            if (selectedTechnicians.size === 0) {
                assignedTechniciansDiv.innerHTML = '<div class="text-muted">No technicians assigned</div>';
                return;
            }

            assignedTechniciansDiv.innerHTML = Array.from(selectedTechnicians).map(techId => {
                const techInfo = technicianData.get(techId);
                const techName = techInfo ? techInfo.name : `Technician #${techId}`;
                const techPeriod = techInfo && techInfo.period ? ` (${techInfo.period})` : '';
                return `
                <div class="badge bg-primary me-2 mb-2 p-2">
                    ${techName}${techPeriod}
                    <button type="button" class="btn-close btn-close-white ms-2"
                            data-tech-id="${techId}" aria-label="Remove"></button>
                </div>
            `;
            }).join('');

            // Add hidden inputs for form submission
            Array.from(selectedTechnicians).forEach(techId => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'technician_ids[]';
                input.value = techId;
                assignedTechniciansDiv.appendChild(input);
            });

            // Add event listeners for remove buttons
            assignedTechniciansDiv.querySelectorAll('button[data-tech-id]').forEach(button => {
                button.addEventListener('click', function () {
                    const techId = this.getAttribute('data-tech-id');
                    removeTechnician(techId);
                });
            });

            // Only show validation errors if user has interacted with the form
            validateTechniciansAssignment(validationState.hasInteracted);
            updateSurgeryStatus();
        }

        // Remove technician from selection
        function removeTechnician(techId) {
            validationState.hasInteracted = true;
            selectedTechnicians.delete(techId);
            updateAssignedTechnicians();
        }

        // Event listeners
        addTechniciansBtn.addEventListener('click', function () {
            // Only show modal if date validation passes
            if (loadAvailableTechnicians()) {
                technicianModal.show();
            }
        });

        saveTechniciansBtn.addEventListener('click', function () {
            // Mark that user has interacted with technicians
            validationState.hasInteracted = true;

            // Get all checked technicians
            document.querySelectorAll('.technician-checkbox:checked').forEach(checkbox => {
                selectedTechnicians.add(checkbox.value);
            });

            updateAssignedTechnicians();
            technicianModal.hide();
        });

        // Add event listener for date changes to check technician availability
        if (dateInput) {
            dateInput.addEventListener('change', function () {
                // Clear selected technicians when date changes
                selectedTechnicians.clear();
                updateAssignedTechnicians();
                // Validate date in real-time
                validationState.hasInteracted = true;
                validateField(this);
            });

            // Add blur event for date validation
            dateInput.addEventListener('blur', function () {
                if (validationState.hasInteracted) {
                    validateField(this);
                }
            });
        }

        // ===== REAL-TIME VALIDATION EVENT LISTENERS =====

        // Patient selection validation
        if (patientSelect) {
            patientSelect.addEventListener('change', function () {
                validationState.hasInteracted = true;
                validateField(this);
            });
            patientSelect.addEventListener('blur', function () {
                if (validationState.hasInteracted) {
                    validateField(this);
                }
            });
        }

        // Room selection validation
        if (roomSelect) {
            roomSelect.addEventListener('change', function () {
                validationState.hasInteracted = true;
                validateField(this);
                // Also check room availability when room changes
                if (dateInput && dateInput.value) {
                    checkRoomAvailability();
                }
            });
            roomSelect.addEventListener('blur', function () {
                if (validationState.hasInteracted) {
                    validateField(this);
                }
            });
        }

        // Handle surgery form submission
        surgeryForm.addEventListener('submit', function (event) {
            event.preventDefault(); // Prevent default form submission

            // Clear previous messages
            hideFormError();

            // Validate form before submission
            if (!validateForm()) {
                return;
            }

            // Re-enable all room options before submitting to ensure selected value is included
            Array.from(roomSelect.options).forEach(option => {
                option.disabled = false;
            });

            const formData = new FormData(surgeryForm);

            // Note: technician IDs are already included as hidden inputs by updateAssignedTechnicians()

            fetch('/api.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        // Redirect after a short delay on success
                        setTimeout(() => {
                            // Redirect based on whether it was an edit or add from patient list
                            if (isEditing ||
                                <?php echo json_encode($patient_id_from_url !== null); ?>) {
                                window.location.href =
                                    `/patient.php?id=${formData.get('patient_id')}`;
                            } else {
                                window.location.href = '/surgery/surgeries.php';
                            }
                        }, 500);
                    } else {
                        showFormError(data.error || data.message || 'An error occurred while saving surgery data.');
                    }
                })
                .catch(error => {
                    console.error('Error submitting surgery form:', error);
                    showFormError('An error occurred while saving surgery data.');
                });
        });

        // Handle new patient modal submission (AJAX to api.php)
        if (saveNewPatientButton) {
            saveNewPatientButton.addEventListener('click', function () {
                // Validate modal form
                const modalForm = document.getElementById('new-patient-form');
                const inputs = modalForm.querySelectorAll('input[required], select[required]');
                let isValid = true;
                let firstInvalidField = null;

                inputs.forEach(input => {
                    if (!validateField(input)) {
                        isValid = false;
                        if (!firstInvalidField) {
                            firstInvalidField = input;
                        }
                    }
                });

                if (!isValid) {
                    if (firstInvalidField) {
                        firstInvalidField.focus();
                    }
                    newPatientStatusDiv.innerHTML =
                        '<div class="alert alert-danger">Please correct the highlighted errors before submitting.</div>';
                    return;
                }

                const formData = new FormData(newPatientForm);
                formData.append('entity', 'patients');
                formData.append('action', 'add');

                // Ensure agency_id is properly set based on user role
                const userRole = '<?php echo get_user_role(); ?>';
                const userAgencyId = '<?php echo get_user_agency_id(); ?>';
                const agencySelect = document.getElementById('new_patient_agency_id');

                console.log('User role:', userRole, 'User agency ID:', userAgencyId); // Debug

                let finalAgencyId = null;

                // For agents, ensure their agency_id is set
                if (userRole === 'agent') {
                    if (userAgencyId && userAgencyId.trim() !== '') {
                        // For agents, the field should be hidden and pre-filled
                        if (agencySelect) {
                            agencySelect.value = userAgencyId;
                        }
                        finalAgencyId = userAgencyId;
                        console.log('Agent agency_id set to:', finalAgencyId);
                    } else {
                        newPatientStatusDiv.innerHTML =
                            '<div class="alert alert-danger">Agent agency ID not found. Please contact support.</div>';
                        return;
                    }
                } else if ((userRole === 'admin' || userRole === 'editor') && agencySelect) {
                    // For admin/editor, ensure the selected value is included
                    const selectedAgencyId = agencySelect.value;
                    if (!selectedAgencyId || selectedAgencyId.trim() === '') {
                        newPatientStatusDiv.innerHTML =
                            '<div class="alert alert-danger">Please select an agency.</div>';
                        return;
                    }
                    finalAgencyId = selectedAgencyId;
                    console.log('Admin/Editor agency_id set to:', finalAgencyId);
                } else {
                    newPatientStatusDiv.innerHTML =
                        '<div class="alert alert-danger">Unable to determine user role or agency. Please contact support.</div>';
                    return;
                }

                // Ensure agency_id is in the FormData
                formData.set('agency_id', finalAgencyId);

                // Final validation that agency_id is present
                if (!formData.get('agency_id') || formData.get('agency_id').trim() === '') {
                    newPatientStatusDiv.innerHTML =
                        '<div class="alert alert-danger">Agency ID is required but missing. Please contact support.</div>';
                    return;
                }

                // Debug: Log the form data being sent
                console.log('Submitting patient data:');
                for (let [key, value] of formData.entries()) {
                    console.log(key + ': ' + value);
                }

                newPatientStatusDiv.innerHTML = ''; // Clear previous status

                fetch('/api.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            newPatientStatusDiv.innerHTML =
                                '<div class="alert alert-success">Patient created successfully!</div>';
                            // Add the new patient to the select dropdown
                            if (patientSelect) {
                                const newOption = new Option(data.patient.name, data.patient.id, true,
                                    true);
                                patientSelect.add(newOption);
                            }
                            // Close the modal after a short delay
                            setTimeout(() => {
                                const modal = bootstrap.Modal.getInstance(newPatientModal);
                                modal.hide();
                            }, 500);
                        } else {
                            newPatientStatusDiv.innerHTML =
                                `<div class="alert alert-danger">${data.error || 'An error occurred.'}</div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error creating patient:', error);
                        newPatientStatusDiv.innerHTML =
                            '<div class="alert alert-danger">An error occurred while creating the patient.</div>';
                    });
            });
        }

        // Add event listener to current_grafts_count to update status
        const currentGraftCountInput = document.getElementById('current_grafts_count');
        const statusSelect = document.getElementById('status');

        if (currentGraftCountInput && statusSelect) {
            currentGraftCountInput.addEventListener('input', function () {
                // Check if the current_grafts_count input has a value (is not empty and not just whitespace)
                if (this.value.trim() !== '' && parseInt(this.value) >= 0) {
                    statusSelect.value = 'completed';
                }
                // Optional: Reset status if current_grafts_count is cleared
                // else {
                //     // You might want to reset to 'booked' or leave as is
                //     // statusSelect.value = 'booked';
                // }
            });
        }

        // Reset modal form when hidden
        if (newPatientModal) {
            newPatientModal.addEventListener('hidden.bs.modal', function () {
                newPatientForm.reset();
                newPatientStatusDiv.innerHTML = '';
            });

            // Ensure agency_id is set when modal is shown
            newPatientModal.addEventListener('shown.bs.modal', function () {
                const userRole = '<?php echo get_user_role(); ?>';
                const userAgencyId = '<?php echo get_user_agency_id(); ?>';
                const agencySelect = document.getElementById('new_patient_agency_id');

                console.log('Modal shown - User role:', userRole, 'Agency ID:', userAgencyId);

                // For agents, ensure their agency_id is always set
                if (userRole === 'agent' && userAgencyId && userAgencyId.trim() !== '' && agencySelect) {
                    agencySelect.value = userAgencyId;
                    console.log('Modal shown - Agent agency_id set to:', userAgencyId);

                    // Also trigger a change event to ensure any listeners are notified
                    agencySelect.dispatchEvent(new Event('change'));
                } else if (userRole === 'agent') {
                    console.warn('Agent user but no agency_id found:', userAgencyId);
                }

                // Focus on the name field for better UX
                const nameField = document.getElementById('new_patient_name');
                if (nameField) {
                    setTimeout(() => nameField.focus(), 100);
                }
            });
        }

        // Initialize status display and inline editing functionality
        function initializeStatusDisplay() {
            const statusDisplay = document.getElementById('status-display');
            const editStatusBtn = document.getElementById('edit-status-btn');
            const statusEditContainer = document.getElementById('status-edit-container');
            const statusInline = document.getElementById('status-inline');
            const saveStatusBtn = document.getElementById('save-status-btn');
            const cancelStatusBtn = document.getElementById('cancel-status-btn');
            const statusHidden = document.getElementById('status');

            // Status badge color mapping
            const statusColors = {
                'scheduled': 'bg-warning text-dark',
                'confirmed': 'bg-info',
                'completed': 'bg-success',
                'cancelled': 'bg-danger'
            };

            // Function to update status display
            function updateStatusDisplay(status) {
                const statusText = status.charAt(0).toUpperCase() + status.slice(1);
                const colorClass = statusColors[status] || 'bg-secondary';

                statusDisplay.className = `badge ${colorClass} ms-1`;
                statusDisplay.textContent = statusText;

                // Update hidden field
                statusHidden.value = status;

                // Update inline select
                if (statusInline) {
                    statusInline.value = status;
                }
            }

            // Function to enter edit mode
            function enterEditMode() {
                console.log('Entering edit mode');
                // Hide status display and edit button
                if (statusDisplay) statusDisplay.style.display = 'none';
                if (editStatusBtn) editStatusBtn.style.display = 'none';

                // Show edit container
                if (statusEditContainer) {
                    statusEditContainer.style.display = 'inline-block';
                    statusEditContainer.classList.add('d-inline-block');
                }
                console.log('edit container displayed');
                if (statusInline) {
                    statusInline.value = statusHidden.value;
                    statusInline.focus();
                }
            }

            // Function to exit edit mode
            function exitEditMode() {
                // Show status display and edit button
                if (statusDisplay) {
                    statusDisplay.style.display = 'inline';
                }
                if (editStatusBtn) {
                    editStatusBtn.style.display = 'inline-block';
                }

                // Hide edit container
                if (statusEditContainer) {
                    statusEditContainer.style.display = 'none';
                    statusEditContainer.classList.remove('d-inline-block');
                }
            }

            // Function to save status
            function saveStatus() {
                const newStatus = statusInline.value;

                // If editing existing surgery, save via API
                if (isEditing) {
                    const formData = new FormData();
                    formData.append('entity', 'surgeries');
                    formData.append('action', 'updateStatus');
                    formData.append('id', surgeryIdInput.value);
                    formData.append('status', newStatus);

                    fetch('/api.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                updateStatusDisplay(newStatus);
                                exitEditMode();
                                showToast('Status updated successfully!', 'success');
                            } else {
                                showToast(`Error updating status: ${data.error}`, 'danger');
                            }
                        })
                        .catch(error => {
                            console.error('Error updating status:', error);
                            showToast('Failed to update status. Please try again.', 'danger');
                        });
                } else {
                    // For new surgery, just update the display and hidden field
                    updateStatusDisplay(newStatus);
                    exitEditMode();
                }
            }

            // Event listeners
            if (editStatusBtn) {
                editStatusBtn.addEventListener('click', enterEditMode);
            }

            if (saveStatusBtn) {
                saveStatusBtn.addEventListener('click', saveStatus);
            }

            if (cancelStatusBtn) {
                cancelStatusBtn.addEventListener('click', exitEditMode);
            }

            // Handle Enter and Escape keys in inline select
            if (statusInline) {
                statusInline.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        saveStatus();
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        exitEditMode();
                    }
                });
            }

            // Initialize with default status for new surgeries
            if (!isEditing) {
                updateStatusDisplay('scheduled');
            }
        }

        // Initialize agencies for the modal
        fetchModalAgencies();
    });
</script>