<?php
require_once __DIR__ . '/../services/LogService.php';

function handle_downloads($action, $method, $db, $input)
{
    $logService = new LogService();
    // This handler is no longer used for streaming file content.
    // The new public/download.php script handles secure file downloads directly.
    // This file can be removed or repurposed if no other download-related API logic is needed.
    $logService->log('downloads', 'info', 'This API handler is deprecated for file streaming.', ['action' => $action]);
    return ['success' => false, 'message' => 'This endpoint is deprecated. Use /download.php for attachments.'];
}
