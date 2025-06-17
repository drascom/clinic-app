<?php
// Include upload configuration
require_once __DIR__ . '/includes/upload_config.php';

// Check if this is an AJAX request for JSON response
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$format = $_GET['format'] ?? ($is_ajax ? 'json' : 'html');

function convertToBytes($value)
{
    $value = trim($value);
    $last = strtolower($value[strlen($value) - 1]);
    $value = (int) $value;
    switch ($last) {
        case 'g':
            $value *= 1024;
        case 'm':
            $value *= 1024;
        case 'k':
            $value *= 1024;
    }
    return $value;
}

function formatBytes($bytes, $precision = 2)
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}

$config = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'max_input_time' => ini_get('max_input_time'),
    'file_uploads' => ini_get('file_uploads'),
    'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: 'Default system temp directory',
];

// Convert to bytes for calculations
$config['upload_max_filesize_bytes'] = convertToBytes($config['upload_max_filesize']);
$config['post_max_size_bytes'] = convertToBytes($config['post_max_size']);
$config['memory_limit_bytes'] = convertToBytes($config['memory_limit']);

// Calculate human-readable sizes
$config['upload_max_filesize_mb'] = round($config['upload_max_filesize_bytes'] / 1024 / 1024, 2);
$config['post_max_size_mb'] = round($config['post_max_size_bytes'] / 1024 / 1024, 2);
$config['memory_limit_mb'] = round($config['memory_limit_bytes'] / 1024 / 1024, 2);

// Calculate practical limits
if ($config['max_file_uploads'] > 0) {
    $config['theoretical_max_per_file_mb'] = round($config['post_max_size_bytes'] / $config['max_file_uploads'] / 1024 / 1024, 2);
    $config['practical_max_files_at_max_size'] = floor($config['post_max_size_bytes'] / $config['upload_max_filesize_bytes']);
}

// Calculate recommendations
$config['recommendations'] = [];

if ($config['post_max_size_bytes'] < ($config['upload_max_filesize_bytes'] * 2)) {
    $config['recommendations'][] = "post_max_size should be at least 2x upload_max_filesize for multiple file uploads";
}

if ($config['max_file_uploads'] > 1 && $config['post_max_size_bytes'] < ($config['upload_max_filesize_bytes'] * 3)) {
    $config['recommendations'][] = "For multiple file uploads, consider increasing post_max_size";
}

if ($config['memory_limit_bytes'] < ($config['upload_max_filesize_bytes'] * 2)) {
    $config['recommendations'][] = "memory_limit should be at least 2x upload_max_filesize for image processing";
}

// Status assessment
$config['status'] = 'good';
if (!empty($config['recommendations'])) {
    $config['status'] = 'warning';
}

if ($config['upload_max_filesize_bytes'] < (5 * 1024 * 1024)) { // Less than 5MB
    $config['status'] = 'poor';
    $config['recommendations'][] = "upload_max_filesize is quite small for modern image files";
}

if ($format === 'json') {
    header('Content-Type: application/json');
    echo json_encode($config, JSON_PRETTY_PRINT);
    exit;
}

