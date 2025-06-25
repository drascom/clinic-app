<?php
require_once '../includes/header.php';
$page_title = "Patients";

?>

<div class="container emp-10">
    <!-- Loading Spinner -->
    <div id="loading-spinner" class="text-center py-4" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center p-2">
                <h4 class="mb-0">
                    <i class="far fa-calendar-check me-2 text-primary"></i>
                    Patients
                </h4>
                <div class="btn-group" role="group">
                    <a href="add_edit_patient.php" class="btn  btn-outline-success">
                        <i class="fas fa-plus me-1"></i>
                        <span class="d-none d-sm-inline">Add Patient</span>
                        <span class="d-inline d-sm-none">Add</span>
                    </a>
                    <a href="/calendar/calendar.php" class="btn  btn-outline-primary">
                        <i class="far fa-calendar me-1"></i>
                        Calendar
                    </a>
                </div>
            </div>
            <!-- Search Bar -->
            <fieldset class="p-4 frosted">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="search-input"
                        placeholder="Search patients by name, date of birth, or agency...">
                    <button class="btn btn-outline-secondary" type="button" id="clear-search" title="Clear search">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="text-muted small ms-4">
                    <i class="fas fa-info-circle me-1"></i>
                    <span id="patient-count">Loading...</span> patients found
                </div>
            </fieldset>
        </div>
        <div class="card-body">
            <!-- Patients Table -->
            <div class="table-responsive">
                <table class="table table-hover table-sm" id="patients-table">
                    <thead class="table-light">
                        <tr>
                            <th>Avatar</th>
                            <th>Name</th>
                            <th>Agency</th>
                            <th>Date of Birth</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Patient rows will be loaded here via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const patientsTable = document.getElementById('patients-table');


        // Function to fetch and display patients
        function fetchAndDisplayPatients() {
            // Show loading spinner
            document.getElementById('loading-spinner').style.display = 'flex';
            patientsTable.style.display = 'none';

            const userRole = '<?php echo get_user_role(); ?>';
            const userAgencyId = '<?php echo get_user_agency_id(); ?>';

            // Build API request data with agency filter for agents
            let requestData = {};
            if (userRole === 'agent' && userAgencyId) {
                requestData.agency = userAgencyId;
            }

            apiRequest('patients', 'list', requestData)
                .then(data => {
                    // Hide loading spinner
                    document.getElementById('loading-spinner').style.display = 'none';
                    patientsTable.style.display = 'table';

                    if (data.success) {
                        const patients = data.patients;
                        let tableRows = '';

                        patients.forEach(patient => {
                            const avatarSrc = patient.avatar ?
                                `${patient.avatar}` :
                                `../assets/avatar.png`;
                            const avatarHtml = `<img src="${avatarSrc}" alt="Avatar" class="avatar">`;

                            // Build agency column for admin/editor users
                            const agencyColumnHtml =
                                `<td><span class="text-truncate-mobile badge bg-secondary">${patient.agency_name || 'No Agency'}</span></td>`;

                            tableRows += `
                            <tr data-patient-id="${patient.id}"
                                data-patient-name="${patient.name}"
                                data-patient-dob="${patient.dob}"
                                data-patient-agency="${patient.agency_name || ''}"
                                data-patient-email="${patient.email || ''}"
                                data-patient-phone="${patient.phone || ''}">
                                <td>
                                    ${avatarHtml}
                                </td>
                                <td>
                                    <a href="patient_details.php?id=${patient.id}&tab=surgeries" class="fw-medium text-decoration-none">
                                        ${patient.name}
                                    </a>
                                </td>
                                ${agencyColumnHtml}
                                <td>
                                    <span class="text-truncate-mobile">${patient.dob || 'N/A'}</span>
                                </td>
                                <td>
                                    <span class="text-truncate-mobile">${patient.phone || 'N/A'}</span>
                                </td>
                                <td>
                                    <span class="text-truncate-mobile">${patient.email || 'N/A'}</span>
                                </td>
                               
                                <td>
                                    <div class="btn-group" role="group" aria-label="Patient Actions">
                                        <a href="add_edit_patient.php?id=${patient.id}"
                                           class="btn btn-sm btn-text text-primary"
                                           title="Edit Patient">
                                            <i class="fas fa-edit"></i>
                                            <span class="d-none d-lg-inline ms-1">Edit</span>
                                        </a>
                                        <?php if (is_admin()): ?>
                                        <button class="btn btn-sm btn-text text-danger delete-patient-btn"
                                                data-patient-id="${patient.id}"
                                                title="Delete Patient">
                                            <i class="fas fa-trash-alt"></i>
                                            <span class="d-none d-lg-inline ms-1">Delete</span>
                                        </button>
                                        <?php endif; ?>
                                        <a href="patient_details.php?id=${patient.id}&tab=images"
                                           class="btn btn-sm btn-text text-info"
                                           title="View Photos">
                                            <i class="fas fa-camera"></i>
                                            <span class="d-none d-lg-inline ms-1">Photos</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        `;
                        });

                        patientsTable.querySelector('tbody').innerHTML = tableRows;

                        // Update patient count
                        document.getElementById('patient-count').textContent = ' ' + patients.length + ' ';
                    } else {
                        showToast(`Error loading patients: ${data.error}`, 'danger');
                        document.getElementById('patient-count').textContent = '0';
                    }
                })
                .catch(error => {
                    // Hide loading spinner
                    document.getElementById('loading-spinner').style.display = 'none';
                    patientsTable.style.display = 'table';

                    console.error('Error fetching patients:', error);
                    showToast('An error occurred while loading patient data.', 'danger');
                    document.getElementById('patient-count').textContent = '0';
                });
        }

        // Delete patient function
        patientsTable.addEventListener('click', function(event) {
            if (event.target.classList.contains('delete-patient-btn')) {
                const patientId = event.target.dataset.patientId;
                if (confirm('Are you sure you want to delete this patient?')) {
                    const formData = new FormData();
                    formData.append('entity', 'patients');
                    formData.append('action', 'delete');
                    formData.append('id', patientId);

                    fetch('/api.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showToast(data.message, 'success');
                                fetchAndDisplayPatients(); // Refresh the patient list
                            } else {
                                showToast(`Error deleting patient: ${data.error}`, 'danger');
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting patient:', error);
                            showToast('An error occurred while deleting the patient.', 'danger');
                        });
                }
            }
        });

        fetchAndDisplayPatients(); // Initial load of patients

        // Search functionality
        const searchInput = document.getElementById('search-input');
        const clearSearchBtn = document.getElementById('clear-search');

        function filterPatients() {
            const searchTerm = searchInput.value.toLowerCase();
            const rows = patientsTable.querySelectorAll('tbody tr');

            rows.forEach(row => {
                console.log('row: ', row.dataset);
                const name = row.dataset.patientName.toLowerCase();
                const dob = row.dataset.patientDob.toLowerCase();
                const agency = row.dataset.patientAgency.toLowerCase();
                const email = row.dataset.patientName.toLowerCase();
                const phone = row.dataset.patientPhone.toLowerCase();

                if (name.includes(searchTerm) || dob.includes(searchTerm) || phone.includes(searchTerm) ||
                    email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }


        searchInput.addEventListener('keyup', function(event) {
            const searchTerm = searchInput.value;
            if (searchTerm.length >= 2 || searchTerm.length === 0) {
                filterPatients();
            }
        });
        if (searchInput && clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                fetchAndDisplayPatients();
            });
        }
        // The existing modal and surgeries list logic can remain mostly unchanged,
        // but might need adjustments to use the new API for fetching surgery data.
        const surgeriesModal = document.getElementById('surgeriesModal');
        if (surgeriesModal) {
            surgeriesModal.addEventListener('show.bs.modal', function(event) {
                const link = event.relatedTarget; // Button or link that triggered the modal
                const patientId = link.getAttribute('data-patient-id');
                const surgeriesListDiv = document.getElementById('surgeries-list');

                // Clear previous content and show loading message
                surgeriesListDiv.innerHTML = 'Loading surgeries...';

                // Fetch surgeries via AJAX
                apiRequest('surgeries', 'list', {
                        patient_id: patientId
                    })
                    .then(data => {
                        surgeriesListDiv.innerHTML = ''; // Clear loading message

                        if (data.surgeries) {
                            // Build list of surgeries
                            let surgeryHtml = '<ul class="list-group">';
                            data.surgeries.forEach(surgery => {
                                surgeryHtml += `<li class="list-group-item">`;
                                surgeryHtml += `<strong>Date:</strong> ${surgery.date}<br>`;
                                surgeryHtml += `<strong>Status:</strong> ${surgery.status}<br>`;
                                if (surgery.notes) {
                                    surgeryHtml += `<strong>Notes:</strong> ${surgery.notes}`;
                                }
                                surgeryHtml += `</li>`;
                            });
                            surgeryHtml += '</ul>';

                            surgeriesListDiv.innerHTML = surgeryHtml;
                        } else if (data.error) {
                            surgeriesListDiv.innerHTML =
                                `<div class="alert alert-danger">${data.error}</div>`;
                            return;
                        }

                        if (data.surgeries.length === 0) {
                            surgeriesListDiv.innerHTML = '<p>No surgeries found for this patient.</p>';
                            return;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching surgeries:', error);
                        surgeriesListDiv.innerHTML =
                            '<div class="alert alert-danger">Could not load surgeries.</div>';
                    });
            });
        }

    });
