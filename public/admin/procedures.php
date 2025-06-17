<?php
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Procedures Management';
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
                    <i class="fas fa-procedures me-2 text-primary"></i>
                    Procedures Management
                </h4>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal"
                        data-bs-target="#addProcedureModal">
                        <i class="fas fa-plus me-1"></i>
                        <span class="d-none d-sm-inline">Add Procedure</span>
                    </button>
                </div>
            </div>
            <!-- Search Bar -->
            <fieldset class="p-4 frosted">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="search-input"
                        placeholder="Search procedures by name...">
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
                <table class="table table-hover table-sm" id="procedures-table"
                    style="table-layout: fixed; width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="procedures-tbody">
                        <!-- Procedures will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Procedure Modal -->
<div class="modal fade" id="addProcedureModal" tabindex="-1" aria-labelledby="addProcedureModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProcedureModalLabel">
                    <i class="fas fa-plus me-2"></i>Add New Procedure
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="add-procedure-form">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add-procedure-name" class="form-label">Procedure Name *</label>
                        <input type="text" class="form-control" id="add-procedure-name" name="name" required
                            placeholder="Enter procedure name">
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Add Procedure
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Procedure Modal -->
<div class="modal fade" id="editProcedureModal" tabindex="-1" aria-labelledby="editProcedureModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProcedureModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Procedure
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="edit-procedure-form">
                <div class="modal-body">
                    <input type="hidden" id="edit-procedure-id" name="id">
                    <div class="mb-3">
                        <label for="edit-procedure-name" class="form-label">Procedure Name *</label>
                        <input type="text" class="form-control" id="edit-procedure-name" name="name" required
                            placeholder="Enter procedure name">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="edit-procedure-status" class="form-label">Status</label>
                        <select class="form-select" id="edit-procedure-status" name="is_active">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Update Procedure
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
    let procedures = [];

    document.addEventListener('DOMContentLoaded', function () {
        loadProcedures();

        // Add procedure form handler
        document.getElementById('add-procedure-form').addEventListener('submit', handleAddProcedure);

        // Edit procedure form handler
        document.getElementById('edit-procedure-form').addEventListener('submit', handleEditProcedure);

        // Search functionality
        const searchInput = document.getElementById('search-input');
        const clearSearchBtn = document.getElementById('clear-search');

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                const filteredProcedures = procedures.filter(procedure => {
                    return procedure.name.toLowerCase().includes(searchTerm);
                });
                renderProceduresTable(filteredProcedures, true);
            });
        }

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function () {
                searchInput.value = '';
                renderProceduresTable(procedures);
            });
        }
    });

    function renderProceduresTable(dataToRender = procedures, isFiltered = false) {
        const tbody = document.getElementById('procedures-tbody');
        const recordsCountSpan = document.getElementById('records-count');

        if (dataToRender.length === 0) {
            const message = isFiltered ?
                `<i class="fas fa-search fa-2x mb-2"></i><br>No procedures found matching your search` :
                `<i class="fas fa-procedures fa-2x mb-2"></i><br>No procedures found`;
            tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-muted py-4">
                    ${message}
                </td>
            </tr>
        `;
            recordsCountSpan.textContent = '0';
            return;
        }

        tbody.innerHTML = dataToRender.map(procedure => `
        <tr>
            <td>${procedure.id}</td>
            <td>${escapeHtml(procedure.name)}</td>
            <td>
                <span class="badge ${procedure.is_active ? 'bg-success' : 'bg-secondary'}">
                    ${procedure.is_active ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td>${formatDate(procedure.created_at)}</td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-text text-primary" onclick="editProcedure(${procedure.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                        <span class="d-none d-lg-inline ms-1">Edit</span>
                    </button>
                    <button type="button" class="btn btn-text text-danger" onclick="deleteProcedure(${procedure.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                        <span class="d-none d-lg-inline ms-1">Delete</span>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
        recordsCountSpan.textContent = dataToRender.length;
    }

    // Load all procedures
    function loadProcedures() {
        showLoading(true);

        apiRequest('procedures', 'list')
            .then(data => {
                if (data.success) {
                    procedures = data.procedures || [];
                    renderProceduresTable(procedures);
                } else {
                    showToast('Error loading procedures: ' + (data.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error loading procedures:', error);
                showToast('Failed to load procedures. Please try again.', 'danger');
            })
            .finally(() => {
                showLoading(false);
            });
    }

    // Handle add procedure form submission
    function handleAddProcedure(e) {
        e.preventDefault();

        const formData = new FormData();
        formData.append('entity', 'procedures');
        formData.append('action', 'create');
        formData.append('name', document.getElementById('add-procedure-name').value.trim());

        fetch('/api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Procedure added successfully!', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('addProcedureModal')).hide();
                    document.getElementById('add-procedure-form').reset();
                    loadProcedures(); // Reload procedures
                } else {
                    showToast('Error: ' + (data.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error adding procedure:', error);
                showToast('Failed to add procedure. Please try again.', 'danger');
            });
    }

    // Handle edit procedure form submission
    function handleEditProcedure(e) {
        e.preventDefault();

        const formData = new FormData();
        formData.append('entity', 'procedures');
        formData.append('action', 'update');
        formData.append('id', document.getElementById('edit-procedure-id').value);
        formData.append('name', document.getElementById('edit-procedure-name').value.trim());
        formData.append('is_active', document.getElementById('edit-procedure-status').value);

        fetch('/api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Procedure updated successfully!', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('editProcedureModal')).hide();
                    loadProcedures(); // Reload procedures
                } else {
                    showToast('Error: ' + (data.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error updating procedure:', error);
                showToast('Failed to update procedure. Please try again.', 'danger');
            });
    }

    // Edit procedure
    function editProcedure(id) {
        const procedure = procedures.find(p => p.id === id);
        if (!procedure) {
            showToast('Procedure not found', 'danger');
            return;
        }

        document.getElementById('edit-procedure-id').value = procedure.id;
        document.getElementById('edit-procedure-name').value = procedure.name;
        document.getElementById('edit-procedure-status').value = procedure.is_active;

        const modal = new bootstrap.Modal(document.getElementById('editProcedureModal'));
        modal.show();
    }

    // Delete procedure
    function deleteProcedure(id) {
        const procedure = procedures.find(p => p.id === id);
        if (!procedure) {
            showToast('Procedure not found', 'danger');
            return;
        }

        if (confirm(`Are you sure you want to delete "${procedure.name}"?`)) {
            const formData = new FormData();
            formData.append('entity', 'procedures');
            formData.append('action', 'delete');
            formData.append('id', id);

            fetch('/api.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Procedure deleted successfully!', 'success');
                        loadProcedures(); // Reload procedures
                    } else {
                        showToast('Error: ' + (data.error || 'Unknown error'), 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error deleting procedure:', error);
                    showToast('Failed to delete procedure. Please try again.', 'danger');
                });
        }
    }

    // Utility functions

    function showLoading(show) {
        // Simple loading implementation
        const tbody = document.getElementById('procedures-tbody');
        if (show) {
            tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2">Loading procedures...</div>
                </td>
            </tr>
        `;
        }
    }

    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>