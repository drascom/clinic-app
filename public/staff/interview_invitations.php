<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Ensure user is logged in and is admin or editor
if (!is_logged_in() || (!is_admin() && !is_editor())) {
    header('Location: /auth/login.php');
    exit();
}

$page_title = "Interview Invitations";
include __DIR__ . '/../includes/header.php';
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
                    <i class="fas fa-envelope me-2 text-primary"></i>Interview Invitations
                </h4>
                <div class="btn-group" role="group">
                    <a href="/staff/send_interview_invitation.php" class="btn btn-outline-success">
                        <i class="fas fa-plus me-1"></i>
                        <span class="d-none d-sm-inline">Send Invitation</span>
                        <span class="d-inline d-sm-none">Send</span>
                    </a>
                </div>
            </div>
            <!-- Search Bar -->
            <fieldset class="p-4 frosted">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="invitation-search"
                        placeholder="Search invitations by candidate name, email, platform, date, or notes...">
                    <button class="btn btn-outline-secondary" type="button" id="clear-search" title="Clear search">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="text-muted small ms-4">
                    <i class="fas fa-info-circle me-1"></i>
                    <span id="invitation-count">Loading...</span> invitations found || <span
                        id="waiting-invitations"></span> waiting
                </div>
            </fieldset>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm" id="invitationsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Candidate</th>
                            <th>Email</th>
                            <th>Interview Date</th>
                            <th>Time</th>
                            <th>Platform</th>
                            <th>Status</th>
                            <th class="d-none d-lg-table-cell">Duration</th>
                            <th class="d-none d-md-table-cell">Sent By</th>
                            <th class="d-none d-lg-table-cell">Sent At</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Invitation rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Optional: Modals for Add/Edit/View Details -->
    <!-- Example: Edit Modal -->
    <div class="modal fade" id="editInvitationModal" tabindex="-1" aria-labelledby="editInvitationModalLabel"
        aria-hidden="true">
        <!-- Modal content -->
    </div>
</div>

<!-- Pagination -->
<nav aria-label="Invitations pagination" id="pagination-container" class="mt-4">
    <!-- Pagination will be populated by JavaScript -->
</nav>