</script>

<style>
    /* Responsive design for patients table */
    @media (max-width: 768px) {

        /* Hide less important columns on mobile */
        #patients-table th:nth-child(4),
        /* Date of Birth */
        #patients-table td:nth-child(4),
        #patients-table th:nth-child(5),
        /* Phone */
        #patients-table td:nth-child(5),
        #patients-table th:nth-child(6),
        /* Email */
        #patients-table td:nth-child(6) {
            display: none;
        }

        /* Ensure agency column is visible on mobile for admin/editor */
        body.is-admin-editor #patients-table th:nth-child(3),
        body.is-admin-editor #patients-table td:nth-child(3) {
            display: table-cell;
        }
    }


    @media (max-width: 576px) {

        /* On very small screens, make agency badges smaller */
        .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.4rem;
        }

        /* Truncate long agency names */
        .text-truncate-mobile {
            max-width: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
        }
    }

    /* Agency badge styling */
    .badge.bg-secondary {
        background-color: #6c757d !important;
        color: white;
        font-size: 0.75rem;
        font-weight: 500;
    }

    /* Improve table spacing */
    #patients-table td {
        vertical-align: middle;
        padding: 0.75rem 0.5rem;
    }

    #patients-table th {
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
        padding: 0.75rem 0.5rem;
    }

    /* Avatar styling */
    .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e9ecef;
    }
</style>

<?php require_once '../includes/footer.php'; ?>