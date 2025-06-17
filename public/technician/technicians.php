<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

$page_title = "Technician Management";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container emp">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="fas fa-user-cog me-2 text-primary"></i>
            Technician Management
        </h4>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal"
                data-bs-target="#techModal" onclick="openTechModal()">
                <i class="fas fa-plus me-1"></i>
                Add Technician
            </button>
            <a href="tech_availability.php" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-calendar-check me-1"></i>
                Availability Grid
            </a>
        </div>
    </div>

    <!-- Status Messages -->

    <!-- Technicians Table -->

    <div id="loading-spinner" class="text-center py-4" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Loading technicians...</p>
    </div>
    <div class="card ">
        <div class="card-body">
            <div id="techs-table-container" class="table-responsive">
                <table class="table table-hover table-responsive table-sm" id="techs-table">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Period</th>
                            <th>Notes</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="techs-tbody" class="p-0">
                        <!-- Technicians will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <!-- Technician Modal -->
    <div class="modal fade" id="techModal" tabindex="-1" aria-labelledby="techModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="techModalLabel">Add Technician</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="tech-form" novalidate>
                    <div class="modal-body">
                        <input type="hidden" id="tech-id" name="id">

                        <!-- General Error Alert -->
                        <div class="alert alert-danger d-none" id="form-error-alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <span id="form-error-message"></span>
                        </div>

                        <div class="mb-3">
                            <label for="tech-name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tech-name" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="tech-phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="tech-phone" name="phone"
                                placeholder="e.g., 07508400686">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="tech-status" class="form-label">Status <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="tech-status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="Self Employed">Self Employed</option>
                                <option value="Sponsorship">Sponsorship</option>
                                <option value="Employed">Employed</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="tech-period" class="form-label">Period <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="tech-period" name="period" required>
                                <option value="">Select Period</option>
                                <option value="Full Time">Full Time</option>
                                <option value="Part Time">Part Time</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="tech-notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="tech-notes" name="notes" rows="3"
                                placeholder="Additional notes about the technician"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="tech-submit-btn">Save Technician</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Options Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Technician</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>What would you like to do with <strong id="delete-tech-name"></strong>?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Archive:</strong> Hides the technician but keeps all data. Can be reactivated later.<br>
                        <strong>Delete:</strong> Permanently removes the technician and all associated data. This cannot
                        be
                        undone.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="archive-btn">
                        <i class="fas fa-archive me-1"></i>
                        Archive
                    </button>
                    <button type="button" class="btn btn-danger" id="delete-btn">
                        <i class="fas fa-trash me-1"></i>
                        Delete Permanently
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let isEditing = false;
    var addModal = '';
    let currentTechId = null;
    let currentTechName = '';

    document.addEventListener('DOMContentLoaded', function () {
        loadTechnicians();

        // Technician form submission
        document.getElementById('tech-form').addEventListener('submit', function (e) {
            e.preventDefault();
            saveTechnician();
        });

        // Real-time validation event listeners
        setupFormValidation();

        // Delete modal button handlers
        document.getElementById('archive-btn').addEventListener('click', function () {
            archiveTechnician(currentTechId, currentTechName);
            closeDeleteModal();
        });

        document.getElementById('delete-btn').addEventListener('click', function () {
            permanentDeleteTechnician(currentTechId, currentTechName);
            closeDeleteModal();
        });
    });

    function setupFormValidation() {
        const form = document.getElementById('tech-form');
        const inputs = form.querySelectorAll('input, select, textarea');

        inputs.forEach(input => {
            // Real-time validation on blur
            input.addEventListener('blur', function () {
                validateField(this);
            });

            // Clear validation on input
            input.addEventListener('input', function () {
                if (this.classList.contains('is-invalid')) {
                    this.classList.remove('is-invalid');
                    const feedback = this.parentNode.querySelector('.invalid-feedback');
                    if (feedback) feedback.textContent = '';
                }
            });
        });
    }


    // Listen for tech modal hidden event to manage focus
    const techModalElement = document.getElementById('techModal');
    techModalElement.addEventListener('hidden.bs.modal', function () {
        // Explicitly move focus to the body to prevent focus remaining inside the hidden modal
        document.body.focus();
        // Listen for tech modal hide event to blur focus
        techModalElement.addEventListener('hide.bs.modal', function () {
            const focusedElement = document.activeElement;
            // Check if the currently focused element is inside the modal
            if (techModalElement.contains(focusedElement)) {
                // Set focus to the first field when the tech modal is fully shown
                techModalElement.addEventListener('shown.bs.modal', function () {
                    document.getElementById('tech-name').focus();
                });
                focusedElement.blur();
            }
        });
    });

    function loadTechnicians() {
        showLoading(true);

        apiRequest('techs', 'list')
            .then(data => {
                if (data.success) {
                    renderTechsTable(data.technicians);
                } else {
                    showToast('Error loading technicians: ' + (data.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error loading technicians:', error);
                showToast('Failed to load technicians. Please try again.', 'danger');
            })
            .finally(() => {
                showLoading(false);
            });
    }

    function renderTechsTable(techs) {
        const tbody = document.getElementById('techs-tbody');

        if (techs.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="fas fa-user-cog fa-2x mb-2"></i><br>
                    No technicians found. <a href="#" onclick="openTechModal()">Add your first technician</a>
                </td>
            </tr>
        `;
            return;
        }

        tbody.innerHTML = techs.map(tech => `
        <tr class=" ${tech.is_active ? '' : 'text-primary'}">
            <td>
                <strong>${escapeHtml(tech.name)}</strong>
            </td>
            <td>
                ${tech.phone ? escapeHtml(tech.phone) : '<span class="text-muted">Not specified</span>'}
            </td>
            <td>
                <span class="badge ${tech.status === 'Self Employed' ? 'bg-primary' : tech.status === 'Sponsorship' ? 'bg-warning' : 'bg-secondary'}">
                    ${tech.status ? escapeHtml(tech.status) : 'Not specified'}
                </span>
            </td>
            <td>
                <span class="badge ${tech.period === 'Full Time' ? 'bg-success' : tech.period === 'Part Time' ? 'bg-info' : 'bg-secondary'}">
                    ${tech.period ? escapeHtml(tech.period) : 'Not specified'}
                </span>
            </td>
            <td>
                <span class="text-truncate" style="max-width: 150px; display: inline-block;" title="${tech.notes ? escapeHtml(tech.notes) : 'No notes'}">
                    ${tech.notes ? escapeHtml(tech.notes) : '<span class="text-muted">No notes</span>'}
                </span>
            </td>
            <td>
                <span class="badge ${tech.is_active ? 'bg-success' : 'bg-secondary'}">
                    ${tech.is_active ? 'Active' : 'Archived'}
                </span>
            </td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-text text-primary" onclick="editTechnician(${tech.id})" title="Edit">
                        <i class="fas fa-edit"></i><span class="d-none d-lg-inline ms-1">Edit</span>
                    </button>
                    ${tech.is_active ? `
                        <button type="button" class="btn btn-text text-danger" onclick="openDeleteModal(${tech.id}, '${escapeHtml(tech.name)}')" title="Delete Options">
                            <i class="fas fa-trash"></i> <span class="d-none d-lg-inline ms-1">Delete</span>
                        </button>
                    ` : `
                        <button type="button" class="btn btn-text text-success" onclick="reactivateTechnician(${tech.id}, '${escapeHtml(tech.name)}')" title="Reactivate">
                            <i class="fas fa-hand-paper"></i> <span class="d-none d-lg-inline ms-1">Activate</span>
                        </button>
                    `}
                </div>
            </td>
        </tr>
    `).join('');
    }

    function openTechModal(techId = null) {
        isEditing = !!techId;
        const modal = document.getElementById('techModal');
        const modalTitle = document.getElementById('techModalLabel');
        const submitBtn = document.getElementById('tech-submit-btn');

        // Reset form
        resetForm();

        if (isEditing) {
            modalTitle.textContent = 'Edit Technician';
            submitBtn.textContent = 'Update Technician';
            loadTechData(techId);
        } else {
            modalTitle.textContent = 'Add Technician';
            submitBtn.textContent = 'Save Technician';
        }

        const modalElement = document.getElementById('techModal');
        const modalInstance = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
        modalInstance.show();
    }

    function loadTechData(techId) {
        apiRequest('techs', 'get', {
            id: techId
        })
            .then(data => {
                if (data.success) {
                    const tech = data.technician;
                    document.getElementById('tech-id').value = tech.id;
                    document.getElementById('tech-name').value = tech.name;
                    document.getElementById('tech-phone').value = tech.phone || '';
                    document.getElementById('tech-status').value = tech.status || '';
                    document.getElementById('tech-period').value = tech.period || '';
                    document.getElementById('tech-notes').value = tech.notes || '';
                } else {
                    showToast('Error loading technician data: ' + (data.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error loading technician data:', error);
                showToast('Failed to load technician data.', 'danger');
            });
    }

    function saveTechnician() {
        const form = document.getElementById('tech-form');

        // Clear previous errors
        hideFormError();

        // Validate form
        if (!validateForm()) {
            return;
        }

        const formData = new FormData(form);
        formData.append('entity', 'techs');
        formData.append('action', isEditing ? 'update' : 'add');

        fetch('/api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    closeModal();
                    loadTechnicians(); // Reload the table
                } else {
                    // Handle user-friendly error messages
                    showFormError(data.error || 'Unknown error occurred');
                }
            })
            .catch(error => {
                console.error('Error saving technician:', error);
                showFormError('Failed to save technician. Please try again.');
            });
    }

    function editTechnician(techId) {
        openTechModal(techId);
    }

    function closeModal() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('techModal'));
        if (modal) {
            modal.hide();
        }
    }

    function archiveTechnician(techId, techName) {
        // if (!confirm(
        //         `Are you sure you want to archive "${techName}"? This will make them unavailable for new assignments.`)) {
        //     return;
        // }

        const formData = new FormData();
        formData.append('entity', 'techs');
        formData.append('action', 'deactivate');
        formData.append('id', techId);

        fetch('/api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    loadTechnicians(); // Reload the table
                } else {
                    showToast('Error: ' + (data.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error archiving technician:', error);
                showToast('Failed to archive technician. Please try again.', 'danger');
            });
    }

    function openDeleteModal(techId, techName) {
        currentTechId = techId;
        currentTechName = techName;

        document.getElementById('delete-tech-name').textContent = techName;

        const deleteModalElement = document.getElementById('deleteModal');
        const deleteModal = bootstrap.Modal.getInstance(deleteModalElement) || new bootstrap.Modal(deleteModalElement);
        deleteModal.show();
    }

    function closeDeleteModal() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
        if (modal) {
            modal.hide();
        }

    }

    function permanentDeleteTechnician(techId, techName) {
        // if (!confirm(
        //         `Are you absolutely sure you want to PERMANENTLY DELETE "${techName}"? This action cannot be undone and will remove all associated data.`
        //     )) {
        //     return;
        // }

        const formData = new FormData();
        formData.append('entity', 'techs');
        formData.append('action', 'delete');
        formData.append('id', techId);

        fetch('/api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    loadTechnicians(); // Reload the table
                } else {
                    showToast('Error: ' + (data.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error deleting technician:', error);
                showToast('Failed to delete technician. Please try again.', 'danger');
            });
    }

    function reactivateTechnician(techId, techName) {
        // if (!confirm(
        //         `Are you sure you want to reactivate "${techName}"? This will make them available for new assignments.`)) {
        //     return;
        // }

        const formData = new FormData();
        formData.append('entity', 'techs');
        formData.append('action', 'reactivate');
        formData.append('id', techId);

        fetch('/api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    loadTechnicians(); // Reload the table
                } else {
                    showToast('Error: ' + (data.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error reactivating technician:', error);
                showToast('Failed to reactivate technician. Please try again.', 'danger');
            });
    }

    function showLoading(show) {
        const spinner = document.getElementById('loading-spinner');
        const table = document.getElementById('techs-table-container');

        if (show) {
            spinner.style.display = 'block';
            table.style.display = 'none';
        } else {
            spinner.style.display = 'none';
            table.style.display = 'block';
        }
    }


    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Validate individual field
     */
    function validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let message = '';

        // Required field validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            message = 'This field is required.';
        }

        // Update field validation state
        if (isValid) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
            const feedback = field.parentNode.querySelector('.invalid-feedback');
            if (feedback) feedback.textContent = message;
        }

        return isValid;
    }

    /**
     * Validate entire form
     */
    function validateForm() {
        const form = document.getElementById('tech-form');
        const inputs = form.querySelectorAll('input[required], select[required]');
        let isValid = true;
        let firstInvalidField = null;

        inputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = input;
                }
            }
        });

        if (!isValid && firstInvalidField) {
            firstInvalidField.focus();
            showFormError('Please correct the highlighted errors before submitting.');
        }

        return isValid;
    }

    /**
     * Reset form
     */
    function resetForm() {
        const form = document.getElementById('tech-form');
        form.reset();
        form.classList.remove('was-validated');
        document.getElementById('tech-id').value = '';

        // Clear validation states
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.classList.remove('is-valid', 'is-invalid');
        });

        hideFormError();
    }

    /**
     * Show form error
     */
    function showFormError(message) {
        const errorAlert = document.getElementById('form-error-alert');
        const errorMessage = document.getElementById('form-error-message');
        errorMessage.textContent = message;
        errorAlert.classList.remove('d-none');
    }

    /**
     * Hide form error
     */
    function hideFormError() {
        const errorAlert = document.getElementById('form-error-alert');
        errorAlert.classList.add('d-none');
    }


</script>

<?php require_once '../includes/footer.php'; ?>