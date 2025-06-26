// public/appointment/page.js

document.addEventListener('DOMContentLoaded', function() {

    // —————————————————————————————————————————————
    // Progressive-enhancement wrappers for Select2 & Datepicker
    // —————————————————————————————————————————————
    async function initSelect2($el, opts = {}) {
        return $el.select2(opts);
    }

    async function initDatepicker(container, options = {}) {
        /* global Datepicker */
        return new Datepicker(container, options);
    }

    // —————————————————————————————————————————————
    // State & DOM cache
    // —————————————————————————————————————————————
    const appointmentData = {
        patient_id:        null,
        appointment_type:  null,
        consultation_type: null,
        procedure_id:      null,
        room_id:           null,
        appointment_date:  null,
        start_time:        null,
        end_time:          null,
        notes:             ''
    };

    let currentStep = 0;
    let stepConfig  = [];

    const pills = {
        patient:  document.getElementById('pills-patient-tab'),
        purpose:  document.getElementById('pills-purpose-tab'),
        service:  document.getElementById('pills-service-tab'),
        datetime: document.getElementById('pills-datetime-tab'),
        notes:    document.getElementById('pills-notes-tab')
    };

    const panes = {
        patient:  document.getElementById('pills-patient'),
        purpose:  document.getElementById('pills-purpose'),
        service:  document.getElementById('pills-service'),
        datetime: document.getElementById('pills-datetime'),
        notes:    document.getElementById('pills-notes')
    };

    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    const btnSave = document.getElementById('btn-save');

    // —————————————————————————————————————————————
    // Block clicking ahead: only allow pill idx ≤ currentStep
    // —————————————————————————————————————————————
    Object.entries(pills).forEach(([key, pill]) => {
        pill.addEventListener('click', e => {
            e.preventDefault();
            const idx = stepConfig.findIndex(s => s.key === key);
            if (idx !== -1 && idx <= currentStep) {
                currentStep = idx;
                updateWizardState();
            }
        });
    });

    // —————————————————————————————————————————————
    // Build & render wizard based on visit_purpose
    // —————————————————————————————————————————————
    function updateWizardState() {
        // 1) Rebuild stepConfig
        const p = appointmentData.appointment_type;
        if (p === 'consultation') {
            stepConfig = [
                { key: 'patient',  title: 'Step 1: Patient' },
                { key: 'purpose',  title: 'Step 2: Purpose' },
                { key: 'service',  title: 'Step 3: Consultation Type' },
                { key: 'datetime', title: 'Step 4: Date & Time' },
                { key: 'notes',    title: 'Step 5: Notes & Save' }
            ];
        } else if (p === 'treatment') {
            stepConfig = [
                { key: 'patient',  title: 'Step 1: Patient' },
                { key: 'purpose',  title: 'Step 2: Purpose' },
                { key: 'service',  title: 'Step 3: Procedure & Room' },
                { key: 'datetime', title: 'Step 4: Date & Time' },
                { key: 'notes',    title: 'Step 5: Notes & Save' }
            ];
        } else {
            stepConfig = [
                { key: 'patient',  title: 'Step 1: Patient' },
                { key: 'purpose',  title: 'Step 2: Purpose' }
            ];
        }

        // 2) Show/hide & label pills; disable future ones
        Object.values(pills).forEach(p => p.parentElement.classList.add('d-none'));
        stepConfig.forEach((step, idx) => {
            const pill = pills[step.key];
            // Only show the current and next pill
            if (idx === currentStep || idx === currentStep + 1) {
                pill.parentElement.classList.remove('d-none');
            }
            pill.textContent = step.title;
            pill.disabled = idx > currentStep;           // block clicks
            pill.classList.toggle('active', idx === currentStep);
            pill.setAttribute('aria-selected', idx === currentStep);
            pill.classList.toggle('disabled', idx > currentStep);
        });

        // 3) Show only the active pane
        Object.values(panes).forEach(p => p.classList.remove('show','active'));
        panes[stepConfig[currentStep].key].classList.add('show','active');

        // 4) If we're on the service step, render its controls
        if (stepConfig[currentStep].key === 'service') {
            if (appointmentData.appointment_type === 'consultation') {
                renderConsultationStep();
            } else if (appointmentData.appointment_type === 'treatment') {
                renderTreatmentStep();
            }
        }

        // 5) Prev/Next/Save buttons
        btnPrev.classList.toggle('d-none', currentStep === 0);
        const isFinalStep = stepConfig[currentStep].key === 'notes';
        btnNext.classList.toggle('d-none', isFinalStep);
        btnSave.classList.toggle('d-none', !isFinalStep);
    }

    // —————————————————————————————————————————————
    // Navigation helpers
    // —————————————————————————————————————————————
    function nextStep() {
        console.log('form data: ', appointmentData)
        currentStep = Math.min(currentStep + 1, stepConfig.length - 1);
        updateWizardState();
    }
    function prevStep() {
        currentStep = Math.max(currentStep - 1, 0);
        updateWizardState();
    }

    // —————————————————————————————————————————————
    // Step 1: Fetch patients (reset downstream on change)
    // —————————————————————————————————————————————
    async function fetchPatients(selectId = null) {
        try {
            const response = await apiRequest('patients', 'list', {});
            if (response.success) {
                const $sel = $('#patient-select');
                // Unbind previous handler to prevent duplicates
                $sel.off('change');

                $sel.empty().append('<option value="">Select Patient</option>');
                response.patients.forEach(p => $sel.append(new Option(p.name, p.id)));
                
                initSelect2($sel, {
                    placeholder: 'Select a patient',
                    dropdownParent: $('#pills-patient')
                });

                // Re-bind the change handler
                $sel.on('change', e => {
                    // reset everything but patient_id
                    appointmentData.appointment_type = null;
                    appointmentData.consultation_type = null;
                    appointmentData.procedure_id = null;
                    appointmentData.room_id = null;
                    appointmentData.start_time = null;
                    appointmentData.end_time = null;
                    appointmentData.notes = '';
                    currentStep = 0;
                    updateWizardState();

                    // now set new patient and advance
                    appointmentData.patient_id = e.target.value;
                    if (e.target.value) nextStep();
                });

                // If an ID is provided, set the value and trigger the change event
                if (selectId) {
                    $sel.val(selectId).trigger('change');
                }
            }
        } catch (err) {
            console.error('Failed to fetch patients', err);
        }
    }

    // —————————————————————————————————————————————
    // Step 2: Purpose selection (one-click)
    // —————————————————————————————————————————————
    document.getElementById('btn-consultation')
            .addEventListener('click', () => handlePurposeSelection('Consultation'));
    document.getElementById('btn-treatment')
            .addEventListener('click', () => handlePurposeSelection('Treatment'));

    function handlePurposeSelection(purpose) {
        appointmentData.appointment_type = purpose.toLowerCase();
        appointmentData.consultation_type = null;
        if (appointmentData.appointment_type === 'consultation') {
            appointmentData.procedure_id = 1;
            appointmentData.room_id = 1;
        } else {
            appointmentData.procedure_id = null;
            appointmentData.room_id = null;
        }
        
        currentStep = 2; // Directly advance to the service step
        updateWizardState();
        // Trigger availability check after purpose and room_id are set
        if (appointmentData.appointment_date && appointmentData.room_id) {
            checkAvailability();
        }
    }

    // —————————————————————————————————————————————
    // Render service step for Consultation
    // —————————————————————————————————————————————
    function renderConsultationStep() {
        panes.service.innerHTML = `
            <fieldset class="mb-3 text-center">
                <legend>Select Consultation Type</legend>
                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                    <button id="btn-face-to-face" class="btn btn-primary">Face-to-Face</button>
                    <button id="btn-video"          class="btn btn-outline-primary">Video Consultation</button>
                </div>
            </fieldset>`;
        document.getElementById('btn-face-to-face')
                .addEventListener('click', () => {
                    appointmentData.consultation_type = 'face-to-face';
                    nextStep();
                });
        document.getElementById('btn-video')
                .addEventListener('click', () => {
                    appointmentData.consultation_type = 'video-to-video';
                    nextStep();
                });
    }

    // —————————————————————————————————————————————
    // Render service step for Treatment
    // —————————————————————————————————————————————
    async function renderTreatmentStep() {
        panes.service.innerHTML = `
            <fieldset class="mb-3 text-center">
                <legend>Select Procedure &amp; Room</legend>
                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                    <select id="procedure-select" class="form-control w-100 mb-3"></select>
                    <select id="room-select" class="form-control w-100"></select>
                </div>
            </fieldset>`;
        await fetchProcedures();
        await fetchRoomsForSelect();

        // Set previously selected values if they exist
        if (appointmentData.procedure_id) {
            $('#procedure-select').val(appointmentData.procedure_id).trigger('change');
        }
        if (appointmentData.room_id) {
            $('#room-select').val(appointmentData.room_id).trigger('change');
        }

        $('#procedure-select').on('change', () => {
            appointmentData.procedure_id = $('#procedure-select').val();
            if (appointmentData.procedure_id && appointmentData.room_id) nextStep();
        });
        $('#room-select').on('change', () => {
            appointmentData.room_id = $('#room-select').val();
            checkAvailability(); // Call checkAvailability when room is selected
            if (appointmentData.procedure_id && appointmentData.room_id) nextStep();
        });
    }

    // —————————————————————————————————————————————
    // Step 4: Datepicker + URL-param + availability
    // —————————————————————————————————————————————
    function initializeDatepicker() {
        const dpContainer = document.getElementById('datepicker-container');
        const hiddenInput = document.getElementById('appointment-date');
        const slotsCont   = document.getElementById('time-slots-container');

        initDatepicker(dpContainer, {
            format:   'yyyy-mm-dd',
            autohide: true
        }).then(dp => {
            const dateParam = new URLSearchParams(window.location.search).get('date');
            if (dateParam) {
                dp.setDate(new Date(dateParam));
                appointmentData.appointment_date = dateParam;
                hiddenInput.value = dateParam;
                slotsCont.classList.remove('d-none');
                checkAvailability();
            }
            dpContainer.addEventListener('changeDate', e => {
                const sel = Datepicker.formatDate(e.detail.date, 'yyyy-mm-dd');
                appointmentData.appointment_date = sel;
                hiddenInput.value = sel;
                slotsCont.classList.remove('d-none');
                checkAvailability();
            });
        });
    }

    async function checkAvailability() {
        if (!appointmentData.appointment_date || !appointmentData.room_id) return;
        try {
            const response = await apiRequest('appointments','get_available_slots',{
                date:    appointmentData.appointment_date,
                room_id: appointmentData.room_id
            });
            document.querySelectorAll('.time-slot-btn').forEach(b => {
                b.disabled = false;
                b.classList.remove('btn-success');
                b.classList.remove('btn-outline-secondary');
                b.classList.add('btn-outline-primary');
            });
            if (response.success && response.booked_slots) {
                response.booked_slots.forEach(b => {
                    const start = b.start_time.substring(0,5);
                    const btn   = document.querySelector(`.time-slot-btn[data-start="${start}"]`);
                    if (btn) btn.disabled = true;
                    if (btn) btn.classList.remove('btn-outline-primary');
                    if (btn) btn.classList.add('btn-outline-secondary');
                });
            }
        } catch (err) {
            console.error('Failed to check availability', err);
        }
    }

    // —————————————————————————————————————————————
    // Time-slot selection
    // —————————————————————————————————————————————
    document.querySelectorAll('.time-slot-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            document.querySelectorAll('.time-slot-btn').forEach(b => {
                b.classList.remove('btn-success');
                b.classList.add('btn-outline-primary');
            });
            e.currentTarget.classList.remove('btn-outline-primary');
            e.currentTarget.classList.add('btn-success');
            appointmentData.start_time = e.currentTarget.dataset.start;
            appointmentData.end_time   = e.currentTarget.dataset.end;
            nextStep();
        });
    });

    // —————————————————————————————————————————————
    // Data fetchers (unchanged API calls)
    // —————————————————————————————————————————————
    async function fetchProcedures() {
        try {
            const response = await apiRequest('procedures','active',{});
            if (response.success) {
                const $sel = $('#procedure-select');
                $sel.empty().append('<option value="">Select Procedure</option>');
                response.procedures.forEach(p => $sel.append(new Option(p.name,p.id)));
                initSelect2($sel, {
                    placeholder:    'Select a procedure',
                    dropdownParent: $('#pills-service')
                });
            }
        } catch (err) {
            console.error('Failed to fetch procedures', err);
        }
    }

    async function fetchRoomsForSelect() {
        try {
            const response = await apiRequest('rooms','list',{});
            if (response.success) {
                const $sel = $('#room-select');
                $sel.empty().append('<option value="">Select Room</option>');
                response.rooms.filter(r => r.is_active).forEach(r => $sel.append(new Option(r.name,r.id)));
                initSelect2($sel, {
                    placeholder:    'Select a room',
                    dropdownParent: $('#pills-service')
                });
            }
        } catch (err) {
            console.error('Failed to fetch rooms', err);
        }
    }

    // —————————————————————————————————————————————
    // Save appointment (unchanged API call)
    // —————————————————————————————————————————————
    btnSave.addEventListener('click', async function() {
        console.log('form data: ', appointmentData)
        appointmentData.notes = document.getElementById('notes').value;
        if (!appointmentData.patient_id ||
            !appointmentData.appointment_date ||
            !appointmentData.start_time   ||
            !appointmentData.room_id       ||
            !appointmentData.procedure_id) {
            alert('Please ensure all steps are completed correctly.');
            return;
        }
        try {
            btnSave.disabled = true;
            btnSave.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

            const response = await apiRequest('appointments','create', appointmentData);
            if (response.success) {
                showToast(`Appointment updated.`, 'success');
                btnSave.disabled = false;
                setTimeout(() => {
                    window.location.href = '/calendar/calendar.php';
                }, 1000);
            } else {
                alert('Failed to save appointment: ' + (response.error||'Unknown error'));
                btnSave.disabled = false;
                btnSave.textContent = 'Save Appointment';
            }
        } catch (err) {
            console.error('Failed to save appointment', err);
            alert('An error occurred while saving the appointment.');
            btnSave.disabled = false;
            btnSave.textContent = 'Save Appointment';
        }
    });

    // —————————————————————————————————————————————
    // Wire up Prev/Next & initialize
    // —————————————————————————————————————————————
    btnPrev.addEventListener('click', prevStep);
    btnNext.addEventListener('click', nextStep);
    document.getElementById('notes')
            .addEventListener('input', e => appointmentData.notes = e.target.value);

    // —————————————————————————————————————————————
    // New Patient Modal Functions
    // —————————————————————————————————————————————

    const newPatientModal = new bootstrap.Modal(document.getElementById('newPatientModal'));
    const newPatientForm = document.getElementById('new-patient-form');
    const saveNewPatientBtn = document.getElementById('save-new-patient');
    const newPatientStatusDiv = document.getElementById('new-patient-status');

    // Validation rules for the new patient form
    function getNewPatientValidationRules() {
        const rules = [
            { id: 'new_patient_name', msg: 'Patient Name is required.' }
        ];
        // Only add agency validation if the field is visible (i.e., not an agent)
        const agencyField = document.getElementById('new_patient_agency_id');
        if (agencyField && agencyField.type !== 'hidden') {
            rules.push({ id: 'new_patient_agency_id', msg: 'Agency is required.' });
        }
        return rules;
    }

    // Validate a single field in the new patient form
    function validateNewPatientSingleField(id) {
        const field = document.getElementById(id);
        if (!field) return true; // Field might not exist (e.g., agency for agent)

        const rule = getNewPatientValidationRules().find(r => r.id === id);
        if (!rule) return true; // No rule for this field

        const isValid = field.value.trim() !== '';
        field.classList.toggle('is-invalid', !isValid);
        const feedbackDiv = field.nextElementSibling; // Assuming invalid-feedback is next sibling
        if (feedbackDiv && feedbackDiv.classList.contains('invalid-feedback')) {
            feedbackDiv.textContent = isValid ? '' : rule.msg;
        }
        return isValid;
    }

    // Validate the entire new patient form
    function validateNewPatientForm(showUIErrors = true) {
        let isFormValid = true;
        getNewPatientValidationRules().forEach(rule => {
            const field = document.getElementById(rule.id);
            if (field && field.type !== 'hidden') { // Only validate visible fields
                const isValid = field.value.trim() !== '';
                if (!isValid) {
                    isFormValid = false;
                    if (showUIErrors) {
                        field.classList.add('is-invalid');
                        const feedbackDiv = field.nextElementSibling;
                        if (feedbackDiv && feedbackDiv.classList.contains('invalid-feedback')) {
                            feedbackDiv.textContent = rule.msg;
                        }
                    }
                } else {
                    field.classList.remove('is-invalid');
                    const feedbackDiv = field.nextElementSibling;
                    if (feedbackDiv && feedbackDiv.classList.contains('invalid-feedback')) {
                        feedbackDiv.textContent = '';
                    }
                }
            }
        });
        return isFormValid;
    }

    // Update the state of the save new patient button
    function updateSaveNewPatientButtonState() {
        saveNewPatientBtn.disabled = !validateNewPatientForm(false); // Don't show UI errors
    }

    // Event listeners for new patient form fields
    newPatientForm.querySelectorAll('input, select').forEach(field => {
        if (field.type !== 'hidden') {
            field.addEventListener('blur', () => {
                validateNewPatientSingleField(field.id);
                updateSaveNewPatientButtonState();
            });
            field.addEventListener('input', () => {
                validateNewPatientSingleField(field.id);
                updateSaveNewPatientButtonState();
            });
            field.addEventListener('change', () => { // For select elements
                validateNewPatientSingleField(field.id);
                updateSaveNewPatientButtonState();
            });
        }
    });

    // Fetch agencies for the new patient modal
    async function fetchAgencies() {
        try {
            const response = await apiRequest('agencies', 'list', {});
            if (response.success) {
                const $sel = $('#new_patient_agency_id');
                $sel.empty().append('<option value="">Select Agency</option>');
                response.agencies.forEach(a => $sel.append(new Option(a.name, a.id)));
                // If it's a select2, initialize it
                if ($sel.hasClass('select2-enable')) {
                    initSelect2($sel, {
                        placeholder: 'Select an agency',
                        dropdownParent: $('#newPatientModal')
                    });
                }
            } else {
                console.error('Failed to fetch agencies:', response.error);
            }
        } catch (err) {
            console.error('Error fetching agencies:', err);
        }
    }

    // Save new patient function
    saveNewPatientBtn.addEventListener('click', async function() {
        if (!validateNewPatientForm(true)) { // Show UI errors on save attempt
            newPatientStatusDiv.innerHTML = '<div class="alert alert-danger">Please correct the errors in the form.</div>';
            return;
        }

        const patientData = {
            agency_id: document.getElementById('new_patient_agency_id').value,
            name:      document.getElementById('new_patient_name').value,
            dob:       document.getElementById('new_patient_dob').value
        };

        // Clear status div
        newPatientStatusDiv.innerHTML = '';
        saveNewPatientBtn.disabled = true;
        saveNewPatientBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...';

        try {
            const response = await apiRequest('patients', 'add', patientData);
            if (response.success) {
                
                showToast('Patient created successfully!', 'success');
             
                let patientSelect;
                patientSelect = document.getElementById('patient-select');
                if (patientSelect) {
                    const newOption = new Option(response.patient.name, response.patient.id, true,
                        true);
                    patientSelect.add(newOption);
                }
                appointmentData.patient_id=response.patient.id;
                nextStep();
                setTimeout(() => {
                    newPatientModal.hide();
                    newPatientForm.reset();
                    newPatientStatusDiv.innerHTML = '';
                    // Clear validation feedback
                    newPatientForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                    newPatientForm.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
                }, 1000);
            } else {
                newPatientStatusDiv.innerHTML = `<div class="alert alert-danger">Failed to create patient: ${response.error || 'Unknown error'}</div>`;
            }
        } catch (err) {
            console.error('Error saving new patient:', err);
            newPatientStatusDiv.innerHTML = '<div class="alert alert-danger">An error occurred while creating the patient.</div>';
        } finally {
            saveNewPatientBtn.disabled = false;
            saveNewPatientBtn.innerHTML = '<i class="far fa-save me-1"></i>Create Patient';
        }
    });

    // When the new patient modal is shown, fetch agencies and reset form
    document.getElementById('newPatientModal').addEventListener('show.bs.modal', () => {
        newPatientForm.reset();
        newPatientStatusDiv.innerHTML = '';
        fetchAgencies();
        // Clear validation feedback
        newPatientForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        newPatientForm.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        updateSaveNewPatientButtonState(); // Disable button initially
    });

    // Kick everything off
    fetchPatients();
    initializeDatepicker();
    updateWizardState();

});
