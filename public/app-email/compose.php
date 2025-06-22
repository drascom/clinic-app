<?php
require_once '../includes/header.php';
$page_title = "Compose Email";

// Get draft_id from URL if it exists
$draft_id = isset($_GET['draft_id']) ? (int)$_GET['draft_id'] : 0;
?>

<div class="container-fluid emp p-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-pen-alt me-2 text-primary"></i>
                        <?php echo $draft_id ? 'Edit Draft' : 'Compose New Email'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form id="compose-form" novalidate>
                        <input type="hidden" id="draft_id" name="draft_id" value="<?php echo $draft_id; ?>">

                        <div class="mb-3">
                            <label for="to" class="form-label">Recipient</label>
                            <input type="email" class="form-control" id="to" name="to" required>
                            <div class="invalid-feedback">
                                Recipient email is required.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject">
                        </div>

                        <div class="mb-3">
                            <label for="body" class="form-label">Body</label>
                            <textarea class="form-control" id="body" name="body" rows="10"></textarea>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" name="action" value="save" class="btn btn-secondary me-2">
                                <i class="fas fa-save me-1"></i> Save Draft
                            </button>
                            <button type="submit" name="action" value="send" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i> Send Email
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('compose-form');
        const draftIdInput = document.getElementById('draft_id');
        const toInput = document.getElementById('to');
        const subjectInput = document.getElementById('subject');
        const bodyInput = document.getElementById('body');

        const draftId = draftIdInput.value;

        // If a draft_id is present, fetch the draft data
        if (draftId > 0) {
            loadDraft(draftId);
        }

        async function loadDraft(id) {
            try {
                const response = await apiRequest('emails', 'get_draft', {
                    draft_id: id
                });
                if (response.success && response.data) {
                    const draft = response.data;
                    toInput.value = draft.to_address || '';
                    subjectInput.value = draft.subject || '';
                    bodyInput.value = draft.body || '';
                } else {
                    showToast('Error', response.message || 'Failed to load draft.', 'error');
                    // Clear draft_id if loading fails to prevent resubmission issues
                    draftIdInput.value = 0;
                }
            } catch (error) {
                console.error('Error loading draft:', error);
                showToast('Error', 'An unexpected error occurred while loading the draft.', 'error');
            }
        }

        form.addEventListener('submit', async function(event) {
            event.preventDefault();

            // Determine which button was clicked
            const action = event.submitter.value;

            // Basic validation
            if (!toInput.value) {
                toInput.classList.add('is-invalid');
                return;
            } else {
                toInput.classList.remove('is-invalid');
            }

            const formData = {
                draft_id: draftIdInput.value,
                to: toInput.value,
                subject: subjectInput.value,
                body: bodyInput.value,
                action: action
            };

            try {
                const response = await apiRequest('emails', 'save_email', formData);
                if (response.success) {
                    showToast('Success', response.message, 'success');
                    // If a new draft was saved, update the draft_id in the form
                    if (response.draft_id) {
                        draftIdInput.value = response.draft_id;
                    }
                    // If the email was sent, redirect to the main email page
                    if (action === 'send') {
                        setTimeout(() => {
                            window.location.href = '/app-email/index.php';
                        }, 1500);
                    }
                } else {
                    showToast(response.message || 'Failed to save email.', 'error');
                }
            } catch (error) {
                console.error('Error saving email:', error);
                showToast(error + ' An unexpected error occurred.', 'error');
            }
        });
    });
</script>