<!-- Send Invitation Modal -->
<div class="modal fade" id="invitationModal" tabindex="-1" aria-labelledby="invitationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="invitationModalLabel">
                    <i class="fas fa-paper-plane me-2"></i><span id="modal-title-text">Send Interview Invitation</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="invitation-form" novalidate>
                <div class="modal-body">
                    <!-- General Error Alert -->
                    <div class="alert alert-danger d-none" id="form-error-alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="form-error-message"></span>
                    </div>

                    <div class="row g-3">
                        <input type="hidden" id="invitation_id" name="id">
                        <!-- Candidate Information -->
                        <div class="col-12">
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-user me-1"></i>Candidate Information
                            </h6>
                        </div>

                        <div class="col-12">
                            <label for="candidate_id" class="form-label">Select Candidate <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="candidate_id" name="candidate_id" required>
                                <option value="">Choose a candidate...</option>
                                <!-- Options will be populated by JavaScript -->
                            </select>
                            <div class="invalid-feedback"></div>
                            <small class="form-text text-muted">
                                Can't find the candidate? <a href="/hr/candidates.php" target="_blank">Add them to the
                                    candidates database first</a>.
                            </small>
                        </div>

                        <!-- Interview Details -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-calendar me-1"></i>Interview Details
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label for="interview_date" class="form-label">Interview Date <span
                                    class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="interview_date" name="interview_date" required>
                            <div class="invalid-feedback"></div>
                            <small class="form-text text-muted">Date of the interview</small>
                        </div>

                        <div class="col-md-6">
                            <label for="interview_time" class="form-label">Interview Time <span
                                    class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="interview_time" name="interview_time" required>
                            <div class="invalid-feedback"></div>
                            <small class="form-text text-muted">Time of the interview</small>
                        </div>

                        <div class="col-md-6">
                            <label for="meeting_platform" class="form-label">Meeting Platform <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="meeting_platform" name="meeting_platform" required>
                                <option value="">Select platform...</option>
                                <option value="Zoom">Zoom</option>
                                <option value="Google Meet">Google Meet</option>
                                <option value="Microsoft Teams">Microsoft Teams</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="invalid-feedback"></div>
                            <small class="form-text text-muted">Video conferencing platform</small>
                        </div>

                        <div class="col-md-6">
                            <label for="interview_duration" class="form-label">Interview Duration</label>
                            <select class="form-select" id="interview_duration" name="interview_duration">
                                <option value="15 minutes">15 minutes</option>
                                <option value="20 minutes" selected>20 minutes</option>
                                <option value="30 minutes">30 minutes</option>
                                <option value="45 minutes">45 minutes</option>
                                <option value="60 minutes">60 minutes</option>
                            </select>
                            <small class="form-text text-muted">Expected duration of the interview</small>
                        </div>

                        <div class="col-12">
                            <label for="meeting_link" class="form-label">Meeting Link <span
                                    class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="meeting_link" name="meeting_link" required
                                placeholder="https://...">
                            <div class="invalid-feedback"></div>
                            <small class="form-text text-muted">Full URL to the meeting room</small>
                        </div>

                        <div class="col-12">
                            <label for="video_upload_link" class="form-label">Video Upload Link</label>
                            <input type="url" class="form-control" id="video_upload_link" name="video_upload_link"
                                placeholder="https://...">
                            <div class="invalid-feedback"></div>
                            <small class="form-text text-muted">Optional: Link for candidate to upload introduction
                                video</small>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                placeholder="Any additional information or special instructions..."></textarea>
                            <small class="form-text text-muted">Optional notes for internal tracking</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-warning" id="preview-email-btn">
                        <i class="fas fa-eye me-1"></i>Preview Email
                    </button>
                    <button type="submit" class="btn btn-outline-secondary" id="save-draft-btn">
                        <i class="fas fa-save me-1"></i>Save for Later
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Email Preview Modal -->
<div class="modal fade" id="emailPreviewModal" tabindex="-1" aria-labelledby="emailPreviewModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailPreviewModalLabel">
                    <i class="fas fa-eye me-2"></i>Email Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="border p-3" id="email-preview-content">
                    <!-- Email preview will be populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="confirm-send-btn">
                    <i class="fas fa-paper-plane me-1"></i>Confirm & Send
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Invitation Details Modal -->
<div class="modal fade" id="viewInvitationModal" tabindex="-1" aria-labelledby="viewInvitationModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewInvitationModalLabel">
                    <i class="fas fa-eye me-2"></i>Invitation Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Loading state -->
                <div id="invitation-loading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                    <p class="text-muted">Loading invitation details...</p>
                </div>

                <!-- Error state -->
                <div id="invitation-error" class="alert alert-danger d-none">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="invitation-error-message"></span>
                </div>

                <!-- Invitation details content -->
                <div id="invitation-details-content" class="d-none">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary d-none" id="send-now-btn">
                    <i class="fas fa-paper-plane me-1"></i>Send Now
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

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

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
        const invitationForm = document.getElementById('invitation-form');
        const invitationsTable = document.getElementById('invitationsTable').querySelector('tbody');
        const statusMessages = document.getElementById('status-messages');
        const saveDraftBtn = document.getElementById('save-draft-btn');
        const previewEmailBtn = document.getElementById('preview-email-btn');
        const confirmSendBtn = document.getElementById('confirm-send-btn');
        const confirmActionBtn = document.getElementById('confirm-action-btn');

        // Initialize page
        loadInvitations();
        loadStats();
        loadCandidates();
        setupEventListeners();
        setupFormValidation();

        // Check for candidate_id URL parameter and handle pre-selection
        handleCandidatePreSelection();

        /**
         * Setup event listeners
         */
        function setupEventListeners() {
            // Form submission
            invitationForm.addEventListener('submit', handleFormSubmit);

            // Preview email button
            previewEmailBtn.addEventListener('click', showEmailPreview);

            // Confirm send button
            confirmSendBtn.addEventListener('click', function() {
                bootstrap.Modal.getInstance(document.getElementById('emailPreviewModal')).hide();
                submitInvitation();
            });

            // Confirm action button
            confirmActionBtn.addEventListener('click', function() {
                if (confirmationCallback) {
                    confirmationCallback();
                    confirmationCallback = null;
                }
                bootstrap.Modal.getInstance(document.getElementById('confirmationModal')).hide();
            });

            // Search functionality
            const searchInput = document.getElementById('invitation-search');
            const clearSearchBtn = document.getElementById('clear-search');

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.trim();

                // Clear previous timeout
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }

                // Debounce search - wait 300ms after user stops typing
                searchTimeout = setTimeout(() => {
                    currentSearch = searchTerm;
                    loadInvitations(1);
                }, 300);
            });

            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                currentSearch = '';
                loadInvitations(1);
            });

            // Reset form when modal is hidden
            document.getElementById('invitationModal').addEventListener('hidden.bs.modal', function() {
                resetForm();
            });

            // Set minimum date to today for interview date field
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('interview_date').setAttribute('min', today);
        }

        /**
         * Setup form validation
         */
        function setupFormValidation() {
            const form = invitationForm;
            const inputs = form.querySelectorAll('input, select, textarea');

            inputs.forEach(input => {
                // Real-time validation on blur
                input.addEventListener('blur', function() {
                    validateField(this);
                });

                // Clear validation on input
                input.addEventListener('input', function() {
                    if (this.classList.contains('is-invalid')) {
                        this.classList.remove('is-invalid');
                        const feedback = this.parentNode.querySelector('.invalid-feedback');
                        if (feedback) feedback.textContent = '';
                    }
                });
            });
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

            // Email validation
            else if (field.type === 'email' && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    message = 'Please enter a valid email address.';
                }
            }

            // URL validation
            else if (field.type === 'url' && value) {
                try {
                    new URL(value);
                } catch {
                    isValid = false;
                    message = 'Please enter a valid URL.';
                }
            }

            // Date validation (not in the past)
            else if (field.type === 'date' && value) {
                const selectedDate = new Date(value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (selectedDate < today) {
                    isValid = false;
                    message = 'Interview date cannot be in the past.';
                }
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
            const form = invitationForm;
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
         * Handle form submission
         */
        function handleFormSubmit(e) {
            e.preventDefault();

            hideFormError();

            if (!validateForm()) {
                return;
            }

            // Save as draft instead of showing email preview
            saveDraft();
        }

        /**
         * Show email preview
         */
        function showEmailPreview() {
            if (!validateForm()) {
                return;
            }

            const formData = new FormData(invitationForm);
            const data = Object.fromEntries(formData.entries());

            // Get candidate name from the selected option
            const candidateSelect = document.getElementById('candidate_id');
            const selectedOption = candidateSelect.options[candidateSelect.selectedIndex];
            const candidateName = selectedOption ? selectedOption.textContent.split(' (')[0] : 'Candidate';

            // Format date and time for preview
            const formattedDate = new Date(data.interview_date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            const formattedTime = new Date(`2000-01-01T${data.interview_time}`).toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });

            let emailContent = `
            <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px;">
                <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                    <h3>Interview Invitation</h3>
                </div>

                <div style="padding: 20px 0;">
                    <p><strong>Subject:</strong> Invitation to Online Interview – Hair Transplant Position at Liv \u0026 Harley Street</p>

                    <p>Dear ${candidateName},</p>

                    <p>Thank you for your interest in the Hair Transplant position at Liv & Harley Street.</p>

                    <p>We are pleased to invite you to an online interview to further discuss your qualifications and experience. The interview will be held via ${data.meeting_platform}, and it will last approximately ${data.interview_duration}.</p>

                    <div style="background-color: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0;">
                        <strong>Interview Details:</strong><br>
                        <strong>Date:</strong> ${formattedDate}<br>
                        <strong>Time:</strong> ${formattedTime}<br>
                        <strong>Platform:</strong> ${data.meeting_platform}<br>
                        <strong>Link:</strong> <a href="${data.meeting_link}">${data.meeting_link}</a>
                    </div>`;

            // Add video upload section if link is provided
            if (data.video_upload_link && data.video_upload_link.trim()) {
                emailContent += `
                    <div style="background-color: #fff3cd; padding: 20px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #ffc107;">
                        <strong style="color: #d63384; font-size: 16px;">📹 REQUIRED VIDEO SUBMISSIONS</strong><br><br>

                        <p style="margin: 10px 0; font-weight: bold; color: #333;">
                            You MUST upload TWO videos via the provided link for your application to be considered complete:
                        </p>

                        <div style="margin: 15px 0; padding: 12px; background-color: #e7f3ff; border-radius: 5px;">
                            <strong style="color: #0066cc;">📋 Video Requirements:</strong><br>
                            <strong>Video 1:</strong> Graft extraction procedure (minimum 1 minute duration)<br>
                            <strong>Video 2:</strong> Graft implantation procedure (minimum 1 minute duration)
                        </div>

                        <p style="margin: 15px 0;">
                            <strong>Upload Link:</strong> <a href="${data.video_upload_link}" style="color: #0066cc; text-decoration: underline;">${data.video_upload_link}</a>
                        </p>

                        <div style="background-color: #ff6b35; color: white; padding: 12px; border-radius: 8px; text-align: center; margin: 15px 0; border: 3px solid #ff4500;">
                            <strong style="font-size: 18px; display: block; margin-bottom: 5px;">🔑 IMPORTANT - LOGIN PASSWORD:</strong>
                            <span style="font-size: 24px; font-weight: bold; letter-spacing: 2px; background-color: #ffffff; color: #ff4500; padding: 8px 16px; border-radius: 5px; display: inline-block;">hsh</span>
                            <div style="font-size: 12px; margin-top: 8px; opacity: 0.9;">(enter exactly as shown, without quotes)</div>
                        </div>

                        <p style="margin: 10px 0; font-weight: bold; color: #d63384;">
                            ⚠️ Both videos are mandatory - incomplete submissions will not be processed.
                        </p>
                    </div>`;
            }

            emailContent += `
                    <p>Please note that the interview may include a section conducted in English, in order to assess your language proficiency and communication skills with international patients.</p>

                    <p>During the interview, we would like to learn more about your background, practical experience in hair transplantation procedures, and your approach to patient care.</p>
                    <p>We look forward to speaking with you soon.</p>
                    <p>The password for the video uploading folder  is:<h4> hsh </h4> </p>
                </div>

                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;">
                    <p>Kind regards,<br>
                    Liv HSH Team<br>
                    <a href="mailto:hr@livharleystreet.uk">hr@livharleystreet.uk</a></p>
                </div>
            </div>
        `;

            document.getElementById('email-preview-content').innerHTML = emailContent;
            new bootstrap.Modal(document.getElementById('emailPreviewModal')).show();
        }

        /**
         * Save invitation as draft
         */
        async function saveDraft() {
            const formData = new FormData(invitationForm);
            const data = Object.fromEntries(formData.entries());

            // Show loading state
            saveDraftBtn.disabled = true;
            saveDraftBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

            try {
                const response = await apiRequest('/api.php', 'POST', {
                    entity: 'interview_invitations',
                    action: 'save_draft',
                    ...data
                });

                if (response.success) {
                    showSuccessMessage(response.message);
                    bootstrap.Modal.getInstance(document.getElementById('invitationModal')).hide();
                    resetForm();
                    loadInvitations();
                    loadStats();
                } else {
                    showFormError(response.error || 'Failed to save invitation draft.');
                }
            } catch (error) {
                console.error('Error saving invitation draft:', error);
                showFormError('An error occurred while saving the invitation draft.');
            } finally {
                // Reset button state
                saveDraftBtn.disabled = false;
                saveDraftBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save for Later';
            }
        }

        /**
         * Send saved draft invitation
         */
        async function sendSavedInvitation(invitationId) {
            try {
                const response = await apiRequest('/api.php', 'POST', {
                    entity: 'interview_invitations',
                    action: 'send_draft',
                    id: invitationId
                });

                if (response.success) {
                    showSuccessMessage(response.message);
                    loadInvitations();
                    loadStats();
                    // Close any open modals
                    const openModals = document.querySelectorAll('.modal.show');
                    openModals.forEach(modal => {
                        bootstrap.Modal.getInstance(modal).hide();
                    });
                } else {
                    showErrorMessage(response.error || 'Failed to send invitation.');
                }
            } catch (error) {
                console.error('Error sending saved invitation:', error);
                showErrorMessage('An error occurred while sending the invitation.');
            }
        }

        /**
         * Load invitations list
         */
        async function loadInvitations(page = 1) {
            currentPage = page;

            try {
                const requestData = {
                    entity: 'interview_invitations',
                    action: 'list',
                    page: page,
                    limit: 20
                };

                // Add search parameter if there's a search term
                if (currentSearch) {
                    requestData.search = currentSearch;
                }

                const response = await apiRequest('/api.php', 'POST', requestData);

                if (response.success) {
                    renderInvitationsTable(response.invitations);
                    renderPagination(response.pagination);
                    updateInvitationCount(response.pagination.total);
                } else {
                    showErrorMessage('Failed to load invitations: ' + (response.error || 'Unknown error'));
                    updateInvitationCount(0);
                }
            } catch (error) {
                console.error('Error loading invitations:', error);
                showErrorMessage('An error occurred while loading invitations.');
                updateInvitationCount(0);
            }
        }

        /**
         * Load candidates for dropdown
         */
        async function loadCandidates() {
            try {
                const response = await apiRequest('/api.php', 'POST', {
                    entity: 'staff',
                    action: 'list',
                    limit: 1000 // Get all candidates for dropdown
                });

                if (response.success) {
                    populateCandidateDropdown(response.staff);
                } else {
                    console.error('Failed to load candidates:', response.error);
                    showErrorMessage('Failed to load candidates: ' + (response.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error loading candidates:', error);
                showErrorMessage('An error occurred while loading candidates.');
            }
        }

        /**
         * Populate candidate dropdown
         */
        function populateCandidateDropdown(candidates) {
            const candidateSelect = document.getElementById('candidate_id');

            // Clear existing options except the first one
            candidateSelect.innerHTML = '<option value="">Choose a candidate...</option>';

            // Add candidates to dropdown
            candidates.forEach(candidate => {
                const option = document.createElement('option');
                option.value = candidate.id;
                option.textContent = `${candidate.name} (${candidate.email})`;
                candidateSelect.appendChild(option);
            });

            // After populating dropdown, check for URL parameter pre-selection
            preselectCandidateFromURL();
        }

        /**
         * Handle candidate pre-selection from URL parameter
         */
        function handleCandidatePreSelection() {
            const urlParams = new URLSearchParams(window.location.search);
            const candidateId = urlParams.get('candidate_id');

            if (candidateId) {
                // Show the invitation modal automatically
                const invitationModal = new bootstrap.Modal(document.getElementById('invitationModal'));
                invitationModal.show();

                // Store the candidate ID for later use when dropdown is populated
                window.preselectedCandidateId = candidateId;
            }
        }

        /**
         * Pre-select candidate in dropdown based on URL parameter
         */
        function preselectCandidateFromURL() {
            if (window.preselectedCandidateId) {
                const candidateSelect = document.getElementById('candidate_id');
                const candidateId = window.preselectedCandidateId;

                // Try to find and select the candidate
                const option = candidateSelect.querySelector(`option[value="${candidateId}"]`);
                if (option) {
                    candidateSelect.value = candidateId;
                    // Trigger validation to show the field as valid
                    validateField(candidateSelect);

                    // Show success message
                    showSuccessMessage(
                        `Candidate "${option.textContent.split(' (')[0]}" has been pre-selected for the interview invitation.`
                    );
                } else {
                    // Invalid candidate ID - show error message
                    showErrorMessage(`Invalid candidate ID provided. Please select a candidate from the dropdown.`);
                }

                // Clear the stored ID
                delete window.preselectedCandidateId;
            }
        }

        /**
         * Load statistics
         */
        async function loadStats() {
            try {
                const response = await apiRequest('/api.php', 'POST', {
                    entity: 'interview_invitations',
                    action: 'stats'
                });

                if (response.success) {
                    document.getElementById('waiting-invitations').textContent = response.stats.waiting || 0;
                    // document.getElementById('month-invitations').textContent = response.stats.month || 0;
                    // document.getElementById('week-invitations').textContent = response.stats.week || 0;
                    // document.getElementById('today-invitations').textContent = response.stats.today || 0;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        /**
         * Render invitations table
         */
        function renderInvitationsTable(invitations) {
            if (!invitations || invitations.length === 0) {
                invitationsTable.innerHTML = `
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No invitations found</p>
                        </td>
                    </tr>
                `;
                return;
            }

            invitationsTable.innerHTML = invitations.map(invitation => {
                const sentDate = invitation.sent_at ? new Date(invitation.sent_at) : null;
                const interviewDate = new Date(invitation.interview_date);
                const senderName = invitation.sent_by_name && invitation.sent_by_surname ?
                    `${invitation.sent_by_name} ${invitation.sent_by_surname}` :
                    invitation.sent_by_username || 'Unknown';

                // Status badge
                const status = invitation.status || 'sent';
                const statusBadge = status === 'draft' ?
                    '<span class="badge bg-warning text-dark">Draft</span>' :
                    '<span class="badge bg-success">Sent</span>';

                // Sent date display
                const sentDateDisplay = sentDate ?
                    `${sentDate.toLocaleDateString()} ${sentDate.toLocaleTimeString()}` :
                    '<span class="text-muted">Not sent</span>';

                return `
                    <tr>
                        <td><span class="fw-medium">${escapeHtml(invitation.candidate_name)}</span></td>
                        <td><span class="text-truncate-mobile">${escapeHtml(invitation.candidate_email)}</span></td>
                        <td><span class="fw-medium">${interviewDate.toLocaleDateString()}</span></td>
                        <td><span class="fw-medium">${invitation.interview_time}</span></td>
                        <td><span class="badge bg-info">${escapeHtml(invitation.meeting_platform)}</span></td>
                        <td>${statusBadge}</td>
                        <td class="d-none d-lg-table-cell"><small>${escapeHtml(invitation.interview_duration)}</small></td>
                        <td class="d-none d-md-table-cell"><small>${escapeHtml(senderName)}</small></td>
                        <td class="d-none d-lg-table-cell"><small>${sentDateDisplay}</small></td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewInvitation(${invitation.id})" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info" onclick="window.location.href='send_interview_invitation.php?invitation_id=${invitation.id}'" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>

                                ${isAdmin ? `
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteInvitation(${invitation.id}, '${escapeHtml(invitation.candidate_name)}')" title="Delete">
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

            if (pagination.pages <= 1) {
                container.innerHTML = '';
                return;
            }

            let paginationHtml = '<ul class="pagination justify-content-center">';

            // Previous button
            if (pagination.page > 1) {
                paginationHtml +=
                    `<li class="page-item"><a class="page-link" href="#" onclick="loadInvitations(${pagination.page - 1})">Previous</a></li>`;
            }

            // Page numbers
            for (let i = Math.max(1, pagination.page - 2); i <= Math.min(pagination.pages, pagination.page +
                    2); i++) {
                const activeClass = i === pagination.page ? 'active' : '';
                paginationHtml +=
                    `<li class="page-item ${activeClass}"><a class="page-link" href="#" onclick="loadInvitations(${i})">${i}</a></li>`;
            }

            // Next button
            if (pagination.page < pagination.pages) {
                paginationHtml +=
                    `<li class="page-item"><a class="page-link" href="#" onclick="loadInvitations(${pagination.page + 1})">Next</a></li>`;
            }

            paginationHtml += '</ul>';
            container.innerHTML = paginationHtml;
        }

        /**
         * Update invitation count display
         */
        function updateInvitationCount(count) {
            const countElement = document.getElementById('invitation-count');
            if (countElement) {
                countElement.textContent = count;
            }
        }

        /**
         * Reset form
         */
        function resetForm() {
            invitationForm.reset();
            invitationForm.classList.remove('was-validated');

            // Clear validation states
            const inputs = invitationForm.querySelectorAll('input, select, textarea');
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

        /**
         * Show success message
         */
        function showSuccessMessage(message) {
            statusMessages.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            statusMessages.scrollIntoView({
                behavior: 'smooth'
            });
        }

        /**
         * Show error message
         */
        function showErrorMessage(message) {
            statusMessages.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            statusMessages.scrollIntoView({
                behavior: 'smooth'
            });
        }

        /**
         * Escape HTML to prevent XSS
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Global functions for onclick handlers
        window.viewInvitation = async function(id) {
            const modal = new bootstrap.Modal(document.getElementById('viewInvitationModal'));
            const loadingDiv = document.getElementById('invitation-loading');
            const errorDiv = document.getElementById('invitation-error');
            const contentDiv = document.getElementById('invitation-details-content');
            const errorMessage = document.getElementById('invitation-error-message');

            // Reset modal state
            loadingDiv.classList.remove('d-none');
            errorDiv.classList.add('d-none');
            contentDiv.classList.add('d-none');

            // Show modal
            modal.show();

            try {
                const response = await apiRequest('/api.php', 'POST', {
                    entity: 'interview_invitations',
                    action: 'get',
                    id: id
                });

                if (response.success) {
                    displayInvitationDetails(response.invitation);
                } else {
                    showInvitationError(response.error || 'Failed to load invitation details.');
                }
            } catch (error) {
                console.error('Error loading invitation details:', error);
                showInvitationError('An error occurred while loading invitation details.');
            }
        };

        /**
         * Display invitation details in modal
         */
        function displayInvitationDetails(invitation) {
            const loadingDiv = document.getElementById('invitation-loading');
            const contentDiv = document.getElementById('invitation-details-content');
            const sendNowBtn = document.getElementById('send-now-btn');

            // Format dates and times
            const interviewDate = new Date(invitation.interview_date);
            const sentDate = new Date(invitation.sent_at);

            const formattedInterviewDate = interviewDate.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            const formattedInterviewTime = new Date(`2000-01-01T${invitation.interview_time}`).toLocaleTimeString(
                'en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });

            const formattedSentDate = sentDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            const formattedSentTime = sentDate.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });

            // Format sender name
            const senderName = invitation.sent_by_name && invitation.sent_by_surname ?
                `${invitation.sent_by_name} ${invitation.sent_by_surname}` :
                invitation.sent_by_username || 'Unknown';

            const content = `
                <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                        <h4 class="mb-0">
                            <i class="fas fa-user me-2 text-primary"></i>
                            Interview Invitation #${invitation.id}
                        </h4>
                    </div>

                    <div class="row g-4">
                        <!-- Candidate Information -->
                        <div class="col-12">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-user me-2"></i>Candidate Information
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Name:</label>
                                        <p class="mb-0">${escapeHtml(invitation.candidate_name)}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Email:</label>
                                        <p class="mb-0">
                                            <a href="mailto:${escapeHtml(invitation.candidate_email)}" class="text-decoration-none">
                                                ${escapeHtml(invitation.candidate_email)}
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Interview Details -->
                        <div class="col-12">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-calendar me-2"></i>Interview Details
                            </h5>
                            <div style="background-color: #e9ecef; padding: 20px; border-radius: 8px;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-muted">Date:</label>
                                            <p class="mb-0 fw-medium">${formattedInterviewDate}</p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-muted">Time:</label>
                                            <p class="mb-0 fw-medium">${formattedInterviewTime}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-muted">Platform:</label>
                                            <p class="mb-0">
                                                <span class="badge bg-info fs-6">${escapeHtml(invitation.meeting_platform)}</span>
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-muted">Duration:</label>
                                            <p class="mb-0">${escapeHtml(invitation.interview_duration)}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-muted">Meeting Link:</label>
                                    <p class="mb-0">
                                        <a href="${escapeHtml(invitation.meeting_link)}" target="_blank" class="text-decoration-none">
                                            <i class="fas fa-external-link-alt me-1"></i>
                                            ${escapeHtml(invitation.meeting_link)}
                                        </a>
                                    </p>
                                </div>
                                ${invitation.video_upload_link ? `
                                <div class="mb-0">
                                    <label class="form-label fw-bold text-primary">📹 Video Upload Requirements:</label>
                                    <div class="alert alert-warning mb-3" style="border-left: 4px solid #ffc107;">
                                        <h6 class="alert-heading text-danger">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            REQUIRED: Two Videos Must Be Uploaded
                                        </h6>
                                        <div class="mb-2">
                                            <strong>Video 1:</strong> Graft extraction procedure (minimum 1 minute)<br>
                                            <strong>Video 2:</strong> Graft implantation procedure (minimum 1 minute)
                                        </div>
                                        <div class="mb-2">
                                            <strong>Upload Link:</strong>
                                            <a href="${escapeHtml(invitation.video_upload_link)}" target="_blank" class="text-decoration-none ms-2">
                                                <i class="fas fa-external-link-alt me-1"></i>
                                                ${escapeHtml(invitation.video_upload_link)}
                                            </a>
                                        </div>
                                        <div class="text-center p-2 bg-danger text-white rounded" style="border: 2px solid #dc3545;">
                                            <strong class="d-block mb-1">🔑 LOGIN PASSWORD:</strong>
                                            <span class="fs-4 fw-bold bg-white text-danger px-3 py-1 rounded" style="letter-spacing: 2px;">hsh</span>
                                            <small class="d-block mt-1 opacity-75">(enter exactly as shown, without quotes)</small>
                                        </div>
                                        <div class="mt-2 text-danger fw-bold">
                                            <i class="fas fa-exclamation-circle me-1"></i>
                                            Both videos are mandatory for complete application
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>

                        <!-- Sending Information -->
                        <div class="col-12">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-paper-plane me-2"></i>Sending Information
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Sent By:</label>
                                        <p class="mb-0">${escapeHtml(senderName)}</p>
                                        ${invitation.sent_by_email ? `<small class="text-muted">${escapeHtml(invitation.sent_by_email)}</small>` : ''}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Sent Date & Time:</label>
                                        <p class="mb-0">${formattedSentDate} at ${formattedSentTime}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Status:</label>
                                        <p class="mb-0">
                                            <span class="badge ${invitation.status === 'draft' ? 'bg-warning text-dark' : 'bg-success'} fs-6">
                                                ${invitation.status === 'draft' ? 'Draft' : 'Sent'}
                                            </span>
                                        </p>
                                        ${invitation.status === 'draft' ? '<small class="text-muted">Email has not been sent yet</small>' : ''}
                                    </div>
                                </div>
                            </div>
                        </div>

                        ${invitation.notes ? `
                        <!-- Additional Notes -->
                        <div class="col-12">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-sticky-note me-2"></i>Additional Notes
                            </h5>
                            <div style="background-color: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
                                <p class="mb-0">${escapeHtml(invitation.notes).replace(/\n/g, '<br>')}</p>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;

            contentDiv.innerHTML = content;
            loadingDiv.classList.add('d-none');
            contentDiv.classList.remove('d-none');

            // Show/hide Send Now button based on status
            if (invitation.status === 'draft') {
                sendNowBtn.classList.remove('d-none');
                sendNowBtn.onclick = function() {
                    showConfirmation(
                        `Are you sure you want to send the interview invitation to ${invitation.candidate_name}?`,
                        function() {
                            sendSavedInvitation(invitation.id);
                        }
                    );
                };
            } else {
                sendNowBtn.classList.add('d-none');
                sendNowBtn.onclick = null;
            }
        }

        /**
         * Show error in invitation modal
         */
        function showInvitationError(message) {
            const loadingDiv = document.getElementById('invitation-loading');
            const errorDiv = document.getElementById('invitation-error');
            const errorMessage = document.getElementById('invitation-error-message');

            errorMessage.textContent = message;
            loadingDiv.classList.add('d-none');
            errorDiv.classList.remove('d-none');
        }

        window.deleteInvitation = function(id, candidateName) {
            const message =
                `Are you sure you want to delete the invitation for "${candidateName}"? This action cannot be undone.`;
            showConfirmation(message, async function() {
                try {
                    const response = await apiRequest('/api.php', 'POST', {
                        entity: 'interview_invitations',
                        action: 'delete',
                        id: id
                    });

                    if (response.success) {
                        showSuccessMessage(response.message);
                        loadInvitations(currentPage);
                        loadStats();
                    } else {
                        showErrorMessage(response.error || 'Failed to delete invitation.');
                    }
                } catch (error) {
                    console.error('Error deleting invitation:', error);
                    showErrorMessage('An error occurred while deleting the invitation.');
                }
            });
        };

        /**
         * Show confirmation modal
         */
        function showConfirmation(message, callback) {
            document.getElementById('confirmation-message').textContent = message;
            confirmationCallback = callback;
            new bootstrap.Modal(document.getElementById('confirmationModal')).show();
        }
    });
</script>