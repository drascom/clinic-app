<?php
// Define the path to the log file
$log_file = "../logs/php_error.log";
// Check if clear log request is made
if (isset($_GET['clear_log'])) {
    // Clear the log file
    file_put_contents($log_file, '');
    // Redirect back to view_log.php to show the cleared log
    header('Location: view_log.php');
    exit();
}

// Check if the log file exists
if (file_exists($log_file)) {
    // Read the content of the log file
    $log_content = file_get_contents($log_file);
} else {
    $log_content = "Log file not found.";
}
// Count existing log entries (keeping this logic)
$existing_content = file_exists($log_file) ? file_get_contents($log_file) : '';
$entry_count = substr_count($existing_content, "\n"); // Count lines instead of "## Response Log -"

// Clear file if entry count is 10 or more
if ($entry_count >= 50) {
    file_put_contents($log_file, ''); // Clear the file
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Log</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        pre {
            background-color: rgb(17, 10, 10);
            border: 1px solid #ddd;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: large;
            color: white;
            margin: 1% 10% 1% 10%;
            min-height: 70vh;
            overflow: auto;
        }

        h1 {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            color: white;
        }

        footer {
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 10px;
            font-size: 0.9em;
            color: #555;
        }
    </style>
</head>


<body>

    <h1 class="text-center">Application Log</h1>
    <form class="text-center" action="view_log.php" method="get">
        <input type="hidden" name="clear_log" value="true">
        <button type="submit">Clear Log</button>
    </form>
    <pre><?php echo htmlspecialchars($log_content); ?></pre>

    <footer>
        <p>End of Log File</p>
    </footer>

</body>

</html>