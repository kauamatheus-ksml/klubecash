<?php
// utils/PermissionManager.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

class PermissionManager {
    
    /**
     * Verifica se um funcionário tem permissão para uma ação específica
     * 
     * @param int $userId ID do usuário (funcionário)
     * @param string $modulo Módulo a ser acessado
     * @param string $acao Ação a ser realizada
     * @return bool True se tem permissão, False caso contrário
     */
    public static function hasPermission($userId, $modulo, $acao) {
        try {
            $db = Database::getConnection();
            
            // Buscar permissão específica do funcionário
            $stmt = $db->prepare("
                SELECT permitido 
                FROM funcionarios_permissoes 
                WHERE funcionario_id = ? AND modulo = ? AND acao = ?
            ");
            $stmt->execute([$userId, $modulo, $acao]);
            
            if ($stmt->rowCount() > 0) {
                $permission = $stmt->fetch(PDO::FETCH_ASSOC);
                return (bool) $permission['permitido'];
            }
            
            // Se não há permissão específica, verificar permissão padrão do subtipo
            $userStmt = $db->prepare("
                SELECT subtipo_funcionario 
                FROM usuarios 
                WHERE id = ? AND tipo = 'funcionario'
            ");
            $userStmt->execute([$userId]);
            
            if ($userStmt->rowCount() > 0) {
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                $defaultStmt = $db->prepare("
                    SELECT permitido_padrao 
                    FROM permissoes_padrao 
                    WHERE subtipo_funcionario = ? AND modulo = ? AND acao = ?
                ");
                $defaultStmt->execute([$user['subtipo_funcionario'], $modulo, $acao]);
                
                if ($defaultStmt->rowCount() > 0) {
                    $defaultPermission = $defaultStmt->fetch(PDO::FETCH_ASSOC);
                    return (bool) $defaultPermission['permitido_padrao'];
                }
            }
            
            // Se não encontrou nada, negar acesso por segurança
            return false;
            
        } catch (PDOException $e) {
            error_log('Erro ao verificar permissão: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Define uma permissão específica para um funcionário
     * 
     * @param int $funcionarioId ID do funcionário
     * @param int $lojaId ID da loja
     * @param string $modulo Módulo
     * @param string $acao Ação
     * @param bool $permitido Se deve permitir ou negar
     * @return bool True se sucesso
     */
    public static function setPermission($funcionarioId, $lojaId, $modulo, $acao, $permitido) {
        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("
                INSERT INTO funcionarios_permissoes 
                (funcionario_id, loja_id, modulo, acao, permitido) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                permitido = VALUES(permitido),
                data_atualizacao = CURRENT_TIMESTAMP
            ");
            
            return $stmt->execute([$funcionarioId, $lojaId, $modulo, $acao, $permitido ? 1 : 0]);
            
        } catch (PDOException $e) {
            error_log('Erro ao definir permissão: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém todas as permissões de um funcionário
     * 
     * @param int $funcionarioId ID do funcionário
     * @return array Lista de permissões
     */
    public static function getUserPermissions($funcionarioId) {
        try {
            $db = Database::getConnection();
            
            // Buscar subtipo do funcionário
            $userStmt = $db->prepare("
                SELECT subtipo_funcionario, loja_vinculada_id
                FROM usuarios 
                WHERE id = ? AND tipo = 'funcionario'
            ");
            $userStmt->execute([$funcionarioId]);
            
            if ($userStmt->rowCount() === 0) {
                return [];
            }
            
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            $permissions = [];
            
            // Para cada módulo e ação, verificar a permissão
            foreach (PERMISSOES_MAPA as $modulo => $config) {
                $permissions[$modulo] = [];
                foreach ($config['acoes'] as $acao => $descricao) {
                    $permissions[$modulo][$acao] = self::hasPermission($funcionarioId, $modulo, $acao);
                }
            }
            
            return [
                'permissions' => $permissions,
                'subtipo' => $user['subtipo_funcionario'],
                'loja_id' => $user['loja_vinculada_id']
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter permissões: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Aplica permissões padrão quando um funcionário é criado
     * 
     * @param int $funcionarioId ID do funcionário
     * @param int $lojaId ID da loja
     * @param string $subtipo Subtipo do funcionário
     * @return bool True se sucesso
     */
    public static function applyDefaultPermissions($funcionarioId, $lojaId, $subtipo) {
        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("
                SELECT modulo, acao, permitido_padrao
                FROM permissoes_padrao
                WHERE subtipo_funcionario = ?
            ");
            $stmt->execute([$subtipo]);
            
            $defaultPermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($defaultPermissions as $permission) {
                self::setPermission(
                    $funcionarioId, 
                    $lojaId, 
                    $permission['modulo'], 
                    $permission['acao'], 
                    $permission['permitido_padrao']
                );
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log('Erro ao aplicar permissões padrão: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Middleware para verificar permissões em rotas
     * 
     * @param string $modulo Módulo necessário
     * @param string $acao Ação necessária
     * @return bool True se tem acesso
     */
    public static function checkAccess($modulo, $acao) {
        // Verificar se está logado
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
            return false;
        }
        
        $userType = $_SESSION['user_type'];
        $userId = $_SESSION['user_id'];
        
        // Se é lojista, tem acesso total
        if ($userType === 'loja') {
            return true;
        }
        
        // Se é funcionário, verificar permissões
        if ($userType === 'funcionario') {
            return self::hasPermission($userId, $modulo, $acao);
        }
        
        // Outros tipos de usuário não têm acesso à área da loja
        return false;
    }
}
?>