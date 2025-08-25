<?php

class ValidationMiddleware {
    
    public static function validateRequired($data, $requiredFields) {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[$field] = "The {$field} field is required";
            }
        }
        
        if (!empty($errors)) {
            throw ApiException::validation('Validation failed', $errors);
        }
    }
    
    public static function validateEmail($email, $fieldName = 'email') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ApiException::validation('Validation failed', [
                $fieldName => 'Invalid email format'
            ]);
        }
    }
    
    public static function validateCPF($cpf, $fieldName = 'cpf') {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            throw ApiException::validation('Validation failed', [
                $fieldName => 'Invalid CPF format'
            ]);
        }
        
        // Verificar dígitos verificadores
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += $cpf[$i] * (10 - $i);
        }
        $digit1 = 11 - ($sum % 11);
        $digit1 = ($digit1 > 9) ? 0 : $digit1;
        
        if ($cpf[9] != $digit1) {
            throw ApiException::validation('Validation failed', [
                $fieldName => 'Invalid CPF'
            ]);
        }
        
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += $cpf[$i] * (11 - $i);
        }
        $digit2 = 11 - ($sum % 11);
        $digit2 = ($digit2 > 9) ? 0 : $digit2;
        
        if ($cpf[10] != $digit2) {
            throw ApiException::validation('Validation failed', [
                $fieldName => 'Invalid CPF'
            ]);
        }
    }
    
    public static function validateCNPJ($cnpj, $fieldName = 'cnpj') {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        if (strlen($cnpj) != 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            throw ApiException::validation('Validation failed', [
                $fieldName => 'Invalid CNPJ format'
            ]);
        }
        
        // Verificar dígitos verificadores
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        
        $sum1 = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum1 += $cnpj[$i] * $weights1[$i];
        }
        $digit1 = $sum1 % 11 < 2 ? 0 : 11 - ($sum1 % 11);
        
        if ($cnpj[12] != $digit1) {
            throw ApiException::validation('Validation failed', [
                $fieldName => 'Invalid CNPJ'
            ]);
        }
        
        $sum2 = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum2 += $cnpj[$i] * $weights2[$i];
        }
        $digit2 = $sum2 % 11 < 2 ? 0 : 11 - ($sum2 % 11);
        
        if ($cnpj[13] != $digit2) {
            throw ApiException::validation('Validation failed', [
                $fieldName => 'Invalid CNPJ'
            ]);
        }
    }
    
    public static function validatePhone($phone, $fieldName = 'phone') {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) < 10 || strlen($phone) > 11) {
            throw ApiException::validation('Validation failed', [
                $fieldName => 'Phone must have 10 or 11 digits'
            ]);
        }
    }
    
    public static function validateAmount($amount, $fieldName = 'amount', $min = 0) {
        if (!is_numeric($amount) || floatval($amount) < $min) {
            throw ApiException::validation('Validation failed', [
                $fieldName => "Amount must be a number greater than or equal to {$min}"
            ]);
        }
    }
    
    public static function validatePercentage($percentage, $fieldName = 'percentage') {
        if (!is_numeric($percentage) || floatval($percentage) < 0 || floatval($percentage) > 100) {
            throw ApiException::validation('Validation failed', [
                $fieldName => 'Percentage must be between 0 and 100'
            ]);
        }
    }
    
    public static function validateEnum($value, $allowedValues, $fieldName) {
        if (!in_array($value, $allowedValues)) {
            throw ApiException::validation('Validation failed', [
                $fieldName => "Invalid value. Allowed values: " . implode(', ', $allowedValues)
            ]);
        }
    }
    
    public static function validatePagination($page, $pageSize) {
        $errors = [];
        
        if (!is_numeric($page) || intval($page) < 1) {
            $errors['page'] = 'Page must be a positive integer';
        }
        
        if (!is_numeric($pageSize) || intval($pageSize) < 1 || intval($pageSize) > MAX_PAGE_SIZE) {
            $errors['page_size'] = 'Page size must be between 1 and ' . MAX_PAGE_SIZE;
        }
        
        if (!empty($errors)) {
            throw ApiException::validation('Validation failed', $errors);
        }
    }
    
    public static function validateDate($date, $fieldName = 'date', $format = 'Y-m-d') {
        $dateTime = DateTime::createFromFormat($format, $date);
        
        if (!$dateTime || $dateTime->format($format) !== $date) {
            throw ApiException::validation('Validation failed', [
                $fieldName => "Date must be in format {$format}"
            ]);
        }
    }
    
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        return is_string($data) ? trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8')) : $data;
    }
    
    public static function validateJsonPayload() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') === false) {
            throw ApiException::validation('Content-Type must be application/json');
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ApiException::validation('Invalid JSON payload: ' . json_last_error_msg());
        }
        
        return self::sanitizeInput($data);
    }
}
?>