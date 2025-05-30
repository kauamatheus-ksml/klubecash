<?php
// utils/GoogleAuth.php

class GoogleAuth {
    
    /**
     * Gera a URL de autorização do Google
     */
    public static function getAuthUrl() {
        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'state' => self::generateState(),
            'access_type' => 'offline', // Para refresh token se necessário
            'prompt' => 'select_account' // Permite escolher conta
        ];
        
        // Armazenar o state na sessão para verificação posterior
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['google_oauth_state'] = $params['state'];
        
        return GOOGLE_AUTH_URL . '?' . http_build_query($params);
    }
    
    /**
     * Troca o código de autorização por um token de acesso
     */
    public static function getAccessToken($code) {
        $postData = [
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => GOOGLE_REDIRECT_URI
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, GOOGLE_TOKEN_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            error_log('Erro cURL no token Google: ' . curl_error($ch));
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log('Erro HTTP no token Google: ' . $httpCode . ' - ' . $response);
            return false;
        }
        
        return json_decode($response, true);
    }
    /**
    * Valida se os dados do usuário Google são suficientes para registro
    */
    public static function validateUserDataForRegistration($userInfo) {
        $errors = [];
        
        // Verificar nome
        if (empty($userInfo['name']) || strlen(trim($userInfo['name'])) < 2) {
            $errors[] = 'Nome fornecido pelo Google é inválido';
        }
        
        // Verificar email
        if (empty($userInfo['email']) || !filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email fornecido pelo Google é inválido';
        }
        
        // Verificar se email está verificado no Google
        if (!isset($userInfo['verified_email']) || !$userInfo['verified_email']) {
            $errors[] = 'Email não está verificado no Google';
        }
        
        // Verificar ID do Google
        if (empty($userInfo['id'])) {
            $errors[] = 'ID do Google não fornecido';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'user_data' => [
                'name' => trim($userInfo['name'] ?? ''),
                'email' => strtolower(trim($userInfo['email'] ?? '')),
                'google_id' => $userInfo['id'] ?? '',
                'avatar_url' => $userInfo['picture'] ?? null,
                'email_verified' => isset($userInfo['verified_email']) && $userInfo['verified_email']
            ]
        ];
    }
    /**
     * Busca informações do usuário usando o token de acesso
     */
    public static function getUserInfo($accessToken) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, GOOGLE_USER_INFO_URL . '?access_token=' . $accessToken);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            error_log('Erro cURL nas informações do usuário Google: ' . curl_error($ch));
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log('Erro HTTP nas informações do usuário Google: ' . $httpCode . ' - ' . $response);
            return false;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Gera um state aleatório para segurança
     */
    private static function generateState() {
        return bin2hex(random_bytes(16));
    }
    
    /**
     * Verifica se o state é válido
     */
    public static function verifyState($state) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['google_oauth_state']) && 
               hash_equals($_SESSION['google_oauth_state'], $state);
    }
}
?>