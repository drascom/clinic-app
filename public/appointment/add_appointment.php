<?php
require_once '../includes/header.php';


$room_id = $_GET['room_id'] ?? null;
$date = $_GET['date'] ?? null;
$request_type = $_GET['request'] ?? null;
$appointment_id = $_GET['id'] ?? null;
$is_edit_mode = ($appointment_id !== null);
$prefilled = ($room_id && $date);
$page_title = $is_edit_mode ? 'Edit Appointment' : 'Add Appointment';

?>

<div class="container emp">
    <div class="card frosted">
        <div class="card-header p-4">
            <div class="d-flex justify-content-between align-items-center">
                <a href="appointments.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>
                    <span class="d-none d-sm-inline">Appointments</span>
                </a>
                <h4 class="mb-0">
                    <i
                        class="far <?= $is_edit_mode ? 'fa-edit text-primary' : 'fa-calendar-plus text-success' ?> me-2"></i><?= $page_title ?>
                </h4>
                <a href="calendar.php" class="btn  btn-outline-primary">
                    <i class="far fa-calendar-alt me-1"></i>
                    <span class="d-none d-sm-inline">Calendar</span>
                </a>
            </div>
        </div>

        <div class="card-body">
            <form id="appointment-form" novalidate>
                <?php if ($is_edit_mode): ?>
                    <input type="hidden" id="appointment-id" name="id" value="<?= htmlspecialchars($appointment_id) ?>">
                <?php endif; ?>
                <div class="row g-2">
                    <div class="col-md-5">
                        <fieldset class="border rounded p-3 mb-4 shadow-sm">
                            <legend class="w-auto px-2 mb-3" style="font-size:1rem;">
                                <i class="far fa-calendar-alt me-2"></i>Date &amp; Room<span
                                    class="text-danger">*</span>
                            </legend>

                            <?php if ($prefilled): ?>
                                <div class="alert alert-info mb-2">
                                    <i class="far fa-calendar me-2"></i>
                                    <strong>Date:</strong>
                                    <?= date('F j, Y', strtotime($date)) ?>
                                    <input type="hidden" id="appointment-date" name="appointment_date"
                                        value="<?= htmlspecialchars($date) ?>" reuired>
                                </div>
                                <div class="alert alert-success">
                                    <i class="fas fa-door-open me-2"></i>
                                    <strong>Room:</strong>
                                    <span id="selected-room-name">Loading…</span>
                                    <input type="hidden" id="room-id" name="room_id"
                                        value="<?= htmlspecialchars($room_id) ?>">
                                </div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <input type="date" class="form-control" id="appointment-date-input"
                                        name="appointment_date">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <select class="form-select" id="room-id-input" name="room_id">
                                        <option value="">Select Room <span class="text-danger">*</span></option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            <?php endif; ?>
                        </fieldset>


                        <fieldset class="border rounded p-3 mb-3 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <legend class="w-auto m-0 p-0" style="font-size:1rem;">
                                    <i class="fas fa-procedures me-2"></i>Procedure
                                </legend>
                                <button type="button" class="btn btn-link " data-bs-toggle="modal"
                                    data-bs-target="#newProcedureModal">
                                    <i class="far fa-plus me-1"></i><span class="d-none d-sm-inline">Add</span>
                                </button>
                            </div>

                            <select class="form-select select2-enable" id="procedure-id" name="procedure_id">
                                <option value="">Select Procedure</option>
                            </select>
                            <div class="invalid-feedback"></div>

                        </fieldset>
                    </div>

                    <div class="col-md-7">
                        <fieldset class="border rounded p-3 mb-3 shadow-sm">
                            <div class="d-flex justify-content-between align-items-baseline mb-3">
                                <legend class="w-auto px-3 m-0 p-0" style="font-size:1rem;">
                                    <i class="far fa-user me-2"></i>Patient Name<span class="text-danger">*</span>
                                </legend>
                                <button type="button"
                                    class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 py-0 px-2 m-0"
                                    data-bs-toggle="modal" data-bs-target="#newPatientModal">
                                    <i class="far fa-plus"></i>
                                    <span class="d-none d-sm-inline">Add</span>
                                </button>
                            </div>
                            <div class="input-group">
                                <select class="form-select select2-enable" id="patient-id" name="patient_id">
                                    <option value="">Select Patient</option>
                                </select>
                            </div>
                            <div class="invalid-feedback"></div>
                        </fieldset>

                        <fieldset class="border rounded p-3 mb-3 shadow-sm">

                            <div class="row">
                                <div class="col-md-5 mt-2">
                                    <legend class="w-auto px-2 mb-3" style="font-size:1.1rem;">
                                        <i class="far fa-clock me-2"></i>Time
                                    </legend>
                                    <div class="row g-1 justify-content-center">
                                        <?php foreach ([['09:00', '10:00'], ['10:00', '11:00'], ['11:00', '12:00'], ['14:00', '15:00'], ['15:00', '16:00'], ['16:00', '17:00']] as [$s, $e]): ?>
                                            <div class="col-auto">
                                                <button type="button" class="btn btn-sm btn-outline-primary "
                                                    onclick="setTimeSlot('<?= $s ?>','<?= $e ?>')">
                                                    <?= $s ?> - <?= $e ?>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="mb-3">
                                        <label for="start-time" class="form-label">Start *</label>
                                        <input type="time" id="start-time" name="start_time" class="form-control">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="end-time" class="form-label">End *</label>
                                        <input type="time" id="end-time" name="end_time" class="form-control">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <fieldset class="border rounded p-3 mb-3 shadow-sm">
                            <legend class="w-auto px-2 mb-3" style="font-size:1rem;">
                                <i class="far fa-sticky-note me-2"></i>Notes
                            </legend>
                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                placeholder="Additional notes or special instructions"></textarea>
                        </fieldset>
                    </div>

                </div>
                <!-- Action Buttons -->
                <div class="d-flex align-items-center justify-content-end gap-2">
                    <div>
                        <a href="<?= $prefilled ? 'calendar.php' : 'appointments.php' ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="far fa-save me-1"></i><?= $is_edit_mode ? 'Update' : 'Create' ?> Appointment
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- New Patient Modal -->
    <div class="modal fade" id="newPatientModal" tabindex="-1" aria-labelledby="newPatientModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newPatientModalLabel">
                        <i class="fas fa-user-plus me-2"></i>
                        Create New Patient
                    </h5>
                    <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="new-patient-form">
                        <?php if (is_admin() || is_editor()): ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="new_patient_agency_id" class="form-label">
                                            <i class="far fa-building me-1"></i>
                                            Agency<span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="new_patient_agency_id" name="agency_id" required>
                                            <option value="">Select Agency</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php elseif (is_agent()): ?>
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
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_patient_dob" class="form-label">
                                        <i class="far fa-calendar me-1"></i>
                                        Date of Birth
                                    </label>
                                    <input type="date" class="form-control" id="new_patient_dob" name="dob">
                                </div>
                            </div>
                        </div>
                    </form>
                    <div id="new-patient-status"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn  btn-outline-primary" id="save-new-patient">
                        <i class="far fa-save me-1"></i>Create Patient
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- New Procedure Modal -->
    <div class="modal fade" id="newProcedureModal" tabindex="-1" aria-labelledby="newProcedureModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newProcedureModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>
                        Create New Procedure
                    </h5>
                    <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="new-procedure-form">
                        <div class="mb-3">
                            <label for="new_procedure_name" class="form-label">
                                <i class="fas fa-stethoscope me-1"></i>
                                Procedure Name *
                            </label>
                            <input type="text" class="form-control" id="new_procedure_name" name="name"
                                placeholder="Enter procedure name" required>
                            <div class="form-text">Enter a unique name for the new procedure</div>
                        </div>
                    </form>
                    <div id="new-procedure-status"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn  btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn  btn-primary" id="save-new-procedure">
                        <i class="far fa-save me-1"></i>Create Procedure
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
    console.log('DEBUG: add_appointment.php script loaded.');

    let rooms = [];
    let patients = [];
    let procedures = [];
    let allAgencies = []; // Declare allAgencies globally
    let formWasSubmitted = false;
    const prefilled = <?= $prefilled ? 'true' : 'false' ?>;
    const isEditMode = <?= $is_edit_mode ? 'true' : 'false' ?>;
    const appointmentId = <?= $appointment_id ? (int) $appointment_id : 'null' ?>;

    function initPage() {
        console.log('Initialising page…');
        if (isEditMode) {
            loadAppointmentForEdit(appointmentId);
        } else {
            loadInitialData();
        }
        fetchModalAgencies(); // Call fetchModalAgencies on page load

        const form = document.getElementById('appointment-form');
        if (form) form.addEventListener('submit', onFormSubmit);

        const submitButton = document.querySelector('#appointment-form button[type="submit"]');


        // Attach blur listeners for standard input fields
        ['start-time', 'end-time', 'appointment-date-input', 'room-id-input'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('blur', () => {
                    validateSingleField(id);
                    updateSubmitButtonState(); // Update button state after single field validation
                });
            }
        });

        // Attach select2:close listeners for select2-enabled dropdowns
        ['patient-id', 'procedure-id'].forEach(id => {
            const el = document.getElementById(id);
            if (el && $(el).hasClass('select2-enable')) {
                $(el).on('select2:close', function() {
                    validateSingleField(id);
                    updateSubmitButtonState(); // Update button state after single field validation
                });
            }
        });

        // Initial validation to set button state on page load
        updateSubmitButtonState();

        if (!prefilled) {
            const dateInput = document.getElementById('appointment-date-input');
            if (dateInput && !dateInput.value) {
                dateInput.value = new Date().toISOString().split('T')[0];
            }
        }
    }

    initPage();

    // Function to update submit button state
    function updateSubmitButtonState() {
        const submitButton = document.querySelector('#appointment-form button[type="submit"]');
        if (submitButton) {
            // Call validateForm without showing errors to just get the validity state
            submitButton.disabled = !validateForm(false);
        }
    }

    async function loadInitialData() {
        console.log('Loading initial data…');
        try {
            const userRole = '<?php echo get_user_role(); ?>';
            const userAgencyId = '<?php echo get_user_agency_id(); ?>';
            console.log('DEBUG: User Role from PHP:', userRole);
            console.log('DEBUG: User Agency ID from PHP:', userAgencyId);

            let patientsRequest;
            if (userRole === 'agent' && userAgencyId) {
                patientsRequest = apiRequest('patients', 'list', {
                    agency: userAgencyId
                });
            } else {
                patientsRequest = apiRequest('patients', 'list');
            }

            const [roomsR, patientsR, proceduresR] = await Promise.all([
                apiRequest('rooms', 'list'),
                patientsRequest,
                apiRequest('procedures', 'active')
            ]);

            rooms = roomsR.rooms || [];
            patients = patientsR.patients || [];
            procedures = proceduresR.procedures || [];

            if (prefilled) {
                populatePrefilledRoomDisplay();
            } else {
                populateRoomSelect();
            }
            populatePatientSelect();
            populateProcedureSelect();
        } catch (err) {
            console.error(err);
            showToast('Failed to load initial data', 'danger');
        }
    }

    async function populatePrefilledRoomDisplay() {
        const span = document.getElementById('selected-room-name');
        if (!span) return;
        const roomId = <?= $room_id ? (int) $room_id : 'null' ?>;
        const rm = rooms.find(r => r.id == roomId);
        if (rm) {
            span.textContent = rm.name;
        } else if (roomId) {
            try {
                const data = await apiRequest('rooms', 'get', {
                    id: roomId
                });
                if (data.success && data.room) {
                    span.textContent = data.room.name;
                } else {
                    span.textContent = 'Unknown Room';
                }
            } catch (error) {
                console.error('Error fetching prefilled room details:', error);
                span.textContent = 'Unknown Room';
            }
        } else {
            span.textContent = 'N/A';
        }
    }

    function populateRoomSelect() {
        const select = document.getElementById('room-id-input');
        if (!select) return;
        select.innerHTML = '<option value="">Select Room</option>';
        const surgeryRegex = /surgery/i; // Case-insensitive regex for "surgery"
        rooms.filter(r => r.is_active && !surgeryRegex.test(r.name)).forEach(r => {
            const opt = new Option(r.name, r.id);
            select.appendChild(opt);
        });
    }

    function populatePatientSelect() {
        const select = document.getElementById('patient-id');
        if (!select) return;

        // Destroy existing Select2 instance if it exists
        if ($(select).data('select2')) {
            $(select).select2('destroy');
        }

        select.innerHTML = '<option value="">Select Patient</option>';
        patients.forEach(p => select.add(new Option(p.name, p.id)));

        // Re-initialize Select2 with dropdownParent to avoid clipping issues
        $(select).select2({
            dropdownParent: $('body')
        });
    }

    function populateProcedureSelect() {
        const selectElement = document.getElementById('procedure-id');
        if (!selectElement) return;

        // Destroy existing Select2 instance if it exists
        if ($(selectElement).data('select2')) {
            $(selectElement).select2('destroy');
        }

        selectElement.innerHTML = '<option value="">Select Procedure</option>';

        const requestType = '<?= $request_type ?? '' ?>';
        let proceduresToDisplay = [];

        if (requestType.toLowerCase() === 'consultation') {
            const consultationProcedure = procedures.find(p => p.id == 1);
            if (consultationProcedure) {
                proceduresToDisplay.push(consultationProcedure);
            }
        } else if (requestType === '') {
            proceduresToDisplay = procedures;
        } else {
            proceduresToDisplay = procedures.filter(p => p.id != 1);
        }

        proceduresToDisplay.forEach(p => {
            const option = document.createElement('option');
            option.value = p.id;
            option.textContent = escapeHtml(p.name);
            selectElement.appendChild(option);
        });

        if (requestType.toLowerCase() === 'consultation') {
            selectElement.value = proceduresToDisplay.length > 0 ? proceduresToDisplay[0].id : '';
        }

        // Re-initialize Select2 with dropdownParent to avoid clipping issues
        $(selectElement).select2({
            dropdownParent: $('body')
        });
    }

    async function loadAppointmentForEdit(id) {
        console.log('Loading appointment for editing…', id);
        try {
            // First, load all the dropdown data like patients, rooms, etc.
            await loadInitialData();

            // Then, fetch the specific appointment's details
            const appointmentData = await apiRequest('appointments', 'get', {
                id
            });
            if (appointmentData.success && appointmentData.appointment) {
                const app = appointmentData.appointment;
                console.log('Appointment data received:', app);

                // Populate the form fields
                document.getElementById('appointment-date-input').value = app.appointment_date;
                document.getElementById('start-time').value = app.start_time;
                document.getElementById('end-time').value = app.end_time;
                document.getElementById('notes').value = app.notes;

                // Set room
                $('#room-id-input').val(app.room_id).trigger('change');

                // Set patient - requires select2 handling
                $('#patient-id').val(app.patient_id).trigger('change');

                // Set procedure - requires select2 handling
                $('#procedure-id').val(app.procedure_id).trigger('change');

                // After populating, update the button state
                updateSubmitButtonState();

            } else {
                throw new Error(appointmentData.error || 'Appointment not found.');
            }
        } catch (err) {
            console.error(err);
            showToast('Failed to load appointment data: ' + err.message, 'danger');
            // Redirect or disable form if loading fails
            document.getElementById('appointment-form').innerHTML =
                '<div class="alert alert-danger">Could not load appointment details.</div>';
        }
    }

    function onFormSubmit(e) {
        e.preventDefault();
        formWasSubmitted = true;
        if (!validateForm(true)) return;

        const payload = {
            patient_id: document.getElementById('patient-id').value,
            room_id: prefilled ? <?= $room_id ? (int) $room_id : 'null' ?> : document.getElementById('room-id-input')
                .value,
            appointment_date: prefilled ? '<?= $date ?? '' ?>' : document.getElementById('appointment-date-input')
                .value,
            start_time: document.getElementById('start-time').value,
            end_time: document.getElementById('end-time').value,
            procedure_id: document.getElementById('procedure-id') ? document.getElementById('procedure-id').value : null,
            notes: document.getElementById('notes').value
        };

        let apiAction;
        let successMessage;

        if (isEditMode) {
            payload.id = appointmentId;
            apiAction = 'update';
            successMessage = 'Appointment updated successfully!';
        } else {
            apiAction = 'create';
            successMessage = 'Appointment created successfully!';
        }

        apiRequest('appointments', apiAction, payload)
            .then(res => {
                if (res.success) {
                    showToast(successMessage, 'success');
                    setTimeout(() => {
                        const patientId = payload.patient_id;
                        location.href = `/patient/patient_details.php?id=${patientId}&tab=appointments`;
                    }, 1500);
                } else {
                    throw new Error(res.error || 'An unknown error occurred.');
                }
            })
            .catch(err => {
                console.error('API Error:', err);
                showToast(`Failed to ${apiAction} appointment: ${err.message}`, 'danger');
            });
    }

    function validateForm(showUIErrors = false) {
        if (showUIErrors) {
            document.querySelectorAll('#appointment-form .is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('#appointment-form .invalid-feedback').forEach(el => el.textContent = '');
        }

        let valid = true;
        const requiredFields = getFieldValidationRules();

        requiredFields.forEach(f => {
            const el = document.getElementById(f.id);
            if (el && !el.value.trim()) {
                valid = false;
                if (showUIErrors) {
                    el.classList.add('is-invalid');
                    let feedbackEl;
                    if (el.parentElement.classList.contains('input-group')) {
                        feedbackEl = el.parentElement.nextElementSibling;
                    } else {
                        feedbackEl = el.nextElementSibling;
                    }
                    if (feedbackEl && feedbackEl.classList.contains('invalid-feedback')) {
                        feedbackEl.textContent = f.msg;
                    }
                }
            }
        });

        // Additional validation for start and end times
        const startTime = document.getElementById('start-time').value;
        const endTime = document.getElementById('end-time').value;
        if (startTime && endTime && startTime >= endTime) {
            valid = false;
            if (showUIErrors) {
                const endField = document.getElementById('end-time');
                endField.classList.add('is-invalid');
                if (endField.nextElementSibling) {
                    endField.nextElementSibling.textContent = 'End time must be after start time.';
                }
            }
        }
        return valid;
    }

    function getFieldValidationRules() {
        const rules = [{
                id: 'patient-id',
                msg: 'Please select a patient.'
            },
            {
                id: 'start-time',
                msg: 'Start time required.'
            },
            {
                id: 'end-time',
                msg: 'End time required.'
            },
            {
                id: 'procedure-id',
                msg: 'Please select a procedure.'
            }
        ];

        if (!prefilled) {
            rules.push({
                id: 'appointment-date-input',
                msg: 'Date required.'
            });
            rules.push({
                id: 'room-id-input',
                msg: 'Room required.'
            });
        }
        return rules;
    }

    function validateSingleField(id) {
        const el = document.getElementById(id);
        if (!el) return;

        const fieldRule = getFieldValidationRules().find(f => f.id === id);
        const msg = fieldRule ? fieldRule.msg : 'This field is required.';

        let feedbackEl;
        if (el.parentElement.classList.contains('input-group')) {
            feedbackEl = el.parentElement.nextElementSibling;
        } else {
            feedbackEl = el.nextElementSibling;
        }

        if (!el.value.trim()) {
            el.classList.add('is-invalid');
            if (feedbackEl && feedbackEl.classList.contains('invalid-feedback')) {
                feedbackEl.textContent = msg;
            }
        } else {
            el.classList.remove('is-invalid');
            if (feedbackEl && feedbackEl.classList.contains('invalid-feedback')) {
                feedbackEl.textContent = '';
            }
        }

        // Phone number validation
        if (el.type === 'tel' && el.value) { // Changed from else if to if
            const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
            if (!phoneRegex.test(el.value)) {
                el.classList.add('is-invalid');
                if (feedbackEl && feedbackEl.classList.contains('invalid-feedback')) {
                    feedbackEl.textContent = 'Please enter a valid phone number.';
                }
            } else {
                el.classList.remove('is-invalid');
                if (feedbackEl && feedbackEl.classList.contains('invalid-feedback')) {
                    feedbackEl.textContent = '';
                }
            }
        }

        // Specific validation for start-time and end-time
        if (id === 'start-time' || id === 'end-time') {
            const startTime = document.getElementById('start-time').value;
            const endTime = document.getElementById('end-time').value;

            if (startTime && endTime && startTime >= endTime) {
                const endField = document.getElementById('end-time');
                endField.classList.add('is-invalid');
                let endFeedbackEl;
                if (endField.parentElement.classList.contains('input-group')) {
                    endFeedbackEl = endField.parentElement.nextElementSibling;
                } else {
                    endFeedbackEl = endField.nextElementSibling;
                }
                if (endFeedbackEl && endFeedbackEl.classList.contains('invalid-feedback')) {
                    endFeedbackEl.textContent = 'End time must be after start time.';
                }
            } else {
                const endField = document.getElementById('end-time');
                endField.classList.remove('is-invalid');
                let endFeedbackEl;
                if (endField.parentElement.classList.contains('input-group')) {
                    endFeedbackEl = endField.parentElement.nextElementSibling;
                } else {
                    endFeedbackEl = endField.nextElementSibling;
                }
                if (endFeedbackEl && endFeedbackEl.classList.contains('invalid-feedback')) {
                    endFeedbackEl.textContent = '';
                }
            }
        }

        // Re-evaluate the entire form's validity after a single field is validated
        updateSubmitButtonState();
    }

    function setTimeSlot(start, end) {
        document.getElementById('start-time').value = start;
        document.getElementById('end-time').value = end;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }


    function populateModalAgencyDropdown() {
        try {
            const agencySelect = document.getElementById('new_patient_agency_id');
            const userRole = '<?php echo get_user_role(); ?>';
            const userAgencyId = '<?php echo get_user_agency_id(); ?>';



            if (!agencySelect) {

                return;
            }

            if (userRole === 'agent') {

                agencySelect.value = userAgencyId;
            } else if (userRole === 'admin' || userRole === 'editor') {

                agencySelect.innerHTML = '<option value="">Select Agency</option>';

                if (typeof allAgencies !== 'undefined' && Array.isArray(allAgencies)) {
                    allAgencies.forEach(agency => {
                        const option = document.createElement('option');
                        option.value = agency.id;
                        option.textContent = agency.name;
                        agencySelect.appendChild(option);
                    });
                } else {
                    console.warn('allAgencies is not defined or not an array:', allAgencies);
                }

            }
        } catch (error) {
            console.error('Error in populateModalAgencyDropdown:', error);
            // Ensure allAgencies is defined to prevent further errors
            if (typeof allAgencies === 'undefined') {
                allAgencies = [];
            }
            populateModalAgencyDropdown();
        }
    }

    function fetchModalAgencies() {
        try {
            const userRole = '<?php echo get_user_role(); ?>';
            const userAgencyId = '<?php echo get_user_agency_id(); ?>';

            console.log('DEBUG: fetchModalAgencies called. User Role:', userRole, 'User Agency ID:', userAgencyId);

            if (userRole === 'agent') {
                console.log('DEBUG: User is agent, populating dropdown directly.');
                populateModalAgencyDropdown();
            } else {
                console.log('DEBUG: User is admin/editor, fetching agencies from API.');
                apiRequest('agencies', 'list')
                    .then(data => {
                        if (data.success) {
                            allAgencies = data.agencies || [];
                            console.log('DEBUG: Agencies fetched successfully:', allAgencies);
                            populateModalAgencyDropdown();
                        } else {
                            console.error('Error fetching agencies:', data.error);
                            allAgencies = [];
                            populateModalAgencyDropdown();
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching agencies:', error);
                        allAgencies = [];
                        populateModalAgencyDropdown();
                    });
            }
        } catch (error) {
            console.error('Error in fetchModalAgencies:', error);
            if (typeof allAgencies === 'undefined') {
                allAgencies = [];
            }
            populateModalAgencyDropdown();
        }
    }

    function populateModalAgencyDropdown() {
        try {
            const agencySelect = document.getElementById('new_patient_agency_id');
            const userRole = '<?php echo get_user_role(); ?>';
            const userAgencyId = '<?php echo get_user_agency_id(); ?>';

            console.log('DEBUG: populateModalAgencyDropdown called. agencySelect:', agencySelect, 'allAgencies:',
                typeof allAgencies !== 'undefined' ? allAgencies.length : 'undefined');

            if (!agencySelect) {
                console.warn('DEBUG: Agency select element not found.');
                return;
            }

            if (userRole === 'agent') {
                console.log('DEBUG: User is agent, setting agencySelect value to userAgencyId:', userAgencyId);
                agencySelect.value = userAgencyId;
            } else if (userRole === 'admin' || userRole === 'editor') {
                console.log('DEBUG: User is admin/editor, populating dropdown with allAgencies.');
                agencySelect.innerHTML = '<option value="">Select Agency</option>';

                if (typeof allAgencies !== 'undefined' && Array.isArray(allAgencies)) {
                    allAgencies.forEach(agency => {
                        const option = document.createElement('option');
                        option.value = agency.id;
                        option.textContent = agency.name;
                        agencySelect.appendChild(option);
                    });
                    console.log('DEBUG: Agencies populated:', allAgencies.length, 'options added.');
                } else {
                    console.warn('DEBUG: allAgencies is not defined or not an array:', allAgencies);
                }
            }
        } catch (error) {
            console.error('Error in populateModalAgencyDropdown:', error);
            if (typeof allAgencies === 'undefined') {
                allAgencies = [];
            }
            populateModalAgencyDropdown(); // Recursive call, might be problematic if allAgencies remains undefined
        }
    }


    document.addEventListener('DOMContentLoaded', function() {
        const newPatientModal = document.getElementById('newPatientModal');
        const saveNewPatientButton = document.getElementById('save-new-patient');
        const newPatientForm = document.getElementById('new-patient-form');
        const newPatientStatusDiv = document.getElementById('new-patient-status');

        if (saveNewPatientButton) {
            saveNewPatientButton.addEventListener('click', function() {
                const formData = new FormData(newPatientForm);
                formData.append('entity', 'patients');
                formData.append('action', 'add');

                newPatientStatusDiv.innerHTML = '';

                const originalText = saveNewPatientButton.innerHTML;
                saveNewPatientButton.disabled = true;
                saveNewPatientButton.innerHTML = '<i class="far fa-spinner fa-spin me-1"></i>Creating...';

                apiRequest('patients', 'add', Object.fromEntries(formData))
                    .then(data => {
                        if (data.success) {
                            newPatientStatusDiv.innerHTML =
                                '<div class="alert alert-success">Patient created successfully!</div>';
                            let patientSelect;
                            patientSelect = document.getElementById('patient-id');
                            if (patientSelect) {
                                const newOption = new Option(data.patient.name, data.patient.id, true,
                                    true);
                                patientSelect.add(newOption);
                            }

                            setTimeout(() => {
                                const modal = bootstrap.Modal.getInstance(newPatientModal);
                                modal.hide();
                            }, 1000);
                        } else {
                            newPatientStatusDiv.innerHTML =
                                `<div class="alert alert-danger">${data.error || 'An error occurred.'}</div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error creating patient:', error);
                        newPatientStatusDiv.innerHTML =
                            '<div class="alert alert-danger">An error occurred while creating the patient.</div>';
                    })
                    .finally(() => {
                        saveNewPatientButton.disabled = false;
                        saveNewPatientButton.innerHTML = originalText;
                    });
            });
        }

        if (newPatientModal) {
            newPatientModal.addEventListener('show.bs.modal', function(event) {
                try {
                    // fetchModalAgencies() is now called on page load, no need to call it here
                } catch (error) {
                    console.error('Error in modal show event:', error);
                }
            });

            newPatientModal.addEventListener('hidden.bs.modal', function() {
                try {
                    newPatientForm.reset();
                    newPatientStatusDiv.innerHTML = '';
                    newPatientForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove(
                        'is-invalid'));
                    newPatientForm.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
                } catch (error) {
                    console.error('Error clearing modal form:', error);
                }
            });
        }

        const newProcedureModal = document.getElementById('newProcedureModal');
        const newProcedureForm = document.getElementById('new-procedure-form');
        const saveNewProcedureButton = document.getElementById('save-new-procedure');
        const newProcedureStatusDiv = document.getElementById('new-procedure-status');

        if (saveNewProcedureButton) {
            saveNewProcedureButton.addEventListener('click', function() {
                const procedureName = document.getElementById('new_procedure_name').value.trim();

                if (!procedureName) {
                    newProcedureStatusDiv.innerHTML =
                        '<div class="alert alert-danger">Please enter a procedure name.</div>';
                    return;
                }

                newProcedureStatusDiv.innerHTML = '';

                const originalText = saveNewProcedureButton.innerHTML;
                saveNewProcedureButton.disabled = true;
                saveNewProcedureButton.innerHTML = '<i class="far fa-spinner fa-spin me-1"></i>Creating...';

                apiRequest('procedures', 'create', {
                        name: procedureName
                    })
                    .then(data => {
                        if (data.success) {
                            newProcedureStatusDiv.innerHTML =
                                '<div class="alert alert-success">Procedure created successfully!</div>';

                            const newProcedure = {
                                id: data.id,
                                name: procedureName
                            };
                            procedures.push(newProcedure);

                            populateProcedureSelect();

                            let procedureSelect;
                            procedureSelect = document.getElementById('procedure-id');
                            if (procedureSelect) {
                                procedureSelect.value = data.id;
                            }

                            document.getElementById('new_procedure_name').value = '';

                            setTimeout(() => {
                                const modal = bootstrap.Modal.getInstance(newProcedureModal);
                                modal.hide();
                            }, 1000);
                        } else {
                            newProcedureStatusDiv.innerHTML =
                                `<div class="alert alert-danger">${data.error || 'An error occurred.'}</div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error creating procedure:', error);
                        newProcedureStatusDiv.innerHTML =
                            '<div class="alert alert-danger">An error occurred while creating the procedure.</div>';
                    })
                    .finally(() => {
                        // Re-enable button
                        saveNewProcedureButton.disabled = false;
                        saveNewProcedureButton.innerHTML = originalText;
                    });
            });
        }

        if (newProcedureModal) {
            newProcedureModal.addEventListener('hidden.bs.modal', function() {
                document.getElementById('new_procedure_name').value = '';
                newProcedureStatusDiv.innerHTML = '';
            });
        }
    });
</script>