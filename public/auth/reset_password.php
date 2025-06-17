<?php
session_start(); // Start the session
require_once '../includes/db.php';
require_once '../includes/auth.php'; // Assuming auth.php has password hashing/verification functions

$message = '';
$message_type = '';
$token = $_GET['token'] ?? '';
$user_id = null;

// Validate the token
if (empty($token)) {
    $message = 'Invalid or missing reset token.';
    $message_type = 'danger';
} else {
    // Find user by token and check expiry
    $stmt = $pdo->prepare("SELECT id, reset_expiry FROM users WHERE reset_token = ? AND reset_expiry > CURRENT_TIMESTAMP");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $user_id = $user['id'];
        // Token is valid and not expired
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Process new password submission
            $new_password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($new_password) || empty($confirm_password)) {
                $message = 'Please enter and confirm your new password.';
                $message_type = 'danger';
            } elseif ($new_password !== $confirm_password) {
                $message = 'Passwords do not match.';
                $message_type = 'danger';
            } else {
                // Hash the new password (assuming hash_password function exists in auth.php)
                // TODO: Ensure your auth.php has a function like hash_password()
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT); // Using PHP's built-in hashing

                // Update the user's password and invalidate the token
                $update_stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
                if ($update_stmt->execute([$hashed_password, $user_id])) {
                    // Set success message in session and let the page render
                    $_SESSION['password_reset_success'] = 'Your password has been reset successfully. You will be redirected to the login page shortly.';
                    $message = 'Your password has been reset successfully. You can now log in.';
                    $message_type = 'success';
                    // Invalidate the token and redirect to login
                } else {
                    $message = 'Failed to update password. Please try again.';
                    $message_type = 'danger';
                }
            }
        }
        // If GET request or POST failed validation, show the form
    } else {
        $message = 'The password reset link is invalid or has expired. Please request a new password reset link.';
        $message_type = 'danger';
    }
}

$page_title = "Reset Password";
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
                    <i class="fas fa-key me-2 text-warning"></i>Reset Password
                </h4>
                <div style="width: 80px;"></div> <!-- Spacer -->
            </div>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($user) && $message_type !== 'success'): ?>
                <fieldset class="border rounded p-3 mb-3">
                    <legend class="w-auto px-2 mb-3" style="font-size: 1rem;">Set New Password</legend>
                    <form method="POST" novalidate>
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                required>
                        </div>
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </form>
                </fieldset>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Password Reset Success Modal -->
<div class="modal fade" id="passwordResetSuccessModal" tabindex="-1" aria-labelledby="passwordResetSuccessModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passwordResetSuccessModalLabel">Password Reset Successful</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Your password has been reset successfully. You will be redirected to the login page shortly.
            </div>
        </div>
    </div>
</div>

<script>
    <?php if ($message_type === 'success'): ?>
        var passwordResetSuccessModal = new bootstrap.Modal(document.getElementById('passwordResetSuccessModal'), {
            keyboard: false
        });
        passwordResetSuccessModal.show();

        setTimeout(function () {
            window.location.href = 'login.php';
        }, 3000); // Redirect after 3 seconds
    <?php endif; ?>
</script>

<?php require_once '../includes/footer.php'; ?>