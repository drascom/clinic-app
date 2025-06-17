<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Only allow agents to access this page
if (!is_agent()) {
    header('Location: /surgery/add_edit_surgery.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit();
}

$errors = [];

// Get URL parameters
$room_id_from_url = $_GET['room_id'] ?? null;
$date_from_url = $_GET['date'] ?? null;

$page_title = 'Quick Add Surgery';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Status Messages -->

<div class="container emp">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>
                        Quick Add Surgery
                    </h4>
                    <a href="/calendar.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Calendar
                    </a>
                </div>
                <div class="card-body">
                    <form id="quick-surgery-form" method="POST">
                        <!-- Hidden fields -->
                        <input type="hidden" name="user_id" value="<?php echo get_user_id(); ?>">
                        <input type="hidden" name="agency_id" value="<?php echo get_user_agency_id(); ?>">
                        <input type="hidden" name="created_by" value="<?php echo get_user_id(); ?>">
                        <input type="hidden" name="status" value="scheduled">

                        <!-- Date and Room Display -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Surgery Date</label>
                                <div class="form-control-plaintext border rounded p-2 bg-light">
                                    <i class="fas fa-calendar me-2"></i>
                                    <span id="date-display"></span>
                                </div>
                                <input type="hidden" name="date" id="date"
                                    value="<?php echo htmlspecialchars($date_from_url ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Room</label>
                                <div class="form-control-plaintext border rounded p-2 bg-light">
                                    <i class="fas fa-door-open me-2"></i>
                                    <span id="room-display"></span>
                                </div>
                                <input type="hidden" name="room_id" id="room_id"
                                    value="<?php echo htmlspecialchars($room_id_from_url ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Patient Selection -->
                        <div class="mb-4">
                            <div class="mb-3">
                                <label for="patient_id" class="form-label">Patient *</label>
                                <div class="input-group">
                                    <select class="form-select" id="patient_id" name="patient_id" required>
                                        <option value="">Select Patient</option>
                                        <!-- Patient options will be loaded via JavaScript -->
                                    </select>
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                        data-bs-target="#newPatientModal">
                                        <i class="fas fa-plus me-1"></i>
                                        <span class="d-none d-sm-inline">New Patient</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Note about technicians -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Quick Add:</strong> This creates a basic surgery booking with "reserved" status.
                            Technicians must be assigned later to confirm the surgery.
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="/calendar.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Create Surgery
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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
                <form id="new-patient-form">
                    <!-- Hidden field for agents - their agency_id will be set via JavaScript -->
                    <input type="hidden" id="new_patient_agency_id" name="agency_id"
                        value="<?php echo get_user_agency_id(); ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="new_patient_name" class="form-label">
                                    <i class="fas fa-user me-1"></i>
                                    Patient Name *
                                </label>
                                <input type="text" class="form-control" id="new_patient_name" name="name"
                                    placeholder="Enter patient name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="new_patient_dob" class="form-label">
                                    <i class="fas fa-calendar me-1"></i>
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
                <button type="button" class="btn btn-primary" id="save-new-patient">
                    <i class="fas fa-save me-1"></i>Create Patient
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Helper function to get cookie value
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }

        const quickSurgeryForm = document.getElementById('quick-surgery-form');
        const patientSelect = document.getElementById('patient_id');
        const roomSelect = document.getElementById('room_id');
        const dateInput = document.getElementById('date');
        const dateDisplay = document.getElementById('date-display');
        const roomDisplay = document.getElementById('room-display');

        const newPatientModal = document.getElementById('newPatientModal');
        const saveNewPatientButton = document.getElementById('save-new-patient');
        const newPatientForm = document.getElementById('new-patient-form');
        const newPatientStatusDiv = document.getElementById('new-patient-status');


        // Format date for display
        function formatDateForDisplay(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-GB', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        // Load initial data
        async function loadInitialData() {
            try {
                // Load patients - filter by agency for agents
                let patientsData;

                // Check if user is an agent and get agency_id from cookie
                const userRole = getCookie('user_role');
                const agencyId = getCookie('agency_id');

                if (userRole === 'agent' && agencyId) {
                    // Filter patients by agency for agents
                    patientsData = await apiRequest('patients', 'list', {
                        agency: agencyId
                    });
                } else {
                    // Load all patients for admin/editor users
                    patientsData = await apiRequest('patients', 'list');
                }

                if (patientsData.success && patientsData.patients) {
                    patientSelect.innerHTML = '<option value="">Select Patient</option>';
                    patientsData.patients.forEach(patient => {
                        const option = document.createElement('option');
                        option.value = patient.id;
                        option.textContent = patient.name;
                        patientSelect.appendChild(option);
                    });
                }

                // Load room name if room_id is provided
                if (roomSelect.value) {
                    const roomsData = await apiRequest('rooms', 'list');

                    if (roomsData.success && roomsData.rooms) {
                        const room = roomsData.rooms.find(r => r.id == roomSelect.value);
                        if (room) {
                            roomDisplay.textContent = room.name;
                        }
                    }
                }

                // Display formatted date
                if (dateInput.value) {
                    dateDisplay.textContent = formatDateForDisplay(dateInput.value);
                }

            } catch (error) {
                console.error('Error loading initial data:', error);
                showToast('Error loading form data.', 'danger');
            }
        }

        // Load initial data
        loadInitialData();

        // Handle form submission
        quickSurgeryForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(quickSurgeryForm);
            formData.append('entity', 'surgeries');
            formData.append('action', 'add');
            formData.append('quick_add', 'true'); // Flag to allow surgery without technicians

            statusMessagesDiv.innerHTML = ''; // Clear previous status

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
                            window.location.href = '/calendar.php';
                        }, 1000);
                    } else {
                        showToast(`Error: ${data.error || data.message}`, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error submitting surgery form:', error);
                    showToast('An error occurred while saving surgery data.', 'danger');
                });
        });

        // Handle new patient modal submission
        if (saveNewPatientButton) {
            saveNewPatientButton.addEventListener('click', function () {
                const formData = new FormData(newPatientForm);
                formData.append('entity', 'patients');
                formData.append('action', 'add');

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
                                const newOption = new Option(data.patient.name, data.patient.id, true, true);
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

        // Reset modal form when hidden
        if (newPatientModal) {
            newPatientModal.addEventListener('hidden.bs.modal', function () {
                newPatientForm.reset();
                newPatientStatusDiv.innerHTML = '';
            });
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>