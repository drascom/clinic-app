<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}


$page_title = "All Surgeries";
require_once '../includes/header.php';
$user_id = $_GET['user_id'];
?>

<div class="container py-1">
    <div id="message" class="alert" style="display: none;"></div>

    <!-- Update Profile Information Card -->
    <div class="card my-4 frosted">
        <div class="card-header frosted">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-user-edit me-2"></i>Update Profile
                </h5>
                <a href="/auth/logout.php" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
        <div class="card-body frosted">
            <form id="updateProfileForm">
                <input type="hidden" name="id" value="<?php echo $user_id; ?>">
                <input type="hidden" name="role" id="role" value="">
                <div class="form-group mb-3">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" class="form-control" id="username" name="username" value="">
                </div>
                <div class="form-group mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" value="">
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-save me-1"></i>Update Profile
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Card -->
    <div class="card mb-4 frosted-glass">
        <div class="card-header frosted">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-lock me-2"></i>Change Password
                </h5>

            </div>
        </div>
        <div class="card-body frosted">
            <form id="changePasswordForm" class="needs-validation" novalidate>
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                <div class="form-group mb-3">
                    <label for="new_password" class="form-label">New Password: <span
                            class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="new_password" name="new_password" minlength="6"
                        maxlength="50" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{6,}$" required>
                    <div class="invalid-feedback" style="display: none;">
                        Password must be at least 6 characters long and contain at least one uppercase letter, one
                        lowercase
                        letter, and one number.
                    </div>
                    <div class="valid-feedback" style="display: none;">
                        Password looks good!
                    </div>
                    <small class="form-text text-muted">Minimum 6 characters with uppercase, lowercase, and
                        number</small>
                </div>
                <div class="form-group mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password: <span
                            class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                        minlength="6" maxlength="50" required>
                    <div class="invalid-feedback" id="confirm-password-feedback" style="display: none;">
                        Please confirm your password.
                    </div>
                    <div class="valid-feedback" style="display: none;">
                        Passwords match!
                    </div>
                    <small id="password-match-message" class="form-text text-muted">Re-enter your password to
                        confirm</small>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-key me-1"></i>Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>
    document.getElementById('updateProfileForm').addEventListener('submit', function (event) {
        event.preventDefault();

        const userId = document.getElementById('updateProfileForm').querySelector('input[name="id"]').value;
        const username = document.getElementById('username').value;
        const role = document.getElementById('role').value;
        const email = document.getElementById('email').value;

        const userData = {
            id: userId,
            username: username,
            email: email,
            role: role,
            entity: 'users',
            action: 'update'
        };

        fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(userData)
        })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('message');
                messageDiv.style.display = 'block';
                if (data.success) {
                    messageDiv.classList.remove('alert-danger');
                    messageDiv.classList.add('alert-success');
                    messageDiv.textContent = data.message ||
                        'Profile updated successfully!'; // Use a default success message
                    // Update username in navbar if it was changed
                    if (data.message && data.message.includes('Username updated')) {
                        // This requires updating the header dynamically or reloading the page
                        // For simplicity, we'll just show the message. A full solution
                        // might involve a page reload or more complex JS DOM manipulation.
                        // location.reload(); // Option to reload page to show updated username in header
                    }
                } else {
                    console.error('Error updating profile:', data.message || data.error);
                    messageDiv.classList.remove('alert-success');
                    messageDiv.classList.add('alert-danger');
                    messageDiv.textContent = data.error || data.message ||
                        'An error occurred while updating the profile.'; // Use a default error message
                }
            })
            .catch(error => {
                console.error('Error updating profile:', error);
                const messageDiv = document.getElementById('message');
                messageDiv.style.display = 'block';
                messageDiv.classList.remove('alert-success');
                messageDiv.classList.add('alert-danger');
                messageDiv.textContent = 'An error occurred while updating the profile.';
            });
    });

    document.getElementById('changePasswordForm').addEventListener('submit', function (event) {
        event.preventDefault();
        event.stopPropagation();

        const form = this;
        const userId = form.querySelector('input[name="user_id"]').value;
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const confirmPasswordField = document.getElementById('confirm_password');
        const confirmFeedback = document.getElementById('confirm-password-feedback');

        // Custom validation for password matching
        if (newPassword !== confirmPassword) {
            confirmPasswordField.setCustomValidity('Passwords do not match');
            confirmFeedback.textContent = 'Passwords do not match.';
        } else {
            confirmPasswordField.setCustomValidity('');
            confirmFeedback.textContent = 'Please confirm your password.';
        }

        // Add Bootstrap validation classes and show feedback
        form.classList.add('was-validated');

        // Show validation feedback messages
        const feedbackElements = form.querySelectorAll('.invalid-feedback, .valid-feedback');
        feedbackElements.forEach(el => el.style.display = 'block');

        // Check if form is valid
        if (!form.checkValidity()) {
            return;
        }

        const passwordData = {
            user_id: userId,
            new_password: newPassword,
            confirm_password: confirmPassword,
            entity: 'users',
            action: 'change_password'
        };

        fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(passwordData)
        })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('message');
                messageDiv.style.display = 'block';
                if (data.success) {
                    messageDiv.classList.remove('alert-danger');
                    messageDiv.classList.add('alert-success');
                    messageDiv.textContent = data.message ||
                        'Password changed successfully!'; // Use a default success message
                    // Clear password fields on success
                    document.getElementById('new_password').value = '';
                    document.getElementById('confirm_password').value = '';
                } else {
                    console.error('Error changing password:', data.message || data.error);
                    messageDiv.classList.remove('alert-success');
                    messageDiv.classList.add('alert-danger');
                    messageDiv.textContent = data.error || data.message ||
                        'An error occurred while changing the password.'; // Use a default error message
                }
            })
            .catch(error => {
                console.error('Error changing password:', error);
                const messageDiv = document.getElementById('message');
                messageDiv.style.display = 'block';
                messageDiv.classList.remove('alert-success');
                messageDiv.classList.add('alert-danger');
                messageDiv.textContent = 'An error occurred while changing the password.';
            });
    });
    document.addEventListener('DOMContentLoaded', function () {
        const user_id = <?php echo json_encode($user_id); ?>;
        const username = document.getElementById('username');
        const role = document.getElementById('role');
        const email = document.getElementById('email');
        const statusMessagesDiv = document.getElementById('message');

        // Function to display messages
        function displayMessage(message, type = 'success') {
            statusMessagesDiv.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        }

        function UserData(id) {
            apiRequest('users', 'get', { id: id })
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        username.value = user.username;
                        email.value = user.email;
                        role.value = user.role;
                    } else {
                        displayMessage(`Error loading patient: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error fetching patient:', error);
                    displayMessage('An error occurred while loading patient data.', 'danger');
                });
        }
        UserData(user_id);

        // Bootstrap form validation with real-time password matching
        const changePasswordForm = document.getElementById('changePasswordForm');
        const newPasswordField = document.getElementById('new_password');
        const confirmPasswordField = document.getElementById('confirm_password');
        const passwordMatchMessage = document.getElementById('password-match-message');
        const confirmFeedback = document.getElementById('confirm-password-feedback');

        function validatePasswordMatch() {
            const newPassword = newPasswordField.value;
            const confirmPassword = confirmPasswordField.value;

            if (confirmPassword === '') {
                passwordMatchMessage.textContent = 'Re-enter your password to confirm';
                passwordMatchMessage.className = 'form-text text-muted';
                confirmPasswordField.setCustomValidity('');
                return;
            }

            if (newPassword === confirmPassword && newPassword.length >= 6) {
                passwordMatchMessage.textContent = '✓ Passwords match';
                passwordMatchMessage.className = 'form-text text-success';
                confirmPasswordField.setCustomValidity('');
            } else if (newPassword !== confirmPassword) {
                passwordMatchMessage.textContent = '✗ Passwords do not match';
                passwordMatchMessage.className = 'form-text text-danger';
                confirmPasswordField.setCustomValidity('Passwords do not match');
            } else {
                passwordMatchMessage.textContent = 'Password too short';
                passwordMatchMessage.className = 'form-text text-warning';
                confirmPasswordField.setCustomValidity('Password must be at least 6 characters');
            }

            // Update validation feedback
            if (changePasswordForm.classList.contains('was-validated')) {
                if (confirmPasswordField.checkValidity()) {
                    confirmFeedback.textContent = 'Passwords match!';
                } else {
                    confirmFeedback.textContent = confirmPasswordField.validationMessage;
                }
            }
        }

        // Real-time validation event listeners
        newPasswordField.addEventListener('input', validatePasswordMatch);
        confirmPasswordField.addEventListener('input', validatePasswordMatch);

        // Clear validation state when user starts typing
        newPasswordField.addEventListener('input', function () {
            if (changePasswordForm.classList.contains('was-validated')) {
                changePasswordForm.classList.remove('was-validated');
                // Hide validation feedback messages
                const feedbackElements = changePasswordForm.querySelectorAll('.invalid-feedback, .valid-feedback');
                feedbackElements.forEach(el => el.style.display = 'none');
            }
        });

        confirmPasswordField.addEventListener('input', function () {
            if (changePasswordForm.classList.contains('was-validated')) {
                changePasswordForm.classList.remove('was-validated');
                // Hide validation feedback messages
                const feedbackElements = changePasswordForm.querySelectorAll('.invalid-feedback, .valid-feedback');
                feedbackElements.forEach(el => el.style.display = 'none');
            }
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>