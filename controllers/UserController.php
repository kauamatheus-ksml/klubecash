<?php
/**
 * UserController.php
 * Controller dedicado para gerenciamento de usuários
 * Klube Cash - Sistema de Cashback
 */

// Importar dependências necessárias
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Security.php';

class UserController {
    
    /**
     * Construtor - Inicializa sessão se necessário
     */
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Verifica se o usuário atual é administrador
     * @return bool
     */
    private static function isAdmin() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === USER_TYPE_ADMIN;
    }
    
    /**
     * Envia resposta JSON formatada
     * @param array $data Dados para enviar
     */
    private static function sendJsonResponse($data) {
        // Limpar qualquer output anterior
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Definir headers corretos
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        // Enviar resposta e finalizar
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Lista usuários com filtros e paginação
     * @param array $filters Filtros aplicados
     * @param int $page Página atual
     * @return array
     */
    public static function listUsers($filters = [], $page = 1) {
        try {
            // Verificar permissões
            if (!self::isAdmin()) {
                return [
                    'status' => false,
                    'message' => 'Acesso negado. Apenas administradores podem visualizar usuários.'
                ];
            }
            
            $db = Database::getConnection();
            $itemsPerPage = ITEMS_PER_PAGE;
            $offset = ($page - 1) * $itemsPerPage;
            
            // Construir consulta base
            $baseQuery = "FROM usuarios u LEFT JOIN lojas l ON u.id = l.usuario_id WHERE 1=1";
            $params = [];
            
            // Aplicar filtros
            if (!empty($filters['tipo']) && $filters['tipo'] !== 'todos') {
                $baseQuery .= " AND u.tipo = :tipo";
                $params[':tipo'] = $filters['tipo'];
            }
            
            if (!empty($filters['status']) && $filters['status'] !== 'todos') {
                $baseQuery .= " AND u.status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['busca'])) {
                $baseQuery .= " AND (u.nome LIKE :busca OR u.email LIKE :busca)";
                $params[':busca'] = '%' . $filters['busca'] . '%';
            }
            
            // Contar total de registros
            $countQuery = "SELECT COUNT(DISTINCT u.id) as total " . $baseQuery;
            $stmt = $db->prepare($countQuery);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $totalUsuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Buscar usuários
            $userQuery = "SELECT DISTINCT u.*, 
                         l.nome_fantasia as loja_nome,
                         l.status as loja_status,
                         CASE 
                            WHEN u.tipo = 'loja' AND l.id IS NOT NULL THEN 'Loja Vinculada'
                            WHEN u.tipo = 'loja' AND l.id IS NULL THEN 'Loja Não Vinculada'
                            ELSE 'N/A'
                         END as loja_info
                         " . $baseQuery . "
                         ORDER BY u.data_criacao DESC 
                         LIMIT :offset, :limit";
            
            $stmt = $db->prepare($userQuery);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Estatísticas
            $statsQuery = "SELECT 
                          COUNT(CASE WHEN tipo = 'cliente' THEN 1 END) as total_clientes,
                          COUNT(CASE WHEN tipo = 'loja' THEN 1 END) as total_lojas,
                          COUNT(CASE WHEN tipo = 'admin' THEN 1 END) as total_admins,
                          COUNT(CASE WHEN status = 'ativo' THEN 1 END) as total_ativos,
                          COUNT(CASE WHEN status = 'inativo' THEN 1 END) as total_inativos,
                          COUNT(CASE WHEN status = 'bloqueado' THEN 1 END) as total_bloqueados
                          FROM usuarios";
            $stmt = $db->prepare($statsQuery);
            $stmt->execute();
            $estatisticas = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Informações de paginação
            $totalPaginas = ceil($totalUsuarios / $itemsPerPage);
            $paginacao = [
                'pagina_atual' => $page,
                'total_paginas' => $totalPaginas,
                'total_registros' => $totalUsuarios,
                'itens_por_pagina' => $itemsPerPage,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPaginas
            ];
            
            return [
                'status' => true,
                'data' => [
                    'usuarios' => $usuarios,
                    'estatisticas' => $estatisticas,
                    'paginacao' => $paginacao,
                    'filtros_aplicados' => $filters
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao listar usuários: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro ao carregar lista de usuários.'
            ];
        }
    }
    
    /**
     * Cria um novo usuário
     * @param array $dadosUsuario Dados do usuário
     * @return array
     */
    public static function createUser($dadosUsuario) {
        try {
            // Verificar permissões
            if (!self::isAdmin()) {
                return [
                    'status' => false,
                    'message' => 'Acesso negado. Apenas administradores podem criar usuários.'
                ];
            }
            
            // Validar dados obrigatórios
            $required = ['nome', 'email', 'senha', 'tipo'];
            foreach ($required as $field) {
                if (empty($dadosUsuario[$field])) {
                    return [
                        'status' => false,
                        'message' => "Campo '{$field}' é obrigatório."
                    ];
                }
            }
            
            // Validações específicas
            if (!filter_var($dadosUsuario['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'status' => false,
                    'message' => 'Email tem formato inválido.'
                ];
            }
            
            if (strlen($dadosUsuario['senha']) < 8) {
                return [
                    'status' => false,
                    'message' => 'Senha deve ter no mínimo 8 caracteres.'
                ];
            }
            
            if (!in_array($dadosUsuario['tipo'], ['cliente', 'loja', 'admin'])) {
                return [
                    'status' => false,
                    'message' => 'Tipo de usuário inválido.'
                ];
            }
            
            // Usar AuthController para criar o usuário
            $resultado = AuthController::register(
                $dadosUsuario['nome'],
                $dadosUsuario['email'],
                $dadosUsuario['telefone'] ?? '',
                $dadosUsuario['senha'],
                $dadosUsuario['tipo']
            );
            
            // Se foi criado com sucesso e o status foi especificado, atualizá-lo
            if ($resultado['status'] && !empty($dadosUsuario['status'])) {
                self::updateUserStatus($resultado['user_id'], $dadosUsuario['status']);
            }
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("Erro ao criar usuário: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro interno do servidor.'
            ];
        }
    }
    
    /**
     * Obtém detalhes de um usuário específico
     * @param int $userId ID do usuário
     * @return array
     */
    public static function getUserDetails($userId) {
        try {
            // Verificar permissões
            if (!self::isAdmin()) {
                return [
                    'status' => false,
                    'message' => 'Acesso negado.'
                ];
            }
            
            $db = Database::getConnection();
            
            // Buscar usuário
            $stmt = $db->prepare("
                SELECT u.*, 
                       l.id as loja_id,
                       l.nome_fantasia,
                       l.razao_social,
                       l.cnpj,
                       l.porcentagem_cashback,
                       l.status as loja_status
                FROM usuarios u 
                LEFT JOIN lojas l ON u.id = l.usuario_id 
                WHERE u.id = :user_id
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                return [
                    'status' => false,
                    'message' => 'Usuário não encontrado.'
                ];
            }
            
            // Buscar estatísticas se for cliente
            if ($usuario['tipo'] === 'cliente') {
                $stmt = $db->prepare("
                    SELECT 
                        COUNT(*) as total_transacoes,
                        COALESCE(SUM(valor_cashback), 0) as total_cashback,
                        COALESCE(SUM(CASE WHEN status = 'aprovado' THEN valor_cashback ELSE 0 END), 0) as cashback_disponivel
                    FROM transacoes_cashback 
                    WHERE usuario_id = :user_id
                ");
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                $estatisticas = $stmt->fetch(PDO::FETCH_ASSOC);
                $usuario['estatisticas'] = $estatisticas;
            }
            
            // Buscar estatísticas se for loja
            if ($usuario['tipo'] === 'loja' && $usuario['loja_id']) {
                $stmt = $db->prepare("
                    SELECT 
                        COUNT(*) as total_vendas,
                        COALESCE(SUM(valor_total), 0) as volume_vendas,
                        COALESCE(SUM(valor_cashback), 0) as cashback_gerado
                    FROM transacoes_cashback 
                    WHERE loja_id = :loja_id
                ");
                $stmt->bindParam(':loja_id', $usuario['loja_id'], PDO::PARAM_INT);
                $stmt->execute();
                $estatisticas = $stmt->fetch(PDO::FETCH_ASSOC);
                $usuario['estatisticas'] = $estatisticas;
            }
            
            return [
                'status' => true,
                'data' => $usuario
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao buscar detalhes do usuário: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro ao carregar detalhes do usuário.'
            ];
        }
    }
    
    /**
     * Atualiza o status de um usuário
     * @param int $userId ID do usuário
     * @param string $novoStatus Novo status
     * @return array
     */
    public static function updateUserStatus($userId, $novoStatus) {
        try {
            // Verificar permissões
            if (!self::isAdmin()) {
                return [
                    'status' => false,
                    'message' => 'Acesso negado.'
                ];
            }
            
            // Validar status
            if (!in_array($novoStatus, ['ativo', 'inativo', 'bloqueado'])) {
                return [
                    'status' => false,
                    'message' => 'Status inválido.'
                ];
            }
            
            $db = Database::getConnection();
            
            // Verificar se usuário existe
            $stmt = $db->prepare("SELECT id, nome, status FROM usuarios WHERE id = :user_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                return [
                    'status' => false,
                    'message' => 'Usuário não encontrado.'
                ];
            }
            
            // Atualizar status
            $stmt = $db->prepare("UPDATE usuarios SET status = :status WHERE id = :user_id");
            $stmt->bindParam(':status', $novoStatus);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return [
                'status' => true,
                'message' => "Status do usuário '{$usuario['nome']}' alterado para '{$novoStatus}' com sucesso."
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar status do usuário: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro ao atualizar status do usuário.'
            ];
        }
    }
    
    /**
     * Deleta um usuário (soft delete)
     * @param int $userId ID do usuário
     * @return array
     */
    public static function deleteUser($userId) {
        try {
            // Verificar permissões
            if (!self::isAdmin()) {
                return [
                    'status' => false,
                    'message' => 'Acesso negado.'
                ];
            }
            
            $db = Database::getConnection();
            
            // Verificar se usuário existe e não é admin
            $stmt = $db->prepare("SELECT id, nome, tipo FROM usuarios WHERE id = :user_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                return [
                    'status' => false,
                    'message' => 'Usuário não encontrado.'
                ];
            }
            
            // Não permitir deletar administradores
            if ($usuario['tipo'] === 'admin') {
                return [
                    'status' => false,
                    'message' => 'Não é possível deletar usuários administradores.'
                ];
            }
            
            // Verificar se é o próprio usuário logado
            if ($userId == $_SESSION['user_id']) {
                return [
                    'status' => false,
                    'message' => 'Você não pode deletar sua própria conta.'
                ];
            }
            
            // Soft delete - marcar como inativo
            $stmt = $db->prepare("UPDATE usuarios SET status = 'inativo', email = CONCAT(email, '_deleted_', id) WHERE id = :user_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return [
                'status' => true,
                'message' => "Usuário '{$usuario['nome']}' removido com sucesso."
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao deletar usuário: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro ao remover usuário.'
            ];
        }
    }
    
    /**
     * Processa requisições AJAX
     */
    public static function handleAjaxRequest() {
        // Verificar se é administrador
        if (!self::isAdmin()) {
            self::sendJsonResponse([
                'status' => false,
                'message' => 'Acesso negado.'
            ]);
        }
        
        $action = $_REQUEST['action'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($action) {
            case 'list':
                // Listar usuários
                $filters = [];
                if (!empty($_GET['tipo'])) $filters['tipo'] = $_GET['tipo'];
                if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
                if (!empty($_GET['busca'])) $filters['busca'] = $_GET['busca'];
                
                $page = max(1, (int)($_GET['page'] ?? 1));
                $result = self::listUsers($filters, $page);
                self::sendJsonResponse($result);
                break;
                
            case 'create':
                // Criar usuário
                if ($method !== 'POST') {
                    self::sendJsonResponse([
                        'status' => false,
                        'message' => 'Método não permitido.'
                    ]);
                }
                
                $dadosUsuario = [
                    'nome' => trim($_POST['nome'] ?? ''),
                    'email' => trim($_POST['email'] ?? ''),
                    'telefone' => trim($_POST['telefone'] ?? ''),
                    'senha' => $_POST['senha'] ?? '',
                    'tipo' => $_POST['tipo'] ?? 'cliente',
                    'status' => $_POST['status'] ?? 'ativo'
                ];
                
                $result = self::createUser($dadosUsuario);
                self::sendJsonResponse($result);
                break;
                
            case 'details':
                // Detalhes do usuário
                $userId = (int)($_GET['id'] ?? 0);
                $result = self::getUserDetails($userId);
                self::sendJsonResponse($result);
                break;
                
            case 'update_status':
                // Atualizar status
                if ($method !== 'POST') {
                    self::sendJsonResponse([
                        'status' => false,
                        'message' => 'Método não permitido.'
                    ]);
                }
                
                $userId = (int)($_POST['user_id'] ?? 0);
                $novoStatus = $_POST['status'] ?? '';
                
                $result = self::updateUserStatus($userId, $novoStatus);
                self::sendJsonResponse($result);
                break;
                
            case 'delete':
                // Deletar usuário
                if ($method !== 'POST') {
                    self::sendJsonResponse([
                        'status' => false,
                        'message' => 'Método não permitido.'
                    ]);
                }
                
                $userId = (int)($_POST['user_id'] ?? 0);
                $result = self::deleteUser($userId);
                self::sendJsonResponse($result);
                break;
                
            default:
                self::sendJsonResponse([
                    'status' => false,
                    'message' => 'Ação não encontrada.'
                ]);
        }
    }
}

// Processar requisições AJAX se o arquivo for chamado diretamente
if (basename($_SERVER['PHP_SELF']) === 'UserController.php') {
    UserController::handleAjaxRequest();
}
?>