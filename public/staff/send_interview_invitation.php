<?php
include __DIR__ . '/../includes/header.php';

$page_title = "Send Interview Invitation";
?>

<div class="container emp frosted">
    <div class="card frosted">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center py-2">
                <a href="/staff/interview_invitations.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left me-1"></i>
                    <span class="d-none d-sm-inline">View All Invitations</span>
                </a>
                <h4 class="mb-0">
                    <i class="fas fa-paper-plane me-2 text-primary"></i>Send Interview Invitation
                </h4>
                <!-- No secondary function button needed -->
            </div>
        </div>

        <div class="card-body">
            <fieldset class="border rounded p-3 mb-3">
                <legend class="w-auto px-2 mb-3" style="font-size: 1rem;">Interview Invitation Details</legend>
                <form id="invitation-form" novalidate>
                    <!-- General Error Alert -->
                    <div class="alert alert-danger d-none" id="form-error-alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="form-error-message"></span>
                    </div>

                    <div class="row g-3">
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
                </form>
            </fieldset>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button type="button" class="btn btn-warning me-2" id="preview-email-btn">
                <i class="fas fa-eye me-1"></i>Preview Email
            </button>
            <button type="button" class="btn btn-outline-secondary me-2" id="save-draft-btn">
                <i class="fas fa-save me-1"></i>Save for Later
            </button>
            <button type="submit" class="btn btn-primary" id="send-invitation-btn">
                <i class="fas fa-paper-plane me-1"></i>Send Invitation
            </button>
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
                <button type="button" class="btn btn-primary" id="confirm-send-invitation-btn">
                    <i class="fas fa-paper-plane me-1"></i>Confirm & Send
                </button>
            </div>
        </div>
    </div>
</div>


