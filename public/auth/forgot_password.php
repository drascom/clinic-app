<?php
require_once '../includes/db.php';
require_once '../api_handlers/email_functions.php'; // Include the email functions

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    // Log the incoming request
    $log_entry = "[" . date('Y-m-d H:i:s') . "] Forgot password request received for email: " . ($email ? $email : 'empty') . "\n";
    file_put_contents(__DIR__ . '/../../logs/auth.log', $log_entry, FILE_APPEND);

    if (empty($email)) {
        $message = 'Please enter your email address.';
        $message_type = 'danger';
        // Log empty email
        $log_entry = "[" . date('Y-m-d H:i:s') . "] Forgot password request failed: Empty email provided.\n";
        file_put_contents(__DIR__ . '/../../logs/auth.log', $log_entry, FILE_APPEND);
    } else {
        // Find the user by email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Log user lookup result
        if ($user) {
            $log_entry = "[" . date('Y-m-d H:i:s') . "] User found for email: {$email}\n";
            file_put_contents(__DIR__ . '/../../logs/auth.log', $log_entry, FILE_APPEND);

            $user_id = $user['id'];
            // Generate a unique token
            $token = bin2hex(random_bytes(32));
            // Set token expiry (e.g., 1 hour)
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // TODO: You need to add columns to your 'users' table for 'reset_token' (VARCHAR) and 'reset_expiry' (DATETIME).
            // Example SQL: ALTER TABLE users ADD reset_token VARCHAR(64) NULL, ADD reset_expiry DATETIME NULL;

            // Store the token and expiry in the database
            $update_stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?");
            if ($update_stmt->execute([$token, $expiry, $user_id])) {
                // Log token storage success
                $log_entry = "[" . date('Y-m-d H:i:s') . "] Reset token stored for user ID: {$user_id}\n";
                file_put_contents(__DIR__ . '/../../logs/auth.log', $log_entry, FILE_APPEND);
            } else {
                // Log token storage failure
                $log_entry = "[" . date('Y-m-d H:i:s') . "] Failed to store reset token for user ID: {$user_id}\n";
                file_put_contents(__DIR__ . '/../../logs/auth.log', $log_entry, FILE_APPEND);
            }


            // Construct the reset link
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/auth/reset_password.php?token=" . $token; // Adjust URL as needed

            // Send the password reset email
            if (send_password_reset_email($email, $reset_link)) {
                $message = 'If an account with that email address exists, a password reset link has been sent.';
                $message_type = 'success';
                // Email sending success is logged in email.php

                // Add JavaScript to redirect after 2 seconds
                echo '<script>
                        setTimeout(function() {
                            window.location.href = "login.php";
                        }, 2000); // 2000 milliseconds = 2 seconds
                      </script>';
            } else {
                $message = 'Failed to send password reset email. Please try again later.';
                $message_type = 'danger';
                // Email sending failure is logged in email.php
            }
        } else {
            // Log user not found
            $log_entry = "[" . date('Y-m-d H:i:s') . "] User not found for email: {$email}\n";
            file_put_contents(__DIR__ . '/../../logs/auth.log', $log_entry, FILE_APPEND);
            $user_not_found = true; // Set flag to show modal
        }
    }
}

$page_title = "Forgot Password";

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
                    <i class="fas fa-key me-2 text-warning"></i>Forgot Password
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
            <fieldset class="border rounded p-3 mb-3">
                <legend class="w-auto px-2 mb-3" style="font-size: 1rem;">Reset Password</legend>
                <form method="POST" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">Enter your email address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Reset Link</button>
                </form>
            </fieldset>
        </div>
    </div>
</div>

<!-- Modal for User Not Found -->
<div class="modal fade" id="userNotFoundModal" tabindex="-1" aria-labelledby="userNotFoundModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userNotFoundModalLabel">Email Address Not Found</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                The email address you entered was not found in our system. Please check the email address and try again.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    <?php if (isset($user_not_found) && $user_not_found): ?>
        var userNotFoundModal = new bootstrap.Modal(document.getElementById('userNotFoundModal'), {});
        userNotFoundModal.show();
    <?php endif; ?>
</script>

<?php require_once '../includes/footer.php'; ?>