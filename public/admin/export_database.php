<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Start session for CSRF protection and result storage (if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    header('Location: /auth/login.php');
    exit();
}

// Handle export request BEFORE any output
$export_result = null;
$backup_path = null;
$zip_path = null;

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export' &&
    isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']
) {
    // Path to Python script
    $script_path = __DIR__ . '/../../db/export_db_to_json.py';

    // Check if Python script exists
    if (!file_exists($script_path)) {
        $_SESSION['export_result'] = ['success' => false, 'message' => 'Export script not found'];
        header('Location: ' . $_SERVER['PHP_SELF'] . '?exported=1');
        exit();
    } else {
        // Execute Python script
        $command = "python3 " . escapeshellarg($script_path) . " 2>&1";
        $output = [];
        $return_code = 0;

        exec($command, $output, $return_code);

        $output_string = implode("\n", $output);

        if ($return_code === 0) {
            // Parse output to get ZIP path only
            foreach ($output as $line) {
                if (strpos($line, 'SUCCESS:') === 0) {
                    $zip_path = substr($line, 8); // Remove 'SUCCESS:' prefix
                    break;
                }
            }

            // Store result in session to show after redirect
            $_SESSION['export_result'] = [
                'success' => true,
                'message' => 'Database exported successfully',
                'zip_path' => $zip_path,
                'output' => $output_string
            ];

            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['PHP_SELF'] . '?exported=1');
            exit();
        } else {
            // Store error result in session
            $_SESSION['export_result'] = [
                'success' => false,
                'message' => 'Export failed',
                'output' => $output_string
            ];

            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['PHP_SELF'] . '?exported=1');
            exit();
        }
    }
}

// Check for export result from session (after redirect)
if (isset($_SESSION['export_result'])) {
    $export_result = $_SESSION['export_result'];
    unset($_SESSION['export_result']); // Clear it so it doesn't show again
}

// Generate CSRF token for form security
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include header after all processing is complete
$page_title = "Database Export";
include __DIR__ . '/../includes/header.php';

// Get list of existing ZIP backups
function getExistingBackups()
{
    $backup_dir = __DIR__ . '/../../db/backups';
    $backups = [];

    if (is_dir($backup_dir)) {
        // Get ZIP files only
        $zip_files = glob($backup_dir . '/backup_*.zip');
        foreach ($zip_files as $zip_file) {
            $zip_name = basename($zip_file, '.zip');

            // Try to extract manifest from ZIP to get metadata
            $zip = new ZipArchive();
            if ($zip->open($zip_file) === TRUE) {
                $manifest_content = $zip->getFromName($zip_name . '/backup_manifest.json');
                $zip->close();

                if ($manifest_content) {
                    $manifest = json_decode($manifest_content, true);
                    if ($manifest) {
                        $backups[] = [
                            'path' => $zip_file,
                            'name' => $zip_name,
                            'timestamp' => $manifest['backup_info']['timestamp'] ?? 'Unknown',
                            'created_at' => $manifest['backup_info']['created_at'] ?? 'Unknown',
                            'total_tables' => $manifest['backup_info']['total_tables'] ?? 0,
                            'total_records' => $manifest['backup_info']['total_records'] ?? 0,
                            'size' => formatBytes(filesize($zip_file)),
                            'zip_file' => $zip_file,
                            'type' => 'zip'
                        ];
                    }
                }
            }
        }

        // Sort by timestamp descending
        usort($backups, function ($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });
    }

    return $backups;
}



function formatBytes($size, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}

$existing_backups = getExistingBackups();
?>

<div class="container emp">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="fas fa-download me-2 text-primary"></i>
            Database Export
        </h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Database Export</li>
            </ol>
        </nav>
    </div>

    <!-- Status Messages -->
    <?php if ($export_result): ?>
        <div
            class="alert alert-<?php echo $export_result['success'] ? 'success' : 'danger'; ?> alert-dismissible fade show">
            <i class="fas fa-<?php echo $export_result['success'] ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php if ($export_result['success']): ?>
                Database backup created successfully!
                <?php if (isset($export_result['zip_path']) && file_exists($export_result['zip_path'])): ?>
                    <a href="/admin/download_zip_backup.php?file=<?php echo urlencode(basename($export_result['zip_path'])); ?>"
                        class="btn btn-success btn-sm ms-3">
                        <i class="fas fa-download me-1"></i>
                        Download Backup
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <?php echo htmlspecialchars($export_result['message']); ?>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Export Form -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-database me-2"></i>
                        Create New Backup
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Export all database tables to JSON files with timestamp versioning.
                        This will create a complete backup of your database.
                    </p>

                    <form method="POST" id="export-form">
                        <input type="hidden" name="action" value="export">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>What will be exported:</strong>
                            <ul class="mb-0 mt-2">
                                <li>All database tables and their data</li>
                                <li>Table schemas and relationships</li>
                                <li>Backup manifest with metadata</li>
                                <li>Timestamped backup folder</li>
                            </ul>
                        </div>

                        <button type="submit" class="btn btn-primary" id="export-btn">
                            <i class="fas fa-download me-2"></i>
                            Start Export
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>
                        Existing Backups
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($existing_backups)): ?>
                        <p class="text-muted">No backups found. Create your first backup using the form on the left.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Backup Name</th>
                                        <th>Created</th>
                                        <th>Tables</th>
                                        <th>Records</th>
                                        <th>Size</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($existing_backups as $backup): ?>
                                        <tr>
                                            <td>
                                                <small
                                                    class="font-monospace"><?php echo htmlspecialchars($backup['name']); ?></small>
                                            </td>
                                            <td>
                                                <small><?php echo date('M j, Y H:i', strtotime($backup['created_at'])); ?></small>
                                            </td>
                                            <td><?php echo $backup['total_tables']; ?></td>
                                            <td><?php echo number_format($backup['total_records']); ?></td>
                                            <td><?php echo $backup['size']; ?></td>
                                            <td>
                                                <a href="/admin/download_zip_backup.php?file=<?php echo urlencode(basename($backup['zip_file'])); ?>"
                                                    class="btn btn-sm btn-success" title="Download Backup">
                                                    <i class="fas fa-download me-1"></i>
                                                    Download
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const exportForm = document.getElementById('export-form');
        const exportBtn = document.getElementById('export-btn');

        exportForm.addEventListener('submit', function () {
            exportBtn.disabled = true;
            exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Exporting...';
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>