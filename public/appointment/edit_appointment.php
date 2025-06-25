<?php
require_once '../includes/header.php';

// Sanitize and validate appointment_id
$appointment_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : null;
$is_edit_mode = ($appointment_id !== null);
$page_title = 'Edit Appointment';
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
                    <i class="far fa-edit text-primary me-2"></i><?= $page_title ?>
                </h4>
                <a href="/calendar/calendar.php" class="btn btn-outline-primary">
                    <i class="far fa-calendar-alt me-1"></i>
                    <span class="d-none d-sm-inline">Calendar</span>
                </a>
            </div>
        </div>

        <div class="card-body">
            <form id="appointment-form">
                <?php if ($is_edit_mode): ?>
                    <input type="hidden" id="appointment-id" name="id" value="<?= htmlspecialchars($appointment_id) ?>">
                <?php endif; ?>
                <div class="row g-3">
                    <!-- First Column -->
                    <div class="col-md-6">
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
                        </fieldset>
                        <fieldset id="date-room-fieldset" class="border rounded p-3 mb-3 shadow-sm">
                            <legend class="w-auto px-2 mb-3" style="font-size:1rem;">
                                <i class="far fa-calendar-alt me-2"></i>Date & Room<span class="text-danger">*</span>
                            </legend>
                            <div class="mb-3 ">
                                <label for="room-id-input" class="form-label">
                                    Room <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="room-id-input" name="room_id">
                                    <option value="" disabled selected hidden>Select Room</option>
                                </select>
                            </div>
                            <div id="datepicker-container" class="d-flex justify-content-around"></div>
                            <input type="hidden" id="appointment-date-input" name="appointment_date">
                        </fieldset>
                    </div>

                    <!-- Second Column -->
                    <div class="col-md-6">
                        <div class="btn-group d-grid gap-2 d-md-flex justify-content-md-center p-4">
                            <button id="btn-consultation" type="button"
                                class="btn btn-outline-primary px-4">Consultation</button>
                            <button id="btn-treatment" type="button"
                                class="btn btn-outline-primary px-4">Treatment</button>
                        </div>
                        <fieldset class="border rounded p-3 mb-3 shadow-sm">
                            <div id="treatment-section" class="fade-out">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <legend class="w-auto m-0 p-0" style="font-size:1rem;">
                                        <i class="fas fa-procedures me-2"></i>Procedure
                                    </legend>
                                    <button type="button" class="btn btn-link" data-bs-toggle="modal"
                                        data-bs-target="#newProcedureModal">
                                        <i class="far fa-plus me-1"></i><span class="d-none d-sm-inline">Add</span>
                                    </button>
                                </div>
                                <select class="form-select select2-enable" id="procedure-id" name="procedure_id">
                                    <option value="">Select Procedure</option>
                                </select>
                            </div>
                            <div id="consultation-section" class="fade-out">
                                <legend class="w-auto px-2 mb-3" style="font-size:1rem;">
                                    <i class="fas fa-headset me-2"></i>Consultation Type<span
                                        class="text-danger">*</span>
                                </legend></br>
                                <div class="btn-group mx-auto" role="group" aria-label="Consultation type selection">
                                    <label class="btn btn-outline-primary"
                                        for="consultation_type_ftof">Face-to-face</label>
                                    <input type="radio" class="btn-check" name="consultation_type"
                                        id="consultation_type_ftof" value="face-to-face" autocomplete="off">

                                    <input type="radio" class="btn-check" name="consultation_type"
                                        id="consultation_type_vtov" value="video-to-video" autocomplete="off">
                                    <label class="btn btn-outline-primary"
                                        for="consultation_type_vtov">Video-to-video</label>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset class="border rounded p-3 mb-3 shadow-sm">
                            <legend class="w-auto px-2 mb-3" style="font-size:1rem;">
                                <i class="far fa-clock me-2"></i>Time
                            </legend>
                            <div id="time-slots-container" class="row g-1 justify-content-center mt-3">
                                <?php
                                $start = new DateTime('08:30');
                                $end = new DateTime('17:00');
                                $interval = new DateInterval('PT30M');
                                foreach (new DatePeriod($start, $interval, $end) as $slot) {
                                    $from = $slot->format('H:i');
                                    $to = (clone $slot)->add($interval)->format('H:i');
                                    echo '<div class="col-auto">';
                                    echo '<button type="button"
                                             class="btn btn-sm btn-outline-primary time-slot-btn"
                                             data-start="' . $from . '"
                                             data-end="' . $to . '">'
                                        . $from . ' – ' . $to .
                                        '</button></div>';
                                }
                                ?>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label for="start-time" class="form-label">Start *</label>
                                    <input type="time" id="start-time" name="start_time" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label for="end-time" class="form-label">End *</label>
                                    <input type="time" id="end-time" name="end_time" class="form-control">
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>

                <!-- Full-width Notes Section -->
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
                        <a href="appointments.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                        <button id="submit" type="submit" class="btn btn-primary">
                            <i class="far fa-save me-1"></i>Update Appointment
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<!-- Dependency & Enhancement scripts -->
<script>
    // Helper to log all major steps and responses
    function debugLog(...args) { console.log('[APPT EDIT]', ...args); }



    let rooms = [], patients = [], procedures = [];
    const appointmentId = <?= $appointment_id ? (int) $appointment_id : 'null' ?>;

    document.addEventListener('DOMContentLoaded', function () {
        if (!appointmentId) {
            debugLog('No appointmentId specified in URL. Nothing to edit.');
            document.getElementById('appointment-form').innerHTML =
                '<div class="alert alert-danger">No appointment ID provided.</div>';
            return;
        }
        loadAppointmentForEdit(appointmentId);

        // Time-slot selection
        document.querySelectorAll('.time-slot-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                document.querySelectorAll('.time-slot-btn').forEach(b => {
                    b.classList.remove('btn-success');
                    b.classList.add('btn-outline-primary');
                });
                e.currentTarget.classList.remove('btn-outline-primary');
                e.currentTarget.classList.add('btn-success');
                document.getElementById('start-time').value = e.currentTarget.dataset.start;
                document.getElementById('end-time').value = e.currentTarget.dataset.end;

                // Automatically submit the form
                const form = document.getElementById('appointment-form');
                onFormSubmit({
                    target: form
                });
            });
        });

        // Validation & submission
        const form = document.getElementById('appointment-form');
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            onFormSubmit(event);
        }, false);

        // Consultation / Treatment Toggle
        const btnConsultation = document.getElementById('btn-consultation');
        const btnTreatment = document.getElementById('btn-treatment');
        const consultationSection = document.getElementById('consultation-section');
        const treatmentSection = document.getElementById('treatment-section');
        const consultationRadios = consultationSection.querySelectorAll('input[name="consultation_type"]');

        // Set initial state - both hidden
        treatmentSection.classList.add('fade-out');
        treatmentSection.classList.remove('fade-in');
        consultationSection.classList.add('fade-out');
        consultationSection.classList.remove('fade-in');

        btnConsultation.addEventListener('click', () => {
            consultationSection.classList.remove('fade-out');
            consultationSection.classList.add('fade-in');

            treatmentSection.classList.remove('fade-in');
            treatmentSection.classList.add('fade-out');

            btnConsultation.classList.add('active');
            btnTreatment.classList.remove('active');
        });

        btnTreatment.addEventListener('click', () => {
            treatmentSection.classList.remove('fade-out');
            treatmentSection.classList.add('fade-in');

            consultationSection.classList.remove('fade-in');
            consultationSection.classList.add('fade-out');
            consultationRadios.forEach(radio => {
                radio.checked = false;
            });

            btnTreatment.classList.add('active');
            btnConsultation.classList.remove('active');
        });

        // Event listeners for fetching available slots
        const roomIdInput = document.getElementById('room-id-input');
        const appointmentDateInput = document.getElementById('appointment-date-input');

        roomIdInput.addEventListener('change', fetchAvailableSlots);
        appointmentDateInput.addEventListener('change', fetchAvailableSlots); // Trigger on hidden input change
        // The event listener for procedure changes is now handled within populateProcedureSelect
        // to ensure it's attached correctly after Select2 is initialized.

        // Setup datepicker and preselect

        const datepickerContainer = document.getElementById('datepicker-container');
        if (datepickerContainer && !datepickerContainer.datepicker) {
            datepickerContainer.datepicker = new Datepicker(datepickerContainer, {
                buttonClass: 'btn',
                format: 'yyyy-mm-dd',
                autohide: true,
            });
        }
        // Always add the event listener after datepicker is ensured
        datepickerContainer.addEventListener('changeDate', (event) => {
            debugLog('Datepicker changeDate event fired!');
            const datepickerInstance = datepickerContainer.datepicker;
            if (datepickerInstance) {
                const formattedDate = datepickerInstance.getDate('yyyy-mm-dd');
                const hiddenInput = document.getElementById('appointment-date-input');
                hiddenInput.value = formattedDate;
                // Manually trigger a 'change' event so other listeners (like fetchAvailableSlots) are notified
                hiddenInput.dispatchEvent(new Event('change'));
            }
        });

    });

    async function loadAppointmentForEdit(id) {
        debugLog('Starting to load appointment for edit:', id);
        try {

            const [roomsR, patientsR, proceduresR, appointmentData] = await Promise.all([
                apiRequest('rooms', 'list'),
                apiRequest('patients', 'list'),
                apiRequest('procedures', 'active'),
                apiRequest('appointments', 'get', { id })
            ]);

            debugLog('API Response - Rooms:', roomsR);
            debugLog('API Response - Patients:', patientsR);
            debugLog('API Response - Procedures:', proceduresR);
            debugLog('API Response - Appointment:', appointmentData);

            rooms = roomsR.rooms || [];
            patients = patientsR.patients || [];
            procedures = proceduresR.procedures || [];

            populateRoomSelect();
            populatePatientSelect();
            populateProcedureSelect();

            // Defensive: if data missing, abort
            if (!(appointmentData && appointmentData.success && appointmentData.appointment)) {
                debugLog('Appointment not found or failed to fetch.');
                document.getElementById('appointment-form').innerHTML =
                    '<div class="alert alert-danger">Could not load appointment details.</div>';
                return;
            }

            const app = appointmentData.appointment;

            // Set fields
            document.getElementById('appointment-date-input').value = app.appointment_date || '';
            document.getElementById('start-time').value = app.start_time || '';
            document.getElementById('end-time').value = app.end_time || '';
            document.getElementById('notes').value = app.notes || '';

            $('#room-id-input').val(app.room_id).trigger('change');
            $('#patient-id').val(app.patient_id).trigger('change');
            $('#procedure-id').val(app.procedure_id).trigger('change');

            // Set appointment type and consultation type
            if (app.appointment_type === 'consultation') {
                document.getElementById('btn-consultation').click();
                if (app.consultation_type) {
                    document.querySelectorAll('input[name="consultation_type"]').forEach(radio => {
                        radio.checked = (radio.value === app.consultation_type);
                    });
                }
            } else if (app.appointment_type === 'treatment') {
                document.getElementById('btn-treatment').click();
            }

            // Set initial date visually if possible
            const datepickerContainer = document.getElementById('datepicker-container');
            if (datepickerContainer.datepicker && app.appointment_date) {
                datepickerContainer.datepicker.setDate(app.appointment_date);
            }

            // Initial fetch of available slots after loading appointment data
            fetchAvailableSlots();

        } catch (err) {
            debugLog('Error loading appointment:', err);
            document.getElementById('appointment-form').innerHTML =
                '<div class="alert alert-danger">Could not load appointment details. Please try again later.</div>';
        }
    }

    async function fetchAvailableSlots() {
        const date = document.getElementById('appointment-date-input').value;
        const roomId = document.getElementById('room-id-input').value;

        if (!date || !roomId) {
            debugLog('Date or Room ID missing for fetching available slots. Skipping API call.');
            return;
        }

        debugLog('Fetching available slots for Date:', date, 'Room ID:', roomId);
        try {
            const response = await apiRequest('appointments', 'get_available_slots', { date, room_id: roomId });
            debugLog('API Response - Available Slots:', response);

            if (response.success && response.booked_slots) {
                // Here you would update the UI to show available/booked slots
                // For now, just logging the booked slots
                debugLog('Booked slots:', response.booked_slots);
                // Example: Visually mark booked slots
                document.querySelectorAll('.time-slot-btn').forEach(btn => {
                    const slotStart = btn.dataset.start;
                    const slotEnd = btn.dataset.end;
                    const isBooked = response.booked_slots.some(booked =>
                        (slotStart >= booked.start_time && slotStart < booked.end_time) ||
                        (slotEnd > booked.start_time && slotEnd <= booked.end_time) ||
                        (booked.start_time >= slotStart && booked.start_time < slotEnd)
                    );

                    if (isBooked) {
                        btn.classList.add('btn-outline-secondary');
                        btn.classList.remove('btn-outline-primary');
                        btn.disabled = true;
                    } else {
                        btn.classList.remove('btn-outline-secondary');
                        btn.classList.add('btn-outline-primary');
                        btn.disabled = false;
                    }
                });
            } else {
                debugLog('Failed to fetch available slots:', response.error || 'Unknown error');
            }
        } catch (error) {
            debugLog('Error fetching available slots:', error);
        }
    }


    // ---- Populate dropdowns ----
    function populateRoomSelect() {
        const select = document.getElementById('room-id-input');
        select.innerHTML = '<option value="" disabled selected hidden>Select Room</option>';
        rooms.forEach(r => {
            const opt = new Option(r.name, r.id);
            select.appendChild(opt);
        });
    }

    function populatePatientSelect() {
        const select = document.getElementById('patient-id');
        if ($(select).data('select2')) $(select).select2('destroy');
        select.innerHTML = '<option value="">Select Patient</option>';
        patients.forEach(p => select.add(new Option(p.name, p.id)));
        $(select).select2({ dropdownParent: $('body') });
    }

    function populateProcedureSelect() {
        const select = document.getElementById('procedure-id');
        if ($(select).data('select2')) $(select).select2('destroy');
        select.innerHTML = '<option value="">Select Procedure</option>';
        procedures.forEach(p => select.add(new Option(p.name, p.id)));
        $(select).select2({ dropdownParent: $('body') });

        // Event listener is now attached once in DOMContentLoaded
        $(select).select2({ dropdownParent: $('body') });

        // Attach the 'change' event listener, which Select2 triggers for both manual and programmatic changes.
        // The toggle is now handled by the main buttons, not the dropdown.
    }

    // ---- Submit ----
    function onFormSubmit(e) {
        const payload = {
            id: appointmentId,
            patient_id: document.getElementById('patient-id').value,
            room_id: document.getElementById('room-id-input').value,
            appointment_date: document.getElementById('appointment-date-input').value,
            start_time: document.getElementById('start-time').value,
            end_time: document.getElementById('end-time').value,
            procedure_id: document.getElementById('procedure-id').value,
            notes: document.getElementById('notes').value,
            consultation_type: document.querySelector('input[name="consultation_type"]:checked')?.value,
            appointment_type: document.querySelector('.btn-group button.active')?.id === 'btn-consultation' ? 'consultation' : 'treatment'
        };
        apiRequest('appointments', 'update', payload)
            .then(res => {
                if (res.success) {
                    alert('Appointment updated successfully!');
                    window.location = `/patient/patient_details.php?id=${payload.patient_id}&tab=appointments`;
                } else {
                    alert('Failed to update: ' + (res.error || 'Unknown error'));
                }
            })
            .catch(err => {
                alert('Error submitting: ' + err);
            });
    }


</script>