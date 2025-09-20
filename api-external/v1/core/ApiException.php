<?php

class ApiException extends Exception {
    private $details;
    
    public function __construct($message, $code = 400, $details = null, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->details = $details;
    }
    
    public function getDetails() {
        return $this->details;
    }
    
    public static function unauthorized($message = 'Unauthorized', $details = null) {
        return new self($message, 401, $details);
    }
    
    public static function forbidden($message = 'Forbidden', $details = null) {
        return new self($message, 403, $details);
    }
    
    public static function notFound($message = 'Resource not found', $details = null) {
        return new self($message, 404, $details);
    }
    
    public static function validation($message = 'Validation failed', $errors = []) {
        return new self($message, 422, ['validation_errors' => $errors]);
    }
    
    public static function rateLimit($message = 'Rate limit exceeded', $details = null) {
        return new self($message, 429, $details);
    }
    
    public static function serverError($message = 'Internal server error', $details = null) {
        return new self($message, 500, $details);
    }
}
?>