<?php

class LogService
{
    private $logDirectory;
    private const MAX_LOG_ENTRIES = 100;

    public function __construct()
    {
        $this->logDirectory = __DIR__ . '/../../logs/';
        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0777, true);
        }
    }

    /**
     * Logs a message to a page-specific file.
     *
     * @param string $pageName The name of the page or component being logged.
     * @param string $responseType The type of response (e.g., 'success', 'error').
     * @param string $message The log message.
     * @param array $context Optional associative array for additional context.
     */
    public function log(string $pageName, string $responseType, string $message, array $context = [])
    {
        $logFile = $this->logDirectory . $pageName . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$responseType}] {$message}";

        if (!empty($context)) {
            $logEntry .= " " . json_encode($context);
        }

        file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND);

        $this->_manageLogFileSize($logFile);
    }

    /**
     * Manages the size of a log file, ensuring it does not exceed MAX_LOG_ENTRIES.
     *
     * @param string $filePath The path to the log file.
     */
    private function _manageLogFileSize(string $filePath)
    {
        if (!file_exists($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (count($lines) > self::MAX_LOG_ENTRIES) {
            $lines = array_slice($lines, count($lines) - self::MAX_LOG_ENTRIES);
            file_put_contents($filePath, implode(PHP_EOL, $lines) . PHP_EOL);
        }
    }
}
