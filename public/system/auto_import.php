<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Authentication Check
if (!is_logged_in()) {
    header('Location: /login.php');
    exit();
}

$page_title = "Auto Import from Google Sheets";
require_once '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-download me-2"></i>
                        Auto Import from Google Sheets
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This will fetch data from Google Sheets API, save to cache, and automatically import all valid
                        entries to create patient records and surgeries with Agency ID 2 and Room ID 1.
                    </div>

                    <button id="test-connection" class="btn btn-secondary me-2">
                        <i class="fas fa-check me-2"></i>
                        Test Connection
                    </button>

                    <button id="start-import" class="btn btn-primary btn-lg">
                        <i class="fas fa-play me-2"></i>
                        Start Auto Import
                    </button>

                    <div id="import-progress" style="display: none;" class="mt-4">
                        <div class="progress mb-3">
                            <div id="progress-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div id="import-status" class="alert alert-info">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            Initializing import...
                        </div>
                    </div>

                    <div id="import-results" style="display: none;" class="mt-4">
                        <h5>Import Results</h5>
                        <div id="results-content"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const testButton = document.getElementById('test-connection');
        const startButton = document.getElementById('start-import');
        const progressDiv = document.getElementById('import-progress');
        const progressBar = document.getElementById('progress-bar');
        const statusDiv = document.getElementById('import-status');
        const resultsDiv = document.getElementById('import-results');
        const resultsContent = document.getElementById('results-content');

        // Test connection button
        testButton.addEventListener('click', async function () {
            testButton.disabled = true;
            testButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing...';

            try {
                const response = await fetch('/api_handlers/google_sheets.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=test'
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const responseText = await response.text();
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error('Invalid JSON response:', responseText);
                    throw new Error('Server returned invalid JSON response');
                }

                if (result.success) {
                    let testResults =
                        '<div class="alert alert-success">Connection tests completed:</div>';
                    testResults += '<ul class="list-group">';
                    for (const [test, status] of Object.entries(result.tests)) {
                        const isOk = status === 'OK';
                        const badgeClass = isOk ? 'bg-success' : 'bg-danger';
                        testResults += `<li class="list-group-item d-flex justify-content-between align-items-center">
                            ${test}: <span class="badge ${badgeClass}">${status}</span>
                        </li>`;
                    }
                    testResults += '</ul>';
                    resultsContent.innerHTML = testResults;
                    resultsDiv.style.display = 'block';
                } else {
                    throw new Error(result.error || 'Test failed');
                }

            } catch (error) {
                resultsContent.innerHTML =
                    `<div class="alert alert-danger">Test failed: ${error.message}</div>`;
                resultsDiv.style.display = 'block';
            } finally {
                testButton.disabled = false;
                testButton.innerHTML = '<i class="fas fa-check me-2"></i>Test Connection';
            }
        });

        startButton.addEventListener('click', async function () {
            startButton.disabled = true;
            progressDiv.style.display = 'block';
            resultsDiv.style.display = 'none';

            try {
                // Step 1: Fetch Google Sheets data via API and save to cache
                updateStatus('Fetching data from Google Sheets API...', 10);

                const response = await fetch('/api_handlers/google_sheets.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=fetch_sheets'
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const responseText = await response.text();
                let fetchResult;
                try {
                    fetchResult = JSON.parse(responseText);
                } catch (e) {
                    console.error('Invalid JSON response:', responseText);
                    throw new Error('Server returned invalid JSON response');
                }

                if (!fetchResult.success) {
                    throw new Error('Failed to fetch Google Sheets data: ' + fetchResult.error);
                }

                const cacheStatus = fetchResult.from_cache ? ' (from cache)' : ' (fresh from API)';
                updateStatus('Processing sheet data' + cacheStatus + '...', 20);

                const data = fetchResult.data;
                const allEntries = [];

                // Extract all valid entries from all sheets
                if (data.sheetTitles && data.sheetValues) {
                    data.sheetTitles.forEach(sheetTitle => {
                        const values = data.sheetValues[sheetTitle] || [];

                        // Skip header row, process data rows
                        for (let i = 1; i < values.length; i++) {
                            const row = values[i];
                            const dateStr = row[0] || '';

                            // Check surgery column (column 2 - Room 3A)
                            const surgeryPatientName = row[2] || '';
                            if (dateStr && surgeryPatientName && !surgeryPatientName.includes(
                                'Closed')) {
                                allEntries.push({
                                    dateStr: dateStr,
                                    patientName: surgeryPatientName,
                                    type: 'surgery',
                                    sheetTitle: sheetTitle
                                });
                            }

                            // Check consultation column (column 7 - Consultation F2F/V2V)
                            const consultationEntry = row[7] || '';
                            if (dateStr && consultationEntry && !consultationEntry.includes(
                                'Closed')) {
                                allEntries.push({
                                    dateStr: dateStr,
                                    patientName: consultationEntry, // Send the raw entry for backend parsing
                                    type: 'consultation', // Let backend determine specific type
                                    originalEntry: consultationEntry,
                                    sheetTitle: sheetTitle
                                });
                            }
                        }
                    });
                }

                updateStatus(`Found ${allEntries.length} valid entries to process...`, 30);

                // Step 2: Process each entry
                updateStatus(`Found ${allEntries.length} valid entries. Sending to server for processing...`, 50);

                // Step 2: Send all entries in a single batch request
                const payload = {
                    action: 'process_entry',
                    entries: allEntries.map(entry => ({
                        date_str: entry.dateStr,
                        original_entry: entry.originalEntry || entry.patientName
                    }))
                };

                const batchResponse = await fetch('/api_handlers/google_sheets.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });


                if (!batchResponse.ok) {
                    throw new Error(`HTTP error! status: ${batchResponse.status}`);
                }

                const batchResultText = await batchResponse.text();
                let batchResult;
                try {
                    batchResult = JSON.parse(batchResultText);
                } catch (e) {
                    console.error('Invalid JSON response from batch processing:', batchResultText);
                    throw new Error('Server returned invalid JSON for batch processing');
                }

                updateStatus('Processing complete!', 100);

                if (batchResult.success) {
                    const results = {
                        processed: batchResult.results.length,
                        patientsCreated: batchResult.results.filter(r => r.patient_created).length,
                        patientsExisting: batchResult.results.filter(r => !r.patient_created).length,
                        surgeriesCreated: batchResult.results.filter(r => r.record_type === 'surgery' && r.record_created).length,
                        surgeriesSkipped: batchResult.results.filter(r => r.record_type === 'surgery' && !r.record_created).length,
                        appointmentsCreated: batchResult.results.filter(r => r.record_type === 'appointment' && r.record_created).length,
                        appointmentsSkipped: batchResult.results.filter(r => r.record_type === 'appointment' && !r.record_created).length,
                        errors: batchResult.errors || []
                    };
                    showResults(results);
                } else {
                    throw new Error('Batch processing failed: ' + (batchResult.error || 'Unknown error'));
                }

            } catch (error) {
                statusDiv.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>Error: ${error.message}`;
                statusDiv.className = 'alert alert-danger';
            } finally {
                startButton.disabled = false;
            }
        });

        function updateStatus(message, progress) {
            statusDiv.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>${message}`;
            progressBar.style.width = progress + '%';
            progressBar.textContent = Math.round(progress) + '%';
        }

        function showResults(results) {
            resultsContent.innerHTML = `
            <div class="row">
                <div class="col-md-4">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h5 class="card-title text-success">Patients</h5>
                            <p class="card-text">
                                <strong>${results.patientsCreated}</strong> created<br>
                                <strong>${results.patientsExisting}</strong> existing
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <h5 class="card-title text-primary">Surgeries</h5>
                            <p class="card-text">
                                <strong>${results.surgeriesCreated}</strong> created<br>
                                <strong>${results.surgeriesSkipped}</strong> skipped
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <h5 class="card-title text-info">Appointments</h5>
                            <p class="card-text">
                                <strong>${results.appointmentsCreated || 0}</strong> created<br>
                                <strong>${results.appointmentsSkipped || 0}</strong> skipped
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <p><strong>Total Processed:</strong> ${results.processed}</p>
                ${results.errors.length > 0 ? `<p class="text-danger"><strong>Errors:</strong> ${results.errors.length}</p>` : ''}
            </div>
        `;
            resultsDiv.style.display = 'block';
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>