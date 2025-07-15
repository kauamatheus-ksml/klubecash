<?php
/**
 * Gerenciador de Permissões para Sistema de Funcionários - Klube Cash v2.1
 * 
 * Este sistema permite controle granular de acesso às funcionalidades da loja
 * baseado no tipo de usuário (lojista/funcionário) e subtipo (gerente/financeiro/vendedor)
 * 
 * Localização: utils/PermissionManager.php
 * Compatibilidade: Totalmente integrado com AuthController e sistema de constantes
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

class PermissionManager {
    
    /**
     * Mapa de permissões por subtipo de funcionário
     * Define o que cada tipo de funcionário pode fazer
     */
    private static $permissionMap = [
        EMPLOYEE_TYPE_MANAGER => [
            'dashboard' => ['ver'],
            'transacoes' => ['ver', 'criar', 'upload_lote'],
            'comissoes' => ['ver', 'pagar'],
            'funcionarios' => ['ver', 'criar', 'editar', 'desativar'],
            'relatorios' => ['ver'],
            'configuracoes' => ['ver', 'editar']
        ],
        EMPLOYEE_TYPE_FINANCIAL => [
            'dashboard' => ['ver'],
            'transacoes' => ['ver', 'criar'],
            'comissoes' => ['ver', 'pagar'],
            'relatorios' => ['ver']
        ],
        EMPLOYEE_TYPE_SALESPERSON => [
            'dashboard' => ['ver'],
            'transacoes' => ['ver', 'criar']
        ]
    ];
    
    /**
     * Verifica se o usuário atual tem acesso a um módulo e ação específicos
     * Esta é a função principal que deve ser usada em todas as verificações
     * 
     * @param string $modulo Nome do módulo (ex: 'dashboard', 'transacoes')
     * @param string $acao Nome da ação (ex: 'ver', 'criar', 'editar')
     * @return bool True se tem permissão, False caso contrário
     */
    public static function checkAccess($modulo, $acao = 'ver') {
        // Garantir que a sessão esteja ativa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar se o usuário está logado
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
            error_log("PermissionManager: Usuário não logado tentando acessar {$modulo}:{$acao}");
            return false;
        }
        
        $userType = $_SESSION['user_type'];
        $userId = $_SESSION['user_id'];
        
        // Log para debug
        error_log("PermissionManager: Verificando acesso - Usuário {$userId}, Tipo: {$userType}, Módulo: {$modulo}, Ação: {$acao}");
        
        // Lojistas sempre têm acesso total
        if ($userType === USER_TYPE_STORE) {
            error_log("PermissionManager: Acesso PERMITIDO (lojista)");
            return true;
        }
        
        // Administradores sempre têm acesso total
        if ($userType === USER_TYPE_ADMIN) {
            error_log("PermissionManager: Acesso PERMITIDO (admin)");
            return true;
        }
        
        // Para funcionários, verificar permissões específicas
        if ($userType === USER_TYPE_EMPLOYEE) {
            $hasPermission = self::checkEmployeePermission($userId, $modulo, $acao);
            error_log("PermissionManager: Acesso " . ($hasPermission ? "PERMITIDO" : "NEGADO") . " (funcionário)");
            return $hasPermission;
        }
        
        // Outros tipos de usuário não têm acesso
        error_log("PermissionManager: Acesso NEGADO (tipo de usuário não autorizado: {$userType})");
        return false;
    }
    
    /**
     * Verifica permissão específica de funcionário
     * Utiliza tanto o banco de dados quanto as regras hardcoded
     * 
     * @param int $funcionarioId ID do funcionário
     * @param string $modulo Nome do módulo
     * @param string $acao Nome da ação
     * @return bool True se tem permissão
     */
    private static function checkEmployeePermission($funcionarioId, $modulo, $acao) {
        try {
            $db = Database::getConnection();
            
            // Buscar informações do funcionário
            $userStmt = $db->prepare("
                SELECT subtipo_funcionario, loja_vinculada_id, status
                FROM usuarios 
                WHERE id = ? AND tipo = ? AND status = ?
            ");
            $userStmt->execute([$funcionarioId, USER_TYPE_EMPLOYEE, USER_ACTIVE]);
            
            if ($userStmt->rowCount() === 0) {
                error_log("PermissionManager: Funcionário {$funcionarioId} não encontrado ou inativo");
                return false;
            }
            
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            $subtipo = $user['subtipo_funcionario'];
            $lojaId = $user['loja_vinculada_id'];
            
            // Verificar se a loja está ativa
            $storeStmt = $db->prepare("
                SELECT status 
                FROM lojas 
                WHERE id = ? AND status = ?
            ");
            $storeStmt->execute([$lojaId, STORE_APPROVED]);
            
            if ($storeStmt->rowCount() === 0) {
                error_log("PermissionManager: Loja {$lojaId} não encontrada ou inativa");
                return false;
            }
            
            // Primeiro, verificar se há permissão específica no banco de dados
            $specificPermission = self::checkSpecificPermission($funcionarioId, $modulo, $acao);
            if ($specificPermission !== null) {
                return $specificPermission;
            }
            
            // Se não há permissão específica, usar as regras padrão
            return self::checkDefaultPermission($subtipo, $modulo, $acao);
            
        } catch (PDOException $e) {
            error_log('PermissionManager: Erro ao verificar permissão de funcionário - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica permissão específica no banco de dados
     * 
     * @param int $funcionarioId ID do funcionário
     * @param string $modulo Nome do módulo
     * @param string $acao Nome da ação
     * @return bool|null True/False se encontrou permissão específica, null se não encontrou
     */
    private static function checkSpecificPermission($funcionarioId, $modulo, $acao) {
        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("
                SELECT permitido 
                FROM funcionarios_permissoes 
                WHERE funcionario_id = ? AND modulo = ? AND acao = ?
            ");
            $stmt->execute([$funcionarioId, $modulo, $acao]);
            
            if ($stmt->rowCount() > 0) {
                $permission = $stmt->fetch(PDO::FETCH_ASSOC);
                $result = (bool) $permission['permitido'];
                error_log("PermissionManager: Permissão específica encontrada - {$modulo}:{$acao} = " . ($result ? 'true' : 'false'));
                return $result;
            }
            
            return null; // Não encontrou permissão específica
            
        } catch (PDOException $e) {
            error_log('PermissionManager: Erro ao verificar permissão específica - ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verifica permissão padrão baseada no subtipo
     * 
     * @param string $subtipo Subtipo do funcionário
     * @param string $modulo Nome do módulo
     * @param string $acao Nome da ação
     * @return bool True se tem permissão
     */
    private static function checkDefaultPermission($subtipo, $modulo, $acao) {
        // Verificar se o subtipo existe no mapa de permissões
        if (!isset(self::$permissionMap[$subtipo])) {
            error_log("PermissionManager: Subtipo não reconhecido: {$subtipo}");
            return false;
        }
        
        $subtipoPermissions = self::$permissionMap[$subtipo];
        
        // Verificar se o módulo existe nas permissões do subtipo
        if (!isset($subtipoPermissions[$modulo])) {
            error_log("PermissionManager: Módulo {$modulo} não permitido para subtipo {$subtipo}");
            return false;
        }
        
        // Verificar se a ação está permitida para o módulo
        $hasPermission = in_array($acao, $subtipoPermissions[$modulo]);
        error_log("PermissionManager: Permissão padrão - {$subtipo}:{$modulo}:{$acao} = " . ($hasPermission ? 'true' : 'false'));
        
        return $hasPermission;
    }
    
    /**
     * Obtém o nome legível do subtipo do funcionário
     * 
     * @param string $subtipo Subtipo do funcionário
     * @return string Nome legível
     */
    public static function getSubtipoDisplayName($subtipo) {
        return EMPLOYEE_SUBTYPES[$subtipo] ?? 'Não definido';
    }
    
    /**
     * Lista todas as permissões disponíveis para um subtipo
     * 
     * @param string $subtipo Subtipo do funcionário
     * @return array Lista de permissões organizadas por módulo
     */
    public static function getSubtipoPermissions($subtipo) {
        if (!isset(self::$permissionMap[$subtipo])) {
            return [];
        }
        
        $permissions = [];
        foreach (self::$permissionMap[$subtipo] as $modulo => $acoes) {
            $permissions[$modulo] = [
                'nome' => PERMISSOES_MAPA[$modulo]['nome'] ?? ucfirst($modulo),
                'descricao' => PERMISSOES_MAPA[$modulo]['descricao'] ?? "Módulo {$modulo}",
                'acoes' => $acoes
            ];
        }
        
        return $permissions;
    }
    
    /**
     * Define uma permissão específica para um funcionário (sobrescreve o padrão)
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
                (funcionario_id, loja_id, modulo, acao, permitido, data_criacao, data_atualizacao) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                permitido = VALUES(permitido),
                data_atualizacao = NOW()
            ");
            
            $result = $stmt->execute([$funcionarioId, $lojaId, $modulo, $acao, $permitido ? 1 : 0]);
            
            if ($result) {
                error_log("PermissionManager: Permissão definida - Funcionário {$funcionarioId}, {$modulo}:{$acao} = " . ($permitido ? 'true' : 'false'));
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log('PermissionManager: Erro ao definir permissão - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove uma permissão específica (volta para o padrão do subtipo)
     * 
     * @param int $funcionarioId ID do funcionário
     * @param string $modulo Módulo
     * @param string $acao Ação
     * @return bool True se sucesso
     */
    public static function removePermission($funcionarioId, $modulo, $acao) {
        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("
                DELETE FROM funcionarios_permissoes 
                WHERE funcionario_id = ? AND modulo = ? AND acao = ?
            ");
            
            $result = $stmt->execute([$funcionarioId, $modulo, $acao]);
            
            if ($result) {
                error_log("PermissionManager: Permissão removida - Funcionário {$funcionarioId}, {$modulo}:{$acao}");
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log('PermissionManager: Erro ao remover permissão - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém todas as permissões efetivas de um funcionário (padrão + específicas)
     * 
     * @param int $funcionarioId ID do funcionário
     * @return array Lista completa de permissões
     */
    public static function getEmployeePermissions($funcionarioId) {
        try {
            $db = Database::getConnection();
            
            // Buscar dados do funcionário
            $userStmt = $db->prepare("
                SELECT subtipo_funcionario, loja_vinculada_id
                FROM usuarios 
                WHERE id = ? AND tipo = ?
            ");
            $userStmt->execute([$funcionarioId, USER_TYPE_EMPLOYEE]);
            
            if ($userStmt->rowCount() === 0) {
                return [];
            }
            
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            $subtipo = $user['subtipo_funcionario'];
            
            // Começar com permissões padrão
            $permissions = self::getSubtipoPermissions($subtipo);
            
            // Buscar permissões específicas que sobrescrevem o padrão
            $specificStmt = $db->prepare("
                SELECT modulo, acao, permitido
                FROM funcionarios_permissoes 
                WHERE funcionario_id = ?
            ");
            $specificStmt->execute([$funcionarioId]);
            
            $specificPermissions = $specificStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Aplicar permissões específicas
            foreach ($specificPermissions as $permission) {
                $modulo = $permission['modulo'];
                $acao = $permission['acao'];
                $permitido = (bool) $permission['permitido'];
                
                if (!isset($permissions[$modulo])) {
                    $permissions[$modulo] = [
                        'nome' => ucfirst($modulo),
                        'descricao' => "Módulo {$modulo}",
                        'acoes' => []
                    ];
                }
                
                if ($permitido) {
                    // Adicionar ação se permitida
                    if (!in_array($acao, $permissions[$modulo]['acoes'])) {
                        $permissions[$modulo]['acoes'][] = $acao;
                    }
                } else {
                    // Remover ação se negada
                    $permissions[$modulo]['acoes'] = array_diff($permissions[$modulo]['acoes'], [$acao]);
                }
            }
            
            return [
                'subtipo' => $subtipo,
                'subtipo_display' => self::getSubtipoDisplayName($subtipo),
                'loja_id' => $user['loja_vinculada_id'],
                'permissions' => $permissions
            ];
            
        } catch (PDOException $e) {
            error_log('PermissionManager: Erro ao obter permissões - ' . $e->getMessage());
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
            // Verificar se o subtipo é válido
            if (!isset(self::$permissionMap[$subtipo])) {
                error_log("PermissionManager: Tentativa de aplicar permissões para subtipo inválido: {$subtipo}");
                return false;
            }
            
            // Limpar permissões existentes
            $db = Database::getConnection();
            $clearStmt = $db->prepare("
                DELETE FROM funcionarios_permissoes 
                WHERE funcionario_id = ?
            ");
            $clearStmt->execute([$funcionarioId]);
            
            // As permissões padrão são aplicadas automaticamente
            // através do sistema de verificação, então não precisamos
            // inserir nada no banco de dados
            
            error_log("PermissionManager: Permissões padrão aplicadas para funcionário {$funcionarioId}, subtipo {$subtipo}");
            return true;
            
        } catch (PDOException $e) {
            error_log('PermissionManager: Erro ao aplicar permissões padrão - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se o usuário atual pode gerenciar funcionários
     * 
     * @return bool True se pode gerenciar funcionários
     */
    public static function canManageEmployees() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_type'])) {
            return false;
        }
        
        $userType = $_SESSION['user_type'];
        
        // Lojistas sempre podem gerenciar funcionários
        if ($userType === USER_TYPE_STORE) {
            return true;
        }
        
        // Funcionários: apenas gerentes podem gerenciar outros funcionários
        if ($userType === USER_TYPE_EMPLOYEE) {
            return isset($_SESSION['employee_subtype']) && 
                   $_SESSION['employee_subtype'] === EMPLOYEE_TYPE_MANAGER;
        }
        
        return false;
    }
    
    /**
     * Obtém estatísticas de uso de permissões
     * 
     * @param int $lojaId ID da loja
     * @return array Estatísticas
     */
    public static function getPermissionStats($lojaId) {
        try {
            $db = Database::getConnection();
            
            // Contar funcionários por subtipo
            $stmt = $db->prepare("
                SELECT subtipo_funcionario, COUNT(*) as total
                FROM usuarios 
                WHERE tipo = ? AND loja_vinculada_id = ? AND status = ?
                GROUP BY subtipo_funcionario
            ");
            $stmt->execute([USER_TYPE_EMPLOYEE, $lojaId, USER_ACTIVE]);
            
            $stats = [
                'total_funcionarios' => 0,
                'por_subtipo' => [],
                'permissoes_customizadas' => 0
            ];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stats['total_funcionarios'] += $row['total'];
                $stats['por_subtipo'][$row['subtipo_funcionario']] = $row['total'];
            }
            
            // Contar permissões customizadas
            $customStmt = $db->prepare("
                SELECT COUNT(DISTINCT funcionario_id) as total
                FROM funcionarios_permissoes fp
                JOIN usuarios u ON fp.funcionario_id = u.id
                WHERE u.loja_vinculada_id = ?
            ");
            $customStmt->execute([$lojaId]);
            
            $customResult = $customStmt->fetch(PDO::FETCH_ASSOC);
            $stats['permissoes_customizadas'] = $customResult['total'];
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log('PermissionManager: Erro ao obter estatísticas - ' . $e->getMessage());
            return [];
        }
    }
}
?>