<?php

class Response {
    
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('c'),
            'version' => API_VERSION
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public static function error($message = 'Error', $code = 400, $details = null) {
        http_response_code($code);
        
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('c'),
            'version' => API_VERSION
        ];
        
        if ($details !== null) {
            $response['details'] = $details;
        }
        
        // Log do erro
        if (API_LOG_ENABLED) {
            error_log(json_encode([
                'level' => 'ERROR',
                'message' => $message,
                'code' => $code,
                'details' => $details,
                'timestamp' => date('c'),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
                'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                'ip' => self::getClientIp()
            ]));
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public static function paginated($data, $page, $pageSize, $total, $message = 'Success') {
        $totalPages = ceil($total / $pageSize);
        
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'page_size' => $pageSize,
                'total_items' => $total,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_previous' => $page > 1
            ],
            'timestamp' => date('c'),
            'version' => API_VERSION
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public static function created($data = null, $message = 'Created successfully') {
        self::success($data, $message, 201);
    }
    
    public static function updated($data = null, $message = 'Updated successfully') {
        self::success($data, $message, 200);
    }
    
    public static function deleted($message = 'Deleted successfully') {
        self::success(null, $message, 200);
    }
    
    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, 401);
    }
    
    public static function forbidden($message = 'Forbidden') {
        self::error($message, 403);
    }
    
    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }
    
    public static function validation($errors, $message = 'Validation failed') {
        self::error($message, 422, ['validation_errors' => $errors]);
    }
    
    public static function rateLimit($message = 'Rate limit exceeded') {
        self::error($message, 429, [
            'retry_after' => 60
        ]);
    }
    
    private static function getClientIp() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
?>