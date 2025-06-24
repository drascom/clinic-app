<?php
include __DIR__ . '/../includes/header.php';

$page_title = "Staff Management";
?>

<div class="container ">
    <!-- Loading Spinner -->
    <div id="loading-spinner" class="text-center py-4" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="card">
        <div class="p-4 pb-2">
            <!-- Page Header -->
            <h4 class="mb-3">
                <i class="fas fa-users me-2 text-primary"></i>
                <?php echo $page_title; ?>
            </h4>



            <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="position-relative search-expand">
                    <div class="position-relative">
                        <input type="text" id="search-input" class="form-control ps-5 pe-5"
                            placeholder="Search staff by name, email, phone, location, position, or specialty...">
                        <i
                            class="fas fa-search position-absolute top-50 start-0 translate-middle-y ps-3 text-muted"></i>
                        <button type="button" id="clear-search"
                            class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2 p-0 border-0 bg-transparent text-muted"
                            title="Clear search" style="line-height:1;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="text-muted small d-none d-lg-inline text-center">
                        <i class="fas fa-info-circle me-1"></i>
                        <span id="candidate-count">Loading...</span> candidates |
                        <span id="staff-total-count">Loading...</span> staff members
                    </div>
                </div>
                <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#staffModal"
                    id="addStaffBtn">
                    <i class="fas fa-plus me-1"></i>
                    Add<span class="d-none d-sm-inline"> New Staff</span>
                </button>
            </div>




        </div>
        <div class="card-body">
            <!-- Tab Navigation -->
            <ul class="nav nav-tabs justify-content-center mb-4" id="staffTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="candidates-tab" data-bs-toggle="tab"
                        data-bs-target="#candidates-pane" type="button" role="tab" aria-controls="candidates-pane"
                        aria-selected="true">
                        <i class="fas fa-user-tie me-2"></i>Candidates
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="staff-tab" data-bs-toggle="tab" data-bs-target="#staff-pane"
                        type="button" role="tab" aria-controls="staff-pane" aria-selected="false">
                        <i class="fas fa-users-cog me-2"></i>Staff
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="/staff/staff-availability.php" class="nav-link d-flex align-items-center px-3">
                        <i class="far fa-calendar me-2"></i>
                        <span class="d-none d-sm-inline"> Staff </span>Calendar
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="staffTabsContent">
                <!-- Candidates Tab Pane -->
                <div class="tab-pane fade show active" id="candidates-pane" role="tabpanel"
                    aria-labelledby="candidates-tab" tabindex="0">
                    <div class="table-responsive">
                        <table class="table table-hover" id="candidatesTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th class="d-none d-lg-table-cell">Location</th>
                                    <th class="d-none d-lg-table-cell">Specialty</th>
                                    <th class="d-none d-md-table-cell">Daily Fee</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Candidate rows will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Staff Tab Pane -->
                <div class="tab-pane fade" id="staff-pane" role="tabpanel" aria-labelledby="staff-tab" tabindex="0">
                    <div class="table-responsive">
                        <table class="table table-hover" id="staffTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th class="d-none d-lg-table-cell">Location</th>
                                    <th class="d-none d-lg-table-cell">Specialty</th>
                                    <th class="d-none d-md-table-cell">Daily Fee</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Staff rows will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <nav aria-label="Staff pagination" id="pagination-container" class="mt-4">
                <!-- Pagination will be populated by JavaScript -->
            </nav>
        </div>
    </div>
</div>

