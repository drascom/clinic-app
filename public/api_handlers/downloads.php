<?php

function handle_downloads($action, $method, $db, $input)
{
    if ($method !== 'POST') {
        return ['success' => false, 'message' => 'Invalid request method. Only POST is allowed.'];
    }

    if ($action === 'validate_attachment_path') {
        // This action will validate the path and return success if valid
        // The actual download will be handled by a direct link to download.php
        if (!isset($input['file_path']) || empty($input['file_path'])) {
            return ['success' => false, 'message' => 'No file path specified.'];
        }

        $requested_file_path = $input['file_path'];
        // Perform basic security check for directory traversal
        $requested_file_path = str_replace(['../', '..\\'], '', $requested_file_path);

        // In a real application, you would also verify if the user has permission to access this file
        // For now, we'll assume the file_path itself is sufficient for the download script to handle.

        return ['success' => true, 'message' => 'File path validated.', 'file_path' => $requested_file_path];
    } else {
        return ['success' => false, 'message' => 'Invalid action for downloads API.'];
    }
}
