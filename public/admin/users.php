<?php
require_once __DIR__ . '/../auth/auth.php';

if (!is_logged_in() || !is_admin()) {
    // Redirect to login page or show an unauthorized message
    header('Location: ../auth/login.php');
    exit();
}
include __DIR__ . '/../includes/header.php';
$page_title = "User Management";
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
                    <i class="fas fa-users me-2 text-primary"></i>
                    User Management
                </h4>
                <div class="btn-group" role="group">
                    <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#userModal"
                        id="addUserBtn">
                        <i class="fas fa-plus me-1"></i>
                        <span class="d-none d-sm-inline">Add User</span>
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
                        placeholder="Search users by email, username, or role...">
                    <button class="btn btn-outline-secondary" type="button" id="clear-search" title="Clear search">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="text-muted small ms-4">
                    <i class="fas fa-info-circle me-1"></i>
                    <span id="user-count">Loading...</span> users found
                </div>
            </fieldset>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm" id="usersTable">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Surname</th>
                            <th>Role</th>
                            <th class="d-none d-md-table-cell">Agency</th>
                            <th class="d-none d-lg-table-cell">Is Active</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- User rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- User Add/Edit Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">
                        <i class="fas fa-user-plus me-2"></i>
                        Add User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="userForm">
                        <input type="hidden" id="userId">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>
                                        Email Address
                                    </label>
                                    <input type="email" class="form-control" id="email" placeholder="user@example.com"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-user me-1"></i>
                                        Username
                                    </label>
                                    <input type="text" class="form-control" id="username" placeholder="Enter username"
                                        required>
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">
                                        <i class="fas fa-user me-1"></i>
                                        Name
                                    </label>
                                    <input type="text" class="form-control" id="name" placeholder="Enter name" required>

                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="surname" class="form-label">
                                        <i class="fas fa-user-tag me-1"></i>
                                        Surname
                                    </label>
                                    <input type="text" class="form-control" id="surname" placeholder="Enter surname"
                                        required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-1"></i>
                                        Password
                                    </label>
                                    <input type="password" class="form-control" id="password"
                                        placeholder="Enter password" required>
                                    <small class="form-text text-muted" id="passwordHelp">
                                        Required for new users. Leave blank to keep current password when editing.
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">
                                        <i class="fas fa-user-tag me-1"></i>
                                        Role
                                    </label>
                                    <select class="form-select" id="role" required>
                                        <option value="admin">Admin</option>
                                        <option value="agent">Agent</option>
                                        <option value="editor">Editor</option>
                                        <option value="technician">Technician</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="agency" class="form-label">
                                        <i class="fas fa-building me-1"></i>
                                        Agency
                                    </label>
                                    <select class="form-select" id="agency">
                                        <option value="">Select Agency (Optional)</option>
                                        <!-- Agency options will be loaded dynamically -->
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-check-label" for="isActive">
                                    <i class="fas fa-toggle-on me-1 fas-lg"></i>
                                    Is Active
                                </label>
                            </div>
                        </div>
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="isActive">
                        </div>
                </div>
                </form>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
                    <button type="button" class="btn btn-primary" id="saveUserBtn">
                        <i class="fas fa-save me-1"></i>Save User
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
    // User Management Script
    document.addEventListener('DOMContentLoaded', function () {
        const userModal = document.getElementById('userModal');
        const userForm = document.getElementById('userForm');
        const userIdInput = document.getElementById('userId');
        const emailInput = document.getElementById('email');
        const nameInput = document.getElementById('name');
        const surnameInput = document.getElementById('surname');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const roleSelect = document.getElementById('role');
        const agencySelect = document.getElementById('agency');
        const addUserBtn = document.getElementById('addUserBtn');
        const saveUserBtn = document.getElementById('saveUserBtn');
        const usersTableBody = document.querySelector('#usersTable tbody');
        const passwordHelp = document.getElementById('passwordHelp');
        let allUsers = []; // Store all users for client-side filtering
        let allAgencies = []; // Store all agencies for dropdown
        const isActiveInput = document.getElementById('isActive');

        const searchInput = document.getElementById('search-input');
        const clearSearchBtn = document.getElementById('clear-search');

        if (searchInput && clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function () {
                searchInput.value = '';
                populateUsersTable(allUsers);
            });
        }
        // Function to display status messages

        // Function to fetch agencies from the API
        function fetchAgencies() {
            apiRequest('agencies', 'list')
                .then(data => {
                    if (data.success) {
                        allAgencies = data.agencies;
                        populateAgencyDropdown();
                    } else {
                        console.error('Error fetching agencies:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error fetching agencies:', error);
                });
        }

        // Function to populate agency dropdown
        function populateAgencyDropdown() {
            agencySelect.innerHTML = '<option value="">Select Agency (Optional)</option>';
            allAgencies.forEach(agency => {
                const option = document.createElement('option');
                option.value = agency.id;
                option.textContent = agency.name;
                agencySelect.appendChild(option);
            });
        }

        // Function to get agency name by ID
        function getAgencyName(agencyId) {
            if (!agencyId) return 'None';
            const agency = allAgencies.find(a => a.id == agencyId);
            return agency ? agency.name : 'Unknown';
        }

        // Function to fetch users from the API
        function fetchUsers() {
            apiRequest('users', 'list')
                .then(data => {
                    if (data.success) {
                        allUsers = data.data; // Store fetched users
                        populateUsersTable(allUsers);
                    } else {
                        console.error('Error fetching users:', data.message); // Log 12
                        showToast('Error fetching users: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    showToast('Error fetching users: ' + error, 'danger');
                });



        }
        // Function to populate the users table
        function populateUsersTable(users) {
            usersTableBody.innerHTML = ''; // Clear existing rows
            if (users.length === 0) {
                usersTableBody.innerHTML = '<tr><td colspan="8" class="text-center">No users found.</td></tr>';
                return;
            }

            users.forEach(user => {
                const roleColor = user.role === 'admin' ? 'danger' : 'primary';
                const agencyName = getAgencyName(user.agency_id);
                const row = `
                        <tr>
                            <td><span class="fw-medium">${user.id}</span></td>
                            <td><span class="text-truncate-mobile">${user.email}</span></td>
                            <td><span class="fw-medium">${user.username}</span></td>
                            <td><span class="fw-medium">${user.name}</span></td>
                            <td><span class="fw-medium">${user.surname}</span></td>
                            <td><span class="badge bg-${roleColor}">${user.role}</span></td>
                            <td class="d-none d-md-table-cell"><small>${agencyName}</small></td>
                            <td class="d-none d-lg-table-cell"><small>${user.is_active ? 'Yes' : 'No'}</small></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="/admin/email_settings.php?id=${user.id}" class="btn btn-sm btn-text text-success " title="Email Settings">
                                        <i class="fas fa-envelope"></i>
                                        <span class="d-none d-lg-inline ms-1">Email</span>
                                    </a>
                                    <button class="btn btn-sm btn-text text-primary edit-user-btn" data-id="${user.id}"
                                            data-bs-toggle="modal" data-bs-target="#userModal" title="Edit User">
                                        <i class="fas fa-edit"></i>
                                        <span class="d-none d-lg-inline ms-1">Edit</span>
                                    </button>
                                    <button class="btn btn-sm btn-text text-danger delete-user-btn" data-id="${user.id}" title="Delete User">
                                        <i class="fas fa-trash-alt"></i>
                                        <span class="d-none d-lg-inline ms-1">Delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                usersTableBody.innerHTML += row;
            });

            // Update user count
            document.getElementById('user-count').textContent = users.length;
        }
        // Function to reset the user form
        function resetUserForm() {
            userIdInput.value = '';
            userForm.reset();
            userForm.classList.remove('was-validated');
            passwordInput.required = true; // Password is required for new users
            showPasswordHelp(true);
            document.getElementById('userModalLabel').textContent = 'Add User';
        }

        // Function to show/hide password help text
        function showPasswordHelp(show) {
            if (passwordHelp) {
                passwordHelp.style.display = show ? 'block' : 'none';
            }
        }

        // Event listener for the Add User button
        if (addUserBtn) {
            addUserBtn.addEventListener('click', resetUserForm);
        }

        // Event listener for the modal show event (for editing)
        if (userModal) {
            userModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; // Button that triggered the modal
                const userId = button.getAttribute('data-id');

                if (userId) {
                    // Editing existing user
                    document.getElementById('userModalLabel').textContent = 'Edit User';
                    userIdInput.value = userId;
                    userForm.classList.remove('was-validated');
                    passwordInput.required = false; // Password is not required when editing
                    showPasswordHelp(false); // Hide password help when editing

                    // Fetch user data to populate the form
                    apiRequest('users', 'get', {
                        id: userId
                    })
                        .then(data => {
                            if (data.success && data.user) {
                                emailInput.value = data.user.email;
                                nameInput.value = data.user.name;
                                surnameInput.value = data.user.surname;
                                usernameInput.value = data.user.username;
                                roleSelect.value = data.user.role;
                                agencySelect.value = data.user.agency_id || '';
                                isActiveInput.checked = data.user.is_active ==
                                    1; // Set switch state
                                // Password field is intentionally not populated for security
                            } else {
                                console.error('Error fetching user data:', data.message);
                                showToast('Error fetching user data: ' + data.message,
                                    'danger');
                                const modal = bootstrap.Modal.getInstance(userModal);
                                modal.hide(); // Hide modal on error
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching user data:', error);
                            showToast('Error fetching user data: ' + error, 'danger');
                            const modal = bootstrap.Modal.getInstance(userModal);
                            modal.hide(); // Hide modal on error
                        });
                } else {
                    // Adding new user (handled by addUserBtn click)
                    resetUserForm();
                }
            });
        }


        // Event listener for the Save User button
        if (saveUserBtn) {
            saveUserBtn.addEventListener('click', function () {
                // Add Bootstrap validation classes
                userForm.classList.add('was-validated');

                if (!userForm.checkValidity()) {
                    userForm.reportValidity();
                    return;
                }

                const userId = userIdInput.value;
                const email = emailInput.value;
                const name = nameInput.value;
                const surname = surnameInput.value;
                const username = usernameInput.value;
                const password = passwordInput.value;
                const role = roleSelect.value;
                const agencyId = agencySelect.value || null;

                const userData = {
                    entity: 'users', // Add the missing entity here
                    email: email,
                    name: name,
                    surname: surname,
                    username: username,
                    role: role,
                    agency_id: agencyId,
                    is_active: isActiveInput.checked ? 1 : 0 // Get switch value (1 or 0)
                };

                let action = 'add';
                if (userId) {
                    action = 'edit';
                    userData.id = userId;
                    if (password) { // Only include password if it's provided during edit
                        userData.password = password;
                    }
                    userData.updated_by = currentUserId;
                } else {
                    userData.password = password; // Password is required for new users
                    userData.created_by = currentUserId;
                }

                userData.action = action; // Add action to the data payload

                fetch('/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(userData)
                })
                    .then(response => response.json())
                    .then(response => {
                        if (response.success) {
                            const modal = bootstrap.Modal.getInstance(userModal);
                            modal.hide();
                            fetchUsers(); // Refresh the table
                            showToast(userId ? 'User updated successfully!' :
                                'User added successfully!', 'success');
                        } else {
                            console.error('Error saving user:', data.message);
                            showToast('Error saving user: ' + data.message, 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error saving user:', error);
                        showToast('Error saving user: ' + error, 'danger');
                    });
            });
        }

        // Event delegation for Edit and Delete buttons
        if (usersTableBody) {
            usersTableBody.addEventListener('click', function (event) {
                const target = event.target;

                // Handle Delete button click
                if (target.classList.contains('delete-user-btn') || target.closest(
                    '.delete-user-btn')) { // Also check parent for icon clicks
                    const deleteButton = target.closest('.delete-user-btn');
                    const userId = deleteButton.getAttribute('data-id');
                    if (confirm('Are you sure you want to delete this user?')) {
                        fetch('/api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                entity: 'users',
                                action: 'delete',
                                id: userId
                            })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    fetchUsers(); // Refresh the table
                                    showToast('User deleted successfully!', 'success');
                                } else {
                                    showToast('Error deleting user: ' + data.message,
                                        'danger');
                                }
                            })
                            .catch(error => {
                                showToast('Error deleting user: ' + error, 'danger');
                            });
                    }
                }
            });
        }

        // Event listener for the search input
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                const filteredUsers = allUsers.filter(user => {
                    // Search by ID, email, username, or role
                    return user.id.toString().includes(searchTerm) ||
                        user.email.toLowerCase().includes(searchTerm) ||
                        user.username.toLowerCase().includes(searchTerm) ||
                        user.role.toLowerCase().includes(searchTerm);
                });
                populateUsersTable(filteredUsers);
            });
        }


        // Initial fetch of agencies and users when the page loads
        if (window.location.pathname.includes("users.php")) {
            fetchAgencies();
            fetchUsers();
        }

    });
</script>