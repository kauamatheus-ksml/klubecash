<?php
// controllers/StoreController.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/AuthController.php';

/**
 * Controlador de Lojas
 * Gerencia operações relacionadas a lojas parceiras
 */
class StoreController {
    
    /**
     * Aprova uma loja pendente
     * 
     * @param int $storeId ID da loja
     * @return array Resultado da operação
     */
    public static function approveStore($storeId) {
        try {
            // Verificar se é um administrador
            if (!AuthController::isAdmin()) {
                return ['status' => false, 'message' => 'Acesso restrito a administradores.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se a loja existe e está pendente
            $stmt = $db->prepare("SELECT * FROM lojas WHERE id = :store_id AND status = :status");
            $stmt->bindParam(':store_id', $storeId);
            $status = STORE_PENDING;
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            $store = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$store) {
                return ['status' => false, 'message' => 'Loja não encontrada ou não está pendente.'];
            }
            
            // Atualizar status para aprovado
            $updateStmt = $db->prepare("
                UPDATE lojas 
                SET status = :new_status, data_aprovacao = NOW() 
                WHERE id = :store_id
            ");
            $newStatus = STORE_APPROVED;
            $updateStmt->bindParam(':new_status', $newStatus);
            $updateStmt->bindParam(':store_id', $storeId);
            $updateStmt->execute();
            
            // Enviar email de notificação
            if (!empty($store['email'])) {
                $subject = 'Loja Aprovada - Klube Cash';
                $message = "
                    <h3>Parabéns, {$store['nome_fantasia']}!</h3>
                    <p>Sua loja foi aprovada no sistema Klube Cash!</p>
                    <p>Agora seus clientes podem começar a receber cashback em suas compras.</p>
                    <p>Atenciosamente,<br>Equipe Klube Cash</p>
                ";
                Email::send($store['email'], $subject, $message, $store['nome_fantasia']);
            }
            
            return ['status' => true, 'message' => 'Loja aprovada com sucesso.'];
            
        } catch (PDOException $e) {
            error_log('Erro ao aprovar loja: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao aprovar loja. Por favor, tente novamente.'];
        }
    }
    
    /**
     * Rejeita uma loja pendente
     * 
     * @param int $storeId ID da loja
     * @param string $observacao Motivo da rejeição
     * @return array Resultado da operação
     */
    public static function rejectStore($storeId, $observacao = '') {
        try {
            // Verificar se é um administrador
            if (!AuthController::isAdmin()) {
                return ['status' => false, 'message' => 'Acesso restrito a administradores.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se a loja existe e está pendente
            $stmt = $db->prepare("SELECT * FROM lojas WHERE id = :store_id AND status = :status");
            $stmt->bindParam(':store_id', $storeId);
            $status = STORE_PENDING;
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            $store = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$store) {
                return ['status' => false, 'message' => 'Loja não encontrada ou não está pendente.'];
            }
            
            // Atualizar status para rejeitado
            $updateStmt = $db->prepare("
                UPDATE lojas 
                SET status = :new_status, observacao = :observacao 
                WHERE id = :store_id
            ");
            $newStatus = STORE_REJECTED;
            $updateStmt->bindParam(':new_status', $newStatus);
            $updateStmt->bindParam(':observacao', $observacao);
            $updateStmt->bindParam(':store_id', $storeId);
            $updateStmt->execute();
            
            // Enviar email de notificação
            if (!empty($store['email'])) {
                $subject = 'Solicitação de Loja Rejeitada - Klube Cash';
                $message = "
                    <h3>Prezado(a), {$store['nome_fantasia']}!</h3>
                    <p>Infelizmente, sua solicitação para se tornar uma loja parceira no Klube Cash foi rejeitada.</p>
                ";
                
                if (!empty($observacao)) {
                    $message .= "<p><strong>Motivo:</strong> " . nl2br(htmlspecialchars($observacao)) . "</p>";
                }
                
                $message .= "
                    <p>Se tiver dúvidas ou quiser mais informações, entre em contato com nosso suporte.</p>
                    <p>Atenciosamente,<br>Equipe Klube Cash</p>
                ";
                Email::send($store['email'], $subject, $message, $store['nome_fantasia']);
            }
            
            return ['status' => true, 'message' => 'Loja rejeitada com sucesso.'];
            
        } catch (PDOException $e) {
            error_log('Erro ao rejeitar loja: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao rejeitar loja. Por favor, tente novamente.'];
        }
    }
    
    /**
     * Cadastra uma nova loja
     * 
     * @param array $data Dados da loja
     * @return array Resultado da operação
     */
    public static function registerStore($data) {
        try {
            
            // Validar dados obrigatórios
            $requiredFields = ['nome_fantasia', 'razao_social', 'cnpj', 'email', 'telefone'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return ['status' => false, 'message' => 'Preencha todos os campos obrigatórios.'];
                }
            }
            
            $db = Database::getConnection();
            
            // Verificar se já existe uma loja com este CNPJ
            $stmt = $db->prepare("SELECT id FROM lojas WHERE cnpj = :cnpj");
            $stmt->bindParam(':cnpj', $data['cnpj']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return ['status' => false, 'message' => 'Já existe uma loja cadastrada com este CNPJ.'];
            }
            
            // Inserir nova loja
            $insertStmt = $db->prepare("
                INSERT INTO lojas (
                    nome_fantasia, razao_social, cnpj, email, telefone,
                    categoria, porcentagem_cashback, descricao, website,
                    status, data_cadastro
                ) VALUES (
                    :nome_fantasia, :razao_social, :cnpj, :email, :telefone,
                    :categoria, :porcentagem_cashback, :descricao, :website,
                    :status, NOW()
                )
            ");
            
            $insertStmt->bindParam(':nome_fantasia', $data['nome_fantasia']);
            $insertStmt->bindParam(':razao_social', $data['razao_social']);
            $insertStmt->bindParam(':cnpj', $data['cnpj']);
            $insertStmt->bindParam(':email', $data['email']);
            $insertStmt->bindParam(':telefone', $data['telefone']);
            $insertStmt->bindParam(':categoria', $data['categoria'] ?? 'Outros');
            $porcentagemCashback = isset($data['porcentagem_cashback']) ? $data['porcentagem_cashback'] : DEFAULT_CASHBACK_TOTAL;
            $insertStmt->bindParam(':porcentagem_cashback', $porcentagemCashback);
            $insertStmt->bindParam(':descricao', $data['descricao'] ?? '');
            $insertStmt->bindParam(':website', $data['website'] ?? '');
            
            // Status inicial (pendente para aprovação ou aprovado direto se for cadastro pelo admin)
            $initialStatus = AuthController::isAdmin() ? STORE_APPROVED : STORE_PENDING;
            $insertStmt->bindParam(':status', $initialStatus);
            
            $insertStmt->execute();
            $storeId = $db->lastInsertId();
            
            // Se tiver endereço, cadastrar
            if (isset($data['endereco']) && !empty($data['endereco'])) {
                $addressStmt = $db->prepare("
                    INSERT INTO lojas_endereco (
                        loja_id, cep, logradouro, numero, complemento,
                        bairro, cidade, estado
                    ) VALUES (
                        :loja_id, :cep, :logradouro, :numero, :complemento,
                        :bairro, :cidade, :estado
                    )
                ");
                
                $addressStmt->bindParam(':loja_id', $storeId);
                $addressStmt->bindParam(':cep', $data['endereco']['cep']);
                $addressStmt->bindParam(':logradouro', $data['endereco']['logradouro']);
                $addressStmt->bindParam(':numero', $data['endereco']['numero']);
                $addressStmt->bindParam(':complemento', $data['endereco']['complemento'] ?? '');
                $addressStmt->bindParam(':bairro', $data['endereco']['bairro']);
                $addressStmt->bindParam(':cidade', $data['endereco']['cidade']);
                $addressStmt->bindParam(':estado', $data['endereco']['estado']);
                $addressStmt->execute();
            }
            
            if (!Validator::validaCNPJ($data['cnpj'])) {
                return ['status' => false, 'message' => 'CNPJ inválido.'];
            }
            // Enviar email de notificação
            $subject = 'Solicitação de Loja Recebida - Klube Cash';
            $message = "
                <h3>Olá, {$data['nome_fantasia']}!</h3>
                <p>Recebemos sua solicitação para se tornar uma loja parceira no Klube Cash.</p>
            ";
            
            if ($initialStatus == STORE_APPROVED) {
                $message .= "<p>Sua loja foi <strong>automaticamente aprovada</strong> e já está disponível no sistema!</p>";
            } else {
                $message .= "<p>Nossa equipe irá analisar suas informações em breve.</p>";
                $message .= "<p>Você receberá uma notificação quando sua loja for aprovada.</p>";
            }
            
            $message .= "<p>Atenciosamente,<br>Equipe Klube Cash</p>";
            
            Email::send($data['email'], $subject, $message, $data['nome_fantasia']);
            
            // Se cadastrado por admin, enviar email adicional
            if (AuthController::isAdmin()) {
                // Enviar email ao admin
                $adminSubject = 'Nova Loja Cadastrada - Klube Cash';
                $adminMessage = "
                    <h3>Nova Loja Cadastrada</h3>
                    <p>Uma nova loja foi cadastrada no sistema:</p>
                    <p><strong>Nome Fantasia:</strong> {$data['nome_fantasia']}</p>
                    <p><strong>Razão Social:</strong> {$data['razao_social']}</p>
                    <p><strong>CNPJ:</strong> {$data['cnpj']}</p>
                    <p><strong>Status:</strong> " . ($initialStatus == STORE_APPROVED ? 'Aprovada' : 'Pendente') . "</p>
                ";
                
                Email::send(ADMIN_EMAIL, $adminSubject, $adminMessage);
            }
            
            return [
                'status' => true, 
                'message' => 'Loja cadastrada com sucesso!',
                'store_id' => $storeId,
                'awaiting_approval' => ($initialStatus == STORE_PENDING)
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao cadastrar loja: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao cadastrar loja. Por favor, tente novamente.'];
        }
    }
    
    /**
     * Obtém lista de lojas
     * 
     * @param array $filters Filtros para a listagem
     * @param int $page Página atual
     * @return array Lista de lojas
     */
    public static function getStores($filters = [], $page = 1) {
        try {
            $db = Database::getConnection();
            
            // Preparar consulta base
            $query = "
                SELECT *
                FROM lojas
                WHERE 1=1
            ";
            
            $params = [];
            
            // Aplicar filtros
            if (!empty($filters)) {
                // Filtro por status
                if (isset($filters['status']) && !empty($filters['status'])) {
                    $query .= " AND status = :status";
                    $params[':status'] = $filters['status'];
                }
                
                // Filtro por categoria
                if (isset($filters['categoria']) && !empty($filters['categoria'])) {
                    $query .= " AND categoria = :categoria";
                    $params[':categoria'] = $filters['categoria'];
                }
                
                // Filtro por busca (nome, razão social ou CNPJ)
                if (isset($filters['busca']) && !empty($filters['busca'])) {
                    $query .= " AND (nome_fantasia LIKE :busca OR razao_social LIKE :busca OR cnpj LIKE :busca)";
                    $params[':busca'] = '%' . $filters['busca'] . '%';
                }
            }
            
            // Padrão de busca para não-admins: apenas lojas aprovadas
            if (!AuthController::isAdmin()) {
                $query .= " AND status = :approved_status";
                $params[':approved_status'] = STORE_APPROVED;
            }
            
            // Ordenação (padrão: nome_fantasia)
            $orderBy = isset($filters['order_by']) ? $filters['order_by'] : 'nome_fantasia';
            $orderDir = isset($filters['order_dir']) && strtolower($filters['order_dir']) == 'desc' ? 'DESC' : 'ASC';
            $query .= " ORDER BY $orderBy $orderDir";
            
            // Calcular total de registros para paginação
            $countStmt = $db->prepare(str_replace('*', 'COUNT(*) as total', $query));
            foreach ($params as $param => $value) {
                $countStmt->bindValue($param, $value);
            }
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Adicionar paginação
            $perPage = ITEMS_PER_PAGE;
            $offset = ($page - 1) * $perPage;
            $query .= " LIMIT $offset, $perPage";
            
            // Executar consulta
            $stmt = $db->prepare($query);
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            $stmt->execute();
            $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obter categorias disponíveis para filtro
            $categoriesStmt = $db->query("SELECT DISTINCT categoria FROM lojas ORDER BY categoria");
            $categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Calcular informações de paginação
            $totalPages = ceil($totalCount / $perPage);
            
            return [
                'status' => true,
                'data' => [
                    'lojas' => $stores,
                    'categorias' => $categories,
                    'paginacao' => [
                        'total' => $totalCount,
                        'por_pagina' => $perPage,
                        'pagina_atual' => $page,
                        'total_paginas' => $totalPages
                    ]
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao listar lojas: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao carregar lojas. Tente novamente.'];
        }
    }
}

// Processar requisições diretas de acesso ao controlador
if (basename($_SERVER['PHP_SELF']) === 'StoreController.php') {
    // Verificar se o usuário está autenticado
    if (!AuthController::isAuthenticated()) {
        header('Location: ' . LOGIN_URL . '?error=' . urlencode('Você precisa fazer login para acessar esta página.'));
        exit;
    }
    
    $action = $_REQUEST['action'] ?? '';
    
    switch ($action) {
        case 'approve':
            // Verificar se é um administrador
            if (!AuthController::isAdmin()) {
                echo json_encode(['status' => false, 'message' => 'Acesso restrito a administradores.']);
                exit;
            }
            
            $storeId = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $result = StoreController::approveStore($storeId);
            echo json_encode($result);
            break;
            
        case 'reject':
            // Verificar se é um administrador
            if (!AuthController::isAdmin()) {
                echo json_encode(['status' => false, 'message' => 'Acesso restrito a administradores.']);
                exit;
            }
            
            $storeId = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $observacao = $_POST['observacao'] ?? '';
            $result = StoreController::rejectStore($storeId, $observacao);
            echo json_encode($result);
            break;
            
        case 'register':
            $data = $_POST;
            $result = StoreController::registerStore($data);
            echo json_encode($result);
            break;
            
        case 'list':
            $filters = $_POST['filters'] ?? [];
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $result = StoreController::getStores($filters, $page);
            echo json_encode($result);
            break;
            
        default:
            // Acesso inválido ao controlador
            if (AuthController::isAdmin()) {
                header('Location: ' . ADMIN_STORES_URL);
            } else {
                header('Location: ' . CLIENT_STORES_URL);
            }
            exit;
    }
}
?>