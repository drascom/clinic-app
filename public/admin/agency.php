<?php
require_once '../includes/header.php';
$page_title = "Agency Management";
?>
<div class="container emp">
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
                    <i class="fas fa-building me-2 text-primary"></i>
                    Agency Management
                </h4>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal"
                        data-bs-target="#addAgencyModal">
                        <i class="fas fa-plus me-1"></i>
                        <span class="d-none d-sm-inline">Add New Agency</span>
                    </button>
                </div>
            </div>
            <!-- Search Bar -->
            <fieldset class="p-4 frosted">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="search-input" placeholder="Search agencies by name...">
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
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm" id="agencies-table" style="table-layout: fixed; width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th>Id</th>
                            <th>Name</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Agency data will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Loading Spinner -->
    <div id="loading-spinner" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-muted">Loading agencies...</p>
    </div>

    <!-- Add Agency Modal -->
    <div class="modal fade" id="addAgencyModal" tabindex="-1" aria-labelledby="addAgencyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAgencyModalLabel">Add New Agency</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="add-agency-form">
                        <div class="mb-3">
                            <label for="agency-name" class="form-label">Agency Name</label>
                            <input type="text" class="form-control" id="agency-name" required>
                        </div>
                    </form>
                    <div id="add-agency-status"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="save-agency-btn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Agency Modal -->
    <div class="modal fade" id="editAgencyModal" tabindex="-1" aria-labelledby="editAgencyModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAgencyModalLabel">Edit Agency</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="edit-agency-form">
                        <input type="hidden" id="edit-agency-id">
                        <div class="mb-3">
                            <label for="edit-agency-name" class="form-label">Agency Name</label>
                            <input type="text" class="form-control" id="edit-agency-name" required>
                        </div>
                    </form>
                    <div id="edit-agency-status"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="update-agency-btn">Update</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteAgencyModal" tabindex="-1" aria-labelledby="deleteAgencyModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAgencyModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this agency? This action cannot be undone.</p>
                    <input type="hidden" id="delete-agency-id">
                    <div id="delete-agency-status"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirm-delete-btn">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const agenciesTable = document.getElementById('agencies-table');
        const loadingSpinner = document.getElementById('loading-spinner');


        // Function to format date
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        // Function to fetch and display agencies
        function fetchAndDisplayAgencies() {
            loadingSpinner.style.display = 'block';

            apiRequest('agencies', 'list')
                .then(data => {
                    loadingSpinner.style.display = 'none';

                    if (data.success) {
                        const agencies = data.agencies;
                        let tableRows = '';

                        agencies.forEach(agency => {
                            tableRows += `
                                <tr>
                                    <td>${agency.id}</td>
                                    <td>${agency.name}</td>
                                    <td>${formatDate(agency.created_at)}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-text text-primary edit-agency-btn"
                                                    data-agency-id="${agency.id}"
                                                    data-agency-name="${agency.name}">
                                                <i class="fas fa-edit"></i>
                                                <span class="d-none d-lg-inline ms-1">Edit</span>
                                            </button>
                                            <button class="btn btn-sm btn-text text-danger delete-agency-btn"
                                                    data-agency-id="${agency.id}">
                                                <i class="fas fa-trash-alt"></i>
                                                <span class="d-none d-lg-inline ms-1">Delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });

                        if (agencies.length === 0) {
                            tableRows = `<tr><td colspan="3" class="text-center">No agencies found</td></tr>`;
                        }

                        agenciesTable.querySelector('tbody').innerHTML = tableRows;
                    } else {
                        showToast(`Error: ${data.error || 'Failed to load agencies'}`, 'danger');
                    }
                })
                .catch(error => {
                    loadingSpinner.style.display = 'none';
                    console.error('Error fetching agencies:', error);
                    showToast('An error occurred while loading agencies.', 'danger');
                });
        }

        // Add new agency
        document.getElementById('save-agency-btn').addEventListener('click', function () {
            const form = document.getElementById('add-agency-form');
            const agencyName = document.getElementById('agency-name').value.trim();
            const statusDiv = document.getElementById('add-agency-status');

            // Add Bootstrap validation classes
            form.classList.add('was-validated');

            // Check form validity
            if (!form.checkValidity()) {
                statusDiv.innerHTML =
                    '<div class="alert alert-danger">Please fill in all required fields correctly.</div>';
                return;
            }

            if (!agencyName) {
                statusDiv.innerHTML = '<div class="alert alert-danger">Agency name is required</div>';
                return;
            }

            const formData = new FormData();
            formData.append('entity', 'agencies');
            formData.append('action', 'create');
            formData.append('name', agencyName);

            fetch('../api.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('agency-name').value = '';
                        statusDiv.innerHTML =
                            '<div class="alert alert-success">Agency created successfully</div>';

                        // Close modal and refresh list after a short delay
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById(
                                'addAgencyModal'));
                            modal.hide();
                            fetchAndDisplayAgencies();
                            showToast('Agency created successfully', 'success');
                        }, 1000);
                    } else {
                        statusDiv.innerHTML =
                            `<div class="alert alert-danger">${data.error || 'Failed to create agency'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error creating agency:', error);
                    statusDiv.innerHTML =
                        '<div class="alert alert-danger">An error occurred while creating the agency</div>';
                });
        });

        // Edit agency - open modal with data
        agenciesTable.addEventListener('click', function (event) {
            const editBtn = event.target.closest('.edit-agency-btn');
            if (editBtn) {
                const agencyId = editBtn.dataset.agencyId;
                const agencyName = editBtn.dataset.agencyName;

                document.getElementById('edit-agency-id').value = agencyId;
                document.getElementById('edit-agency-name').value = agencyName;

                const editModal = new bootstrap.Modal(document.getElementById('editAgencyModal'));
                editModal.show();
            }

            // Delete agency - open confirmation modal
            const deleteBtn = event.target.closest('.delete-agency-btn');
            if (deleteBtn) {
                const agencyId = deleteBtn.dataset.agencyId;

                document.getElementById('delete-agency-id').value = agencyId;

                const deleteModal = new bootstrap.Modal(document.getElementById('deleteAgencyModal'));
                deleteModal.show();
            }
        });

        // Update agency
        document.getElementById('update-agency-btn').addEventListener('click', function () {
            const form = document.getElementById('edit-agency-form');
            const agencyId = document.getElementById('edit-agency-id').value;
            const agencyName = document.getElementById('edit-agency-name').value.trim();
            const statusDiv = document.getElementById('edit-agency-status');

            // Add Bootstrap validation classes
            form.classList.add('was-validated');

            // Check form validity
            if (!form.checkValidity()) {
                statusDiv.innerHTML =
                    '<div class="alert alert-danger">Please fill in all required fields correctly.</div>';
                return;
            }

            if (!agencyName) {
                statusDiv.innerHTML = '<div class="alert alert-danger">Agency name is required</div>';
                return;
            }

            const formData = new FormData();
            formData.append('entity', 'agencies');
            formData.append('action', 'update');
            formData.append('id', agencyId);
            formData.append('name', agencyName);

            fetch('../api.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        statusDiv.innerHTML =
                            '<div class="alert alert-success">Agency updated successfully</div>';

                        // Close modal and refresh list after a short delay
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById(
                                'editAgencyModal'));
                            modal.hide();
                            fetchAndDisplayAgencies();
                            showToast('Agency updated successfully', 'success');
                        }, 1000);
                    } else {
                        statusDiv.innerHTML =
                            `<div class="alert alert-danger">${data.error || 'Failed to update agency'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error updating agency:', error);
                    statusDiv.innerHTML =
                        '<div class="alert alert-danger">An error occurred while updating the agency</div>';
                });
        });

        // Delete agency
        document.getElementById('confirm-delete-btn').addEventListener('click', function () {
            const agencyId = document.getElementById('delete-agency-id').value;
            const statusDiv = document.getElementById('delete-agency-status');

            const formData = new FormData();
            formData.append('entity', 'agencies');
            formData.append('action', 'delete');
            formData.append('id', agencyId);

            fetch('../api.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        statusDiv.innerHTML =
                            '<div class="alert alert-success">Agency deleted successfully</div>';

                        // Close modal and refresh list after a short delay
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById(
                                'deleteAgencyModal'));
                            modal.hide();
                            fetchAndDisplayAgencies();
                            showToast('Agency deleted successfully', 'success');
                        }, 1000);
                    } else {
                        statusDiv.innerHTML =
                            `<div class="alert alert-danger">${data.error || 'Failed to delete agency'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error deleting agency:', error);
                    statusDiv.innerHTML =
                        '<div class="alert alert-danger">An error occurred while deleting the agency</div>';
                });
        });

        // Search functionality
        const searchInput = document.getElementById('search-input');
        const clearSearchBtn = document.getElementById('clear-search');

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                const rows = agenciesTable.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const agencyName = row.cells[1].textContent.toLowerCase();
                    if (agencyName.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function () {
                searchInput.value = '';
                fetchAndDisplayAgencies();
            });
        }

        // Reset forms when modals are shown
        document.getElementById('addAgencyModal').addEventListener('show.bs.modal', function () {
            const form = document.getElementById('add-agency-form');
            form.reset();
            form.classList.remove('was-validated');
        });

        document.getElementById('editAgencyModal').addEventListener('show.bs.modal', function () {
            const form = document.getElementById('edit-agency-form');
            form.classList.remove('was-validated');
        });

        // Clear status messages when modals are hidden
        document.getElementById('addAgencyModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('add-agency-status').innerHTML = '';
        });

        document.getElementById('editAgencyModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('edit-agency-status').innerHTML = '';
        });

        document.getElementById('deleteAgencyModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('delete-agency-status').innerHTML = '';
        });

        // Initial load
        fetchAndDisplayAgencies();
    });
</script>

<?php require_once '../includes/footer.php'; ?>