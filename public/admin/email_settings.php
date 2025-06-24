<?php
require_once __DIR__ . '/../auth/auth.php';

if (!is_logged_in()) {
    header('Location: ../login.php');
    exit();
}
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container p-4 pb-5 content-pb-extra">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card pb-4 mb-4">
                <div class="card-header d-flex justify-content-between">
                    <h2>Email Settings</h2>
                    <a href="/admin/users.php" class="btn text-primary " title="Email Settings">
                        <i class="fas fa-user me-1"></i>
                        <span class="d-none d-sm-inline ms-1">Users</span>
                    </a>
                </div>
                <div class="card-body pb-4">
                    <form id="email-settings-form" class="mb-4">
                        <input type="hidden" class="form-control" name="user_id"
                            value="<?php echo isset($_GET['id']) ? (int)$_GET['id'] : get_user_id(); ?>">
                        <div class="mb-3">
                            <label for="email_address" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email_address" name="email_address" required>
                        </div>
                        <div class="mb-3">
                            <label for="smtp_host" class="form-label">SMTP Host</label>
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" required>
                        </div>
                        <div class="mb-3">
                            <label for="smtp_port" class="form-label">SMTP Port</label>
                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" required>
                        </div>
                        <div class="mb-3">
                            <label for="smtp_username" class="form-label">SMTP Username</label>
                            <input type="text" class="form-control" id="smtp_username" name="smtp_username" required>
                        </div>
                        <div class="mb-3">
                            <label for="smtp_password" class="form-label">SMTP Password</label>
                            <input type="password" class="form-control" id="smtp_password" name="smtp_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="smtp_secure" class="form-label">SMTP Secure</label>
                            <select class="form-control" id="smtp_secure" name="smtp_secure">
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-end mb-4">
                            <button type="submit" class="btn btn-primary ">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Function to fetch email settings
            function fetchEmailSettings() {
                apiRequest('users', 'get_email_settings', {
                        user_id: <?php echo isset($_GET['id']) ? (int)$_GET['id'] : get_user_id(); ?>
                    })
                    .then(response => {
                        if (response.success && response.settings) {
                            const settings = response.settings;
                            document.getElementById('email_address').value = settings.email_address;
                            document.getElementById('smtp_host').value = settings.smtp_host;
                            document.getElementById('smtp_port').value = settings.smtp_port;
                            document.getElementById('smtp_username').value = settings.smtp_username;
                            document.getElementById('smtp_password').value = settings.smtp_password;
                            document.getElementById('smtp_secure').value = settings.smtp_secure;
                        } else {
                            console.error('Error fetching email settings:', response.message);
                            alert('Error fetching email settings: ' + response.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching email settings:', error);
                        alert('An error occurred while fetching email settings.');
                    });
            }

            // Fetch settings on page load
            fetchEmailSettings();

            document.getElementById('email-settings-form').addEventListener('submit', function(event) {
                event.preventDefault();
                const formData = new FormData(this);
                const data = Object.fromEntries(formData.entries());
                const userId = <?php echo isset($_GET['user_id']) ? (int)$_GET['user_id'] : 'null'; ?>;
                if (userId !== null) {
                    data.user_id = userId;
                }

                apiRequest('email_settings', 'save', data)
                    .then(response => {
                        if (response.success) {
                            console.log(response)
                            showToast('Mail Setting Saved',
                                'success'
                            );
                            setTimeout(() => {
                                window.location.href = '/admin/users.php';
                            }, 1500);

                        } else {
                            alert('Error saving settings: ' + response.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error saving settings:', error);
                        alert('An error occurred while saving settings.');
                    });
            });
        });
    </script>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>