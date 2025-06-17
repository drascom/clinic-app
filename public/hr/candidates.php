<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Ensure user is logged in and is admin or editor
if (!is_logged_in() || (!is_admin() && !is_editor())) {
    header('Location: /auth/login.php');
    exit();
}

$page_title = "Job Candidates";
include __DIR__ . '/../includes/header.php';
?>

<div class="container emp">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="fas fa-users me-2 text-primary"></i>
            Job Candidates
        </h4>
        <button type="button" class="btn btn-sm btn-outline-success" id="add-candidate-btn">
            <i class="fas fa-plus-circle me-1"></i>
            <span class="d-none d-sm-inline">Add New Candidate</span>
            <span class="d-inline d-sm-none">Add</span>
        </button>
    </div>

    <!-- Loading Spinner -->
    <div id="loading-spinner" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-muted">Loading candidates...</p>
    </div>

    <!-- Status Messages -->
    <div id="status-messages">
        <!-- Success or error messages will be displayed here -->
    </div>

    <!-- Search Section -->
    <div class="search-section">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="candidate-search" class="form-control"
                        placeholder="Search candidates by name, email, position, company, or status...">
                </div>
                <div class="text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    <span id="candidate-count">Loading...</span> candidates found
                </div>
            </div>
        </div>
    </div>

    <!-- Candidates Table -->
    <div class="table-responsive">
        <table class="table table-hover table-sm" id="candidates-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th class="d-none d-lg-table-cell">Experience</th>
                    <th class="d-none d-md-table-cell">Applied Date</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Candidate rows will be loaded here via JavaScript -->
            </tbody>
        </table>
    </div>
</div>

