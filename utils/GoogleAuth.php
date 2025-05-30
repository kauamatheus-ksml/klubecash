<?php
// utils/GoogleAuth.php - VERSÃO ATUALIZADA

class GoogleAuth {
    
    /**
     * Gera a URL de autorização do Google
     */
    public static function getAuthUrl() {
        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'response_type' => 'code',
            'scope' => 'openid email profile', // Escopos necessários
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'state' => self::generateState(),
            'access_type' => 'offline',
            'prompt' => 'select_account'
        ];
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['google_oauth_state'] = $params['state'];
        
        return GOOGLE_AUTH_URL . '?' . http_build_query($params);
    }
    
    /**
     * Busca informações do usuário usando People API
     */
    public static function getUserInfo($accessToken) {
        // Tentar People API primeiro (recomendada)
        $peopleApiUrl = 'https://people.googleapis.com/v1/people/me?personFields=names,emailAddresses,photos';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $peopleApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            
            // Converter formato People API para formato compatível
            return self::convertPeopleApiToLegacyFormat($data);
        }
        
        // Fallback para API antiga se People API falhar
        error_log('People API falhou, tentando API legada');
        return self::getUserInfoLegacy($accessToken);
    }
    
    /**
     * Fallback para API legada (Google+ API ou userinfo)
     */
    private static function getUserInfoLegacy($accessToken) {
        $legacyUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $legacyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log('API legada também falhou: ' . $httpCode . ' - ' . $response);
            return false;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Converte resposta da People API para formato compatível
     */
    private static function convertPeopleApiToLegacyFormat($peopleData) {
        $converted = [];
        
        // ID do usuário
        $converted['id'] = $peopleData['resourceName'] ?? '';
        $converted['id'] = str_replace('people/', '', $converted['id']);
        
        // Nome
        if (isset($peopleData['names']) && !empty($peopleData['names'])) {
            $converted['name'] = $peopleData['names'][0]['displayName'] ?? '';
        }
        
        // Email
        if (isset($peopleData['emailAddresses']) && !empty($peopleData['emailAddresses'])) {
            $converted['email'] = $peopleData['emailAddresses'][0]['value'] ?? '';
            $converted['verified_email'] = true; // People API já retorna emails verificados
        }
        
        // Foto
        if (isset($peopleData['photos']) && !empty($peopleData['photos'])) {
            $converted['picture'] = $peopleData['photos'][0]['url'] ?? '';
        }
        
        return $converted;
    }
    
    // ... resto dos métodos permanecem iguais ...
    
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
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log('Erro HTTP no token Google: ' . $httpCode . ' - ' . $response);
            return false;
        }
        
        return json_decode($response, true);
    }
    
    private static function generateState() {
        return bin2hex(random_bytes(16));
    }
    
    public static function verifyState($state) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['google_oauth_state']) && 
               hash_equals($_SESSION['google_oauth_state'], $state);
    }
}
?>