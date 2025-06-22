<?php
require_once __DIR__ . '/../services/LogService.php';
// Initialize LogService
$logService = new LogService();

/**
 * Upload Configuration
 * Sets PHP configuration for file uploads at runtime
 */

// Set upload limits programmatically
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '50M');
ini_set('max_file_uploads', '20');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');
ini_set('memory_limit', '512M');
ini_set('file_uploads', '1');


// Log the current configuration for debugging
$logService->log('upload_config', 'info', "Upload config set", [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads')
]);
