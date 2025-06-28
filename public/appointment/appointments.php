<?php
require_once '../includes/header.php';
$page_title = "Appointment Management";

?>
<div class="container emp-10">
    <!-- Loading Spinner -->
    <div id="loading-spinner" class="text-center py-4" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Appointments Table -->
    <div class="card ">
        <div class="card-header">
            <!-- Page Header -->
            <div class="row align-items-center p-2 gx-2">
                <div class="col">
                    <h4 class="mb-0">
                        <i class="far fa-calendar-check text-primary me-2"></i>
                        Appointments
                    </h4>
                </div>

                <div class="col-auto">
                    <div class="btn-group" role="group">
                        <a href="add_appointment.php" class="btn btn-outline-success d-flex align-items-center">
                            <i class="fas fa-plus"></i>
                            <span class="d-none d-sm-inline ms-1">Add Appointment</span>
                        </a>
                        <a href="/calendar/calendar.php" class="btn btn-outline-primary d-flex align-items-center">
                            <i class="far fa-calendar"></i>
                            <span class="d-none d-sm-inline ms-1">Calendar</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <fieldset class="p-4 frosted">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="search-input"
                        placeholder="Search appointments by patient name, room, date, procedure, or notes...">
                    <button class="btn btn-outline-secondary" type="button" id="clear-search" title="Clear search">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="text-muted small ms-4">
                    <i class="fas fa-info-circle me-1"></i>
                    <span id="records-count">Loading...</span> records found
                </div>
            </fieldset>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover  table-sm" id="appointments-table">
                <thead class="table-light">
                    <tr>
                        <th class="sortable" data-sort-by="appointment_date" data-sort-order="desc">Date <i
                                class="fas fa-sort-down ms-1"></i></th>
                        <th class="sortable" data-sort-by="start_time" data-sort-order="asc">Time <i
                                class="fas fa-sort ms-1"></i></th>
                        <th class="sortable" data-sort-by="patient_name" data-sort-order="asc">Patient <i
                                class="fas fa-sort ms-1"></i></th>
                        <th class="sortable" data-sort-by="room_name" data-sort-order="asc">Room <i
                                class="fas fa-sort ms-1"></i></th>
                        <th class="sortable" data-sort-by="procedure_name" data-sort-order="asc">Procedure <i
                                class="fas fa-sort ms-1"></i></th>
                        <th class="sortable" data-sort-by="consultation_type" data-sort-order="asc">Type <i
                                class="fas fa-sort ms-1"></i></th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="appointments-tbody">
                    <!-- Appointments will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>


    <!-- Edit Appointment Modal -->
    <div class="modal fade" id="editAppointmentModal" tabindex="-1" aria-labelledby="editAppointmentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAppointmentModalLabel">Edit Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="edit-appointment-form">
                    <div class="modal-body">
                        <input type="hidden" id="edit-appointment-id">

                        <div class="mb-3">
                            <label for="edit-patient-id" class="form-label">Patient *</label>
                            <select class="form-select" id="edit-patient-id" required>
                                <option value="">Select Patient</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit-room-id" class="form-label">Room *</label>
                            <select class="form-select" id="edit-room-id" required>
                                <option value="">Select Room</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit-appointment-date" class="form-label">Date *</label>
                            <input type="date" class="form-control" id="edit-appointment-date" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit-start-time" class="form-label">Start Time *</label>
                                    <input type="time" class="form-control" id="edit-start-time" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit-end-time" class="form-label">End Time *</label>
                                    <input type="time" class="form-control" id="edit-end-time" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit-procedure-id" class="form-label">Procedure *</label>
                            <div class="input-group">
                                <select class="form-select" id="edit-procedure-id" required>
                                    <option value="">Select Procedure</option>
                                    <!-- Procedures will be loaded dynamically -->
                                </select>
                                <button type="button" class="btn btn-link btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#newProcedureModal">
                                    <i class="fas fa-plus me-1"></i>
                                    <span class="d-none d-sm-inline">Add New</span>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit-consultation-type" class="form-label">Consultation Type *</label>
                            <select class="form-select" id="edit-consultation-type" required>
                                <option value="face-to-face">Face-to-face</option>
                                <option value="video-to-video">Video-to-video</option>
                            </select>
                            <div class="invalid-feedback">Please select a consultation type.</div>
                        </div>

                        <div class="mb-3">
                            <label for="edit-notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="edit-notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Appointment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let appointments = [];
    let rooms = [];
    let patients = [];
    let currentSortColumn = 'appointment_date';
    let currentSortOrder = 'desc';

    document.addEventListener('DOMContentLoaded', function () {
        loadInitialData();

        // Search functionality
        const searchInput = document.getElementById('search-input');
        searchInput.addEventListener('input', searchAppointments);

        const clearSearchBtn = document.getElementById('clear-search');
        if (searchInput && clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function () {
                searchInput.value = '';
                searchAppointments();
            });
        }

        // Edit form submission
        document.getElementById('edit-appointment-form').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!validateEditForm(true)) return; // Add validation check
            updateAppointment();
        });

        // Attach blur listeners for standard input fields in the edit modal
        ['edit-start-time', 'edit-end-time', 'edit-appointment-date', 'edit-room-id', 'edit-consultation-type'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('blur', () => {
                    validateSingleEditField(id);
                    updateEditSubmitButtonState
                        (); // Update button state after single field validation
                });
            }
        });

        // Attach select2:close listeners for select2-enabled dropdowns in the edit modal
        ['edit-patient-id', 'edit-procedure-id'].forEach(id => {
            const el = document.getElementById(id);
            if (el && $(el).hasClass('select2-enable')) { // Check if it's a select2 element
                $(el).on('select2:close', function () {
                    validateSingleEditField(id);
                    updateEditSubmitButtonState
                        (); // Update button state after single field validation
                });
            } else if (el) { // For regular select elements
                el.addEventListener('change', () => {
                    validateSingleEditField(id);
                    updateEditSubmitButtonState();
                });
            }
        });

        const sortableHeaders = document.querySelectorAll('.sortable');
        sortableHeaders.forEach(header => {
            header.addEventListener('click', function () {
                const sortColumn = this.dataset.sortBy;
                let newSortOrder;

                if (currentSortColumn === sortColumn) {
                    // Toggle order if same column is clicked
                    newSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    // Default to 'asc' for new column, or use its default sort order from data attribute
                    newSortOrder = this.dataset.sortOrder || 'asc';
                }
                sortAndDisplayAppointments(appointments, sortColumn, newSortOrder);
            });
        });
    });

    // Function to update edit submit button state
    function updateEditSubmitButtonState() {
        const submitButton = document.querySelector('#edit-appointment-form button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = !validateEditForm(false);
        }
    }

    function validateEditForm(showUIErrors = false) {
        const form = document.getElementById('edit-appointment-form');
        if (showUIErrors) {
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        }

        let isValid = true;
        const requiredFields = getEditFieldValidationRules();

        requiredFields.forEach(f => {
            const el = document.getElementById(f.id);
            if (el && !el.value.trim()) {
                isValid = false;
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
        const startTime = document.getElementById('edit-start-time').value;
        const endTime = document.getElementById('edit-end-time').value;
        if (startTime && endTime && startTime >= endTime) {
            isValid = false;
            if (showUIErrors) {
                const endField = document.getElementById('edit-end-time');
                endField.classList.add('is-invalid');
                if (endField.nextElementSibling) {
                    endField.nextElementSibling.textContent = 'End time must be after start time.';
                }
            }
        }
        return isValid;
    }

    function validateSingleEditField(id) {
        const el = document.getElementById(id);
        if (!el) return;

        const fieldRule = getEditFieldValidationRules().find(f => f.id === id);
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

        // Specific validation for start-time and end-time
        if (id === 'edit-start-time' || id === 'edit-end-time') {
            const startTime = document.getElementById('edit-start-time').value;
            const endTime = document.getElementById('edit-end-time').value;

            if (startTime && endTime && startTime >= endTime) {
                const endField = document.getElementById('edit-end-time');
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
                const endField = document.getElementById('edit-end-time');
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
        updateEditSubmitButtonState(); // Update button state after single field validation
    }

    function getEditFieldValidationRules() {
        return [{
            id: 'edit-patient-id',
            msg: 'Please select a patient.'
        },
        {
            id: 'edit-room-id',
            msg: 'Please select a room.'
        },
        {
            id: 'edit-appointment-date',
            msg: 'Date required.'
        },
        {
            id: 'edit-start-time',
            msg: 'Start time required.'
        },
        {
            id: 'edit-end-time',
            msg: 'End time required.'
        },
        {
            id: 'edit-procedure-id',
            msg: 'Please select a procedure.'
        },
        {
            id: 'edit-consultation-type',
            msg: 'Please select a consultation type.'
        }
        ];
    }

    async function loadInitialData() {
        showLoading(true);

        try {
            // Prepare patient request with agency filtering for agents
            const userRole = '<?php echo get_user_role(); ?>';
            const userAgencyId = '<?php echo get_user_agency_id(); ?>';

            let patientsRequest;
            if (userRole === 'agent' && userAgencyId) {
                patientsRequest = apiRequest('patients', 'list', {
                    agency: userAgencyId
                });
            } else {
                patientsRequest = apiRequest('patients', 'list');
            }

            // Load rooms, patients, procedures, and appointments in parallel
            const [roomsData, patientsData, proceduresData, appointmentsData] = await Promise.all([
                apiRequest('rooms', 'list'),
                patientsRequest,
                apiRequest('procedures', 'active'),
                apiRequest('appointments', 'list')
            ]);

            if (roomsData.success) {
                rooms = roomsData.rooms || [];
                populateRoomSelects();
            }

            if (patientsData.success) {
                patients = patientsData.patients || [];
                populatePatientSelects();
            }

            if (proceduresData.success) {
                procedures = proceduresData.procedures || [];
                populateProcedureSelects();
            }

            if (appointmentsData.success) {
                appointments = appointmentsData.appointments || [];
                sortAndDisplayAppointments(appointments, currentSortColumn, currentSortOrder);
            } else {
                showToast('Error loading appointments: ' + (appointmentsData.error || 'Unknown error'), 'danger');
            }
        } catch (error) {
            console.error('Error loading data:', error);
            showToast('Failed to load data. Please try again.', 'danger');
        } finally {
            showLoading(false);
        }
    }

    function populateRoomSelects() {
        const editSelect = document.getElementById('edit-room-id');

        // Clear existing options (except first)
        editSelect.innerHTML = '<option value="">Select Room</option>';

        rooms.forEach(room => {
            if (room.is_active) {
                const editOption = new Option(room.name, room.id);
                editSelect.add(editOption);
            }
        });
    }

    function populatePatientSelects() {
        const editSelect = document.getElementById('edit-patient-id');

        // Clear existing options (except first)
        editSelect.innerHTML = '<option value="">Select Patient</option>';

        patients.forEach(patient => {
            const option = new Option(patient.name, patient.id);
            editSelect.add(option);
        });
    }

    function populateProcedureSelects() {
        const editSelect = document.getElementById('edit-procedure-id');

        if (editSelect) {
            editSelect.innerHTML = '<option value="">Select Procedure</option>';
            procedures.forEach(procedure => {
                const editOption = new Option(procedure.name, procedure.id);
                editSelect.add(editOption);
            });
        }
    }

    function renderAppointmentsTable(appointmentsToRender) {
        const tbody = document.getElementById('appointments-tbody');
        document.getElementById('records-count').textContent = ' ' + appointmentsToRender.length + ' ';

        if (appointmentsToRender.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="fas fa-calendar-times fa-2x mb-2"></i><br>
                    No appointments found
                </td>
            </tr>
        `;
            return;
        }

        tbody.innerHTML = appointmentsToRender.map(appointment => `
        <tr>
            <td>${formatDate(appointment.appointment_date)}</td>
            <td>${appointment.start_time} - ${appointment.end_time}</td>
            <td><a href="/patient/patient_details.php?id=${appointment.patient_id}&tab=appointments">${appointment.patient_name}</a></td>
            <td>${appointment.room_name}</td>
            <td>
                <span class="badge bg-primary">
                    ${appointment.procedure_name || 'No Procedure'}
                </span>
            </td>
            <td>
                <span class="badge ${getStatusBadgeClass(appointment.consultation_type)}">
                    <i class="fas ${appointment.consultation_type === 'video-to-video' ? 'fa-video' : 'fa-user-friends'} me-1"></i>
                    ${appointment.consultation_type}
                </span>
            </td>
            <td>${appointment.notes || '-'}</td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                   <a href="/appointment/edit_appointment.php?id=${appointment.id}"
                        class="btn  btn-text text-info"
                        title="Edit Surgery">
                        <i class="fas fa-edit"></i>
                        <span class="d-none d-lg-inline ms-1">Edit</span>
                    </a>
                  <!--  <button type="button" class="btn btn-text text-primary" onclick="editAppointment(${appointment.id})" title="Edit">
                        <i class="fas fa-edit"></i><span class="d-none d-lg-inline ms-1">Mini Edit</span>
                    </button> -->
                    <button type="button" class="btn btn-text text-danger" onclick="deleteAppointment(${appointment.id}, '${appointment.patient_name}')" title="Delete">
                        <i class="fas fa-trash"></i> <span class="d-none d-lg-inline ms-1">Delete</span>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    }

    function formatDate(dateString) {
        const date = new Date(dateString + 'T00:00:00');
        return date.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    function searchAppointments() {
        const searchTerm = document.getElementById('search-input').value.toLowerCase().trim();

        const filteredAppointments = appointments.filter(appointment => {
            if (!searchTerm) return true;
            return (
                appointment.patient_name.toLowerCase().includes(searchTerm) ||
                appointment.room_name.toLowerCase().includes(searchTerm) ||
                appointment.appointment_date.includes(searchTerm) ||
                (appointment.procedure_name && appointment.procedure_name.toLowerCase().includes(searchTerm)) ||
                (appointment.notes && appointment.notes.toLowerCase().includes(searchTerm))
            );
        });

        sortAndDisplayAppointments(filteredAppointments, currentSortColumn, currentSortOrder);
    }

    function editAppointment(appointmentId) {
        const appointment = appointments.find(a => a.id == appointmentId);
        if (!appointment) {
            showToast('Appointment not found', 'danger');
            return;
        }

        // Populate form
        document.getElementById('edit-appointment-id').value = appointment.id;
        document.getElementById('edit-patient-id').value = appointment.patient_id;
        document.getElementById('edit-room-id').value = appointment.room_id;
        document.getElementById('edit-appointment-date').value = appointment.appointment_date;
        document.getElementById('edit-start-time').value = appointment.start_time;
        document.getElementById('edit-end-time').value = appointment.end_time;
        document.getElementById('edit-procedure-id').value = appointment.procedure_id || '';
        document.getElementById('edit-notes').value = appointment.notes || '';
        document.getElementById('edit-consultation-type').value = appointment.consultation_type || 'face-to-face';

        // Clear previous validation states
        const form = document.getElementById('edit-appointment-form');
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('editAppointmentModal'));
        modal.show();

        // Update button state after populating the form
        updateEditSubmitButtonState();
    }

    function updateAppointment() {
        const formData = new FormData();
        formData.append('entity', 'appointments');
        formData.append('action', 'update');
        formData.append('id', document.getElementById('edit-appointment-id').value);
        formData.append('patient_id', document.getElementById('edit-patient-id').value);
        formData.append('room_id', document.getElementById('edit-room-id').value);
        formData.append('appointment_date', document.getElementById('edit-appointment-date').value);
        formData.append('start_time', document.getElementById('edit-start-time').value);
        formData.append('end_time', document.getElementById('edit-end-time').value);
        formData.append('procedure_id', document.getElementById('edit-procedure-id').value);
        formData.append('notes', document.getElementById('edit-notes').value);
        formData.append('consultation_type', document.getElementById('edit-consultation-type').value);

        fetch('/api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('editAppointmentModal')).hide();
                    loadInitialData(); // Reload appointments
                } else {
                    showToast('Error: ' + (data.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error updating appointment:', error);
                showToast('Failed to update appointment. Please try again.', 'danger');
            });
    }

    function deleteAppointment(appointmentId, patientName) {
        if (!confirm(`Are you sure you want to delete the appointment for "${patientName}"?`)) {
            return;
        }

        const formData = new FormData();
        formData.append('entity', 'appointments');
        formData.append('action', 'delete');
        formData.append('id', appointmentId);

        fetch('/api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    loadInitialData(); // Reload appointments
                } else {
                    showToast('Error: ' + (data.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error deleting appointment:', error);
                showToast('Failed to delete appointment. Please try again.', 'danger');
            });
    }

    function showLoading(show) {
        document.getElementById('loading-spinner').style.display = show ? 'block' : 'none';
    }

    function sortAndDisplayAppointments(appointmentsToSort, sortColumn, sortOrder) {
        const sortedAppointments = [...appointmentsToSort].sort((a, b) => {
            let valA = a[sortColumn];
            let valB = b[sortColumn];

            if (sortColumn === 'appointment_date') {
                valA = new Date(valA);
                valB = new Date(valB);
            }

            if (typeof valA === 'string') valA = valA.toLowerCase();
            if (typeof valB === 'string') valB = valB.toLowerCase();

            if (valA < valB) return sortOrder === 'asc' ? -1 : 1;
            if (valA > valB) return sortOrder === 'asc' ? 1 : -1;
            return 0;
        });

        renderAppointmentsTable(sortedAppointments);

        currentSortColumn = sortColumn;
        currentSortOrder = sortOrder;

        document.querySelectorAll('.sortable').forEach(header => {
            const icon = header.querySelector('i');
            if (icon) {
                icon.className = 'fas ms-1'; // Reset classes
                if (header.dataset.sortBy === currentSortColumn) {
                    icon.classList.add(currentSortOrder === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
                } else {
                    icon.classList.add('fa-sort');
                }
            }
        });
    }
</script>

<?php require_once '../includes/footer.php'; ?>