<!-- API Helper for secure POST requests -->
<script src="/assets/js/api-helper.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Global variables
        const isAdmin = <?php echo is_admin() ? 'true' : 'false'; ?>;
        const isEditor = <?php echo is_editor() ? 'true' : 'false'; ?>;
        let invitation_id = null;

        // DOM elements
        const invitationForm = document.getElementById('invitation-form');
        const saveDraftBtn = document.getElementById('save-draft-btn');
        const previewEmailBtn = document.getElementById('preview-email-btn');
        const sendInvitationBtn = document.getElementById('send-invitation-btn'); // New button for direct send

        // Initialize page
        loadCandidates();
        setupEventListeners();
        setupFormValidation();
        handleCandidatePreSelection();

        /**
         * Setup event listeners
         */
        function setupEventListeners() {
            // Form submission (for the main send button)
            invitationForm.addEventListener('submit', handleFormSubmit);

            // Preview email button
            previewEmailBtn.addEventListener('click', showEmailPreview);

            // Confirm send button inside preview modal
            document.getElementById('confirm-send-invitation-btn').addEventListener('click', async function() {
                const modal = bootstrap.Modal.getInstance(document.getElementById('emailPreviewModal'));
                if (modal) {
                    modal.hide();
                }

                // Use the existing ID if we have one
                if (invitation_id) {
                    await send_saved_invitation(invitation_id);
                } else {
                    // If no ID, try to save a draft first
                    const new_invitation_id = await saveDraft();
                    if (new_invitation_id) {
                        await send_saved_invitation(new_invitation_id);
                    } else {
                        showToast('Failed to save a draft before sending. Please try again.', 'danger');
                    }
                }
            });

            // Save Draft button
            saveDraftBtn.addEventListener('click', saveDraft);

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

            // Email validation (not applicable for this form, but keeping for robustness if fields change)
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
         * Handle form submission (for the main send button)
         */
        async function handleFormSubmit(e) {
            e.preventDefault();
            hideFormError();

            if (!validateForm()) {
                return;
            }

            // If we already have an ID, just send it. Otherwise, save first.
            if (invitation_id) {
                await send_saved_invitation(invitation_id);
            } else {
                const savedId = await saveDraft();
                if (savedId) {
                    await send_saved_invitation(savedId);
                } else {
                    showToast('Could not save the invitation draft before sending.', 'danger');
                }
            }
        }

        /**
         * Show email preview
         */
        async function showEmailPreview() {
            if (!validateForm()) {
                return;
            }

            // If we don't have an ID, save a draft first.
            if (!invitation_id) {
                const savedId = await saveDraft();
                if (!savedId) {
                    showToast('Could not save a draft to generate preview.', 'danger');
                    return;
                }
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
            // Trigger validation before saving
            if (!validateForm()) {
                showToast('Please correct the highlighted errors before saving.', 'danger');
                return null; // Prevent saving if validation fails
            }

            const formData = new FormData(invitationForm);
            const data = Object.fromEntries(formData.entries());

            // Show loading state
            saveDraftBtn.disabled = true;
            saveDraftBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
            sendInvitationBtn.disabled = true;

            try {
                const payload = {
                    entity: 'interview_invitations',
                    action: 'save_draft',
                    candidate_id: data.candidate_id, // Ensure candidate_id is explicitly passed
                    interview_date: data.interview_date,
                    interview_time: data.interview_time,
                    meeting_platform: data.meeting_platform,
                    meeting_link: data.meeting_link,
                    interview_duration: data.interview_duration,
                    video_upload_link: data.video_upload_link,
                    notes: data.notes
                };

                // If we already have an ID, pass it to update the existing draft
                if (invitation_id) {
                    payload.id = invitation_id;
                }

                const response = await apiRequest('/api.php', 'POST', payload);

                if (response.success && response.invitation_id) {
                    showToast(response.message, 'success');
                    saveDraftBtn.innerHTML = '<i class="fas fa-check me-1"></i>Saved';
                    invitation_id = response.invitation_id;
                    setTimeout(() => {
                        window.location.href = 'interview_invitations.php';
                    }, 500);
                    return response.invitation_id;
                } else {
                    showFormError(response.error || 'Failed to save invitation draft.');
                    showToast(response.error || 'Failed to save invitation draft.', 'danger');
                    saveDraftBtn.disabled = false; // Re-enable on failure
                    saveDraftBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save for Later';
                    return null;
                }
            } catch (error) {
                console.error('Error saving invitation draft:', error);
                showFormError('An error occurred while saving the invitation draft.');
                showToast('An error occurred while saving the invitation draft.', 'danger');
                saveDraftBtn.disabled = false; // Re-enable on error
                saveDraftBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save for Later';
                return null;
            } finally {
                sendInvitationBtn.disabled = false;
            }
        }
        /**
         * Submit draft invitation
         */
        async function send_saved_invitation(invitationId) {
            try {
                const response = await apiRequest('/api.php', 'POST', {
                    entity: 'interview_invitations',
                    action: 'send_draft',
                    id: invitationId
                });

                if (response.success) {
                    showSuccessMessage(response.message);
                    // Close any open modals
                    const openModals = document.querySelectorAll('.modal.show');
                    openModals.forEach(modal => {
                        bootstrap.Modal.getInstance(modal).hide();
                    });

                    // Redirect after a short delay
                    setTimeout(() => {
                        window.location.href = '/staff/interview_invitations.php';
                    }, 500);
                } else {
                    showErrorMessage(response.error || 'Failed to send invitation.');
                }
            } catch (error) {
                console.error('Error sending saved invitation:', error);
                showErrorMessage('An error occurred while sending the invitation.');
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
                    showToast('Failed to load candidates: ' + (response.error || 'Unknown error'), 'danger');
                }
            } catch (error) {
                console.error('Error loading candidates:', error);
                showToast('An error occurred while loading candidates.', 'danger');
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

            // After populating dropdown, pre-select candidate if ID is in URL
            preselectCandidateFromURL();
        }

        /**
         * Handle candidate pre-selection from URL parameter
         */
        function handleCandidatePreSelection() {
            const urlParams = new URLSearchParams(window.location.search);
            const candidateId = urlParams.get('candidate_id');
            const retrievedInvitationId = urlParams.get('invitation_id');

            if (candidateId) {
                // Store the candidate ID for later use when dropdown is populated
                window.preselectedCandidateId = candidateId;
            }
            if (retrievedInvitationId) {
                // Store the candidate ID for later use when dropdown is populated
                window.preselectedInviteId = retrievedInvitationId;
            }
            if (window.preselectedInviteId) {
                invitation_id = window.preselectedInviteId; // Set global invitation_id
                loadInvitationForEdit(invitation_id);
                // Clear the stored ID after use
                delete window.preselectedInviteId;
            }
        }

        /**
         * Load existing invitation data for editing
         */
        async function loadInvitationForEdit(id) {
            try {
                const response = await apiRequest('/api.php', 'POST', {
                    entity: 'interview_invitations',
                    action: 'get',
                    id: id
                });

                if (response.success && response.invitation) {
                    populateFormWithData(response.invitation);
                    // Update page title and button text for edit mode
                    document.querySelector('h4.mb-0').innerHTML =
                        '<i class="fas fa-edit me-2 text-primary"></i>Edit Interview Invitation';
                    sendInvitationBtn.innerHTML =
                        '<i class="fas fa-paper-plane me-1"></i>Update & Send Invitation';
                    saveDraftBtn.innerHTML = '<i class="fas fa-save me-1"></i>Update Draft';
                    document.getElementById('confirm-send-invitation-btn').innerHTML =
                        '<i class="fas fa-paper-plane me-1"></i>Confirm & Update';
                    showToast('Invitation loaded for editing.', 'success');
                } else {
                    showToast(response.error || 'Failed to load invitation for editing.', 'danger');
                    console.error('Failed to load invitation:', response.error);
                    // Redirect to new invitation page if not found
                    setTimeout(() => {
                        window.location.href = '/staff/send_interview_invitation.php';
                    }, 1500);
                }
            } catch (error) {
                console.error('Error loading invitation for edit:', error);
                showToast('An error occurred while loading invitation for editing.', 'danger');
                // Redirect to new invitation page on error
                setTimeout(() => {
                    window.location.href = '/staff/send_interview_invitation.php';
                }, 1500);
            }
        }

        /**
         * Populate form fields with invitation data
         */
        function populateFormWithData(invitation) {
            document.getElementById('candidate_id').value = invitation.staff_id;
            document.getElementById('interview_date').value = invitation.interview_date;
            document.getElementById('interview_time').value = invitation.interview_time;
            document.getElementById('meeting_platform').value = invitation.meeting_platform;
            document.getElementById('interview_duration').value = invitation.interview_duration;
            document.getElementById('meeting_link').value = invitation.meeting_link;
            document.getElementById('video_upload_link').value = invitation.video_upload_link || '';
            document.getElementById('notes').value = invitation.notes || '';

            // Manually trigger validation for populated fields
            const inputs = invitationForm.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                validateField(input);
            });

            // Pre-select candidate dropdown if it's already populated
            if (document.getElementById('candidate_id').options.length > 1) {
                document.getElementById('candidate_id').value = invitation.staff_id;
                validateField(document.getElementById('candidate_id'));
            } else {
                // If dropdown not yet populated, store for pre-selection after loadCandidates
                window.preselectedCandidateId = invitation.staff_id;
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
                    showToast(
                        `Candidate "${option.textContent.split(' (')[0]}" has been pre-selected for the interview invitation.`,
                        'success');
                } else {
                    // Invalid candidate ID - show error message
                    showToast(`Invalid candidate ID provided. Please select a candidate from the dropdown.`,
                        'danger');
                }

                // Clear the stored ID
                delete window.preselectedCandidateId;
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
         * Show success message (using global showToast)
         */
        function showSuccessMessage(message) {
            if (typeof showToast === 'function') {
                showToast(message, 'success');
            } else {
                console.warn('showToast function not available. Displaying message via console:', message);
            }
        }

        /**
         * Show error message (using global showToast)
         */
        function showErrorMessage(message) {
            if (typeof showToast === 'function') {
                showToast(message, 'danger');
            } else {
                console.warn('showToast function not available. Displaying error via console:', message);
            }
        }

        /**
         * Escape HTML to prevent XSS
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>