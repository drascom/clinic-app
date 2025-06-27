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


<div class="container emp frosted">
    <!-- Surgery Form -->
    <div class="card frosted">
        <!-- Page Header -->
        <div class="card-header p-4">
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
                    <!-- top column -->
                    <!-- <div class="col-12">
                       
                    </div> -->
                    <!-- Left Column -->
                    <div class="col-12 col-md-5">
                        <!-- Patient Selection -->
                        <?php if (!$is_editing): ?>
                            <fieldset class="border rounded p-3 mb-3 shadow-sm ">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <legend class="w-auto m-0 px-2" style="font-size: 1rem;">
                                        <i class="far fa-user me-2"></i>Patient Selection<span class="text-danger">*</span>
                                    </legend>
                                    <button type="button"
                                        class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 py-0 px-2 m-0"
                                        data-bs-toggle="modal" data-bs-target="#newPatientModal">
                                        <i class="far fa-plus"></i>
                                        <span class="d-none d-sm-inline">New Patient</span>
                                    </button>
                                </div>

                                <div class="mb-3">
                                    <div class="input-group">
                                        <select class="form-select select2-enable" id="patient_id" name="patient_id"
                                            required>
                                            <option value="">Select Patient</option>
                                        </select>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </fieldset>
                        <?php else: ?>
                            <fieldset class="border rounded p-3 mb-3 shadow-sm bg-body-secondary">
                                <div class="d-flex justify-content-between flex-wrap align-items-start gap-3 ">
                                    <legend class="w-auto m-0 px-2">
                                        <h4 class="mb-0 text-primary">
                                            <a href="#" id="patient_name_link" class="text-decoration-none text-primary">
                                                <i class="fas fa-user me-2"></i><span id="patient_name"></span>
                                            </a>
                                        </h4>
                                    </legend>
                                    <p class="mb-2 fs-6">
                                        <i class="fas fa-phone-alt text-primary me-1"></i>
                                        <span class="fw-bold text-muted">Phone:</span>
                                        <span id="patient_phone" class="ms-2"></span>
                                    </p>
                                    <p class="mb-0 fs-6">
                                        <i class="far fa-envelope text-primary me-1"></i>
                                        <span class="fw-bold text-muted">Email:</span>
                                        <span id="patient_email" class="ms-2"></span>
                                    </p>
                                </div>
                            </fieldset>
                            <input type="hidden" name="patient_id" id="patient_id">
                        <?php endif; ?>
                        <!-- Date and Room Section -->
                        <fieldset class="border rounded p-3 mb-4">
                            <legend class="w-auto px-2 mb-3" style="font-size: 1rem;">
                                <i class="far fa-calendar-alt me-2"></i>Date & Room Selection
                            </legend>
                            <?php if (!empty($date_from_url)): ?>
                                <div class="align-items-center">
                                    <div class="alert alert-info mb-2">
                                        <i class="far fa-calendar me-2"></i> Date:
                                        <span class="text-primary">
                                            <?php echo date('F j, Y', strtotime($date_from_url)); ?></span>
                                        <input type="hidden" name="date" value="<?php echo $date_from_url; ?>">
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
                    </div>

                    <!-- Right Column -->
                    <div class="col-md-7">
                        <!-- Technicians Section -->
                        <fieldset class="border rounded p-3 mb-3 shadow-sm">
                            <div class="d-flex justify-content-between align-items-baseline">
                                <legend class="w-auto m-0" style="font-size:1rem;">
                                    <i class="fas fa-user-md me-2"></i>Assigned Technicians
                                </legend>

                                <button type="button"
                                    class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 py-0 px-2 m-0"
                                    id="add-technicians-btn">
                                    <i class="far fa-plus"></i>
                                    Add Technicians
                                </button>
                            </div>



                            <div class="mb-3">
                                <select class="form-select" id="technicians" name="technicians[]" multiple
                                    style="display: none;"></select>
                                <div class="invalid-feedback"></div>
                                <div id="assigned-technicians" class="mt-2">
                                </div>
                                <div class="form-text" id="technician-availability-text"></div>
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
                            <input type="hidden" id="status" name="status" value="scheduled">
                        </fieldset>
                        <fieldset class="border rounded p-3 mb-3 shadow-sm">
                            <legend class="w-auto px-2 mb-3" style="font-size: 1rem;">
                                <i class="far fa-file-alt me-2"></i>Forms
                            </legend>
                            <div id="forms-container" class="d-flex flex-wrap gap-3">
                                <!-- Form toggles will be loaded here -->
                            </div>
                            <input type="hidden" name="forms" id="forms-input">
                        </fieldset>
                    </div>
                    <div class="col-12">
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

                </div>
        </div>

        <!-- Action Buttons -->
        <div class="card-footer d-flex justify-content-end">
            <div>
                <a href="<?php echo $is_editing ? '/surgery/surgeries.php' : '/surgery/surgeries.php'; ?>"
                    class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i>Cancel
                </a>
                <button type="submit" class="btn btn-primary" id="save-surgery-button">
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
                <h5 class="modal-title" id="technicianModalLabel"><i class="far fa-user-md me-2"></i>Select Technicians
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="technician-modal-body-content">
                <!-- Content injected by JavaScript -->
            </div>
            <div class="modal-footer" id="technician-modal-footer-content">
                <!-- Footer injected by JavaScript -->
            </div>
        </div>
    </div>