<!-- Candidate Modal (Add/Edit) -->
<div class="modal fade" id="candidateModal" tabindex="-1" aria-labelledby="candidateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="candidateModalLabel">Add New Candidate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Status Messages for Modal -->
            <div id="modal-status-messages">
                <!-- Success or error messages will be displayed here -->
            </div>
            <form id="candidate-form">
                <input type="hidden" name="entity" value="candidates">
                <input type="hidden" id="form-action" name="action" value="add">
                <input type="hidden" id="candidate-id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <fieldset class="border rounded p-3 mb-3">
                                <legend class="w-auto px-2 mb-3" style="font-size: 1rem;">
                                    <i class="fas fa-user me-2"></i>Basic Information
                                </legend>

                                <div class="mb-3">
                                    <label for="candidate-name" class="form-label">
                                        <i class="fas fa-user me-1"></i>
                                        Full Name *
                                    </label>
                                    <input type="text" class="form-control" id="candidate-name" name="name" required>
                                </div>

                                <div class="mb-3">
                                    <label for="candidate-email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>
                                        Email *
                                    </label>
                                    <input type="email" class="form-control" id="candidate-email" name="email" required>
                                </div>

                                <div class="mb-3">
                                    <label for="candidate-phone" class="form-label">
                                        <i class="fas fa-phone me-1"></i>
                                        Phone
                                    </label>
                                    <input type="tel" class="form-control" id="candidate-phone" name="phone">
                                </div>

                                <div class="mb-3">
                                    <label for="candidate-position" class="form-label">
                                        <i class="fas fa-briefcase me-1"></i>
                                        Position Applied *
                                    </label>
                                    <input type="text" class="form-control" id="candidate-position"
                                        name="position_applied" required>
                                </div>
                            </fieldset>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <fieldset class="border rounded p-3 mb-3">
                                <legend class="w-auto px-2 mb-3" style="font-size: 1rem;">
                                    <i class="fas fa-info-circle me-2"></i>Professional Info
                                </legend>

                                <div class="mb-3">
                                    <label for="candidate-experience" class="form-label">
                                        <i class="fas fa-star me-1"></i>
                                        Experience Level *
                                    </label>
                                    <select class="form-select" id="candidate-experience" name="experience_level"
                                        required>
                                        <option value="">Select Experience Level</option>
                                        <option value="Entry Level">Entry Level</option>
                                        <option value="Junior">Junior</option>
                                        <option value="Mid Level">Mid Level</option>
                                        <option value="Senior">Senior</option>
                                        <option value="Expert">Expert</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="candidate-company" class="form-label">
                                        <i class="fas fa-building me-1"></i>
                                        Current Company
                                    </label>
                                    <input type="text" class="form-control" id="candidate-company"
                                        name="current_company">
                                </div>

                                <div class="mb-3">
                                    <label for="candidate-source" class="form-label">
                                        <i class="fas fa-search me-1"></i>
                                        Source *
                                    </label>
                                    <select class="form-select" id="candidate-source" name="source" required>
                                        <option value="">Select Source</option>
                                        <option value="Website">Website</option>
                                        <option value="LinkedIn">LinkedIn</option>
                                        <option value="Indeed">Indeed</option>
                                        <option value="Referral">Referral</option>
                                        <option value="Agency">Agency</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <div class="mb-3" id="application-date-field">
                                    <label for="candidate-application-date" class="form-label">
                                        <i class="fas fa-calendar me-1"></i>
                                        Application Date *
                                    </label>
                                    <input type="date" class="form-control" id="candidate-application-date"
                                        name="application_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>

                                <div class="mb-3" id="status-field" style="display: none;">
                                    <label for="candidate-status" class="form-label">
                                        <i class="fas fa-flag me-1"></i>
                                        Status
                                    </label>
                                    <select class="form-select" id="candidate-status" name="status">
                                        <option value="Applied">Applied</option>
                                        <option value="Screening">Screening</option>
                                        <option value="Interview Scheduled">Interview Scheduled</option>
                                        <option value="Interviewed">Interviewed</option>
                                        <option value="Second Interview">Second Interview</option>
                                        <option value="Reference Check">Reference Check</option>
                                        <option value="Offer Extended">Offer Extended</option>
                                        <option value="Hired">Hired</option>
                                        <option value="Rejected">Rejected</option>
                                        <option value="Withdrawn">Withdrawn</option>
                                    </select>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submit-btn">
                        <i class="fas fa-save me-1"></i>
                        <span id="submit-text">Add Candidate</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>
    document.addEventListener('DOMContentLoaded', function () {
        const candidatesTable = document.getElementById('candidates-table');
        const statusMessagesDiv = document.getElementById('status-messages');
        const modalStatusDiv = document.getElementById('modal-status-messages');
        const candidateForm = document.getElementById('candidate-form');
        const candidateModal = document.getElementById('candidateModal');
        const addCandidateBtn = document.getElementById('add-candidate-btn');

        // Function to display messages in different containers
        function displayMessage(message, type = 'success', container = 'main') {
            let targetDiv;
            switch (container) {
                case 'modal':
                    targetDiv = modalStatusDiv;
                    break;
                default:
                    targetDiv = statusMessagesDiv;
            }
            targetDiv.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        }

        // Function to clear messages from a specific container
        function clearMessages(container = 'main') {
            let targetDiv;
            switch (container) {
                case 'modal':
                    targetDiv = modalStatusDiv;
                    break;
                default:
                    targetDiv = statusMessagesDiv;
            }
            targetDiv.innerHTML = '';
        }

        // Function to open modal for adding new candidate
        function openAddModal() {
            // Reset form
            candidateForm.reset();
            candidateForm.classList.remove('was-validated');
            clearMessages('modal');

            // Set modal for add mode
            document.getElementById('candidateModalLabel').textContent = 'Add New Candidate';
            document.getElementById('form-action').value = 'add';
            document.getElementById('candidate-id').value = '';
            document.getElementById('submit-text').textContent = 'Add Candidate';

            // Show application date field, hide status field
            document.getElementById('application-date-field').style.display = 'block';
            document.getElementById('status-field').style.display = 'none';

            // Set default application date
            document.getElementById('candidate-application-date').value = '<?php echo date('Y-m-d'); ?>';

            // Show modal
            new bootstrap.Modal(candidateModal).show();
        }

        // Function to open modal for editing candidate
        function openEditModal(candidateId) {
            // Clear form and messages
            candidateForm.reset();
            candidateForm.classList.remove('was-validated');
            clearMessages('modal');

            // Set modal for edit mode
            document.getElementById('candidateModalLabel').textContent = 'Edit Candidate';
            document.getElementById('form-action').value = 'update';
            document.getElementById('submit-text').textContent = 'Update Candidate';

            // Hide application date field, show status field
            document.getElementById('application-date-field').style.display = 'none';
            document.getElementById('status-field').style.display = 'block';

            // Fetch candidate data and populate form
            apiRequest('candidates', 'get', { id: candidateId })
                .then(data => {
                    if (data.success) {
                        const candidate = data.candidate;

                        // Populate form fields
                        document.getElementById('candidate-id').value = candidate.id;
                        document.getElementById('candidate-name').value = candidate.name;
                        document.getElementById('candidate-email').value = candidate.email;
                        document.getElementById('candidate-phone').value = candidate.phone || '';
                        document.getElementById('candidate-position').value = candidate.position_applied;
                        document.getElementById('candidate-experience').value = candidate.experience_level || '';
                        document.getElementById('candidate-company').value = candidate.current_company || '';
                        document.getElementById('candidate-source').value = candidate.source || '';
                        document.getElementById('candidate-status').value = candidate.status || 'Applied';

                        // Show modal
                        new bootstrap.Modal(candidateModal).show();
                    } else {
                        displayMessage(`Error loading candidate: ${data.error}`, 'danger', 'main');
                    }
                })
                .catch(error => {
                    console.error('Error fetching candidate:', error);
                    displayMessage('An error occurred while loading candidate data.', 'danger', 'main');
                });
        }

        // Function to get status badge HTML
        function getStatusBadge(status) {
            const statusClasses = {
                'Applied': 'bg-primary',
                'Screening': 'bg-info',
                'Interview Scheduled': 'bg-warning text-dark',
                'Interviewed': 'bg-secondary',
                'Second Interview': 'bg-warning text-dark',
                'Reference Check': 'bg-info',
                'Offer Extended': 'bg-success',
                'Hired': 'bg-success',
                'Rejected': 'bg-danger',
                'Withdrawn': 'bg-dark'
            };
            const badgeClass = statusClasses[status] || 'bg-secondary';
            return `<span class="badge ${badgeClass}">${status}</span>`;
        }

        // Function to fetch and display candidates
        function fetchAndDisplayCandidates() {
            // Show loading spinner
            document.getElementById('loading-spinner').style.display = 'flex';
            candidatesTable.style.display = 'none';

            apiRequest('candidates', 'list')
                .then(data => {
                    // Hide loading spinner
                    document.getElementById('loading-spinner').style.display = 'none';
                    candidatesTable.style.display = 'table';

                    if (data.success) {
                        const candidates = data.candidates;
                        let tableRows = '';

                        candidates.forEach(candidate => {
                            const applicationDate = candidate.application_date ?
                                new Date(candidate.application_date).toLocaleDateString() : 'N/A';

                            tableRows += `
                            <tr data-candidate-id="${candidate.id}"
                                data-candidate-name="${candidate.name}"
                                data-candidate-email="${candidate.email}"
                                data-candidate-position="${candidate.position_applied}"
                                data-candidate-status="${candidate.status}">
                                <td>
                                    <div class="fw-medium">${candidate.name}</div>
                                    ${candidate.phone ? `<small class="text-muted">${candidate.phone}</small>` : ''}
                                </td>
                                <td>
                                    <a href="mailto:${candidate.email}" class="text-decoration-none">
                                        ${candidate.email}
                                    </a>
                                </td>
                                <td>
                                    <span class="text-truncate-mobile">${candidate.position_applied}</span>
                                </td>
                                <td>
                                    ${getStatusBadge(candidate.status)}
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    ${candidate.experience_level ?
                                    `<span class="badge bg-secondary">${candidate.experience_level}</span>` : 'N/A'}
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <span class="text-truncate-mobile">${applicationDate}</span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group" aria-label="Candidate Actions">
                                        <a href="/hr/interview_invitations.php?candidate_id=${candidate.id}"
                                           class="btn btn-sm btn-text text-success"
                                           title="Send Interview Invitation">
                                            <i class="fas fa-paper-plane"></i>
                                            <span class="d-none d-lg-inline ms-1">Invite</span>
                                        </a>
                                        <button class="btn btn-sm btn-text text-primary edit-candidate-btn"
                                                data-candidate-id="${candidate.id}"
                                                title="Edit Candidate">
                                            <i class="fas fa-edit"></i>
                                            <span class="d-none d-lg-inline ms-1">Edit</span>
                                        </button>
                                        <?php if (is_admin()): ?>
                                        <button class="btn btn-sm btn-text text-danger delete-candidate-btn"
                                                data-candidate-id="${candidate.id}"
                                                title="Delete Candidate">
                                            <i class="fas fa-trash-alt"></i>
                                            <span class="d-none d-lg-inline ms-1">Delete</span>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        `;
                        });

                        candidatesTable.querySelector('tbody').innerHTML = tableRows;

                        // Update candidate count
                        document.getElementById('candidate-count').textContent = ' ' + candidates.length + ' ';
                    } else {
                        displayMessage(`Error loading candidates: ${data.error}`, 'danger', 'main');
                        document.getElementById('candidate-count').textContent = '0';
                    }
                })
                .catch(error => {
                    // Hide loading spinner
                    document.getElementById('loading-spinner').style.display = 'none';
                    candidatesTable.style.display = 'table';

                    console.error('Error fetching candidates:', error);
                    displayMessage('An error occurred while loading candidate data.', 'danger', 'main');
                    document.getElementById('candidate-count').textContent = '0';
                });
        }

        // Event listeners
        addCandidateBtn.addEventListener('click', openAddModal);

        // Form submission handler
        candidateForm.addEventListener('submit', function (event) {
            event.preventDefault();

            // Clear previous messages in modal
            clearMessages('modal');

            // Add Bootstrap validation classes
            this.classList.add('was-validated');

            // Check form validity
            if (!this.checkValidity()) {
                displayMessage('Please fill in all required fields correctly.', 'danger', 'modal');
                return;
            }

            handleFormSubmission(this);
        });

        // Function to handle form submission (both add and edit)
        function handleFormSubmission(form) {
            const formData = new FormData(form);
            const action = formData.get('action');
            const isEdit = action === 'update';

            fetch('/api.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayMessage(data.message, 'success', 'main');
                        bootstrap.Modal.getInstance(candidateModal).hide();
                        form.reset();
                        form.classList.remove('was-validated');
                        fetchAndDisplayCandidates();
                    } else {
                        const errorMsg = isEdit ?
                            `Error updating candidate: ${data.error}` :
                            `Error adding candidate: ${data.error}`;
                        displayMessage(errorMsg, 'danger', 'modal');
                    }
                })
                .catch(error => {
                    console.error('Error submitting form:', error);
                    const errorMsg = isEdit ?
                        'An error occurred while updating the candidate.' :
                        'An error occurred while adding the candidate.';
                    displayMessage(errorMsg, 'danger', 'modal');
                });
        }

        // Edit candidate functionality
        candidatesTable.addEventListener('click', function (event) {
            if (event.target.classList.contains('edit-candidate-btn') ||
                event.target.closest('.edit-candidate-btn')) {
                const button = event.target.closest('.edit-candidate-btn');
                const candidateId = button.dataset.candidateId;
                openEditModal(candidateId);
            }
        });

        // Delete candidate functionality
        candidatesTable.addEventListener('click', function (event) {
            if (event.target.classList.contains('delete-candidate-btn') ||
                event.target.closest('.delete-candidate-btn')) {
                const button = event.target.closest('.delete-candidate-btn');
                const candidateId = button.dataset.candidateId;
                const row = button.closest('tr');
                const candidateName = row.dataset.candidateName;

                if (confirm(`Are you sure you want to delete candidate "${candidateName}"? This action cannot be undone.`)) {
                    const formData = new FormData();
                    formData.append('entity', 'candidates');
                    formData.append('action', 'delete');
                    formData.append('id', candidateId);

                    fetch('/api.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                displayMessage(data.message, 'success', 'main');
                                fetchAndDisplayCandidates(); // Refresh the candidate list
                            } else {
                                displayMessage(`Error deleting candidate: ${data.error}`, 'danger', 'main');
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting candidate:', error);
                            displayMessage('An error occurred while deleting the candidate.', 'danger', 'main');
                        });
                }
            }
        });

        // Search functionality
        const candidateSearchInput = document.getElementById('candidate-search');

        function filterCandidates() {
            const searchTerm = candidateSearchInput.value.toLowerCase();
            const rows = candidatesTable.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const name = row.dataset.candidateName.toLowerCase();
                const email = row.dataset.candidateEmail.toLowerCase();
                const position = row.dataset.candidatePosition.toLowerCase();
                const status = row.dataset.candidateStatus.toLowerCase();

                if (name.includes(searchTerm) || email.includes(searchTerm) ||
                    position.includes(searchTerm) || status.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        candidateSearchInput.addEventListener('keyup', function (event) {
            const searchTerm = candidateSearchInput.value;
            if (searchTerm.length >= 2 || searchTerm.length === 0) {
                filterCandidates();
            }
        });

        // Reset form and clear messages when modal is shown/hidden
        candidateModal.addEventListener('show.bs.modal', function () {
            clearMessages('modal');
        });

        candidateModal.addEventListener('hidden.bs.modal', function () {
            candidateForm.reset();
            candidateForm.classList.remove('was-validated');
            clearMessages('modal');
        });

        fetchAndDisplayCandidates(); // Initial load of candidates
    });
