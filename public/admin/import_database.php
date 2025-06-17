<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Ensure user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    header('Location: /auth/login.php');
    exit();
}

$page_title = "Database Import";
include __DIR__ . '/../includes/header.php';

// Handle import request
$import_result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import') {
    $backup_name = $_POST['backup_name'] ?? '';

    if (empty($backup_name)) {
        $import_result = ['success' => false, 'message' => 'Please select a backup to import'];
    } else {
        // Find the backup (could be ZIP file or directory)
        $backup_path = null;
        $backup_dir = __DIR__ . '/../../db/backups/' . $backup_name;
        $backup_zip = __DIR__ . '/../../db/backups/' . $backup_name . '.zip';

        if (file_exists($backup_zip)) {
            $backup_path = $backup_zip;
        } elseif (is_dir($backup_dir) && file_exists($backup_dir . '/backup_manifest.json')) {
            $backup_path = $backup_dir;
        }

        if (!$backup_path) {
            $import_result = ['success' => false, 'message' => 'Invalid backup selected'];
        } else {
            // Path to Python script
            $script_path = __DIR__ . '/../../db/import_json_to_db.py';

            // Check if Python script exists
            if (!file_exists($script_path)) {
                $import_result = ['success' => false, 'message' => 'Import script not found'];
            } else {
                // Execute Python script
                $command = "python3 " . escapeshellarg($script_path) . " " . escapeshellarg($backup_path) . " 2>&1";
                $output = [];
                $return_code = 0;

                exec($command, $output, $return_code);

                $output_string = implode("\n", $output);

                if ($return_code === 0) {
                    $import_result = [
                        'success' => true,
                        'message' => 'Database imported successfully',
                        'output' => $output_string
                    ];
                } else {
                    $import_result = [
                        'success' => false,
                        'message' => 'Import failed',
                        'output' => $output_string
                    ];
                }
            }
        }
    }
}