</div>



<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // --- Core Validation Setup ---
        const form = document.getElementById("surgery-form");
        const submitButton = form.querySelector('button[type="submit"]');

        const surgeryIdInput = document.querySelector('#surgery-form input[name="id"]');
        const isEditing = surgeryIdInput !== null;
        const patientSelect = document.getElementById('patient_id');
        const patientIdHiddenInput = document.querySelector('#surgery-form input[name="patient_id"]');
        const roomSelect = document.getElementById('room_id');
        const dateInput = document.getElementById('date');
        const roomAvailabilityText = document.getElementById('room-availability-text');

        const newPatientModal = document.getElementById('newPatientModal');
        const saveNewPatientButton = document.getElementById('save-new-patient');
        const newPatientForm = document.getElementById('new-patient-form');
        const newPatientStatusDiv = document.getElementById('new-patient-status');
        let allAgencies = []; // Store all agencies for modal dropdown
        const validationState = {
            hasInteracted: false
        }; // Track if user has interacted with form

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

        // --- Initialization ---
        initValidation();

        function initValidation() {
            if (form) {
                form.addEventListener("submit", onFormSubmit);
            }

            // --- Event Listeners for Real-time Validation ---
            const fieldsToValidate = getFieldValidationRules().map(rule => rule.id);

            fieldsToValidate.forEach((id) => {
                const el = document.getElementById(id);
                if (el) {
                    if ($(el).hasClass("select2-enable")) {
                        $(el).on("select2:close", () => validateSingleField(id));
                    } else if (el.tagName === 'SELECT' || el.type === 'date') {
                        el.addEventListener("change", () => validateSingleField(id));
                    } else {
                        el.addEventListener("blur", () => validateSingleField(id));
                    }
                }
            });

            // Set initial state of the submit button
            updateSubmitButtonState();

            // Initial load of availability if date is pre-filled
            if (dateInput.value) {
                loadAvailableTechnicians();
            }
        }

        // --- Core Functions ---

        function onFormSubmit(e) {
            e.preventDefault();
            // On submit, validate and show any UI errors
            if (!validateForm(true)) {
                showToast('Please correct the highlighted errors.', 'danger');
                return;
            }
            // If valid, proceed with form submission logic
            console.log("Form is valid and ready to submit.");

            // Re-enable all room options before submitting to ensure selected value is included
            Array.from(roomSelect.options).forEach(option => {
                option.disabled = false;
            });

            const formData = new FormData(form);

            fetch('/api.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => {
                            if (isEditing ||
                                <?php echo json_encode($patient_id_from_url !== null); ?>) {
                                window.location.href =
                                    `/patient/patient_details.php?id=${formData.get('patient_id')}&tab=surgery`;
                            } else {
                                window.location.href = '/surgery/surgeries.php';
                            }
                        }, 500);
                    } else {
                        showToast(data.error || data.message || 'An error occurred.', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error submitting surgery form:', error);
                    showToast('An unexpected error occurred.', 'danger');
                });
        }

        function updateSubmitButtonState() {
            if (submitButton) {
                // Check validity silently without showing errors
                submitButton.disabled = !validateForm(false);
            }
        }

        function validateSingleField(id) {
            const el = document.getElementById(id);
            if (!el) return;

            const fieldRule = getFieldValidationRules().find((f) => f.id === id);
            const msg = fieldRule ? fieldRule.msg : "This field is required.";

            let feedbackEl;
            if (el.parentElement.classList.contains("input-group")) {
                feedbackEl = el.parentElement.nextElementSibling;
            } else {
                feedbackEl = el.nextElementSibling;
            }

            if (!el.value.trim()) {
                el.classList.add("is-invalid");
                if (feedbackEl && feedbackEl.classList.contains("invalid-feedback")) {
                    feedbackEl.textContent = msg;
                }
            } else {
                el.classList.remove("is-invalid");
                if (feedbackEl && feedbackEl.classList.contains("invalid-feedback")) {
                    feedbackEl.textContent = "";
                }
            }
            // Re-evaluate the entire form's validity after each field is validated
            updateSubmitButtonState();
        }

        function validateForm(showUIErrors = false) {
            if (showUIErrors) {
                form.querySelectorAll(".is-invalid").forEach((el) => el.classList.remove("is-invalid"));
                form.querySelectorAll(".invalid-feedback").forEach((el) => (el.textContent = ""));
            }

            let isValid = true;
            const requiredFields = getFieldValidationRules();

            requiredFields.forEach((f) => {
                const el = document.getElementById(f.id);
                if (el && !el.value.trim()) {
                    isValid = false;
                    if (showUIErrors) {
                        el.classList.add("is-invalid");
                        let feedbackEl;
                        if (el.parentElement.classList.contains("input-group")) {
                            feedbackEl = el.parentElement.nextElementSibling;
                        } else {
                            feedbackEl = el.nextElementSibling;
                        }
                        if (feedbackEl && feedbackEl.classList.contains("invalid-feedback")) {
                            feedbackEl.textContent = f.msg;
                        }
                    }
                }
            });

            // --- Custom Validation Logic ---

            // 1. Date cannot be in the past for non-admins
            const dateField = document.getElementById('date');
            if (dateField && dateField.value) {
                const userRole = '<?php echo get_user_role(); ?>';
                if (userRole !== 'admin') {
                    const selectedDate = new Date(dateField.value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    if (selectedDate < today) {
                        isValid = false;
                        if (showUIErrors) {
                            dateField.classList.add('is-invalid');
                            if (dateField.nextElementSibling) {
                                dateField.nextElementSibling.textContent = 'Surgery date cannot be in the past.';
                            }
                        }
                    }
                }
            }

            // 2. At least 2 technicians must be assigned
            const assignedTechniciansDiv = document.getElementById('assigned-technicians');
            const techFeedback = assignedTechniciansDiv.parentNode.querySelector('.invalid-feedback');
            if (selectedTechnicians.size < 2) {
                isValid = false;
                if (showUIErrors) {
                    assignedTechniciansDiv.classList.add('is-invalid');
                    if (techFeedback) techFeedback.textContent = 'At least 2 technicians must be assigned.';
                }
            } else {
                if (showUIErrors) {
                    assignedTechniciansDiv.classList.remove('is-invalid');
                    if (techFeedback) techFeedback.textContent = '';
                }
            }

            return isValid;
        }


        function getFieldValidationRules() {
            // Centralize validation rules for easy management
            const rules = [{
                id: "date",
                msg: "Please select a surgery date."
            },
            {
                id: "room_id",
                msg: "Please select a room."
            },
            ];

            // Patient ID is only required when creating a new surgery
            if (!isEditing) {
                rules.push({
                    id: "patient_id",
                    msg: "Please select a patient."
                });
            }

            return rules;
        }

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
                apiRequest('agencies', 'list')
                    .then(data => {
                        if (data.success) {
                            allAgencies = data.agencies;
                            populateModalAgencyDropdown();
                        } else { }
                    }).catch(error => {
                        console.error('Error fetching agencies:', error);
                    });

            }
        }

        // Function to populate agency dropdown in modal
        function populateModalAgencyDropdown() {
            const agencySelect = document.getElementById('new_patient_agency_id');
            const userRole = '<?php echo get_user_role(); ?>';
            const userAgencyId = '<?php echo get_user_agency_id(); ?>';



            if (!agencySelect) {
                return; // Field might not exist for all roles
            }

            // For agents, set their agency_id in the hidden field
            if (userRole === 'agent' && userAgencyId) {
                agencySelect.value = userAgencyId;
            } else {
                // For admin/editor, populate dropdown with all agencies
                agencySelect.innerHTML = '<option value="">Select Agency*</option>';
                allAgencies.forEach(agency => {
                    const option = document.createElement('option');
                    option.value = agency.id;
                    option.textContent = agency.name;
                    agencySelect.appendChild(option);
                });
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

                                if (room.name.toLowerCase() === "consultation" || room.name
                                    .toLowerCase() === "cosmetology") {
                                    option.disabled =
                                        true; // Disable consultation and cosmetology rooms
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
                        document.getElementById('predicted_grafts_count').value = surgery
                            .predicted_grafts_count;
                        document.getElementById('current_grafts_count').value = surgery.current_grafts_count;
                        document.getElementById('notes').value = surgery.notes;

                        if (surgery.forms) {
                            try {
                                const forms = JSON.parse(surgery.forms);
                                renderForms(forms);
                            } catch (e) {
                                console.error('Error parsing forms JSON:', e);
                            }
                        }

                        // Update status display in header
                        updateStatusDisplayFromData(surgery.status);

                        // Set the hidden patient_id for editing
                        if (patientIdHiddenInput) {
                            patientIdHiddenInput.value = surgery.patient_id;
                        }

                        // Fetch and display patient details if in editing mode
                        if (isEditing && surgery.patient_id) {
                            apiRequest('patients', 'get', {
                                id: surgery.patient_id
                            })
                                .then(patientData => {
                                    if (patientData.success && patientData.patient) {
                                        const patient = patientData.patient;
                                        const patientNameEl = document.getElementById('patient_name');
                                        const patientPhoneEl = document.getElementById('patient_phone');
                                        const patientEmailEl = document.getElementById('patient_email');

                                        if (patientNameEl) {
                                            patientNameEl.textContent = patient.name;
                                            const patientNameLink = document.getElementById('patient_name_link');
                                            if (patientNameLink) {
                                                patientNameLink.href = `/patient/patient_details.php?id=${patient.id}`;
                                            }
                                        }
                                        if (patientPhoneEl) {
                                            patientPhoneEl.textContent = patient.phone || 'N/A';
                                        }
                                        if (patientEmailEl) {
                                            patientEmailEl.textContent = patient.email || 'N/A';
                                        }
                                    } else {
                                        console.error('Error fetching patient details:', patientData.error);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error fetching patient details:', error);
                                });
                        }

                        // Store room_id to select later
                        roomToSelect = surgery.room_id;

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

            // Render default forms for a new surgery
            const defaultForms = { "form1": false, "form2": false, "form3": false };
            renderForms(defaultForms);
        }

        // Fetch agencies for modal
        fetchModalAgencies();

        // Initialize status display and inline editing
        initializeStatusDisplay();

        // Add event listener for date changes to check room availability
        if (dateInput) {
            dateInput.addEventListener('change', function () {
                checkRoomAvailability();
                loadAvailableTechnicians(); // Fetch data and update text indicator

                // Clear selected technicians when date changes, as they may no longer be available
                selectedTechnicians.clear();
                technicianData.clear();
                updateAssignedTechnicians();

                // Validate date in real-time
                validateSingleField(this.id);
            });
        }

        // Initialize technician selection
        const technicianModal = new bootstrap.Modal(document.getElementById('technicianModal'));
        const addTechniciansBtn = document.getElementById('add-technicians-btn');
        const assignedTechniciansDiv = document.getElementById('assigned-technicians');
        const selectedTechnicians = new Set();
        const technicianData = new Map(); // Store technician info (id -> {name, specialty, period})
        let availableTechnicians = []; // Store last fetched available technicians

        // Load available technicians for the selected date and update availability text
        function loadAvailableTechnicians() {
            const selectedDate = dateInput.value;
            const techAvailabilityText = document.getElementById('technician-availability-text');

            if (!selectedDate) {
                techAvailabilityText.innerHTML = '';
                availableTechnicians = [];
                addTechniciansBtn.style.display = 'none'; // Hide the button when no date is selected
                return;
            }

            techAvailabilityText.innerHTML = '<small class="text-muted">Checking availability...</small>';
            apiRequest('staff_availability', 'byDate', {
                date: selectedDate
            })
                .then(data => {
                    if (data.success) {
                        availableTechnicians = data.technicians || [];
                        const count = data.count || 0;
                        if (count > 0) {
                            techAvailabilityText.innerHTML =
                                `<small class="text-success"><i class="fas fa-check me-1"></i>${count} technicians available.</small>`;
                            addTechniciansBtn.style.display = 'inline-block'; // Show the button
                        } else {
                            techAvailabilityText.innerHTML =
                                `<small class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>No technicians available for this date.</small>`;
                            addTechniciansBtn.style.display = 'none'; // Hide the button
                        }
                    } else {
                        availableTechnicians = [];
                        techAvailabilityText.innerHTML =
                            `<small class="text-danger">Error checking availability.</small>`;
                    }
                })
                .catch(error => {
                    console.error('Error checking technician availability:', error);
                    availableTechnicians = [];
                    techAvailabilityText.innerHTML =
                        `<small class="text-danger">Error checking availability.</small>`;
                });
        }

        // Render the technician modal content based on pre-fetched data
        function renderTechnicianModal() {
            const modalBody = document.getElementById('technician-modal-body-content');
            const modalFooter = document.getElementById('technician-modal-footer-content');
            const modalTitle = document.getElementById('technicianModalLabel');

            if (!dateInput.value) {
                showToast('Please select a surgery date first.', 'danger');
                if (dateInput) dateInput.focus();
                return false;
            }

            modalBody.innerHTML = '';
            modalFooter.innerHTML = '';

            if (availableTechnicians.length > 0) {
                modalTitle.innerHTML =
                    `<i class="far fa-user-md me-2"></i> Select Available Technicians (${availableTechnicians.length} available)`;
                modalBody.innerHTML = renderTechnicianList(availableTechnicians);
                modalFooter.innerHTML = `
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="save-technicians">Save Selection</button>
                `;
            } else {
                modalTitle.innerHTML =
                    `<i class="fas fa-exclamation-triangle me-2 text-warning"></i> No Technicians Available`;
                modalBody.innerHTML = `
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>There are no technicians available for the selected date. Please try a different date or check technician availability.
                    </div>`;
                modalFooter.innerHTML = `
                    <a href="/calendar/calendar.php" class="btn btn-outline-secondary"><i class="far fa-calendar-alt me-1"></i>Go to Calendar</a>
                    <a href="/staff/staff-availability.php?surgery_id=<?php echo $surgery_id; ?>" class="btn btn-outline-primary"><i class="fas fa-user-md me-1"></i>Staff Availability</a>
                `;
            }
            return true;
        }


        // Render technician list HTML
        function renderTechnicianList(technicians) {
            // Store technician data for later use
            technicians.forEach(tech => {
                if (!technicianData.has(tech.id.toString())) {
                    technicianData.set(tech.id.toString(), {
                        name: tech.name,
                        specialty: tech.specialty,
                        period: tech.period || 'full'
                    });
                }
            });

            return technicians.map(tech => {
                let periodDisplay = tech.period === 'full' ? 'All day' : (tech.period === 'am' ? 'Morning' :
                    'Afternoon');
                if (tech.available_periods) {
                    if (tech.available_periods.includes('full')) {
                        periodDisplay = 'All day';
                    } else {
                        periodDisplay = tech.available_periods.map(p => p === 'am' ? 'Morning' :
                            'Afternoon').join(', ');
                    }
                }

                return `
                <div class="form-check mb-2">
                    <input class="form-check-input technician-checkbox" type="checkbox" value="${tech.id}" id="tech-${tech.id}" ${selectedTechnicians.has(tech.id.toString()) ? 'checked' : ''}>
                    <label class="form-check-label" for="tech-${tech.id}">
                        <strong>${tech.name}</strong> ${tech.specialty ? `<span class="badge bg-secondary">${tech.specialty}</span>` : ''}
                        <small class="text-muted d-block">Available: ${periodDisplay}</small>
                        ${tech.phone ? `<small class="text-muted d-block">Phone: ${tech.phone}</small>` : ''}
                    </label>
                </div>`;
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
            if (renderTechnicianModal()) {
                technicianModal.show();
            }
        });

        // Use event delegation for the dynamically created save button
        document.getElementById('technicianModal').addEventListener('click', function (event) {
            if (event.target && event.target.id === 'save-technicians') {
                validationState.hasInteracted = true;

                // Get all checked technicians from the modal
                document.querySelectorAll('.technician-checkbox').forEach(checkbox => {
                    if (checkbox.checked) {
                        selectedTechnicians.add(checkbox.value);
                    } else {
                        selectedTechnicians.delete(checkbox.value);
                    }
                });

                updateAssignedTechnicians();
                technicianModal.hide();
            }
        });

        // Add event listener for date changes to check technician availability
        if (dateInput) {
            // Add blur event for date validation
            dateInput.addEventListener('blur', function () {
                if (validationState.hasInteracted) {
                    validateSingleField(this.id);
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
        form.addEventListener('submit', function (event) {
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

            const formData = new FormData(form);

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
                                    `/patient/patient_details.php?id=${formData.get('patient_id')}&tab=surgeries`;


                            } else {
                                window.location.href = '/surgery/surgeries.php';
                            }
                        }, 500);
                    } else {
                        showFormError(data.error || data.message ||
                            'An error occurred while saving surgery data.');
                    }
                })
                .catch(error => {
                    console.error('Error submitting surgery form:', error);
                    showFormError('An error occurred while saving surgery data.');
                });
        });

        if (saveNewPatientButton) {
            saveNewPatientButton.addEventListener('click', function () {
                if (!validateNewPatientForm()) {
                    newPatientStatusDiv.innerHTML =
                        '<div class="alert alert-danger mt-2">Please fill out all required fields.</div>';
                    return;
                }
                newPatientStatusDiv.innerHTML = ''; // Clear previous errors

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

        function getNewPatientValidationRules() {
            const rules = [{
                id: 'new_patient_name',
                msg: 'Patient name is required.'
            }];
            const userRole = '<?php echo get_user_role(); ?>';
            if (userRole === 'admin' || userRole === 'editor') {
                rules.push({
                    id: 'new_patient_agency_id',
                    msg: 'Please select an agency.'
                });
            }
            return rules;
        }

        function validateNewPatientForm() {
            let isValid = true;
            const rules = getNewPatientValidationRules();
            rules.forEach(rule => {
                const el = document.getElementById(rule.id);
                if (el && !el.value.trim()) {
                    isValid = false;
                    el.classList.add('is-invalid');
                    const feedbackEl = el.nextElementSibling;
                    if (feedbackEl && feedbackEl.classList.contains('invalid-feedback')) {
                        feedbackEl.textContent = rule.msg;
                    }
                } else if (el) {
                    el.classList.remove('is-invalid');
                }
            });
            return isValid;
        }


        // Add event listener to current_grafts_count to update status
        const currentGraftCountInput = document.getElementById('current_grafts_count');
        const statusSelect = document.getElementById('status');

        if (currentGraftCountInput && statusSelect) {
            currentGraftCountInput.addEventListener('input', function () {
                if (this.value.trim() !== '' && parseInt(this.value) >= 0) {
                    statusSelect.value = 'completed';
                    updateStatusDisplayFromData('completed');
                }
            });
        }

        // Reset modal form when hidden
        if (newPatientModal) {
            newPatientModal.addEventListener('hidden.bs.modal', function () {
                newPatientForm.reset();
                newPatientForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove(
                    'is-invalid'));
                newPatientStatusDiv.innerHTML = '';
            });

            newPatientModal.addEventListener('shown.bs.modal', function () {
                const userRole = '<?php echo get_user_role(); ?>';
                const userAgencyId = '<?php echo get_user_agency_id(); ?>';
                const agencySelect = document.getElementById('new_patient_agency_id');

                if (userRole === 'agent' && userAgencyId && agencySelect) {
                    agencySelect.value = userAgencyId;
                }
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

        function renderForms(forms) {
            const container = document.getElementById('forms-container');
            const formsInput = document.getElementById('forms-input');
            container.innerHTML = '';
            let formState = {};

            for (const [formName, isCompleted] of Object.entries(forms)) {
                const isChecked = Boolean(isCompleted);
                formState[formName] = isChecked;
                const iconClass = isChecked ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
                const formId = `form-toggle-${formName}`;

                const formElement = document.createElement('div');
                formElement.className = 'form-check form-switch';
                formElement.innerHTML = `
                   <i class="fas ${iconClass} fa-2x" id="icon-${formId}" style="cursor: pointer;"></i>
                   <label class="form-check-label ms-2" for="${formId}">${formName}</label>
               `;

                container.appendChild(formElement);

                document.getElementById(`icon-${formId}`).addEventListener('click', () => {
                    formState[formName] = !formState[formName];
                    const newIconClass = formState[formName] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
                    document.getElementById(`icon-${formId}`).className = `fas ${newIconClass} fa-2x`;
                    formsInput.value = JSON.stringify(formState);
                });
            }
            formsInput.value = JSON.stringify(formState);
        }

        // Initialize agencies for the modal
        fetchModalAgencies();
    });
</script>