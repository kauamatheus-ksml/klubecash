<?php
/**
 * Helper para verificações simplificadas - Klube Cash v2.1
 * Sistema ultrarrápido que substitui toda complexidade de permissões
 */

require_once __DIR__ . '/../config/constants.php';

class StoreHelper {
    
    /**
     * Verificação obrigatória para páginas da loja - USA EM TODOS OS ARQUIVOS
     * Substitui todas as verificações complexas de permissão
     */
    public static function requireStoreAccess() {
        // Verificar se está logado
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
            header("Location: " . LOGIN_URL . "?error=session_expired");
            exit;
        }
        
        $userType = $_SESSION['user_type'];
        
        // Apenas lojistas e funcionários têm acesso
        if (!in_array($userType, [USER_TYPE_STORE, USER_TYPE_EMPLOYEE])) {
            header("Location: " . LOGIN_URL . "?error=access_denied");
            exit;
        }
    }
    
    /**
     * Verifica se o usuário tem acesso à loja específica
     * ÚNICA verificação necessária - substitui todo PermissionManager
     */
    public static function hasStoreAccess($userType, $userStoreId, $requiredStoreId) {
        return ($userType === USER_TYPE_STORE || 
                ($userType === USER_TYPE_EMPLOYEE && $userStoreId == $requiredStoreId));
    }
    
    /**
     * Obtém ID da loja do usuário atual
     * Funciona para lojistas E funcionários
     */
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
    
    /**
     * Verifica se pode gerenciar funcionários
     * Apenas para a página específica de funcionários
     */
    public static function canManageEmployees() {
        $userType = $_SESSION['user_type'] ?? '';
        
        // Lojistas sempre podem
        if ($userType === USER_TYPE_STORE) {
            return true;
        }
        
        // Funcionários com subtipo "gerente" também podem
        if ($userType === USER_TYPE_EMPLOYEE) {
            $subtipo = $_SESSION['subtipo_funcionario'] ?? '';
            return in_array($subtipo, ['gerente', 'coordenador']);
        }
        
        return false;
    }
    
    /**
     * Registra ação do usuário para auditoria
     * Substitui logs complexos - registra QUEM fez O QUE
     */
    public static function logUserAction($userId, $action, $details = []) {
        if (!defined('TRACK_USER_ACTIONS') || !TRACK_USER_ACTIONS) return;
        
        $logData = [
            'usuario_id' => $userId,
            'acao' => $action,
            'detalhes' => json_encode($details),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 200),
            'data_hora' => date('Y-m-d H:i:s')
        ];
        
        error_log("KLUBE_AUDIT: " . json_encode($logData));
    }
    
    /**
     * Adiciona campo criado_por em transações/pagamentos
     * Para auditoria - saber quem criou cada registro
     */
    public static function addCreatedByField($data) {
        if (LOG_TRANSACTION_CREATOR || LOG_PAYMENT_CREATOR) {
            $data[AUDIT_CREATED_BY] = $_SESSION['user_id'] ?? null;
        }
        return $data;
    }
}