// HTML output
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Upload Configuration Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-good {
            color: #28a745;
        }

        .status-warning {
            color: #ffc107;
        }

        .status-poor {
            color: #dc3545;
        }

        .config-table th {
            background-color: #f8f9fa;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">PHP Upload Configuration Check</h1>

                <div
                    class="alert alert-<?php echo $config['status'] === 'good' ? 'success' : ($config['status'] === 'warning' ? 'warning' : 'danger'); ?>">
                    <h5>Overall Status: <span
                            class="status-<?php echo $config['status']; ?>"><?php echo ucfirst($config['status']); ?></span>
                    </h5>
                    <?php if (!empty($config['recommendations'])): ?>
                        <hr>
                        <h6>Recommendations:</h6>
                        <ul class="mb-0">
                            <?php foreach ($config['recommendations'] as $rec): ?>
                                <li><?php echo htmlspecialchars($rec); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h3>Current Configuration</h3>
                        <table class="table table-striped config-table">
                            <thead>
                                <tr>
                                    <th>Setting</th>
                                    <th>Value</th>
                                    <th>Bytes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>upload_max_filesize</td>
                                    <td><?php echo $config['upload_max_filesize']; ?></td>
                                    <td><?php echo formatBytes($config['upload_max_filesize_bytes']); ?></td>
                                </tr>
                                <tr>
                                    <td>post_max_size</td>
                                    <td><?php echo $config['post_max_size']; ?></td>
                                    <td><?php echo formatBytes($config['post_max_size_bytes']); ?></td>
                                </tr>
                                <tr>
                                    <td>max_file_uploads</td>
                                    <td><?php echo $config['max_file_uploads']; ?></td>
                                    <td>-</td>
                                </tr>
                                <tr>
                                    <td>memory_limit</td>
                                    <td><?php echo $config['memory_limit']; ?></td>
                                    <td><?php echo formatBytes($config['memory_limit_bytes']); ?></td>
                                </tr>
                                <tr>
                                    <td>max_execution_time</td>
                                    <td><?php echo $config['max_execution_time']; ?> seconds</td>
                                    <td>-</td>
                                </tr>
                                <tr>
                                    <td>max_input_time</td>
                                    <td><?php echo $config['max_input_time']; ?> seconds</td>
                                    <td>-</td>
                                </tr>
                                <tr>
                                    <td>file_uploads</td>
                                    <td><?php echo $config['file_uploads'] ? 'Enabled' : 'Disabled'; ?></td>
                                    <td>-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="col-md-6">
                        <h3>Practical Limits</h3>
                        <table class="table table-striped">
                            <tbody>
                                <tr>
                                    <td>Max file size (individual)</td>
                                    <td><?php echo $config['upload_max_filesize_mb']; ?> MB</td>
                                </tr>
                                <tr>
                                    <td>Max total upload size</td>
                                    <td><?php echo $config['post_max_size_mb']; ?> MB</td>
                                </tr>
                                <tr>
                                    <td>Max files at max size</td>
                                    <td><?php echo $config['practical_max_files_at_max_size'] ?? 'N/A'; ?> files</td>
                                </tr>
                                <tr>
                                    <td>Theoretical max per file (if all slots used)</td>
                                    <td><?php echo $config['theoretical_max_per_file_mb'] ?? 'N/A'; ?> MB</td>
                                </tr>
                            </tbody>
                        </table>

                        <h4>Example Scenarios</h4>
                        <div class="card">
                            <div class="card-body">
                                <h6>✅ Supported:</h6>
                                <ul>
                                    <li><?php echo min(5, $config['max_file_uploads']); ?> files × 5MB each =
                                        <?php echo min(5, $config['max_file_uploads']) * 5; ?>MB total
                                    </li>
                                    <li><?php echo min(10, $config['max_file_uploads']); ?> files × 2MB each =
                                        <?php echo min(10, $config['max_file_uploads']) * 2; ?>MB total
                                    </li>
                                    <li>1 file × <?php echo $config['upload_max_filesize_mb']; ?>MB =
                                        <?php echo $config['upload_max_filesize_mb']; ?>MB total
                                    </li>
                                </ul>

                                <?php if ($config['post_max_size_mb'] < 100): ?>
                                    <h6>⚠️ May have issues:</h6>
                                    <ul>
                                        <li>20 files × 5MB each = 100MB total (exceeds
                                            <?php echo $config['post_max_size_mb']; ?>MB limit)
                                        </li>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="?format=json" class="btn btn-secondary">View as JSON</a>
                    <button onclick="location.reload()" class="btn btn-primary">Refresh</button>
                </div>
            </div>
        </div>
    </div>
</body>

</html>