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
    * Cadastra uma nova loja com senha
    * 
    * @param array $data Dados da loja
    * @return array Resultado da operação
    */
    public static function registerStore($data) {
        try {
            // Validar dados obrigatórios (incluindo senha)
            $requiredFields = ['nome_fantasia', 'razao_social', 'cnpj', 'email', 'telefone', 'senha'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return ['status' => false, 'message' => 'Preencha todos os campos obrigatórios.'];
                }
            }
            
            // Validar confirmação de senha
            if (!isset($data['confirma_senha']) || $data['senha'] !== $data['confirma_senha']) {
                return ['status' => false, 'message' => 'As senhas não coincidem.'];
            }
            
            // Validar força da senha
            if (strlen($data['senha']) < PASSWORD_MIN_LENGTH) {
                return ['status' => false, 'message' => 'A senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se já existe uma loja com este CNPJ
            $stmt = $db->prepare("SELECT id FROM lojas WHERE cnpj = :cnpj");
            $stmt->bindParam(':cnpj', $data['cnpj']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return ['status' => false, 'message' => 'Já existe uma loja cadastrada com este CNPJ.'];
            }
            
            // Verificar se já existe um usuário com este email
            $userStmt = $db->prepare("SELECT id FROM usuarios WHERE email = :email");
            $userStmt->bindParam(':email', $data['email']);
            $userStmt->execute();
            
            if ($userStmt->rowCount() > 0) {
                return ['status' => false, 'message' => 'Já existe um usuário cadastrado com este email.'];
            }
            
            // INICIAR TRANSAÇÃO PARA CRIAR USUÁRIO E LOJA JUNTOS
            $db->beginTransaction();
            
            // 1. CRIAR O USUÁRIO PRIMEIRO
            $senhaHash = password_hash($data['senha'], PASSWORD_DEFAULT);
            
            $userInsertStmt = $db->prepare("
                INSERT INTO usuarios (nome, email, telefone, senha_hash, tipo, status, data_criacao)
                VALUES (:nome, :email, :telefone, :senha_hash, :tipo, :status, NOW())
            ");
            
            $userInsertStmt->bindParam(':nome', $data['nome_fantasia']); // Usar nome fantasia como nome do usuário
            $userInsertStmt->bindParam(':email', $data['email']);
            $userInsertStmt->bindParam(':telefone', $data['telefone']);
            $userInsertStmt->bindParam(':senha_hash', $senhaHash);
            
            // IMPORTANTE: Usuário criado como INATIVO até loja ser aprovada
            $tipoUsuario = USER_TYPE_STORE;
            $statusUsuario = USER_INACTIVE;

            $stmt->bindParam(':tipo', $tipoUsuario);
            $stmt->bindParam(':status', $statusUsuario);
            
            if (!$userInsertStmt->execute()) {
                $db->rollBack();
                return ['status' => false, 'message' => 'Erro ao criar usuário da loja.'];
            }
            
            $userId = $db->lastInsertId();
            
            // 2. CRIAR A LOJA VINCULADA AO USUÁRIO
            $storeInsertStmt = $db->prepare("
                INSERT INTO lojas (
                    nome_fantasia, razao_social, cnpj, email, telefone,
                    categoria, porcentagem_cashback, descricao, website,
                    usuario_id, status, data_cadastro
                ) VALUES (
                    :nome_fantasia, :razao_social, :cnpj, :email, :telefone,
                    :categoria, :porcentagem_cashback, :descricao, :website,
                    :usuario_id, :status, NOW()
                )
            ");
            
            $storeInsertStmt->bindParam(':nome_fantasia', $data['nome_fantasia']);
            $storeInsertStmt->bindParam(':razao_social', $data['razao_social']);
            $storeInsertStmt->bindParam(':cnpj', $data['cnpj']);
            $storeInsertStmt->bindParam(':email', $data['email']);
            $storeInsertStmt->bindParam(':telefone', $data['telefone']);
            
            $categoria = $data['categoria'] ?? 'Outros';
            $porcentagemCashback = DEFAULT_CASHBACK_TOTAL; // Sempre 10%
            $descricao = $data['descricao'] ?? '';
            $website = $data['website'] ?? '';
            
            $storeInsertStmt->bindParam(':categoria', $categoria);
            $storeInsertStmt->bindParam(':porcentagem_cashback', $porcentagemCashback);
            $storeInsertStmt->bindParam(':descricao', $descricao);
            $storeInsertStmt->bindParam(':website', $website);
            $storeInsertStmt->bindParam(':usuario_id', $userId); // VINCULAR USUÁRIO À LOJA
            
            // Status inicial sempre pendente
            $initialStatus = STORE_PENDING;
            $storeInsertStmt->bindParam(':status', $initialStatus);
            
            if (!$storeInsertStmt->execute()) {
                $db->rollBack();
                return ['status' => false, 'message' => 'Erro ao cadastrar loja.'];
            }
            
            $storeId = $db->lastInsertId();
            
            // 3. PROCESSAR ENDEREÇO SE FORNECIDO
            if (isset($data['endereco']) && is_array($data['endereco'])) {
                $endereco = $data['endereco'];
                $enderecoStmt = $db->prepare("
                    INSERT INTO lojas_endereco (
                        loja_id, cep, logradouro, numero, complemento, bairro, cidade, estado
                    ) VALUES (
                        :loja_id, :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :estado
                    )
                ");
                
                $enderecoStmt->bindParam(':loja_id', $storeId);
                $enderecoStmt->bindParam(':cep', $endereco['cep'] ?? '');
                $enderecoStmt->bindParam(':logradouro', $endereco['logradouro'] ?? '');
                $enderecoStmt->bindParam(':numero', $endereco['numero'] ?? '');
                $enderecoStmt->bindParam(':complemento', $endereco['complemento'] ?? '');
                $enderecoStmt->bindParam(':bairro', $endereco['bairro'] ?? '');
                $enderecoStmt->bindParam(':cidade', $endereco['cidade'] ?? '');
                $enderecoStmt->bindParam(':estado', $endereco['estado'] ?? '');
                
                if (!$enderecoStmt->execute()) {
                    $db->rollBack();
                    return ['status' => false, 'message' => 'Erro ao cadastrar endereço da loja.'];
                }
            }
            
            // CONFIRMAR TODAS AS OPERAÇÕES
            $db->commit();
            
            // Enviar email de notificação
            if (!empty($data['email'])) {
                $subject = 'Cadastro Recebido - Klube Cash';
                $message = "
                    <h3>Olá, {$data['nome_fantasia']}!</h3>
                    <p>Recebemos sua solicitação para se tornar uma loja parceira do Klube Cash.</p>
                    <p>Criamos sua conta de acesso com as seguintes informações:</p>
                    <ul>
                        <li><strong>Email:</strong> {$data['email']}</li>
                        <li><strong>Tipo de conta:</strong> Loja Parceira</li>
                    </ul>
                    <p>Sua solicitação está sob análise. Assim que for aprovada:</p>
                    <ul>
                        <li>Sua conta será ativada automaticamente</li>
                        <li>Você poderá fazer login no sistema</li>
                        <li>Sua loja será exibida no catálogo para clientes</li>
                    </ul>
                    <p>Em breve entraremos em contato com o resultado da análise.</p>
                    <p>Atenciosamente,<br>Equipe Klube Cash</p>
                ";
                Email::send($data['email'], $subject, $message, $data['nome_fantasia']);
            }
            
            return [
                'status' => true, 
                'message' => 'Loja e usuário cadastrados com sucesso! Aguarde a aprovação para acessar o sistema.',
                'data' => [
                    'store_id' => $storeId,
                    'user_id' => $userId,
                    'awaiting_approval' => true
                ]
            ];
            
        } catch (PDOException $e) {
            // Reverter todas as alterações em caso de erro
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            
            error_log('Erro ao cadastrar loja: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao processar cadastro. Tente novamente.'];
        }
    }
    
     /**
     * Valida CNPJ
     * @param string $cnpj CNPJ com ou sem máscara
     * @return bool Verdadeiro se o CNPJ for válido
     */
   
    public static function validaCNPJ($cnpj) {
        // Remover caracteres especiais
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        // Verificar se tem 14 dígitos
        if (strlen($cnpj) != 14) {
            return false;
        }
        
        // Verificar se todos os dígitos são iguais
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        
        // Validação do primeiro dígito verificador
        $soma = 0;
        $multiplicador = 5;
        
        for ($i = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $multiplicador;
            $multiplicador = ($multiplicador == 2) ? 9 : $multiplicador - 1;
        }
        
        $resto = $soma % 11;
        $dv1 = ($resto < 2) ? 0 : 11 - $resto;
        
        // Validação do segundo dígito verificador
        $soma = 0;
        $multiplicador = 6;
        
        for ($i = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $multiplicador;
            $multiplicador = ($multiplicador == 2) ? 9 : $multiplicador - 1;
        }
        
        $resto = $soma % 11;
        $dv2 = ($resto < 2) ? 0 : 11 - $resto;
        
        // Verificar se os dígitos verificadores são válidos
        return ($cnpj[12] == $dv1 && $cnpj[13] == $dv2);
        
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