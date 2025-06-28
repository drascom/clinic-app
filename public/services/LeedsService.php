<?php

require_once __DIR__ . '/LogService.php';

class LeedsService
{
    private $apiUrl;
    private $apiKey;
    private $apiUser;
    private $db;
    private $logService;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->logService = new LogService();
        $secretsPath = __DIR__ . '/../../secrets/leeds.json';
        if (!file_exists($secretsPath)) {
            throw new Exception("Secrets file not found at {$secretsPath}");
        }
        $secrets = json_decode(file_get_contents($secretsPath), true);

        $this->apiUrl = $secrets['url'];
        $this->apiKey = $secrets['apikey'];
        $this->apiUser = $secrets['name'];
    }

    public function getSubmissions()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_USERPWD, $this->apiUser . ':' . $this->apiKey);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            $this->logService->log('leeds', 'error', 'API request failed', ['error' => $error_msg]);
            return ['success' => false, 'message' => $error_msg];
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode >= 400) {
            $this->logService->log('leeds', 'error', "API returned status code {$httpcode}", ['details' => json_decode($result, true)]);
            return ['success' => false, 'message' => "API returned status code {$httpcode}", 'details' => json_decode($result, true)];
        }

        $submissions = json_decode($result, true);
        $processedSubmissions = [];

        if (is_array($submissions)) {
            foreach ($submissions as $submission) {
                $fields = [];
                if (is_array($submission['fields'])) {
                    foreach ($submission['fields'] as $field) {
                        $fields[$field['key']] = $field['value'];
                    }
                }

                $this->saveSubmission($submission['submission_id'], $fields);

                $processedSubmission = [
                    'submission_id' => $submission['submission_id'],
                    'fields' => $fields
                ];
                $processedSubmissions[] = $processedSubmission;
            }
        }

        $this->logService->log('leeds', 'success', 'Successfully fetched submissions', ['count' => count($processedSubmissions)]);
        return ['success' => true, 'data' => $processedSubmissions];
    }

    private function saveSubmission($submission_id, $fields)
    {
        // Check if the submission already exists
        $stmt = $this->db->prepare("SELECT id FROM leeds WHERE submission_id = :submission_id");
        $stmt->execute([':submission_id' => $submission_id]);
        if ($stmt->fetch()) {
            return; // Submission already exists
        }

        $stmt = $this->db->prepare(
            "INSERT INTO leeds (submission_id, name, email, phone, treatment, created_by, status) VALUES (:submission_id, :name, :email, :phone, :treatment, :created_by, :status)"
        );

        $stmt->execute([
            ':submission_id' => $submission_id,
            ':name' => $fields['name'] ?? null,
            ':email' => $fields['email'] ?? null,
            ':phone' => $fields['field_30995f8'] ?? null,
            ':treatment' => $fields['field_39331b2'] ?? null,
            ':created_by' => $_SESSION['user_id'] ?? 1,
            ':status' => 'intake'
        ]);
    }

    public function getRecentLeads($limit = 10)
    {
        $stmt = $this->db->prepare(
            "SELECT submission_id, name, email, treatment
             FROM leeds
             ORDER BY id DESC
             LIMIT :limit"
        );
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $leeds = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['success' => true, 'data' => $leeds];
    }
}