// Get list of available backups (both ZIP files and directories)
function getAvailableBackups()
{
    $backup_dir = __DIR__ . '/../../db/backups';
    $backups = [];

    if (is_dir($backup_dir)) {
        // Get ZIP files
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
                            'name' => $zip_name,
                            'type' => 'zip',
                            'file_path' => $zip_file,
                            'timestamp' => $manifest['backup_info']['timestamp'] ?? 'Unknown',
                            'created_at' => $manifest['backup_info']['created_at'] ?? 'Unknown',
                            'total_tables' => $manifest['backup_info']['total_tables'] ?? 0,
                            'total_records' => $manifest['backup_info']['total_records'] ?? 0,
                            'successful_tables' => $manifest['export_summary']['successful_tables'] ?? 0,
                            'failed_tables' => $manifest['export_summary']['failed_tables'] ?? 0
                        ];
                    }
                }
            }
        }

        // Get backup directories (for backward compatibility)
        $dirs = glob($backup_dir . '/backup_*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $manifest_file = $dir . '/backup_manifest.json';
            if (file_exists($manifest_file)) {
                $manifest = json_decode(file_get_contents($manifest_file), true);
                if ($manifest) {
                    $backups[] = [
                        'name' => basename($dir),
                        'type' => 'directory',
                        'file_path' => $dir,
                        'timestamp' => $manifest['backup_info']['timestamp'] ?? 'Unknown',
                        'created_at' => $manifest['backup_info']['created_at'] ?? 'Unknown',
                        'total_tables' => $manifest['backup_info']['total_tables'] ?? 0,
                        'total_records' => $manifest['backup_info']['total_records'] ?? 0,
                        'successful_tables' => $manifest['export_summary']['successful_tables'] ?? 0,
                        'failed_tables' => $manifest['export_summary']['failed_tables'] ?? 0
                    ];
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

$available_backups = getAvailableBackups();
?>

<div class="container emp">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="fas fa-upload me-2 text-primary"></i>
            Database Import
        </h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Database Import</li>
            </ol>
        </nav>
    </div>

    <!-- Status Messages -->
    <?php if ($import_result): ?>
        <div
            class="alert alert-<?php echo $import_result['success'] ? 'success' : 'danger'; ?> alert-dismissible fade show">
            <i class="fas fa-<?php echo $import_result['success'] ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($import_result['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Warning Alert -->
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Important:</strong> This operation will update existing records and add new ones.
        It will NOT delete any existing data from your database. Always backup your current database before importing.
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-database me-2"></i>
                        Import from Backup
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($available_backups)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No backup files found. Please create a backup first using the
                            <a href="/admin/export_database.php" class="alert-link">Database Export</a> feature.
                        </div>
                    <?php else: ?>
                        <form method="POST" id="import-form">
                            <input type="hidden" name="action" value="import">

                            <div class="mb-3">
                                <label for="backup_name" class="form-label">Select Backup to Import</label>
                                <select class="form-select" id="backup_name" name="backup_name" required>
                                    <option value="">Choose a backup...</option>
                                    <?php foreach ($available_backups as $backup): ?>
                                        <option value="<?php echo htmlspecialchars($backup['name']); ?>"
                                            data-tables="<?php echo $backup['total_tables']; ?>"
                                            data-records="<?php echo $backup['total_records']; ?>"
                                            data-created="<?php echo htmlspecialchars($backup['created_at']); ?>">
                                            <?php echo htmlspecialchars($backup['name']); ?>
                                            (<?php echo date('M j, Y H:i', strtotime($backup['created_at'])); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div id="backup-details" class="alert alert-light d-none">
                                <h6>Backup Details:</h6>
                                <ul class="mb-0">
                                    <li><strong>Created:</strong> <span id="detail-created"></span></li>
                                    <li><strong>Tables:</strong> <span id="detail-tables"></span></li>
                                    <li><strong>Total Records:</strong> <span id="detail-records"></span></li>
                                </ul>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Import Process:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Existing records will be updated based on primary keys</li>
                                    <li>New records will be inserted</li>
                                    <li>No existing data will be deleted</li>
                                    <li>Foreign key relationships will be maintained</li>
                                    <li>The operation is performed in a transaction (rollback on failure)</li>
                                </ul>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="confirm-import" required>
                                <label class="form-check-label" for="confirm-import">
                                    I understand that this will modify the database and I have backed up my current data
                                </label>
                            </div>

                            <button type="submit" class="btn btn-warning" id="import-btn" disabled>
                                <i class="fas fa-upload me-2"></i>
                                Start Import
                            </button>
                            <a href="/admin/export_database.php" class="btn btn-outline-secondary">
                                <i class="fas fa-download me-2"></i>
                                Create Backup First
                            </a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Available Backups
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($available_backups)): ?>
                        <p class="text-muted">No backups available.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($available_backups as $backup): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1 font-monospace small">
                                                <?php echo htmlspecialchars($backup['name']); ?>
                                                <span
                                                    class="badge bg-<?php echo $backup['type'] === 'zip' ? 'success' : 'secondary'; ?> ms-1">
                                                    <?php echo strtoupper($backup['type']); ?>
                                                </span>
                                            </h6>
                                            <p class="mb-1 small text-muted">
                                                <?php echo date('M j, Y H:i', strtotime($backup['created_at'])); ?>
                                            </p>
                                            <small class="text-muted">
                                                <?php echo $backup['total_tables']; ?> tables,
                                                <?php echo number_format($backup['total_records']); ?> records
                                            </small>
                                        </div>
                                        <span
                                            class="badge bg-<?php echo $backup['failed_tables'] > 0 ? 'warning' : 'success'; ?>">
                                            <?php echo $backup['successful_tables']; ?>/<?php echo $backup['total_tables']; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const importForm = document.getElementById('import-form');
        const importBtn = document.getElementById('import-btn');
        const confirmCheck = document.getElementById('confirm-import');
        const backupSelect = document.getElementById('backup_name');
        const backupDetails = document.getElementById('backup-details');

        // Enable/disable import button based on confirmation
        if (confirmCheck) {
            confirmCheck.addEventListener('change', function () {
                importBtn.disabled = !this.checked || !backupSelect.value;
            });
        }

        // Show backup details when selected
        if (backupSelect) {
            backupSelect.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];

                if (this.value) {
                    document.getElementById('detail-created').textContent = selectedOption.dataset.created;
                    document.getElementById('detail-tables').textContent = selectedOption.dataset.tables;
                    document.getElementById('detail-records').textContent = parseInt(selectedOption.dataset.records).toLocaleString();
                    backupDetails.classList.remove('d-none');
                } else {
                    backupDetails.classList.add('d-none');
                }

                if (confirmCheck) {
                    importBtn.disabled = !confirmCheck.checked || !this.value;
                }
            });
        }

        // Handle form submission
        if (importForm) {
            importForm.addEventListener('submit', function () {
                importBtn.disabled = true;
                importBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Importing...';
            });
        }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>