<!-- Add/Edit Staff Modal -->
<div class="modal fade" id="staffModal" tabindex="-1" aria-labelledby="staffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staffModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Add New Staff
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="staff-form" novalidate>
                <input type="hidden" id="staff_id" name="id">
                <div class="modal-body">
                    <!-- General Error Alert -->
                    <div class="alert alert-danger d-none" id="form-error-alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="form-error-message"></span>
                    </div>

                    <div class="row g-3">
                        <!-- Staff Information -->
                        <div class="col-12">
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-id-card me-1"></i>Staff Information
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-sm-6 col-md-12 col-lg-6">
                                    <label class="form-label">Status <span class="text-danger">*</span></label></br>
                                    <div class="btn-group" role="group" aria-label="Staff status selection">
                                        <input type="radio" class="btn-check" name="is_active" id="is_active_1" value="1" autocomplete="off" required>
                                        <label class="btn btn-outline-success" for="is_active_1">Active</label>

                                        <input type="radio" class="btn-check" name="is_active" id="is_active_0" value="0" autocomplete="off" required>
                                        <label class="btn btn-outline-danger" for="is_active_0">Inactive</label>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-sm-6 col-md-12 col-lg-6">
                                    <label class="form-label">Staff Type <span class="text-danger">*</span></label></br>
                                    <div class="btn-group" role="group" aria-label="Staff type selection">
                                        <input type="radio" class="btn-check" name="staff_type" id="staff_type_candidate" value="candidate" autocomplete="off" required>
                                        <label class="btn btn-outline-primary" for="staff_type_candidate">Candidate</label>

                                        <input type="radio" class="btn-check" name="staff_type" id="staff_type_staff" value="staff" autocomplete="off" required>
                                        <label class="btn btn-outline-primary" for="staff_type_staff">Staff</label>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="location" name="location" required>
                            <div class="invalid-feedback"></div>
                        </div>


                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                            <div class="invalid-feedback"></div>
                        </div>




                        <div class="col-md-6 staff-detail-field">
                            <label for="speciality" class="form-label">Specialty</label>
                            <input type="text" class="form-control" id="speciality" name="speciality">
                            <div class="invalid-feedback"></div>
                        </div>



                        <!-- Staff Details (Conditional) -->
                        <div class="col-12 mt-4" id="staff-details-section">
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-info-circle me-1"></i>Additional Details (for candidates)
                            </h6>
                        </div>

                        <div class="col-md-6 staff-detail-field">
                            <label for="position_applied" class="form-label">Position Applied</label>
                            <input type="text" class="form-control" id="position_applied" name="position_applied">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 staff-detail-field">
                            <label for="source" class="form-label">Source<span class="text-danger">*</span></label>
                            <select class="form-select" id="source" name="source" required>
                                <option value="">Select source...</option>
                                <option value="Website">Website</option>
                                <option value="LinkedIn">LinkedIn</option>
                                <option value="Indeed">Indeed</option>
                                <option value="Referral">Referral</option>
                                <option value="Agency">Agency</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>


                        <div class="col-md-6 staff-detail-field">
                            <label for="experience_level" class="form-label">Experience Level<span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="experience_level" name="experience_level" required>
                                <option value="">Select experience level...</option>
                                <option value="entry level">Entry Level</option>
                                <option value="junior">Junior</option>
                                <option value="mid-level">Mid level</option>
                                <option value="senior">Senior</option>
                                <option value="expert">Expert</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 staff-detail-field">
                            <label for="linkedin_profile" class="form-label">LinkedIn Profile</label>
                            <input type="url" class="form-control" id="linkedin_profile" name="linkedin_profile"
                                placeholder="https://...">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 staff-detail-field">
                            <label for="current_company" class="form-label">Current Company</label>
                            <input type="text" class="form-control" id="current_company" name="current_company">
                            <div class="invalid-feedback"></div>
                        </div>




                        <div class="col-md-6 staff-detail-field">
                            <label for="salary_expectation" class="form-label">Salary Expectation</label>
                            <div class="input-group">
                                <div class="input-group-text" id="salary_expectation_input">£</div>
                                <input type="number" class="form-control" placeholder="Enter Amount" aria-label="salary expectation" aria-describedby="salary_expectation_input" id="salary_expectation" name="salary_expectation">
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 staff-detail-field">
                            <label for="willing_to_relocate" class="form-label">Willing to Relocate</label></br>
                            <div class="btn-group" role="group" aria-label="Staff type selection">
                                <input type="radio" class="btn-check" name="willing_to_relocate" id="willing_to_relocate_1" value="1" autocomplete="off" required>
                                <label class="btn btn-outline-primary" for="willing_to_relocate_1">Yes</label>
                                <input type="radio" class="btn-check" name="willing_to_relocate" id="willing_to_relocate_0" value="0" autocomplete="off" required>
                                <label class="btn btn-outline-primary" for="willing_to_relocate_0">No</label>
                            </div>
                        </div>
                        <div class="col-md-6 staff-detail-field">
                            <label for="daily_fee" class="form-label">Daily Fee</label>
                            <div class="input-group">
                                <div class="input-group-text">£</div>
                                <input type="number" class="form-control" placeholder="Enter Amount" aria-label="daily fee" aria-describedby="daily_fee_input" id="daily_fee_input" name="daily_fee" min="0">
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>


                    </div>
                </div>
                <div class="modal-footer mb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="saveStaffBtn">
                        <i class="fas fa-save me-1"></i>Save Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Staff Details Modal -->
<div class="modal fade" id="viewStaffModal" tabindex="-1" aria-labelledby="viewStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewStaffModalLabel">
                    <i class="fas fa-user me-2"></i>Staff Name
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Loading state -->
                <div id="staff-loading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                    <p class="text-muted">Loading staff details...</p>
                </div>

                <!-- Error state -->
                <div id="staff-error" class="alert alert-danger d-none">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="staff-error-message"></span>
                </div>

                <!-- Staff details content -->
                <div id="staff-details-content" class="d-none">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editStaffFromViewBtn">
                    <i class="fas fa-edit me-1"></i>Edit
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationModalLabel">
                    <i class="fas fa-question-circle me-2"></i>Confirm Action
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="confirmation-message">Are you sure you want to perform this action?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-action-btn">Confirm</button>
            </div>
        </div>
    </div>
</div>


