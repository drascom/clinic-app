<?php
function handle_invitations($action, $method, $db, $request_data = [])
{
    $input = $request_data;
    // log_response($action, $method, $request_data); // Uncomment for debugging

    switch ($action) {
        case 'list':
            if ($method === 'POST') {
                // Fetch all invitations
                $stmt = $db->query("SELECT id, email, role, agency_id, status, created_at, used_at FROM invitations ORDER BY created_at DESC");
                return ['success' => true, 'invitations' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
            }
            break;

        case 'get':
            if ($method === 'POST') {
                $id = $input['id'] ?? null;
                if ($id) {
                    $stmt = $db->prepare("SELECT id, email, role, agency_id, status, created_at, used_at FROM invitations WHERE id = ?");
                    $stmt->execute([$id]);
                    $data = $stmt->fetch(PDO::FETCH_ASSOC);
                    return $data ? ['success' => true, 'invitation' => $data] : ['success' => false, 'error' => "Invitation not found with ID: {$id}"];
                }
                return ['success' => false, 'error' => 'ID is required.'];
            }
            break;

        case 'delete':
            if ($method === 'POST') {
                $id = $input['id'] ?? null;
                if ($id) {
                    // Check if invitation exists
                    $check_stmt = $db->prepare("SELECT id FROM invitations WHERE id = ?");
                    $check_stmt->execute([$id]);
                    if (!$check_stmt->fetch()) {
                        return ['success' => false, 'error' => 'Invitation not found.'];
                    }

                    $stmt = $db->prepare("DELETE FROM invitations WHERE id = ?");
                    $stmt->execute([$id]);
                    return ['success' => true, 'message' => 'Invitation deleted successfully.'];
                }
                return ['success' => false, 'error' => 'ID is required.'];
            }
            break;

        case 'resend':
            if ($method === 'POST') {
                $id = $input['id'] ?? null;
                if ($id) {
                    // Check if invitation exists and is pending
                    $check_stmt = $db->prepare("SELECT id, email FROM invitations WHERE id = ? AND status = 'pending'");
                    $check_stmt->execute([$id]);
                    $invitation = $check_stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$invitation) {
                        return ['success' => false, 'error' => 'Invitation not found or is not pending.'];
                    }

                    // Generate new token and expiry
                    $token = bin2hex(random_bytes(16)); // Generate a 32-character hex token
                    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours')); // Token valid for 24 hours

                    // Update invitation with new token and expiry, reset status to pending
                    $update_stmt = $db->prepare("UPDATE invitations SET token = :token, reset_expiry = :expiry, status = 'pending', used_at = NULL, updated_at = datetime('now') WHERE id = :id");
                    $update_stmt->bindParam(':token', $token);
                    $update_stmt->bindParam(':expiry', $expiry);
                    $update_stmt->bindParam(':id', $id);

                    if ($update_stmt->execute()) {
                        // TODO: Implement actual email sending logic here
                        // Use $invitation['email'] and the new $token to send the email

                        return ['success' => true, 'message' => 'Invitation resent successfully.'];
                    } else {
                        return ['success' => false, 'error' => 'Failed to update invitation token.'];
                    }
                }
                return ['success' => false, 'error' => 'ID is required.'];
            }
            break;

        default:
            return ['success' => false, 'error' => "Invalid action '{$action}' for invitations entity."];
    }

    return ['success' => false, 'error' => "Invalid request for action '{$action}' with method '{$method}'."];
}
