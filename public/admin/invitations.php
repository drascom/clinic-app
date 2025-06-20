<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

if (!is_logged_in() || !is_admin()) {
    // Redirect to login page or show an unauthorized message
    header('Location: ../auth/login.php');
    exit();
}
?>

<?php
$page_title = "Invitation Management";
include __DIR__ . '/../includes/header.php';
?>
<div class="container  py-4 emp">
    <!-- Status Messages -->
    <div id="status-messages">
        <!-- Success or error messages will be displayed here -->
    </div>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="fas fa-envelope-open-text me-2 text-primary"></i>
            Invitation Management
        </h2>
        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#inviteModal"
            id="inviteUserBtn">
            <i class="fas fa-plus-circle me-1"></i>
            <span class="d-none d-sm-inline">Invite New User</span>
        </button>
    </div>

    <!-- Search Section -->
    <div class="search-section">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="invitation-search" class="form-control"
                        placeholder="Search invitations by email, role, or status...">
                </div>
            </div>
            <div class="col-md-4 mt-3 mt-md-0">
                <div class="text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    <span id="invitation-count">Loading...</span> invitations found
                </div>
            </div>
        </div>
    </div>

    <!-- Invitations Table -->
    <div class="table-responsive">
        <table class="table table-hover" id="invitationsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th class="d-none d-md-table-cell">Agency</th>
                    <th>Status</th>
                    <th class="d-none d-lg-table-cell">Created At</th>
                    <th class="d-none d-lg-table-cell">Used At</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Invitation rows will be populated by JavaScript -->
            </tbody>
        </table>
    </div>

    <!-- Removed User Add/Edit Modal -->

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <!-- Invite User Modal -->
    <div class="modal fade" id="inviteModal" tabindex="-1" aria-labelledby="inviteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="inviteModalLabel">
                        <i class="fas fa-envelope-open-text me-2"></i>
                        Invite New User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="inviteUserForm">
                        <div class="form-group mb-3">
                            <label for="inviteEmail" class="form-label">Email:</label>
                            <input type="email" class="form-control" id="inviteEmail" name="email" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="inviteAgency" class="form-label">Agency:</label>
                            <select class="form-control" id="inviteAgency" name="agency_id" required>
                                <option value="">Select Agency</option>
                                <!-- Options will be populated by JavaScript -->
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="inviteRole" class="form-label">Role:</label>
                            <select class="form-control" id="inviteRole" name="role" required>
                                <option value="">Select Role</option>
                                <option value="admin">Admin</option>
                                <option value="agent">Agent</option>
                                <option value="editor">Editor</option>
                                <option value="technician">Technician</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
                    <button type="button" class="btn btn-primary" id="sendInvitationBtn">
                        <i class="fas fa-paper-plane me-1"></i>Send Invitation
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    // Invitation Management Script
    document.addEventListener('DOMContentLoaded', function () {
        const invitationsTableBody = document.querySelector('#invitationsTable tbody');
        const statusMessagesDiv = document.getElementById('status-messages');
        const inviteModal = document.getElementById('inviteModal');
        const inviteUserForm = document.getElementById('inviteUserForm');
        const inviteAgencySelect = document.getElementById('inviteAgency');
        const sendInvitationBtn = document.getElementById('sendInvitationBtn');


        let allInvitations = []; // Store all invitations for client-side filtering
        let allAgencies = []; // Store all agencies for dropdown (needed for agency name display and modal dropdown)

        // Function to display status messages
        function displayStatusMessage(message, type = 'success') {
            statusMessagesDiv.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = bootstrap.Alert.getInstance(statusMessagesDiv.querySelector('.alert'));
                if (alert) {
                    alert.hide();
                }
            }, 5000);
        }

        // Function to fetch agencies from the API (needed for displaying agency name and modal dropdown)
        function fetchAgencies() {
            apiRequest('agencies', 'list')
                .then(data => {
                    if (data.success) {
                        allAgencies = data.agencies;
                        populateAgencyDropdown(inviteAgencySelect); // Populate modal dropdown
                        fetchInvitations(); // Fetch invitations after agencies are loaded
                    } else {
                        console.error('Error fetching agencies:', data.message);
                        displayStatusMessage('Error fetching agencies: ' + data.message, 'danger');
                        fetchInvitations(); // Still try to fetch invitations even if agencies fail
                    }
                })
                .catch(error => {
                    console.error('Error fetching agencies:', error);
                    displayStatusMessage('Error fetching agencies: ' + error, 'danger');
                    fetchInvitations(); // Still try to fetch invitations even if agencies fail
                });
        }

        // Function to populate agency dropdown (reused for modal)
        function populateAgencyDropdown(selectElement) {
            selectElement.innerHTML = '<option value="">Select Agency</option>';
            allAgencies.forEach(agency => {
                const option = document.createElement('option');
                option.value = agency.id;
                option.textContent = agency.name;
                selectElement.appendChild(option);
            });
        }

        // Function to get agency name by ID
        function getAgencyName(agencyId) {
            if (!agencyId) return 'None';
            const agency = allAgencies.find(a => a.id == agencyId);
            return agency ? agency.name : 'Unknown';
        }


        // Function to fetch invitations from the API
        function fetchInvitations() {
            apiRequest('invitations', 'list')
                .then(data => {
                    if (data.success) {
                        allInvitations = data.invitations; // Store fetched invitations
                        populateInvitationsTable(allInvitations);
                    } else {
                        console.error('Error fetching invitations:', data.message);
                        displayStatusMessage('Error fetching invitations: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error fetching invitations:', error);
                    displayStatusMessage('Error fetching invitations: ' + error, 'danger');
                });
        }

        // Function to populate the invitations table
        function populateInvitationsTable(invitations) {
            invitationsTableBody.innerHTML = ''; // Clear existing rows

            if (invitations.length === 0) {
                invitationsTableBody.innerHTML =
                    '<tr><td colspan="8" class="text-center">No invitations found.</td></tr>';
                return;
            }

            invitations.forEach(invitation => {
                const statusColor = invitation.status === 'pending' ? 'warning' : (invitation.status ===
                    'accepted' ? 'success' : 'secondary');
                const agencyName = getAgencyName(invitation.agency_id);
                const row = `
                    <tr>
                        <td><span class="fw-medium">${invitation.id}</span></td>
                        <td><span class="text-truncate-mobile">${invitation.email}</span></td>
                        <td><span class="badge bg-info">${invitation.role}</span></td>
                        <td class="d-none d-md-table-cell"><small>${agencyName}</small></td>
                        <td><span class="badge bg-${statusColor}">${invitation.status}</span></td>
                        <td class="d-none d-lg-table-cell"><small>${invitation.created_at}</small></td>
                        <td class="d-none d-lg-table-cell"><small>${invitation.used_at || 'N/A'}</small></td>
                        <td>
                            <div class="btn-group" role="group">
                                ${invitation.status === 'pending' ? `
                                <button class="btn btn-sm btn-text text-primary resend-invitation-btn" data-id="${invitation.id}" title="Resend Invitation">
                                    <i class="fas fa-redo"></i>
                                    <span class="d-none d-lg-inline ms-1">Resend</span>
                                </button>
                                ` : ''}
                                <button class="btn btn-sm btn-text text-danger delete-invitation-btn" data-id="${invitation.id}" title="Delete Invitation">
                                    <i class="fas fa-trash-alt"></i>
                                    <span class="d-none d-lg-inline ms-1">Delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                invitationsTableBody.innerHTML += row;
            });

            // Update invitation count
            document.getElementById('invitation-count').textContent = invitations.length;
        }

        // Event delegation for Resend and Delete buttons
        if (invitationsTableBody) {
            invitationsTableBody.addEventListener('click', function (event) {
                const target = event.target;

                // Handle Delete button click
                if (target.classList.contains('delete-invitation-btn') || target.closest(
                    '.delete-invitation-btn')) {
                    const deleteButton = target.closest('.delete-invitation-btn');
                    const invitationId = deleteButton.getAttribute('data-id');
                    if (confirm('Are you sure you want to delete this invitation?')) {
                        apiRequest('invitations', 'delete', {
                            id: invitationId
                        })
                            .then(data => {
                                if (data.success) {
                                    fetchInvitations(); // Refresh the table
                                    displayStatusMessage('Invitation deleted successfully!', 'success');
                                } else {
                                    console.error('Error deleting invitation:', data.message);
                                    displayStatusMessage('Error deleting invitation: ' + data.message,
                                        'danger');
                                }
                            })
                            .catch(error => {
                                console.error('Error deleting invitation:', error);
                                displayStatusMessage('Error deleting invitation: ' + error, 'danger');
                            });
                    }
                }

                // Handle Resend button click
                if (target.classList.contains('resend-invitation-btn') || target.closest(
                    '.resend-invitation-btn')) {
                    const resendButton = target.closest('.resend-invitation-btn');
                    const invitationId = resendButton.getAttribute('data-id');
                    if (confirm('Are you sure you want to resend this invitation?')) {
                        apiRequest('invitations', 'resend', {
                            id: invitationId
                        })
                            .then(data => {
                                if (data.success) {
                                    fetchInvitations(); // Refresh the table
                                    displayStatusMessage('Invitation resent successfully!', 'success');
                                } else {
                                    console.error('Error resending invitation:', data.message);
                                    displayStatusMessage('Error resending invitation: ' + data.message,
                                        'danger');
                                }
                            })
                            .catch(error => {
                                console.error('Error resending invitation:', error);
                                displayStatusMessage('Error resending invitation: ' + error, 'danger');
                            });
                    }
                }
            });
        }

        // Event listener for the search input
        const invitationSearchInput = document.getElementById('invitation-search');
        if (invitationSearchInput) {
            invitationSearchInput.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                const filteredInvitations = allInvitations.filter(invitation => {
                    // Search by ID, email, role, agency name, or status
                    const agencyName = getAgencyName(invitation.agency_id).toLowerCase();
                    return invitation.id.toString().includes(searchTerm) ||
                        invitation.email.toLowerCase().includes(searchTerm) ||
                        invitation.role.toLowerCase().includes(searchTerm) ||
                        agencyName.includes(searchTerm) ||
                        invitation.status.toLowerCase().includes(searchTerm);
                });
                populateInvitationsTable(filteredInvitations);
            });
        }

        // Event listener for the Send Invitation button in the modal
        if (sendInvitationBtn) {
            sendInvitationBtn.addEventListener('click', function () {
                // Add Bootstrap validation classes
                inviteUserForm.classList.add('was-validated');

                if (!inviteUserForm.checkValidity()) {
                    inviteUserForm.reportValidity();
                    return;
                }

                const formData = new FormData(inviteUserForm);
                const data = {};
                formData.forEach((value, key) => {
                    data[key] = value;
                });

                // The data object will now contain email, agency_id, and role
                console.log('Invite form data:', data); // Log data to verify

                apiRequest('user_invitation', 'invite', data)
                    .then(response => {
                        const responseDiv = document.getElementById(
                            'responseMessage'
                        ); // Need to add a response div in the modal or use the main one
                        const modal = bootstrap.Modal.getInstance(inviteModal);
                        if (response.success) {
                            displayStatusMessage('Invitation sent successfully!', 'success');
                            inviteUserForm.reset(); // Clear the form on success
                            inviteUserForm.classList.remove(
                                'was-validated'); // Remove validation classes
                            modal.hide(); // Hide the modal
                            fetchInvitations(); // Refresh the invitations list
                        } else {
                            // Display error message within the modal or using the main status div
                            displayStatusMessage('Error sending invitation: ' + (response.error ||
                                'An unknown error occurred.'), 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('API Error sending invitation:', error);
                        displayStatusMessage('An error occurred while sending the invitation.',
                            'danger');
                    });
            });
        }

        // Initial fetch of agencies and invitations when the page loads
        // Fetch agencies first, then invitations
        fetchAgencies();

    });
</script>