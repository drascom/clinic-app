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
                <div class="btn-group" role="group">
                    <a href="/surgery/add_edit_surgery.php" class="btn btn-outline-success">
                        <i class="fas fa-plus me-1"></i>
                        <span class="d-none d-sm-inline">Add New Surgery</span>
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
                            <th class="sortable" data-sort-by="date" data-sort-order="desc">
                                Date <i class="fas fa-sort-down ms-1"></i>
                            </th>
                            <th class="sortable" data-sort-by="patient_name" data-sort-order="asc">
                                Patient Name <i class="fas fa-sort ms-1"></i>
                            </th>
                            <?php if (is_admin()): ?>
                                <th class="sortable" data-sort-by="agency_name" data-sort-order="asc">
                                    Agency <i class="fas fa-sort ms-1"></i>
                                </th>
                            <?php endif; ?>
                            <th class="sortable" data-sort-by="predicted_grafts_count" data-sort-order="asc">
                                E / C Grafts <i class="fas fa-sort ms-1"></i>
                            </th>
                            <th class="sortable" data-sort-by="status" data-sort-order="asc">
                                Status <i class="fas fa-sort ms-1"></i>
                            </th>
                            <th>Forms</th>
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
        const sortableHeaders = document.querySelectorAll('.sortable');

        let allSurgeries = []; // Store all fetched surgeries
        let currentSortColumn = 'date';
        let currentSortOrder = 'desc';

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

        function sortAndDisplaySurgeries(surgeries, sortColumn, sortOrder) {
            const sortedSurgeries = [...surgeries].sort((a, b) => {
                let valA = a[sortColumn];
                let valB = b[sortColumn];

                if (sortColumn === 'date') {
                    valA = new Date(valA);
                    valB = new Date(valB);
                } else if (sortColumn === 'predicted_grafts_count') {
                    valA = parseInt(valA, 10);
                    valB = parseInt(valB, 10);
                }

                if (valA < valB) {
                    return sortOrder === 'asc' ? -1 : 1;
                }
                if (valA > valB) {
                    return sortOrder === 'asc' ? 1 : -1;
                }
                return 0;
            });

            let tableRows = '';
            sortedSurgeries.forEach(surgery => {
                let formsHtml = '<div class="d-flex justify-content-start align-items-center">';
                if (surgery.forms) {
                    try {
                        const forms = JSON.parse(surgery.forms);
                        for (const [formName, isCompleted] of Object.entries(forms)) {
                            const icon = isCompleted ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
                            formsHtml += `<i class="fas ${icon} me-2 form-toggle-icon" data-surgery-id="${surgery.id}" data-form-name="${formName}" title="${formName}" style="cursor: pointer;"></i>`;
                        }
                    } catch (e) {
                        formsHtml += '<span class="text-danger">Error</span>';
                    }
                } else {
                    formsHtml += '<span class="text-muted">N/A</span>';
                }
                formsHtml += '</div>';

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
                    <td>
                        <span class="fw-medium">${surgery.predicted_grafts_count || '0'} / ${surgery.current_grafts_count || '0'}</span>
                    </td>
                    <td>
                        <span class="badge bg-${getStatusColor(surgery.status)} status-${surgery.status}">
                            ${surgery.status}
                        </span>
                    </td>
                    <td>${formsHtml}</td>
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
            document.getElementById('surgery-count').textContent = sortedSurgeries.length;

            // Update current sort state
            currentSortColumn = sortColumn;
            currentSortOrder = sortOrder;

            // Update sort icons
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
                        allSurgeries = data.surgeries; // Store fetched data
                        sortAndDisplaySurgeries(allSurgeries, currentSortColumn, currentSortOrder);
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

        // Add event listener for sorting
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
                sortAndDisplaySurgeries(allSurgeries, sortColumn, newSortOrder);
            });
        });

        // Event listener for form icons
        surgeriesTable.addEventListener('click', function (event) {
            if (event.target.classList.contains('form-toggle-icon')) {
                const icon = event.target;
                const surgeryId = icon.dataset.surgeryId;
                const formName = icon.dataset.formName;
                toggleFormStatus(surgeryId, formName, icon);
            }
        });

        // Function to toggle form status and update via API
        function toggleFormStatus(surgeryId, formName, iconElement) {
            apiRequest('surgeries', 'get', { id: surgeryId })
                .then(data => {
                    if (data.success && data.surgery) {
                        const surgery = data.surgery;
                        let forms = {};
                        try {
                            forms = JSON.parse(surgery.forms || '{}');
                        } catch (e) {
                            console.error('Error parsing forms JSON:', e);
                        }

                        // Toggle the status
                        forms[formName] = !forms[formName];

                        // Update the icon visually
                        const newIconClass = forms[formName] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
                        iconElement.className = `fas ${newIconClass} me - 2 form - toggle - icon`;

                        // Prepare data for API update
                        const formData = new FormData();
                        formData.append('entity', 'surgeries');
                        formData.append('action', 'updateForms'); // New action for updating forms
                        formData.append('id', surgeryId);
                        formData.append('forms', JSON.stringify(forms));

                        fetch('/api.php', {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.json())
                            .then(updateData => {
                                if (updateData.success) {
                                    showToast(`Form '${formName}' status updated successfully!`, 'success');
                                } else {
                                    showToast(`Error updating form status: ${updateData.error}`, 'danger');
                                    // Revert icon if API update fails
                                    const originalIconClass = !forms[formName] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
                                    iconElement.className = `fas ${originalIconClass} me - 2 form - toggle - icon`;
                                }
                            })
                            .catch(error => {
                                console.error('Error updating form status:', error);
                                showToast('An error occurred while updating form status.', 'danger');
                                // Revert icon if fetch fails
                                const originalIconClass = !forms[formName] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
                                iconElement.className = `fas ${originalIconClass} me - 2 form - toggle - icon`;
                            });
                    } else {
                        showToast(`Error fetching surgery details: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error fetching surgery for form toggle:', error);
                    showToast('An error occurred while fetching surgery details.', 'danger');
                });
        }

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
                const status = isAdmin ? row.cells[4].textContent.toLowerCase() : row.cells[3].textContent
                    .toLowerCase();

                if (date.includes(searchTerm) || patientName.includes(searchTerm) ||
                    agencyName.includes(searchTerm) || status.includes(
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