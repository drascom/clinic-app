<?php
// Start session only if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/auth/auth.php';


$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (login_user($email, $password)) {
        // login_user() has already set the session data
        // Get the session data for cookies
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['user_role'];
        $username = $_SESSION['username'];
        $agency_id = $_SESSION['agency_id'];

        // Set cookies (client-side access)
        setcookie('user_id', $user_id, time() + (86400 * 30), "/");
        setcookie('username', $username, time() + (86400 * 30), "/");
        setcookie('user_role', $user_role, time() + (86400 * 30), "/");
        setcookie('agency_id', $agency_id, time() + (86400 * 30), "/");

        // Redirect based on user role after successful login
        if (is_staff()) {
            header('Location: /staff/staff-calendar.php');
        } else {
            header('Location: /dashboard.php');
        }
        exit();
    } else {
        $error_message = 'Invalid email or password.';
    }
}

$page_title = "Login";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Surgery Patient Management</title>

    <!-- Custom Fonts -->
    <link rel="stylesheet" href="/assets/css/fonts.css">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">

    <!-- API Helper for secure POST requests -->
    <script src="/assets/js/api-helper.js"></script>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid pe-2 ">
            <a class="navbar-brand fw-bold" href="/">
                <i class="fas fa-heartbeat me-2"></i>
                <span class="d-none d-sm-inline">Liv Patient Management</span>
                <span class="d-inline d-sm-none">LivPM</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse mx-auto" id="navbarNav">

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="/auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/auth/signup.php">
                            <i class="fas fa-user-plus me-1"></i>Sign Up
                        </a>
                    </li>
                    <li class="nav-item">
                        <a id="theme-btn" class="nav-link" href="#">
                            <i class="fas fa-moon"></i>dasd
                        </a>
                    </li>
                </ul>

            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container emp-10 frosted">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card ">
                        <div class="card-header frosted">
                            <h4>Login</h4>
                        </div>
                        <div class="card-body ">
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>
                            <!-- Form Error Message -->
                            <div id="form-error" class="alert alert-danger d-none" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <span id="form-error-message"></span>
                            </div>

                            <form method="POST" id="loginForm" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email address</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <button type="submit" class="btn btn-primary" id="login-btn">
                                    <i class="fas fa-sign-in-alt me-1"></i>Login
                                </button>
                            </form>

                            <!-- Quick Login Buttons -->
                            <div class="mt-4">
                                <h6 class="text-muted mb-3">Quick Login (Demo Users)</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <button type="button"
                                            class="btn btn-outline-danger btn-sm w-100 quick-login-btn"
                                            data-email="admin@example.com" data-password="">
                                            <i class="fas fa-user-shield me-1"></i>Admin
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <button type="button"
                                            class="btn btn-outline-primary btn-sm w-100 quick-login-btn"
                                            data-email="editor@example.com" data-password="">
                                            <i class="fas fa-user-edit me-1"></i>Editor
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <button type="button"
                                            class="btn btn-outline-success btn-sm w-100 quick-login-btn"
                                            data-email="agent@example.com" data-password="">
                                            <i class="fas fa-user-tie me-1"></i>Agent
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <button type="button"
                                            class="btn btn-outline-warning btn-sm w-100 quick-login-btn"
                                            data-email="tech@example.com" data-password="">
                                            <i class="fas fa-user-cog me-1"></i>Tech
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3">
                                <a href="/auth/forgot_password.php">Forgot Password?</a>
                            </p>
                            <p class="mt-3">Ask website Admin invitation to be a member.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer class="bg-dark text-light py-4 mt-5 d-none d-xl-block">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-heartbeat me-2"></i>
                        Surgery Patient Management
                    </h6>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-flex flex-row justify-content-md-end ">
                        <small class="text-muted text-light">
                            <a class="dropdown-item text-light" style="color: white;" href="/auth/signup.php">
                                <i class="fas fa-copyright ms-2"></i>Surgery Patient
                                Management.
                            </a>
                        </small>
                        <small class="text-muted text-light"></small>
                        <a class="dropdown-item text-light" style="color: white;" href="/auth/logout.php">
                            <?php echo date('Y'); ?> All rights reserved.
                        </a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Login Form JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const loginForm = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const loginBtn = document.getElementById('login-btn');
            const quickLoginButtons = document.querySelectorAll('.quick-login-btn');

            // Setup form validation
            setupFormValidation();

            // Setup form submission
            loginForm.addEventListener('submit', handleFormSubmit);

            // Quick login functionality
            quickLoginButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const email = this.getAttribute('data-email');
                    const password = this.getAttribute('data-password');

                    // Fill the form fields
                    emailInput.value = email;
                    // passwordInput.value = password;

                    // Clear any validation states
                    resetForm();

                    // Add visual feedback
                    this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Logging in...';
                    this.disabled = true;

                    // Submit the form after validation
                    setTimeout(() => {
                        if (validateForm()) {
                            loginForm.submit();
                        } else {
                            // Reset button if validation fails
                            this.innerHTML = '<i class="fas fa-user-shield me-1"></i>' + this.textContent.split(' ')[0];
                            this.disabled = false;
                        }
                    }, 500);
                });
            });

            /**
             * Setup form validation
             */
            function setupFormValidation() {
                const inputs = loginForm.querySelectorAll('input');

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
                // Password validation
                else if (field.type === 'password' && value && value.length < 1) {
                    isValid = false;
                    message = 'Password is required.';
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
                const inputs = loginForm.querySelectorAll('input[required]');
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

                // Show loading state
                loginBtn.disabled = true;
                loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Logging in...';

                // Submit the form
                setTimeout(() => {
                    loginForm.submit();
                }, 300);
            }

            /**
             * Show form error message
             */
            function showFormError(message) {
                const errorDiv = document.getElementById('form-error');
                const errorMessage = document.getElementById('form-error-message');
                errorMessage.textContent = message;
                errorDiv.classList.remove('d-none');
                errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            /**
             * Hide form error message
             */
            function hideFormError() {
                const errorDiv = document.getElementById('form-error');
                errorDiv.classList.add('d-none');
            }

            /**
             * Reset form validation state
             */
            function resetForm() {
                loginForm.classList.remove('was-validated');
                const inputs = loginForm.querySelectorAll('input');
                inputs.forEach(input => {
                    input.classList.remove('is-valid', 'is-invalid');
                    const feedback = input.parentNode.querySelector('.invalid-feedback');
                    if (feedback) feedback.textContent = '';
                });
                hideFormError();
            }
        });
    </script>
</body>

</html>