<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header('Location: /login.php');
    exit();
}


$page_title = "Surgeries";
require_once __DIR__ . '/../includes/header.php';
?>


<div class="container emp-10">
    <!-- Loading Spinner -->
    <div id="loading-spinner" class="text-center py-4" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="card">
        <div class="card-header">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center p-2">
                <h4 class="mb-0">
                    <i class="fas fa-hospital me-2 text-primary"></i>
                    Surgeries
                </h4>
                <a href="/surgery/add_edit_surgery.php" class="btn btn-outline-success">
                    <i class="fas fa-plus me-1"></i>
                    <span class="d-none d-sm-inline">Add New Surgery</span>
                    <span class="d-inline d-sm-none">Add</span>
                </a>
            </div>
            <!-- Search Bar -->
            <fieldset class="p-4 frosted">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="search-input"
                        placeholder="Search surgeries by date, patient, or status...">
                    <button class="btn btn-outline-secondary" type="button" id="clear-search" title="Clear search">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="text-muted small ms-4">
                    <i class="fas fa-info-circle me-1"></i>
                    <span id="surgery-count">Loading...</span> surgeries found
                </div>
            </fieldset>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm" id="surgeries-table">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Patient Name</th>
                            <?php if (is_admin()): ?>
                                <th>Agency</th>
                            <?php endif; ?>
                            <th>Room</th>
                            <th>Predicted / Current Grafts</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="surgeries-tbody">
                        <!-- Surgery rows will be loaded here by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const surgeriesTable = document.getElementById('surgeries-table');

        // Function to fetch and display surgeries
        // Function to format date as DD, MMM / YY
        function formatDate(dateString) {
            const options = {
                day: '2-digit',
                month: 'short',
                year: '2-digit'
            };
            const date = new Date(dateString);
            return date.toLocaleDateString('en-GB', options).replace(/\//g, ' / ');
        }

        function fetchAndDisplaySurgeries() {
            // Show loading spinner
            document.getElementById('loading-spinner').style.display = 'flex';
            surgeriesTable.style.display = 'none';

            const userRole = '<?php echo get_user_role(); ?>';
            const userAgencyId = '<?php echo get_user_agency_id(); ?>';

            // Build API request data with agency filter for agents
            let requestData = {};
            if (userRole === 'agent' && userAgencyId) {
                requestData.agency = userAgencyId;
            }

            apiRequest('surgeries', 'list', requestData)
                .then(data => {
                    // Hide loading spinner
                    document.getElementById('loading-spinner').style.display = 'none';
                    surgeriesTable.style.display = 'table';

                    if (data.success) {
                        const surgeries = data.surgeries;
                        let tableRows = '';

                        surgeries.forEach(surgery => {
                            const userRole = '<?php echo get_user_role(); ?>';
                            const isCompleted = surgery.status.toLowerCase() === 'completed';
                            const canEdit = !(userRole === 'agent' && isCompleted);

                            const isAdmin = <?php echo is_admin() ? 'true' : 'false'; ?>;

                            tableRows += `
                            <tr data-surgery-id="${surgery.id}">
                                <td>
                                    <span class="fw-medium">${formatDate(surgery.date)}</span>
                                </td>
                                <td>
                                    ${surgery.patient_name ?
                                    `<a href="/patient/patient_details.php?id=${surgery.patient_id}&tab=surgeries" class="fw-medium text-decoration-none">
                                            <span class="text-truncate-mobile">${surgery.patient_name}</span>
                                        </a>` :
                                    '<span class="text-muted">N/A</span>'
                                }
                                </td>
                                ${isAdmin ? `
                                <td>
                                    <span class="text-truncate-mobile">${surgery.agency_name || 'No Agency'}</span>
                                </td>` : ''}
                                <td>${surgery.room_name || '-'}</td>
                                <td>
                                    <span class="fw-medium">${surgery.predicted_grafts_count || '0'} / ${surgery.current_grafts_count || '0'}</span>
                                </td>
                                <td>
                                    <span class="badge bg-${getStatusColor(surgery.status)} status-${surgery.status}">
                                        ${surgery.status}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group" aria-label="Surgery Actions">
                                        ${canEdit ?
                                    `<a href="/surgery/add_edit_surgery.php?id=${surgery.id}"
                                               class="btn  btn-sm btn-text text-primary"
                                               title="Edit Surgery">
                                                <i class="fas fa-edit"></i>
                                                <span class="d-none d-lg-inline ms-1">Edit</span>
                                            </a>` :
                                    `<button class="btn btn-sm btn-text-secondary" disabled
                                                     title="Cannot edit completed surgery">
                                                <i class="fas fa-edit"></i>
                                                <span class="d-none d-lg-inline ms-1">Edit</span>
                                            </button>`
                                }
                                        ${isAdmin ? `
                                        <button class="btn btn-sm btn-text text-danger delete-surgery-btn"
                                                data-surgery-id="${surgery.id}"
                                                title="Delete Surgery">
                                            <i class="fas fa-trash-alt"></i>
                                            <span class="d-none d-lg-inline ms-1">Delete</span>
                                        </button>` : ''}
                                    </div>
                                </td>
                            </tr>
                        `;
                        });

                        surgeriesTable.querySelector('tbody').innerHTML = tableRows;

                        // Update surgery count
                        document.getElementById('surgery-count').textContent = surgeries.length;
                    } else {
                        showToast(`Error loading surgeries: ${data.error}`, 'danger');
                        document.getElementById('surgery-count').textContent = '0';
                    }
                })
                .catch(error => {
                    // Hide loading spinner
                    document.getElementById('loading-spinner').style.display = 'none';
                    surgeriesTable.style.display = 'table';

                    console.error('Error fetching surgeries:', error);
                    showToast('An error occurred while loading surgery data.', 'danger');
                    document.getElementById('surgery-count').textContent = '0';
                });
        }


        // Delete surgery function
        surgeriesTable.addEventListener('click', function (event) {
            if (event.target.classList.contains('delete-surgery-btn')) {
                const surgeryId = event.target.dataset.surgeryId;
                if (confirm('Are you sure you want to delete this surgery?')) {
                    const formData = new FormData();
                    formData.append('entity', 'surgeries');
                    formData.append('action', 'delete');
                    formData.append('id', surgeryId);

                    fetch('/api.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showToast(data.message, 'success');
                                fetchAndDisplaySurgeries(); // Refresh the surgery list
                            } else {
                                showToast(`Error deleting surgery: ${data.error}`, 'danger');
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting surgery:', error);
                            showToast('An error occurred while deleting the surgery.', 'danger');
                        });
                }
            }
        });

        fetchAndDisplaySurgeries(); // Initial load of surgeries

        // Search functionality
        const searchInput = document.getElementById('search-input');
        const clearSearchBtn = document.getElementById('clear-search');

        function filterSurgeries() {
            const searchTerm = searchInput.value.toLowerCase();
            const rows = surgeriesTable.querySelectorAll('tbody tr');
            const isAdmin = <?php echo is_admin() ? 'true' : 'false'; ?>;

            rows.forEach(row => {
                const date = row.cells[0].textContent.toLowerCase();
                const patientName = row.cells[1].textContent.toLowerCase();
                const agencyName = isAdmin ? row.cells[2].textContent.toLowerCase() : '';
                const roomName = isAdmin ? row.cells[3].textContent.toLowerCase() : row.cells[2].textContent
                    .toLowerCase();
                const status = isAdmin ? row.cells[5].textContent.toLowerCase() : row.cells[4].textContent
                    .toLowerCase();

                if (date.includes(searchTerm) || patientName.includes(searchTerm) ||
                    agencyName.includes(searchTerm) || roomName.includes(searchTerm) || status.includes(
                        searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        if (searchInput) {
            searchInput.addEventListener('keyup', function (event) {
                const searchTerm = searchInput.value.toLowerCase();
                if (searchTerm.length >= 2 || searchTerm.length === 0) {
                    filterSurgeries();
                } else if (searchTerm.length === 1) {
                    // If only one character, clear the filter
                    const rows = surgeriesTable.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        row.style.display = '';
                    });
                }
            });
        }

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function () {
                searchInput.value = '';
                fetchAndDisplaySurgeries();
            });
        }
    });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>