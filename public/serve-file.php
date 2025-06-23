<?php

/**
 * Secure File Serving Script
 * Serves files from the uploads directory outside the public folder
 * Includes security checks and proper headers
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/auth/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    exit('Unauthorized');
}

// This file is no longer used for serving email attachments directly from disk.
// Email attachments are now fetched on-demand from the IMAP server via public/api_handlers/downloads.php.
// This file can be repurposed for other secure file serving needs or removed if no longer necessary.

http_response_code(404);
exit('This endpoint is deprecated for email attachments.');
