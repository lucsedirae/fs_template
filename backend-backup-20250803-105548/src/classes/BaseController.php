<?php
// backend/src/classes/BaseController.php

abstract class BaseController {
    
    /**
     * Send JSON response
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Send success response
     */
    protected function success($data, $statusCode = 200) {
        $this->json($data, $statusCode);
    }

    /**
     * Send error response
     */
    protected function error($message, $statusCode = 400, $details = null) {
        $response = [
            'status' => 'error',
            'message' => $message
        ];
        
        if ($details !== null) {
            $response['details'] = $details;
        }
        
        $this->json($response, $statusCode);
    }

    /**
     * Get request body as JSON
     */
    protected function getJsonInput() {
        return json_decode(file_get_contents('php://input'), true);
    }

    /**
     * Validate required fields in input
     */
    protected function validateRequired($input, $requiredFields) {
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                $missing[] = $field;
            }
        }
        
        return $missing;
    }

    /**
     * Handle method not allowed
     */
    protected function methodNotAllowed() {
        $this->error('Method not allowed', 405);
    }

    /**
     * Log error message
     */
    protected function logError($message, $context = []) {
        $logMessage = "[" . date('Y-m-d H:i:s') . "] ERROR: " . $message;
        
        if (!empty($context)) {
            $logMessage .= " Context: " . json_encode($context);
        }
        
        error_log($logMessage);
    }
}
