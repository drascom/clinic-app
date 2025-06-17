<?php
require_once '../includes/db.php';

// Fetch agencies for dropdown
$agencies = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM agencies ORDER BY name");
    $agencies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If there's an error fetching agencies, continue without them
}

$page_title = "Sign Up";
?>
<?php require_once '../includes/header.php'; ?>
<div class="container emp frosted">
    <div class="card frosted">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center py-2">
                <a href="login.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left me-1"></i>
                    <span class="d-none d-sm-inline">Login</span>
                </a>
                <h4 class="mb-0">
                    <i class="fas fa-user-plus me-2 text-primary"></i>Sign Up
                </h4>
                <div style="width: 80px;"></div> <!-- Spacer -->
            </div>
        </div>
        <div class="card-body">
            <!-- Alert container for messages -->
            <div id="alert-container"></div>
            <fieldset class="border rounded p-3 mb-3">
                <legend class="w-auto px-2 mb-3" style="font-size: 1rem;">Create Your Account</legend>
                <form id="signup-form" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required minlength="2">
                        <div class="form-text">At least 2 characters required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="surname" class="form-label">Surname</label>
                        <input type="text" class="form-control" id="surname" name="surname" required minlength="2">
                        <div class="form-text">At least 2 characters required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required
                            minlength="8">
                        <div class="form-text">Password must be at least 8 characters long.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                            required>
                    </div>
                    <div class="mb-3">
                        <label for="agency_id" class="form-label">Agency</label>
                        <select class="form-select" id="agency_id" name="agency_id">
                            <option value="">Select Agency (Optional)</option>
                            <?php foreach ($agencies as $agency): ?>
                                <option value="<?php echo htmlspecialchars($agency['id']); ?>">
                                    <?php echo htmlspecialchars($agency['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" id="signup-btn">
                        <i class="fas fa-user-plus me-1"></i>Sign Up
                    </button>
                </form>
            </fieldset>
            <p class="mt-3 text-center">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</div>

<script>
    // Function to display alert messages
    function showAlert(message, type = 'danger') {
        const alertContainer = document.getElementById('alert-container');
        const alertHtml = `
    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
`;
        alertContainer.innerHTML = alertHtml;

        // Scroll to top to show the alert
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Function to clear alerts
    function clearAlerts() {
        document.getElementById('alert-container').innerHTML = '';
    }

    // Handle form submission
    document.getElementById('signup-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        clearAlerts();

        // Get form data
        const formData = new FormData(this);
        const data = {
            email: formData.get('email'),
            name: formData.get('name'),
            surname: formData.get('surname'),
            password: formData.get('password'),
            confirm_password: formData.get('confirm_password'),
            agency_id: formData.get('agency_id') || null
        };

        // Disable submit button and show loading state
        const submitBtn = document.getElementById('signup-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Signing Up...';

        try {
            // Make API request
            const response = await apiRequest('users', 'register', data);

            if (response.success) {
                showAlert(response.message || 'Registration successful! Redirecting to login...', 'success');

                // Redirect to login page after a short delay
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                showAlert(response.error || 'Registration failed. Please try again.');
            }
        } catch (error) {
            console.error('Registration error:', error);
            showAlert('An error occurred. Please try again.');
        } finally {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });

    // Client-side password validation
    document.getElementById('confirm_password').addEventListener('input', function () {
        const password = document.getElementById('password').value;
        const confirmPassword = this.value;

        if (confirmPassword && password !== confirmPassword) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });

    // Password strength validation
    document.getElementById('password').addEventListener('input', function () {
        const password = this.value;

        if (password.length > 0 && password.length < 8) {
            this.setCustomValidity('Password must be at least 8 characters long');
        } else {
            this.setCustomValidity('');
        }

        // Also check confirm password when password changes
        const confirmPassword = document.getElementById('confirm_password');
        if (confirmPassword.value && password !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    });
</script>
<?php require_once '../includes/footer.php'; ?>