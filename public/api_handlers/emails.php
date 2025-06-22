<?php
require_once __DIR__ . '/email_functions.php';
require_once __DIR__ . '/../services/LogService.php';

function handle_emails($action, $method, $db, $input)
{
    $logService = new LogService();
    // Increase execution time for potentially long-running email operations
    set_time_limit(300); // 5 minutes

    // Check if the IMAP extension is loaded
    if (!function_exists('imap_open')) {
        $error_message = 'The IMAP extension is not installed or enabled. Please enable it in your php.ini file to use email functionality.';
        $logService->log('emails', 'error', $error_message);
        return ['success' => false, 'message' => $error_message];
    }
    switch ($action) {
        case 'check_new_emails':
            // Set headers for Server-Sent Events (SSE)
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            // ob_implicit_flush(true); // Removed as it can interfere with other requests
            // ob_end_clean(); // Removed as it can interfere with other requests

            try {
                $user_id = $_SESSION['user_id'];
                // Get the date of the last email stored locally
                $last_email_date = get_last_email_date($db, $user_id);

                // Fetch only new emails from the server
                $new_emails = fetch_new_emails($db, $user_id, $last_email_date);

                // Add user_id to each email before storing
                foreach ($new_emails as &$email) {
                    $email['user_id'] = $user_id;
                }
                unset($email); // Break the reference

                // Store the new emails in the database
                $new_email_count = store_emails($db, $new_emails);

                // Send final success message
                echo "data: " . json_encode(['status' => 'complete', 'new_email_count' => $new_email_count]) . "\n\n";
                flush();
                exit; // Exit after sending SSE
            } catch (Exception $e) {
                $logService->log('emails', 'error', "API Error (check_new_emails): " . $e->getMessage(), ['error' => $e->getMessage()]);
                echo "data: " . json_encode(['status' => 'error', 'message' => 'Failed to check for new emails.']) . "\n\n";
                flush();
                exit; // Exit after sending SSE
            }
            break;

        case 'list_senders':
            try {
                $user_id = $_SESSION['user_id'];
                // Now fetches emails from the local database
                $emails = get_emails_from_db($db, $user_id, 1);
                $conversations = group_emails_by_sender($emails);
                $logService->log('emails', 'success', 'Senders listed successfully.', ['count' => count($conversations)]);
                return ['success' => true, 'conversations' => array_values($conversations)];
            } catch (Exception $e) {
                $logService->log('emails', 'error', "API Error (list_senders): " . $e->getMessage(), ['error' => $e->getMessage()]);
                return ['success' => false, 'message' => 'Failed to retrieve email conversations from the database.'];
            }
            break;

        case 'get_conversation':
            if (!isset($input['sender_email'])) {
                $logService->log('emails', 'error', 'Missing sender_email for get_conversation.', $input);
                return ['success' => false, 'message' => 'Sender email is required.'];
            }
            try {
                $user_id = $_SESSION['user_id'];
                // Now fetches emails from the local database
                $emails = get_emails_from_db($db, $user_id);
                $conversations = group_emails_by_sender($emails);
                $sender_email = $input['sender_email'];

                if (isset($conversations[$sender_email])) {
                    // Sort emails within the conversation from newest to oldest
                    usort($conversations[$sender_email]['emails'], function ($a, $b) {
                        return $b['date'] - $a['date'];
                    });
                    return ['success' => true, 'conversation' => $conversations[$sender_email]];
                } else {
                    return ['success' => false, 'message' => 'Conversation not found.'];
                }
            } catch (Exception $e) {
                $logService->log('emails', 'error', "API Error (get_conversation): " . $e->getMessage(), ['error' => $e->getMessage(), 'sender_email' => $input['sender_email']]);
                return ['success' => false, 'message' => 'Failed to retrieve conversation details.'];
            }
            break;

        case 'mark_as_read':
            if (!isset($input['sender_email'])) {
                $logService->log('emails', 'error', 'Missing sender_email for mark_as_read.', $input);
                return ['success' => false, 'message' => 'Sender email is required.'];
            }
            try {
                $sender_email = $input['sender_email'];
                $user_id = $_SESSION['user_id'];
                $updated_count = mark_conversation_as_read($db, $sender_email, $user_id);
                if ($updated_count !== false) {
                    return ['success' => true, 'updated_count' => $updated_count];
                } else {
                    return ['success' => false, 'message' => 'Failed to mark conversation as read.'];
                }
            } catch (Exception $e) {
                $logService->log('emails', 'error', "API Error (mark_as_read): " . $e->getMessage(), ['error' => $e->getMessage(), 'sender_email' => $input['sender_email']]);
                return ['success' => false, 'message' => 'An error occurred while marking emails as read.'];
            }
            break;

        case 'delete_conversation':
            if (!isset($input['sender_email'])) {
                $logService->log('emails', 'error', 'Missing sender_email for delete_conversation.', $input);
                return ['success' => false, 'message' => 'Sender email is required.'];
            }
            try {
                $sender_email = $input['sender_email'];
                $user_id = $_SESSION['user_id'];
                $deleted_count = delete_emails_by_sender($db, $sender_email, $user_id);
                if ($deleted_count !== false) {
                    return ['success' => true, 'deleted_count' => $deleted_count];
                } else {
                    return ['success' => false, 'message' => 'Failed to delete conversation.'];
                }
            } catch (Exception $e) {
                $logService->log('emails', 'error', "API Error (delete_conversation): " . $e->getMessage(), ['error' => $e->getMessage(), 'sender_email' => $input['sender_email']]);
                return ['success' => false, 'message' => 'An error occurred while deleting the conversation.'];
            }
            break;
        case 'deactivate_conversation':
            if (!isset($input['sender_email'])) {
                $logService->log('emails', 'error', 'Missing sender_email for deactivate_conversation.', $input);
                return ['success' => false, 'message' => 'Sender email is required.'];
            }
            try {
                $sender_email = $input['sender_email'];
                $user_id = $_SESSION['user_id'];
                $deactivated_count = deactivate_emails_by_sender($db, $sender_email, $user_id);
                if ($deactivated_count !== false) {
                    return ['success' => true, 'deactivated_count' => $deactivated_count];
                } else {
                    return ['success' => false, 'message' => 'Failed to deactivate conversation.'];
                }
            } catch (Exception $e) {
                $logService->log('emails', 'error', "API Error (deactivate_conversation): " . $e->getMessage(), ['error' => $e->getMessage(), 'sender_email' => $input['sender_email']]);
                return ['success' => false, 'message' => 'An error occurred while deactivating the conversation.'];
            }
            break;

        case 'list_deactivated_senders':
            try {
                $user_id = $_SESSION['user_id'];
                // Fetches emails from the local database where is_active = 0
                $emails = get_emails_from_db($db, $user_id, 0); // Assuming 0 for deactivated
                $conversations = group_emails_by_sender($emails);
                $logService->log('emails', 'success', 'Deactivated senders listed successfully.', ['count' => count($conversations)]);
                return ['success' => true, 'conversations' => array_values($conversations)];
            } catch (Exception $e) {
                $logService->log('emails', 'error', "API Error (list_deactivated_senders): " . $e->getMessage(), ['error' => $e->getMessage()]);
                return ['success' => false, 'message' => 'Failed to retrieve deactivated email conversations from the database.'];
            }
            break;

        case 'get_draft':
            if (!isset($input['draft_id'])) {
                $logService->log('emails', 'error', 'Draft ID is required for get_draft.', $input);
                return ['success' => false, 'message' => 'Draft ID is required.'];
            }
            try {
                $user_id = $_SESSION['user_id'];
                $draft = get_draft_by_id($db, $input['draft_id'], $user_id);
                if ($draft) {
                    return ['success' => true, 'data' => $draft];
                } else {
                    return ['success' => false, 'message' => 'Draft not found or access denied.'];
                }
            } catch (Exception $e) {
                $logService->log('emails', 'error', "API Error (get_draft): " . $e->getMessage(), ['error' => $e->getMessage(), 'draft_id' => $input['draft_id']]);
                return ['success' => false, 'message' => 'Failed to retrieve draft.'];
            }
            break;

        case 'save_email':
            // Basic validation
            if (empty($input['to'])) {
                $logService->log('emails', 'error', 'Recipient email is required for save_email.', $input);
                return ['success' => false, 'message' => 'Recipient email is required.'];
            }

            try {
                $user_id = $_SESSION['user_id'] ?? 1; // Hardcoded for now
                $staff_id = $_SESSION['staff_id'] ?? 1; // Hardcoded for now

                $data = [
                    'draft_id' => $input['draft_id'] ?? null,
                    'user_id' => $user_id,
                    'staff_id' => $staff_id,
                    'to' => $input['to'],
                    'subject' => $input['subject'] ?? '',
                    'body' => $input['body'] ?? '',
                    'is_draft' => ($input['action'] === 'save') ? 1 : 0
                ];

                $result = save_email($db, $data);

                if ($result) {
                    $message = ($data['is_draft']) ? 'Draft saved successfully.' : 'Email sent successfully.';
                    return ['success' => true, 'message' => $message, 'draft_id' => $result];
                } else {
                    return ['success' => false, 'message' => 'Failed to save email.'];
                }
            } catch (Exception $e) {
                $logService->log('emails', 'error', "API Error (save_email): " . $e->getMessage(), ['error' => $e->getMessage(), 'input' => $input]);
                return ['success' => false, 'message' => 'An error occurred while saving the email.'];
            }
            break;

        default:
            $logService->log('emails', 'error', 'Invalid action for emails.', ['action' => $action]);
            return ['success' => false, 'message' => 'Invalid action for emails.'];
    }
}