</script>

<style>
    /* Responsive design for candidates table */
    @media (max-width: 768px) {

        /* Hide less important columns on mobile */
        #candidates-table th:nth-child(5),
        /* Experience */
        #candidates-table td:nth-child(5),
        #candidates-table th:nth-child(6),
        /* Applied Date */
        #candidates-table td:nth-child(6) {
            display: none;
        }
    }

    @media (max-width: 576px) {

        /* On very small screens, make badges smaller */
        .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.4rem;
        }

        /* Truncate long text */
        .text-truncate-mobile {
            max-width: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
        }

        /* Make action buttons stack vertically on very small screens */
        #candidates-table .btn-group {
            flex-direction: column;
            width: 100%;
        }

        #candidates-table .btn-group .btn {
            margin-bottom: 0.25rem;
            border-radius: 0.25rem !important;
        }

        /* Allow text wrapping in table cells on mobile */
        #candidates-table td,
        #candidates-table th {
            white-space: normal;
            word-wrap: break-word;
        }
    }

    /* Badge styling */
    .badge.bg-secondary {
        background-color: #6c757d !important;
        color: white;
        font-size: 0.75rem;
        font-weight: 500;
    }

    /* Ensure table fits within container */


    /* Loading spinner styling */
    #loading-spinner {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    /* Search section styling */
    .search-section {
        margin-bottom: 1.5rem;
    }

    /* Ensure container doesn't overflow */
    .container.emp {
        overflow-x: hidden;
    }

    /* Modal fieldset styling */
    fieldset {
        border: 1px solid #dee2e6 !important;
        border-radius: 0.375rem;
    }

    fieldset legend {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0;
        padding: 0 0.5rem;
        width: auto;
        font-size: 0.9rem !important;
    }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>