<!-- API Helper for secure POST requests -->
<script src="/assets/js/api-helper.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Global variables
        let currentPage = 1;
        let currentSearch = '';
        let searchTimeout = null;
        let confirmationCallback = null;
        const isAdmin = <?php echo is_admin() ? 'true' : 'false'; ?>;
        const isEditor = <?php echo is_editor() ? 'true' : 'false'; ?>;

        // DOM elements
        const staffForm = document.getElementById('staff-form');
        const staffModalLabel = document.getElementById('staffModalLabel');
        const candidatesTableBody = document.getElementById('candidatesTable').querySelector('tbody');
        const staffTableBody = document.getElementById('staffTable').querySelector('tbody');
        const statusMessages = document.getElementById('status-messages');
        const saveStaffBtn = document.getElementById('saveStaffBtn');
        const confirmActionBtn = document.getElementById('confirm-action-btn');
        const staffDetailsSection = document.getElementById('staff-details-section');
        const staffDetailFields = document.querySelectorAll('.staff-detail-field');
        const staffTypeRadios = document.querySelectorAll('input[name="staff_type"]');
        const isActiveRadios = document.querySelectorAll('input[name="is_active"]');
        const editStaffFromViewBtn = document.getElementById('editStaffFromViewBtn');

        // Global variable to track current active tab
        let currentStaffTypeFilter = 'candidate'; // Default to 'candidate' tab

        // Initialize page
        loadStaff(1, currentStaffTypeFilter); // Load candidates by default
        setupEventListeners();
        setupFormValidation();
        updateFieldRequirements(); // Set initial state
        updateSubmitButtonState(); // Set initial button state

        /**
         * Setup event listeners
         */
        function setupEventListeners() {
            // Form submission
            staffForm.addEventListener('submit', handleFormSubmit);

            // Confirm action button
            confirmActionBtn.addEventListener('click', function() {
                if (confirmationCallback) {
                    confirmationCallback();
                    confirmationCallback = null;
                }
                bootstrap.Modal.getInstance(document.getElementById('confirmationModal')).hide();
            });

            // Search functionality
            const searchInput = document.getElementById('search-input');
            const clearSearchBtn = document.getElementById('clear-search');

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.trim();

                    // Clear previous timeout
                    if (searchTimeout) {
                        clearTimeout(searchTimeout);
                    }

                    // Debounce search - wait 300ms after user stops typing
                    searchTimeout = setTimeout(() => {
                        currentSearch = searchTerm;
                        // loadStaff(1);
                        loadStaff(1, currentStaffTypeFilter);
                    }, 300);
                });
            }


            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    currentSearch = '';
                    loadStaff(1);
                });
            }

            // Reset form and reload staff list when modal is hidden
            document.getElementById('staffModal').addEventListener('hidden.bs.modal', function() {
                resetForm();
                loadStaff(); // Reload staff list after modal is fully hidden
                removeModalBackdrops
                    (); // Ensure any lingering backdrops are removed - Let Bootstrap handle this
                document.activeElement.blur(); // Remove focus from any element inside the hidden modal
            });

            // Toggle staff details section based on staff type
            staffTypeRadios.forEach(radio => {
                radio.addEventListener('change', () => {
                    toggleStaffDetails();
                    updateFieldRequirements();
                    updateSubmitButtonState();
                });
            });

            isActiveRadios.forEach(radio => {
                radio.addEventListener('change', () => validateField(radio));
            });

            // Edit button from view modal
            editStaffFromViewBtn.addEventListener('click', function() {
                const staffId = this.dataset.staffId;
                bootstrap.Modal.getInstance(document.getElementById('viewStaffModal')).hide();
                editStaff(staffId);
            });

            // Tab switching
            document.getElementById('candidates-tab').addEventListener('shown.bs.tab', function(event) {
                console.log('Tab clicked:', event.target.id); // Log the ID of the activated tab
                currentStaffTypeFilter = 'candidate';
                loadStaff(1, currentStaffTypeFilter);
            });

            document.getElementById('staff-tab').addEventListener('shown.bs.tab', function(event) {
                console.log('Tab clicked:', event.target.id); // Log the ID of the activated tab
                currentStaffTypeFilter = 'staff';
                loadStaff(1, currentStaffTypeFilter);
            });
        }

        /**
         * Toggle visibility of staff details fields based on staff type
         */
        function toggleStaffDetails() {
            const selectedStaffType = document.querySelector('input[name="staff_type"]:checked')?.value;
            const isCandidate = selectedStaffType === 'candidate';

            staffDetailsSection.classList.toggle('d-none', !isCandidate);
            staffDetailFields.forEach(field => field.classList.toggle('d-none', !isCandidate));

            if (!isCandidate) {
                // Clear values and validation when hidden
                staffDetailFields.forEach(field => {
                    const input = field.querySelector('input:not([type="radio"]), select, textarea');
                    const radioGroup = field.querySelector('.btn-group[role="group"]');

                    if (input) {
                        if (input.type === 'checkbox' || input.type === 'radio') {
                            input.checked = false;
                        } else {
                            input.value = '';
                        }
                        input.classList.remove('is-valid', 'is-invalid');
                    } else if (radioGroup) {
                        radioGroup.querySelectorAll('input[type="radio"]').forEach(radio => {
                            radio.checked = false;
                        });
                        const feedback = radioGroup.nextElementSibling;
                        if (feedback) feedback.textContent = '';
                    }
                });
            }
        }


        /**
         * Dynamically set required attribute for candidate fields
         */
        function updateFieldRequirements() {
            const selectedStaffType = document.querySelector('input[name="staff_type"]:checked')?.value;
            const isCandidate = selectedStaffType === 'candidate';

            const candidateFieldIds = [
                'position_applied', 'source', 'experience_level', 'linkedin_profile',
                'current_company', 'salary_expectation', 'daily_fee_input'
            ];
            const candidateRadioNames = ['willing_to_relocate'];

            candidateFieldIds.forEach(id => {
                const field = document.getElementById(id);
                if (field) {
                    if (isCandidate) {
                        field.setAttribute('required', '');
                    } else {
                        field.removeAttribute('required');
                        // Clean up validation state
                        const container = field.closest('.input-group') || field;
                        container.classList.remove('is-invalid', 'is-valid');
                        field.classList.remove('is-invalid', 'is-valid');
                        const formGroup = field.closest('.col-md-6');
                        if (formGroup) {
                            const feedback = formGroup.querySelector('.invalid-feedback');
                            if (feedback) feedback.textContent = '';
                        }
                    }
                }
            });

            candidateRadioNames.forEach(name => {
                const radios = document.querySelectorAll(`input[name="${name}"]`);
                if (radios.length > 0) {
                    radios.forEach(radio => {
                        if (isCandidate) {
                            radio.setAttribute('required', '');
                        } else {
                            radio.removeAttribute('required');
                        }
                    });

                    // Clean up validation state
                    if (!isCandidate) {
                        const container = radios[0].closest('.btn-group');
                        if (container) {
                            container.classList.remove('is-invalid', 'is-valid');
                            const formGroup = container.closest('.col-md-6, .col-lg-6');
                            if (formGroup) {
                                const feedback = formGroup.querySelector('.invalid-feedback');
                                if (feedback) feedback.textContent = '';
                            }
                        }
                    }
                }
            });
        }


        /**
         * Updates the state of the submit button based on form validity.
         */
        function updateSubmitButtonState() {
            const isFormValid = validateForm(false); // Validate without showing errors
            saveStaffBtn.disabled = !isFormValid;
        }


        /**
         * Setup form validation
         */
        function setupFormValidation() {
            const form = staffForm;
            const inputs = form.querySelectorAll(
                'input:not([type="radio"]), select, textarea');

            inputs.forEach(input => {
                const eventType = input.tagName === 'SELECT' ? 'change' : 'blur';
                input.addEventListener(eventType, function() {
                    validateField(this, true); // Show error on blur/change
                    updateSubmitButtonState();
                });

                input.addEventListener('input', function() {
                    if (this.classList.contains('is-invalid')) {
                        validateField(this,
                            true); // Re-validate on input to clear error when valid
                    }
                    updateSubmitButtonState();
                });
            });

            form.querySelectorAll('input[type="radio"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    validateField(this, true); // Show error on change
                    updateSubmitButtonState();
                });
            });
        }

        /**
         * Validate individual field
         */
        function validateField(field, showUIError = false) {
            let isValid = true;
            let message = '';
            const isRequired = field.hasAttribute('required');

            // For radio buttons, check the whole group
            if (field.type === 'radio') {
                const groupName = field.name;
                const group = document.querySelectorAll(`input[name="${groupName}"]`);
                const isChecked = Array.from(group).some(radio => radio.checked);
                if (isRequired && !isChecked) {
                    isValid = false;
                    message = 'Please select an option.';
                }
                // Apply feedback to the container
                const container = field.closest('.btn-group');
                if (container) {
                    updateValidationUI(container, isValid, message, showUIError);
                }
                return isValid;
            }


            const value = field.value.trim();

            if (isRequired && value === '') {
                isValid = false;
                message = 'This field is required.';
            } else if (field.type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                isValid = false;
                message = 'Please enter a valid email address.';
            } else if (field.type === 'url' && value) {
                try {
                    new URL(value);
                } catch (_) {
                    isValid = false;
                    message = 'Please enter a valid URL (e.g., https://example.com).';
                }
            } else if (field.type === 'number' && field.min && value !== '' && parseFloat(value) < parseFloat(field
                    .min)) {
                isValid = false;
                message = `Value must be at least ${field.min}.`;
            }

            updateValidationUI(field, isValid, message, showUIError);
            return isValid;
        }


        /**
         * Updates the UI to show or hide validation feedback.
         */
        function updateValidationUI(element, isValid, message, showUIError) {
            const container = element.closest('.input-group, .btn-group');
            const elementToStyle = container || element;
            let formGroup = element.closest('.col-md-6, .col-sm-6, .col-lg-6');
            if (!formGroup) {
                formGroup = element.closest('.staff-detail-field');
            }
            const feedbackElement = formGroup ? formGroup.querySelector('.invalid-feedback') : null;


            // Always remove both classes before adding the correct one
            elementToStyle.classList.remove('is-valid', 'is-invalid');
            element.classList.remove('is-valid', 'is-invalid');

            if (isValid) {
                if (showUIError && (element.value || (element.type === 'radio' && document.querySelector(
                        `input[name="${element.name}"]:checked`)))) {
                    elementToStyle.classList.add('is-valid');
                }
                if (feedbackElement) feedbackElement.textContent = '';
            } else { // If invalid
                if (showUIError) {
                    elementToStyle.classList.add('is-invalid');
                    if (feedbackElement) feedbackElement.textContent = message;
                }
            }
        }

        /**
         * Validate entire form
         */
        function validateForm(showUIErrors = false) {
            let isFormValid = true;
            let firstInvalidField = null;

            // Get all fields that might require validation
            const fieldsToValidate = staffForm.querySelectorAll(
                'input, select, textarea'
            );

            fieldsToValidate.forEach(field => {
                // Only validate visible fields
                if (field.offsetParent !== null) {
                    const isFieldValid = validateField(field, showUIErrors);
                    if (!isFieldValid) {
                        isFormValid = false;
                        if (!firstInvalidField) {
                            firstInvalidField = field;
                        }
                    }
                }
            });

            if (!isFormValid && showUIErrors && firstInvalidField) {
                firstInvalidField.focus();
                showFormError('Please correct the highlighted errors.');
            }

            return isFormValid;
        }

        /**
         * Handle form submission (Add/Edit Staff)
         */
        async function handleFormSubmit(e) {
            e.preventDefault();

            hideFormError();

            if (!validateForm(true)) { // Pass true to show errors on submit
                return;
            }
            const formData = new FormData(staffForm);
            const data = Object.fromEntries(formData.entries());
            console.log('handleFormSubmit: Submitting form data', data);

            // Convert relevant fields to integers
            data.is_active = parseInt(data.is_active);
            data.willing_to_relocate = parseInt(data.willing_to_relocate);
            if (data.daily_fee) {
                data.daily_fee = parseInt(data.daily_fee);
            }

            const staffId = document.getElementById('staff_id').value;
            const action = staffId ? 'update' : 'add'; // Determine if adding or updating

            // Show loading state
            saveStaffBtn.disabled = true;
            saveStaffBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

            try {
                if (action === 'add') {
                    data.created_by = currentUserId;
                } else {
                    data.updated_by = currentUserId;
                }
                const response = await apiRequest('/api.php', 'POST', {
                    entity: 'staff',
                    action: action,
                    ...data
                });

                if (response.success) {
                    showSuccessMessage(response.message);
                    const staffModalInstance = bootstrap.Modal.getInstance(document.getElementById(
                        'staffModal'));
                    if (staffModalInstance) {
                        staffModalInstance.hide();
                    } else {
                        console.warn('handleFormSubmit: staffModal instance not found.');
                    }
                    // removeModalBackdrops() // Let Bootstrap handle this
                    // resetForm() and loadStaff() are now handled by the 'hidden.bs.modal' event listener
                } else {
                    console.error('handleFormSubmit: Failed to save staff', response.error);
                    showFormError(response.error || `Failed to ${action} staff.`);
                }
            } catch (error) {
                console.error(`handleFormSubmit: An error occurred while ${action}ing staff:`, error);
                showFormError(`An error occurred while ${action}ing staff.`);
            } finally {
                // Reset button state
                saveStaffBtn.disabled = false;
                saveStaffBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save Staff';
            }
        }

        /**
         * Load staff list
         */
        async function loadStaff(page = 1, staffTypeFilter = currentStaffTypeFilter) {
            currentPage = page;

            try {
                const requestData = {
                    entity: 'staff',
                    action: 'list',
                    page: page,
                    limit: 20,
                    staff_type: staffTypeFilter // Add staff_type filter
                };

                // Add search parameter if there's a search term
                if (currentSearch) {
                    requestData.search = currentSearch;
                }

                const response = await apiRequest('/api.php', 'POST', requestData);

                if (response.success) {
                    if (staffTypeFilter === 'candidate') {
                        renderStaffTable('candidatesTable', response.staff);
                    } else {
                        renderStaffTable('staffTable', response.staff);
                    }
                    renderPagination(response.pagination);
                    updateStaffCount(response.pagination); // Pass the entire pagination object
                } else {
                    showErrorMessage('Failed to load staff: ' + (response.error || 'Unknown error'));
                    updateStaffCount({
                        total: 0,
                        candidate_total: 0,
                        staff_total: 0
                    }); // Pass default counts on error
                }
            } catch (error) {
                console.error('Error loading staff:', error);
                showErrorMessage('An error occurred while loading staff.');
                updateStaffCount({
                    total: 0,
                    candidate_total: 0,
                    staff_total: 0
                }); // Pass default counts on error
            }
        }

        /**
         * Render staff table
         */
        function renderStaffTable(tableId, staffMembers) {
            const tableBody = document.getElementById(tableId).querySelector('tbody');

            if (!staffMembers || staffMembers.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-users-slash fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No staff members found</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = staffMembers.map(staff => {
                const staffTypeBadge = staff.staff_type === 'candidate' ?
                    '<span class="badge bg-info">candidate</span>' :
                    '<span class="badge bg-primary">Staff</span>'; // Changed 'Tech' to 'Staff'
                const isActiveBadge = staff.is_active == 1 ?
                    '<span class="badge bg-success">Active</span>' :
                    '<span class="badge bg-danger">Inactive</span>';

                return `
                    <tr>
                        <td><span class="fw-medium" style="cursor: pointer;" onclick="viewStaff(${staff.id})">${escapeHtml(staff.name)}</span></td>
                        <td><span class="text-truncate-mobile">${escapeHtml(staff.email)}</span></td>
                        <td >${escapeHtml(staff.phone || 'N/A')}</td>
                        <td class="d-none d-lg-table-cell">${escapeHtml(staff.location || 'N/A')}</td>
                        <td class="d-none d-lg-table-cell">${escapeHtml(staff.speciality || 'N/A')}</td>
                        <td class="d-none d-md-table-cell">${staff.daily_fee !== null ? escapeHtml(staff.daily_fee) : 'N/A'}</td>
                        <td>
                            <div class="btn-group" role="group">
                             ${staff.staff_type === 'candidate' ? `
                                <a href="/staff/send_interview_invitation.php?candidate_id=${staff.id}" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" data-bs-placement="top" title="Send Interview Invitation">
                                    <i class="fas fa-paper-plane"></i>
                                </a>
                            ` : ''}
                              <!--  <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewStaff(${staff.id})" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-toggle="tooltip" data-bs-placement="top" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button> -->
                                ${isAdmin ? `
                                <button type="button" class="btn btn-sm btn-outline-info" onclick="editStaff(${staff.id})" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                            
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteStaff(${staff.id}, '${escapeHtml(staff.name)}')" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        /**
         * Render pagination
         */
        function renderPagination(pagination) {
            const container = document.getElementById('pagination-container');

            if (pagination.total_pages <= 1) {
                container.innerHTML = '';
                return;
            }

            let paginationHtml = '<ul class="pagination justify-content-center">';

            // Previous button
            if (pagination.current_page > 1) {
                paginationHtml +=
                    `<li class="page-item"><a class="page-link" href="#" onclick="window.loadStaff(${pagination.current_page - 1}, '${currentStaffTypeFilter}')">Previous</a></li>`;
            }

            // Page numbers
            for (let i = Math.max(1, pagination.current_page - 2); i <= Math.min(pagination.total_pages, pagination
                    .current_page + 2); i++) {
                const activeClass = i === pagination.current_page ? 'active' : '';
                paginationHtml +=
                    `<li class="page-item ${activeClass}"><a class="page-link" href="#" onclick="window.loadStaff(${i}, '${currentStaffTypeFilter}')">${i}</a></li>`;
            }

            // Next button
            if (pagination.current_page < pagination.total_pages) {
                paginationHtml +=
                    `<li class="page-item"><a class="page-link" href="#" onclick="window.loadStaff(${pagination.current_page + 1}, '${currentStaffTypeFilter}')">Next</a></li>`;
            }

            paginationHtml += '</ul>';
            container.innerHTML = paginationHtml;
        }

        /**
         * Update staff count display
         */
        function updateStaffCount(pagination) {
            const candidateCountElement = document.getElementById('candidate-count');
            const staffTotalCountElement = document.getElementById('staff-total-count');

            if (candidateCountElement) {
                candidateCountElement.textContent = pagination.candidate_total;
            }
            if (staffTotalCountElement) {
                staffTotalCountElement.textContent = pagination.staff_total;
            }
        }

        /**
         * Reset form
         */
        function resetForm() {
            staffForm.reset();
            document.getElementById('staff_id').value = ''; // Clear hidden ID
            staffForm.classList.remove('was-validated');
            staffModalLabel.innerHTML = '<i class="fas fa-user-plus me-2"></i>Add New Staff'; // Reset modal title

            // Clear validation states for text/select inputs
            const inputs = staffForm.querySelectorAll('input:not([type="radio"]), select, textarea');
            inputs.forEach(input => {
                input.classList.remove('is-valid', 'is-invalid');
            });

            // Clear validation states for radio button groups
            staffForm.querySelectorAll('.btn-group').forEach(group => {
                group.classList.remove('is-valid', 'is-invalid');
                const feedback = group.nextElementSibling;
                if (feedback) feedback.textContent = '';
            });

            // Uncheck all radio buttons
            staffForm.querySelectorAll('input[type="radio"]').forEach(radio => {
                radio.checked = false;
            });

            hideFormError();
            toggleStaffDetails(); // Reset visibility of staff details
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

        /**
         * Escape HTML to prevent XSS
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * Explicitly remove all modal backdrops
         */
        function removeModalBackdrops() {
            console.log('removeModalBackdrops: Attempting to remove all modal backdrops.');
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => {
                backdrop.remove();
                console.log('removeModalBackdrops: Removed a backdrop.');
            });
        }

        // Global functions for onclick handlers
        window.loadStaff = loadStaff;
        window.viewStaff = async function(id) {
            console.log('viewStaff: Attempting to view staff with ID:', id);
            const modal = new bootstrap.Modal(document.getElementById('viewStaffModal'));
            const loadingDiv = document.getElementById('staff-loading');
            const errorDiv = document.getElementById('staff-error');
            const contentDiv = document.getElementById('staff-details-content');
            const errorMessage = document.getElementById('staff-error-message');
            const editBtn = document.getElementById('editStaffFromViewBtn');

            // Reset modal state
            loadingDiv.classList.remove('d-none');
            errorDiv.classList.add('d-none');
            contentDiv.classList.add('d-none');
            editBtn.dataset.staffId = id; // Store ID for edit button

            // Show modal
            modal.show();

            try {
                const response = await apiRequest('/api.php', 'POST', {
                    entity: 'staff',
                    action: 'get',
                    id: id
                });

                if (response.success) {
                    console.log('viewStaff: Staff details loaded successfully.');
                    displayStaffDetails(response.staff);
                } else {
                    console.error('viewStaff: Failed to load staff details:', response.error);
                    showStaffError(response.error || 'Failed to load staff details.');
                }
            } catch (error) {
                console.error('viewStaff: An error occurred while loading staff details:', error);
                showStaffError('An error occurred while loading staff details.');
            }
        };

        /**
         * Display staff details in modal
         */
        function displayStaffDetails(staff) {
            const loadingDiv = document.getElementById('staff-loading');
            const contentDiv = document.getElementById('staff-details-content');
            const viewStaffModalLabel = document.getElementById(
                'viewStaffModalLabel'); // Get the modal title element

            // Set the modal title to the staff member's name
            viewStaffModalLabel.innerHTML = `<i class="fas fa-user me-2"></i>${escapeHtml(staff.name)}`;

            const staffTypeBadge = staff.staff_type === 'candidate' ?
                '<span class="badge bg-info fs-6">candidate</span>' :
                '<span class="badge bg-primary fs-6">Tech</span>';
            const isActiveBadge = staff.is_active == 1 ?
                '<span class="badge bg-success fs-6">Active</span>' :
                '<span class="badge bg-danger fs-6">Inactive</span>';
            const isWillingToMove = staff.willing_to_relocate == 1 ?
                '<span class="badge bg-success fs-6">Yes</span>' :
                '<span class="badge bg-danger fs-6">No</span>';

            let staffDetailsHtml = '';
            staffDetailsHtml = `
                    <div class="col-12 mt-4">
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-info-circle me-2"></i>Additional Details
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <span class="form-label fw-bold text-muted me-2">Specialty:</span>
                                    <span>${escapeHtml(staff.speciality || 'N/A')}</span>
                                </div>
                                <div class="mb-2">
                                    <span class="form-label fw-bold text-muted me-2">Experience Level:</span>
                                    <span>${escapeHtml(staff.experience_level || 'N/A')}</span>
                                </div>
                                <div class="mb-2">
                                    <span class="form-label fw-bold text-muted me-2">Current Company:</span>
                                    <span>${escapeHtml(staff.current_company || 'N/A')}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <span class="form-label fw-bold text-muted me-2">LinkedIn Profile:</span>
                                    <span>
                                        ${staff.linkedin_profile ? `<a href="${escapeHtml(staff.linkedin_profile)}" target="_blank" class="text-decoration-none">
                                            <i class="fas fa-external-link-alt me-1"></i>${escapeHtml(staff.linkedin_profile)}
                                        </a>` : 'N/A'}
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <span class="form-label fw-bold text-muted me-2">Source:</span>
                                    <span>${escapeHtml(staff.source || 'N/A')}</span>
                                </div>
                                <div class="mb-2">
                                    <span class="form-label fw-bold text-muted me-2">Salary Expectation:</span>
                                    <span>${escapeHtml(staff.salary_expectation || 'N/A')}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <span class="form-label fw-bold text-muted me-2">Daily Fee:</span>
                                    <span>${staff.daily_fee !== null ? escapeHtml(staff.daily_fee) : 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

            const content = `
                <div style="line-height: 1.6; color: #333;">
                    <div class="row g-4">
                        <!-- Basic Staff Information -->
                        <div class="col-12">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-id-card me-2"></i>Basic Information
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <span class="form-label fw-bold text-muted me-2">Email:</span>
                                        <span>
                                            <a href="mailto:${escapeHtml(staff.email)}" class="text-decoration-none">
                                                ${escapeHtml(staff.email)}
                                            </a>
                                        </span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="form-label fw-bold text-muted me-2">Phone:</span>
                                        <span>${escapeHtml(staff.phone || 'N/A')}</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="form-label fw-bold text-muted me-2">Location:</span>
                                        <span>${escapeHtml(staff.location || 'N/A')}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <span class="form-label fw-bold text-muted me-2">Position Applied:</span>
                                        <span>${escapeHtml(staff.position_applied || 'N/A')}</span>
                                    </div>
                                  <div class="mb-2 d-flex justify-content-between">
                                        <span class="form-label fw-bold text-muted me-2">Staff Type:</span>
                                        <span>${staffTypeBadge}</span>
                                    </div>
                                    <div class="mb-2 d-flex justify-content-between">
                                        <span class="form-label fw-bold text-muted me-2">Status:</span>
                                        <span>${isActiveBadge}</span>
                                    </div> 
                                    <div class="mb-2 d-flex justify-content-between">
                                        <span class="form-label fw-bold text-muted me-2">Willing to Relocate:</span>
                                        <span>${isWillingToMove}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        ${staffDetailsHtml}
                    </div>
                </div>
            `;

            contentDiv.innerHTML = content;
            loadingDiv.classList.add('d-none');
            contentDiv.classList.remove('d-none');
        }

        /**
         * Show error in staff view modal
         */
        function showStaffError(message) {
            const loadingDiv = document.getElementById('staff-loading');
            const errorDiv = document.getElementById('staff-error');
            const errorMessage = document.getElementById('staff-error-message');

            errorMessage.textContent = message;
            loadingDiv.classList.add('d-none');
            errorDiv.classList.remove('d-none');
        }

        window.editStaff = async function(id) {
            console.log('editStaff: Attempting to edit staff with ID:', id);
            resetForm(); // Clear form and validation
            staffModalLabel.innerHTML = '<i class="fas fa-edit me-2"></i>Edit Staff'; // Change modal title
            document.getElementById('staff_id').value = id; // Set hidden ID for update

            const modal = new bootstrap.Modal(document.getElementById('staffModal'));
            modal.show();

            try {
                const response = await apiRequest('/api.php', 'POST', {
                    entity: 'staff',
                    action: 'get',
                    id: id
                });

                if (response.success) {
                    const staff = response.staff;
                    document.getElementById('name').value = staff.name;
                    document.getElementById('email').value = staff.email;
                    document.getElementById('phone').value = staff.phone;
                    document.getElementById('location').value = staff.location;
                    document.getElementById('position_applied').value = staff.position_applied || '';
                    // Set the correct radio button for staff_type
                    document.getElementById(`staff_type_${staff.staff_type}`).checked = true;
                    // Set the correct radio button for is_active
                    document.getElementById(`is_active_${staff.is_active}`).checked = true;
                    // Set the correct radio button for willing_to_relocate
                    // document.getElementById(`willing_to_relocate_${staff.willing_to_relocate}`).checked = true;
                    const willingToRelocateValue = staff.willing_to_relocate !== null ? staff.willing_to_relocate : '0';
                    const willingToRelocateRadio = document.getElementById(`willing_to_relocate_${willingToRelocateValue == 1 ? '1' : '0'}`);
                    if (willingToRelocateRadio) {
                        willingToRelocateRadio.checked = true;
                    }

                    // Populate staff details if staff type is candidate or staff
                    if (staff.staff_type === 'candidate' || staff.staff_type === 'staff') {
                        document.getElementById('speciality').value = staff.speciality || '';
                        document.getElementById('experience_level').value = staff.experience_level || '';
                        document.getElementById('current_company').value = staff.current_company || '';
                        document.getElementById('linkedin_profile').value = staff.linkedin_profile || '';
                        document.getElementById('source').value = staff.source || '';
                        document.getElementById('salary_expectation').value = staff.salary_expectation ||
                            '';

                        document.getElementById('daily_fee_input').value = staff.daily_fee !== null ? staff
                            .daily_fee :
                            '';
                    }
                    toggleStaffDetails(); // Ensure correct fields are shown/hidden
                } else {
                    showErrorMessage(response.error || 'Failed to load staff for editing.');
                    const staffModalInstance = bootstrap.Modal.getInstance(document.getElementById(
                        'staffModal'));
                    if (staffModalInstance) {
                        staffModalInstance.hide();
                    } else {
                        console.warn(
                            'editStaff: staffModal instance not found when trying to hide on error.');
                    }
                }
            } catch (error) {
                console.error('editStaff: An error occurred while loading staff for editing:', error);
                showErrorMessage('An error occurred while loading staff for editing.');
                const staffModalInstance = bootstrap.Modal.getInstance(document.getElementById(
                    'staffModal'));
                if (staffModalInstance) {
                    staffModalInstance.hide();
                } else {
                    console.warn(
                        'editStaff: staffModal instance not found when trying to hide on error in catch block.'
                    );
                }
            }
        };

        window.deleteStaff = function(id, staffName) {
            const message =
                `Are you sure you want to delete staff member "${staffName}"? This action cannot be undone.`;
            showConfirmation(message, async function() {
                try {
                    const response = await apiRequest('/api.php', 'POST', {
                        entity: 'staff',
                        action: 'delete',
                        id: id
                    });

                    if (response.success) {
                        showSuccessMessage(response.message);
                        loadStaff(currentPage);
                    } else {
                        showErrorMessage(response.error || 'Failed to delete staff member.');
                    }
                } catch (error) {
                    console.error('Error deleting staff member:', error);
                    showErrorMessage('An error occurred while deleting the staff member.');
                }
            });
        };

        /**
         * Show success message using toast
         */
        function showSuccessMessage(message) {
            showToast(message, 'success');
        }

        /**
         * Show confirmation modal
         */
        function showConfirmation(message, callback) {
            document.getElementById('confirmation-message').textContent = message;
            confirmationCallback = callback;
            new bootstrap.Modal(document.getElementById('confirmationModal')).show();
        }

        /**
         * Show error message using toast
         */
        function showErrorMessage(message) {
            showToast(message, 'danger');
        }

    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>