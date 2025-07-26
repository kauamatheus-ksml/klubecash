<?php
/**
 * TESTE BÁSICO - StoreHelper
 * Se este arquivo não der erro fatal, está funcionando
 */

require_once __DIR__ . '/../config/constants.php';

class StoreHelper {
    
    public static function test() {
        return "StoreHelper funcionando!";
    }
    
    public static function requireStoreAccess() {
        // Versão de teste simples
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
            echo "❌ ERRO: Usuário não logado<br>";
            return false;
        }
        
        $userType = $_SESSION['user_type'];
        
        if (!in_array($userType, [USER_TYPE_STORE, USER_TYPE_EMPLOYEE])) {
            echo "❌ ERRO: Tipo de usuário não autorizado: " . $userType . "<br>";
            return false;
        }
        
        echo "✅ SUCESSO: Acesso autorizado para " . $userType . "<br>";
        return true;
    }
    
    public static function getCurrentStoreId() {
        if (!isset($_SESSION['user_type'])) return null;
        
        if ($_SESSION['user_type'] === USER_TYPE_STORE) {
            return $_SESSION['store_id'] ?? null;
        }
        
        if ($_SESSION['user_type'] === USER_TYPE_EMPLOYEE) {
            return $_SESSION['loja_vinculada_id'] ?? null;
        }
        
        return null;
    }
    
    public static function logUserAction($userId, $action, $details = []) {
        $logData = [
            'usuario_id' => $userId,
            'acao' => $action,
            'detalhes' => json_encode($details),
            'data_hora' => date('Y-m-d H:i:s')
        ];
        
        error_log("TESTE_AUDIT: " . json_encode($logData));
        echo "📝 LOG: " . $action . " registrado para usuário " . $userId . "<br>";
    }
}

// TESTE IMEDIATO
if (basename($_SERVER['PHP_SELF']) === 'StoreHelper.php') {
    echo "<h3>🧪 TESTE do StoreHelper</h3>";
    echo StoreHelper::test() . "<br>";
    
    // Simular sessão para teste
    if (!session_id()) session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['user_type'] = 'loja';
    $_SESSION['store_id'] = 1;
    
    StoreHelper::requireStoreAccess();
    echo "Store ID: " . StoreHelper::getCurrentStoreId() . "<br>";
    StoreHelper::logUserAction(1, 'teste_sistema', ['teste' => true]);
}
?>