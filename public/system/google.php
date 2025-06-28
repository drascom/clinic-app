<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Debug logging function
function debugLog($message, $data = null, $level = 'INFO')
{
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "\n## Debug Log - {$timestamp}\n\n";
    $logEntry .= "**Level:** {$level}\n";
    $logEntry .= "**Message:** {$message}\n";

    if ($data !== null) {
        $logEntry .= "**Data:**\n```json\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n```\n";
    }

    $logEntry .= "---\n";

    // Append to log file
    file_put_contents(__DIR__ . '/../../logs/google_debug.log', $logEntry, FILE_APPEND | LOCK_EX);

    // Also output as HTML comment for debugging
    echo "<!-- DEBUG: " . htmlspecialchars($message) . " -->\n";
}

// Performance tracking
$startTime = microtime(true);
$memoryStart = memory_get_usage();

// Step 1: Authentication Check
debugLog("Starting Google Sheets workflow", [
    'user_id' => $_SESSION['user_id'] ?? 'not_set',
    'timestamp' => date('Y-m-d H:i:s'),
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
]);

if (!is_logged_in()) {
    debugLog("Authentication failed - redirecting to login", null, 'ERROR');
    header('Location: /login.php');
    exit();
}

debugLog("Authentication successful", ['user_id' => $_SESSION['user_id']]);

$page_title = "Google Sheets";
require_once __DIR__ . '/../includes/header.php';

