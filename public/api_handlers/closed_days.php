<?php
require_once __DIR__ . '/../services/LogService.php';

/**
 * Handles API requests for the closed_days entity.
 *
 * @param string $action The action to perform (e.g., 'add', 'list', 'delete').
 * @param string $method The HTTP request method.
 * @param PDO $db The database connection object.
 * @param array $input The input data from the request.
 * @return array The response array.
 */
function handle_closed_days($action, $method, $db, $input = [])
{
    $logService = new LogService();
    $userId = $input['authenticated_user_id'] ?? 0;

    if ($method !== 'POST') {
        $logService->log('closed_days', 'error', 'Invalid request method.', ['action' => $action, 'method' => $method]);
        return ['success' => false, 'message' => 'Invalid request method. Only POST is accepted.'];
    }

    switch ($action) {
        case 'add':
            $date = $input['date'] ?? null;
            $reason = $input['reason'] ?? null;

            if (empty($date)) {
                $logService->log('closed_days', 'error', 'Validation failed: Date is required.', ['input' => $input, 'user_id' => $userId]);
                return ['success' => false, 'message' => 'Date is required.'];
            }

            try {
                $stmt = $db->prepare("INSERT INTO closed_days (date, reason, closed_by_user_id) VALUES (?, ?, ?)");
                $stmt->execute([$date, $reason, $userId]);
                $logService->log('closed_days', 'success', 'Day closed successfully.', ['date' => $date, 'reason' => $reason, 'user_id' => $userId]);
                return ['success' => true, 'message' => 'Day closed successfully.'];
            } catch (PDOException $e) {
                if ($e->getCode() == 23000 || strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                    $logService->log('closed_days', 'error', 'Attempted to close an already closed day.', ['date' => $date, 'user_id' => $userId]);
                    return ['success' => false, 'message' => 'This date is already closed.'];
                }
                $logService->log('closed_days', 'error', 'Database error on add: ' . $e->getMessage(), ['input' => $input, 'user_id' => $userId]);
                return ['success' => false, 'message' => 'Failed to close day. An error occurred.'];
            }
            break;

        case 'list':
            $year = $input['year'] ?? date('Y');
            $month = $input['month'] ?? date('m');
            $month = str_pad($month, 2, '0', STR_PAD_LEFT);

            try {
                $start_date = "$year-$month-01";
                $end_date = date("Y-m-t", strtotime($start_date));

                $query = "SELECT id, date, reason, closed_by_user_id, created_at FROM closed_days WHERE date BETWEEN ? AND ? ORDER BY date DESC";
                $logService->log('closed_days', 'info', 'Executing query:', ['query' => $query, 'params' => [$start_date, $end_date]]);

                $stmt = $db->prepare($query);
                $stmt->execute([$start_date, $end_date]);
                $closed_days = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $logService->log('closed_days', 'success', 'Retrieved closed days list for month.', ['year' => $year, 'month' => $month, 'count' => count($closed_days), 'results' => $closed_days, 'user_id' => $userId]);
                return ['success' => true, 'closed_days' => $closed_days];
            } catch (PDOException $e) {
                $logService->log('closed_days', 'error', 'Database error on list: ' . $e->getMessage(), ['user_id' => $userId]);
                return ['success' => false, 'message' => 'Failed to retrieve closed days.'];
            }
            break;

        case 'delete':
            $date = $input['date'] ?? null;

            if (empty($date)) {
                $logService->log('closed_days', 'error', 'Validation failed: Date is required for deletion.', ['input' => $input, 'user_id' => $userId]);
                return ['success' => false, 'message' => 'Date is required to open a day.'];
            }

            try {
                $stmt = $db->prepare("DELETE FROM closed_days WHERE date = ?");
                $stmt->execute([$date]);

                if ($stmt->rowCount() > 0) {
                    $logService->log('closed_days', 'success', 'Day opened successfully.', ['date' => $date, 'user_id' => $userId]);
                    return ['success' => true, 'message' => 'Day opened successfully.'];
                } else {
                    $logService->log('closed_days', 'warning', 'Attempted to open a day that was not closed.', ['date' => $date, 'user_id' => $userId]);
                    return ['success' => false, 'message' => 'Day could not be opened. It may not have been closed.'];
                }
            } catch (PDOException $e) {
                $logService->log('closed_days', 'error', 'Database error on delete: ' . $e->getMessage(), ['date' => $date, 'user_id' => $userId]);
                return ['success' => false, 'message' => 'Failed to open day. An error occurred.'];
            }
            break;

        default:
            $logService->log('closed_days', 'error', 'Invalid action specified.', ['action' => $action, 'user_id' => $userId]);
            return ['success' => false, 'message' => 'Invalid action specified.'];
    }
}
