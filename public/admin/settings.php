<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

if (!is_logged_in()) {
    header('Location: ../auth/login.php');
    exit();
}

// Function to get a setting value from the database
function get_setting($key)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = :key");
    $stmt->bindValue(':key', $key, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['value'] : null;
}

// Function to update a setting value in the database
function update_setting($key, $value)
{
    global $pdo;
    // Use INSERT OR REPLACE to handle both inserting new settings and updating existing ones
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (:key, :value)");
    $stmt->bindValue(':key', $key, PDO::PARAM_STR);
    $stmt->bindValue(':value', $value, PDO::PARAM_STR);
    return $stmt->execute();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $spreadsheetId = $_POST['spreadsheet_id'] ?? '';
    $cacheDuration = $_POST['cache_duration'] ?? '';
    $cellRange = $_POST['cell_range'] ?? '';

    // Validate and update settings
    if (!empty($spreadsheetId)) {
        update_setting('spreadsheet_id', $spreadsheetId);
    }
    if (is_numeric($cacheDuration) && $cacheDuration >= 0) {
        update_setting('cache_duration', $cacheDuration);
    }
    if (!empty($cellRange)) {
        update_setting('cell_range', $cellRange);
    }

    $message = "Settings updated successfully!";
}

// Fetch current settings
$currentSpreadsheetId = get_setting('spreadsheet_id');
$currentCacheDuration = get_setting('cache_duration');
$currentCellRange = get_setting('cell_range');

$page_title = "Settings";
require_once '../includes/header.php';
?>

<div class="container emp frosted">
    <div class="card frosted">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center py-2">
                <a href="/dashboard.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left me-1"></i>
                    <span class="d-none d-sm-inline">Dashboard</span>
                </a>
                <h4 class="mb-0">
                    <i class="fas fa-cog me-2 text-primary"></i>Settings
                </h4>
                <div style="width: 80px;"></div> <!-- Spacer -->
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($message)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <fieldset class="border rounded p-3 mb-3">
                <legend class="w-auto px-2 mb-3" style="font-size: 1rem;">Google Sheets Configuration</legend>
                <form method="POST" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="spreadsheet_id" class="form-label">
                                    <i class="fas fa-table me-1"></i>
                                    Spreadsheet ID
                                </label>
                                <input type="text" class="form-control" id="spreadsheet_id" name="spreadsheet_id"
                                    value="<?php echo htmlspecialchars($currentSpreadsheetId ?? ''); ?>"
                                    placeholder="Enter Google Sheets ID" required>
                                <div class="form-text">
                                    The ID from your Google Sheets URL
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="cache_duration" class="form-label">
                                    <i class="fas fa-clock me-1"></i>
                                    Cache Duration (seconds)
                                </label>
                                <input type="number" class="form-control" id="cache_duration" name="cache_duration"
                                    value="<?php echo htmlspecialchars($currentCacheDuration ?? ''); ?>"
                                    placeholder="3600" required min="0">
                                <div class="form-text">
                                    How long to cache data
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="cell_range" class="form-label">
                                    <i class="fas fa-border-all me-1"></i>
                                    Cell Range
                                </label>
                                <input type="text" class="form-control" id="cell_range" name="cell_range"
                                    value="<?php echo htmlspecialchars($currentCellRange ?? ''); ?>" placeholder="A1:Z"
                                    required>
                                <div class="form-text">
                                    Range of cells to fetch
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Save Settings
                        </button>
                    </div>
                </form>
            </fieldset>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>