?>
<div class="container-fluid mt-4">
    <div id="loading-spinner" style="text-align: center; margin-top: 50px;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p>Loading data from server...</p>
    </div>
    <div id="main-content" style="display: none;">
        <!-- Content will be loaded here by JavaScript -->
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            console.log('Google Sheets page loaded');

            const loadingSpinner = document.getElementById('loading-spinner');
            const mainContent = document.getElementById('main-content');
            const transferScript = document.createElement('script');
            transferScript.src = 'assets/js/transfer.js';

            // Function to fetch and display data
            async function fetchDataAndDisplay() {
                // Show spinner, hide content
                if (loadingSpinner) loadingSpinner.style.display = 'block';
                if (mainContent) mainContent.style.display = 'none';

                try {
                    const response = await fetch('/api_handlers/google_sheets.php');
                    const result = await response.json();

                    if (result.success) {
                        renderData(result.data);
                    } else {
                        displayError(result.message);
                    }
                } catch (error) {
                    console.error('Error fetching Google Sheets data:', error);
                    displayError('Failed to load data. Please try again.');
                } finally {
                    // Hide spinner, show content
                    if (loadingSpinner) loadingSpinner.style.display = 'none';
                    if (mainContent) mainContent.style.display = 'block';
                    // Append transfer.js after content is loaded
                    document.body.appendChild(transferScript);
                }
            }

            // Function to render the fetched data
            function renderData(data) {
                if (!mainContent) return;

                let html = `<h2>Data from Google Sheet: ${escapeHTML(data.spreadsheetTitle)}</h2>`;
                if (data.from_cache) {
                    html += `<p><em>(Last Updated at ${new Date(data.timestamp * 1000).toLocaleString()})</em></p>`;
                }


                if (!data.sheetTitles || data.sheetTitles.length === 0) {
                    html += `<p>No sheets found in the spreadsheet.</p>`;
                } else {
                    // Generate Tab Navigation
                    html += `<ul class='nav nav-tabs' id='sheetTabs' role='tablist'>`;
                    data.sheetTitles.forEach((sheetTitle, index) => {
                        const tabId = 'sheet-' + index;
                        const activeClass = index === 0 ? 'active' : '';
                        const ariaSelected = index === 0 ? 'true' : 'false';
                        html += `
                        <li class='nav-item' role='presentation'>
                            <button class='nav-link ${activeClass}' id='${tabId}-tab' data-bs-toggle='tab' data-bs-target='#${tabId}' type='button' role='tab' aria-controls='${tabId}' aria-selected='${ariaSelected}'>${escapeHTML(sheetTitle)}</button>
                        </li>
                    `;
                    });
                    html += `</ul>`;

                    // Generate Tab Content
                    html += `<div class='tab-content' id='sheetTabsContent'>`;
                    data.sheetTitles.forEach((sheetTitle, index) => {
                        const tabId = 'sheet-' + index;
                        const activeClass = index === 0 ? 'show active' : '';
                        const values = data.sheetValues[sheetTitle] || [];

                        html +=
                            `<div class='tab-pane fade ${activeClass}' id='${tabId}' role='tabpanel' aria-labelledby='${tabId}-tab'>`;

                        if (values.length === 0) {
                            html += `<p class='mt-3'>No data found in this sheet.</p>`;
                        } else {
                            html += `<table class='table table-striped mt-3'>`;
                            // Assuming the first row is headers
                            html += `<thead><tr>`;
                            values[0].forEach(header => {
                                html += `<th>${escapeHTML(header)}</th>`;
                            });
                            html += `<th>Actions</th>`; // Add new header for actions
                            html += `</tr></thead>`;
                            html += `<tbody>`;

                            // Data rows (skip header row)
                            for (let i = 1; i < values.length; i++) {
                                const row = values[i];
                                html += `<tr>`;
                                row.forEach(cell => {
                                    html += `<td>${escapeHTML(cell)}</td>`;
                                });

                                // Add a cell for actions
                                html += `<td>`;
                                const dateStr = row[0] || '';
                                const patientName = row[2] || '';
                                const fullDateStr = dateStr + ' 2025'; // Append the year 2025

                                // Simple date parsing for display/data attribute
                                const dateObj = new Date(fullDateStr);
                                const formattedDate = dateObj instanceof Date && !isNaN(dateObj) ? dateObj
                                    .toISOString().split('T')[0] : fullDateStr;

                                // Check if patient name is not empty and does not include 'Closed'
                                if (patientName && !patientName.includes('Closed')) {
                                    // Note: is_recorded status needs to be checked server-side or passed with data
                                    // For now, we'll assume buttons are always enabled unless we refactor the API
                                    const isRecorded = false; // Placeholder - needs actual check
                                    const buttonText = isRecorded ? 'Recorded' : 'Create Records';
                                    const buttonClass = isRecorded ? 'btn-success' : 'btn-primary';
                                    const disabledAttr = isRecorded ? 'disabled' : '';

                                    html += `
                                    <button class='btn btn-sm create-record-btn ${buttonClass}' data-date='${escapeHTML(formattedDate)}' data-patient-name='${escapeHTML(patientName)}' data-recorded='${isRecorded ? 'true' : 'false'}' ${disabledAttr}>
                                        ${escapeHTML(buttonText)}
                                    </button>
                                `;
                                }
                                html += `</td>`;
                                html += `</tr>`;
                            }
                            html += `</tbody>`;
                            html += `</table>`;
                        }

                        html += `</div>`; // Close tab-pane
                    });
                    html += `</div>`; // Close tab-content
                }

                mainContent.innerHTML = html;

                // Re-initialize Bootstrap tabs after content is rendered
                const tabEls = mainContent.querySelectorAll('button[data-bs-toggle="tab"]');
                tabEls.forEach(tabEl => {
                    new bootstrap.Tab(tabEl);
                });

            }

            // Function to display error messages
            function displayError(message) {
                if (mainContent) {
                    mainContent.innerHTML = `<div class='alert alert-danger mt-3'>${escapeHTML(message)}</div>`;
                }
            }

            // Helper function to escape HTML
            function escapeHTML(str) {
                const div = document.createElement('div');
                div.appendChild(document.createTextNode(str));
                return div.innerHTML;
            }

            // Initial data fetch when the page loads
            fetchDataAndDisplay();
        });
    </script>
</div>

<?php
// Final performance and completion logging
$finalTime = microtime(true);
$finalMemory = memory_get_usage();
$totalExecutionTime = round(($finalTime - $startTime) * 1000, 2);
$finalMemoryUsage = round($finalMemory / 1024 / 1024, 2);

debugLog("Initial page load completed", [
    'total_execution_time_ms' => $totalExecutionTime,
    'final_memory_usage_mb' => $finalMemoryUsage,
    'peak_memory_usage_mb' => round(memory_get_peak_usage() / 1024 / 1024, 2),
]);

require_once __DIR__ . '/../includes/footer.php';
?>