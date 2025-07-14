<?php
// controllers/StoreController.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/AuthController.php';
require_once dirname(__DIR__) . '/utils/Validator.php';

/**
 * Controlador de Lojas
 * Gerencia operações relacionadas a lojas parceiras
 */
class StoreController {
    
    /**
    * Aprova uma loja pendente E ativa o usuário associado
    */
    public static function approveStore($storeId) {
        try {
            if (!AuthController::isAdmin()) {
                return ['status' => false, 'message' => 'Acesso restrito a administradores.'];
            }
            
            $db = Database::getConnection();
            
            $stmt = $db->prepare("SELECT * FROM lojas WHERE id = ? AND status = ?");
            $stmt->execute([$storeId, STORE_PENDING]);
            $store = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$store) {
                return ['status' => false, 'message' => 'Loja não encontrada ou não está pendente.'];
            }
            
            $db->beginTransaction();
            
            $updateStoreStmt = $db->prepare("UPDATE lojas SET status = ?, data_aprovacao = NOW() WHERE id = ?");
            $storeResult = $updateStoreStmt->execute([STORE_APPROVED, $storeId]);
            
            if (!$storeResult) {
                $db->rollBack();
                return ['status' => false, 'message' => 'Erro ao aprovar loja.'];
            }
            
            if (!empty($store['usuario_id'])) {
                $updateUserStmt = $db->prepare("UPDATE usuarios SET status = ? WHERE id = ?");
                $userResult = $updateUserStmt->execute([USER_ACTIVE, $store['usuario_id']]);
                
                if (!$userResult) {
                    $db->rollBack();
                    return ['status' => false, 'message' => 'Erro ao ativar usuário da loja aprovada.'];
                }
            } else {
                $db->rollBack();
                return ['status' => false, 'message' => 'Loja não possui usuário associado para ativar.'];
            }
            
            $db->commit();
            
            if (!empty($store['email']) && class_exists('Email')) {
                $subject = 'Loja Aprovada - Klube Cash';
                $message = "
                    <h3>🎉 Parabéns, {$store['nome_fantasia']}!</h3>
                    <p>Sua loja foi <strong>aprovada</strong> no sistema Klube Cash!</p>
                    
                    <h4>✅ O que aconteceu agora:</h4>
                    <ul>
                        <li>Sua loja está <strong>ativa</strong> no sistema</li>
                        <li>Sua conta de usuário foi <strong>ativada</strong></li>
                        <li>Você já pode fazer login no sistema</li>
                        <li>Sua loja será exibida para os clientes</li>
                    </ul>
                    
                    <h4>🚀 Próximos passos:</h4>
                    <ul>
                        <li>Acesse o sistema com seu email e senha: <a href='" . LOGIN_URL . "'>Fazer Login</a></li>
                        <li>Configure seu painel de controle</li>
                        <li>Comece a registrar vendas e oferecer cashback</li>
                    </ul>
                    
                    <p><strong>Dados de acesso:</strong></p>
                    <ul>
                        <li><strong>Email:</strong> {$store['email']}</li>
                        <li><strong>Senha:</strong> A mesma que você cadastrou</li>
                    </ul>
                    
                    <p>Agora seus clientes podem começar a receber cashback em suas compras!</p>
                    <p>Atenciosamente,<br>Equipe Klube Cash</p>
                ";
                Email::send($store['email'], $subject, $message, $store['nome_fantasia']);
            }
            
            return [
                'status' => true, 
                'message' => 'Loja aprovada e usuário ativado com sucesso!',
                'data' => [
                    'store_id' => $storeId,
                    'user_id' => $store['usuario_id'],
                    'store_status' => STORE_APPROVED,
                    'user_status' => USER_ACTIVE
                ]
            ];
            
        } catch (PDOException $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            
            error_log('Erro ao aprovar loja: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao aprovar loja. Por favor, tente novamente.'];
        }
    }
    
    /**
    * Rejeita uma loja pendente e mantém o usuário inativo
    */
    public static function rejectStore($storeId, $observacao = '') {
        try {
            if (!AuthController::isAdmin()) {
                return ['status' => false, 'message' => 'Acesso restrito a administradores.'];
            }
            
            $db = Database::getConnection();
            
            $stmt = $db->prepare("SELECT * FROM lojas WHERE id = ? AND status = ?");
            $stmt->execute([$storeId, STORE_PENDING]);
            $store = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$store) {
                return ['status' => false, 'message' => 'Loja não encontrada ou não está pendente.'];
            }
            
            $db->beginTransaction();
            
            $updateStoreStmt = $db->prepare("UPDATE lojas SET status = ?, observacao = ? WHERE id = ?");
            $storeResult = $updateStoreStmt->execute([STORE_REJECTED, $observacao, $storeId]);
            
            if (!$storeResult) {
                $db->rollBack();
                return ['status' => false, 'message' => 'Erro ao rejeitar loja.'];
            }
            
            if (!empty($store['usuario_id'])) {
                $updateUserStmt = $db->prepare("UPDATE usuarios SET status = ? WHERE id = ?");
                $userResult = $updateUserStmt->execute([USER_INACTIVE, $store['usuario_id']]);
                
                if (!$userResult) {
                    $db->rollBack();
                    return ['status' => false, 'message' => 'Erro ao atualizar status do usuário.'];
                }
            }
            
            $db->commit();
            
            if (!empty($store['email']) && class_exists('Email')) {
                $subject = 'Solicitação de Loja Rejeitada - Klube Cash';
                $message = "
                    <h3>Prezado(a), {$store['nome_fantasia']}!</h3>
                    <p>Infelizmente, sua solicitação para se tornar uma loja parceira no Klube Cash foi <strong>rejeitada</strong>.</p>
                ";
                
                if (!empty($observacao)) {
                    $message .= "<p><strong>Motivo da rejeição:</strong><br>" . nl2br(htmlspecialchars($observacao)) . "</p>";
                }
                
                $message .= "
                    <h4>📧 Entre em contato conosco:</h4>
                    <p>Se você tem dúvidas sobre esta decisão ou gostaria de mais informações para uma nova solicitação, entre em contato com nosso suporte:</p>
                    <ul>
                        <li><strong>Email:</strong> " . ADMIN_EMAIL . "</li>
                        <li><strong>Assunto:</strong> Dúvida sobre Rejeição de Loja - {$store['nome_fantasia']}</li>
                    </ul>
                    
                    <p>Agradecemos seu interesse em fazer parte do Klube Cash.</p>
                    <p>Atenciosamente,<br>Equipe Klube Cash</p>
                ";
                Email::send($store['email'], $subject, $message, $store['nome_fantasia']);
            }
            
            return [
                'status' => true, 
                'message' => 'Loja rejeitada com sucesso.',
                'data' => [
                    'store_id' => $storeId,
                    'user_id' => $store['usuario_id'],
                    'reason' => $observacao
                ]
            ];
            
        } catch (PDOException $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            
            error_log('Erro ao rejeitar loja: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao rejeitar loja. Por favor, tente novamente.'];
        }
    }
    
    /**
     * Cadastra uma nova loja com senha
     */
    public static function registerStore($data) {
        try {
            $requiredFields = ['nome_fantasia', 'razao_social', 'cnpj', 'email', 'telefone', 'senha'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return ['status' => false, 'message' => 'Preencha todos os campos obrigatórios.'];
                }
            }
            
            if (!isset($data['confirma_senha']) || $data['senha'] !== $data['confirma_senha']) {
                return ['status' => false, 'message' => 'As senhas não coincidem.'];
            }
            
            if (strlen($data['senha']) < PASSWORD_MIN_LENGTH) {
                return ['status' => false, 'message' => 'A senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres.'];
            }
            
            $db = Database::getConnection();
            
            $cnpjLimpo = preg_replace('/[^0-9]/', '', $data['cnpj']);
            
            $stmt = $db->prepare("SELECT id FROM lojas WHERE cnpj = ?");
            $stmt->execute([$cnpjLimpo]);
            
            if ($stmt->rowCount() > 0) {
                return ['status' => false, 'message' => 'Já existe uma loja cadastrada com este CNPJ.'];
            }
            
            $userStmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $userStmt->execute([$data['email']]);
            
            if ($userStmt->rowCount() > 0) {
                return ['status' => false, 'message' => 'Já existe um usuário cadastrado com este email.'];
            }
            
            $db->beginTransaction();
            
            $nomeUsuario = $data['nome_fantasia'];
            $emailUsuario = $data['email'];
            $telefoneUsuario = $data['telefone'];
            $senhaHash = password_hash($data['senha'], PASSWORD_DEFAULT);
            $tipoUsuario = USER_TYPE_STORE;
            $statusUsuario = USER_INACTIVE;
            
            $userInsertStmt = $db->prepare("
                INSERT INTO usuarios (nome, email, telefone, senha_hash, tipo, status, data_criacao)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $userResult = $userInsertStmt->execute([
                $nomeUsuario,
                $emailUsuario,
                $telefoneUsuario,
                $senhaHash,
                $tipoUsuario,
                $statusUsuario
            ]);
            
            if (!$userResult) {
                $db->rollBack();
                return ['status' => false, 'message' => 'Erro ao criar usuário da loja.'];
            }
            
            $userId = $db->lastInsertId();
            
            $nomeFantasia = $data['nome_fantasia'];
            $razaoSocial = $data['razao_social'];
            $logoFilename = isset($data['logo']) ? $data['logo'] : null;
            $emailLoja = $data['email'];
            $telefoneLoja = $data['telefone'];
            $categoria = isset($data['categoria']) && !empty($data['categoria']) ? $data['categoria'] : 'Outros';
            $porcentagemCashback = 10.00;
            $descricao = isset($data['descricao']) ? $data['descricao'] : '';
            $website = isset($data['website']) ? $data['website'] : '';
            $statusLoja = STORE_PENDING;
            
            $storeInsertStmt = $db->prepare("
                INSERT INTO lojas (
                    nome_fantasia, razao_social, cnpj, email, telefone,
                    categoria, porcentagem_cashback, descricao, website,
                    logo, usuario_id, status, data_cadastro
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $storeResult = $storeInsertStmt->execute([
                $nomeFantasia,
                $razaoSocial,
                $cnpjLimpo,
                $emailLoja,
                $telefoneLoja,
                $categoria,
                $porcentagemCashback,
                $descricao,
                $website,
                $logoFilename,
                $userId,
                $statusLoja
            ]);
            
            if (!$storeResult) {
                $db->rollBack();
                return ['status' => false, 'message' => 'Erro ao cadastrar loja.'];
            }
            
            $storeId = $db->lastInsertId();
            
            if (isset($data['endereco']) && is_array($data['endereco'])) {
                $endereco = $data['endereco'];
                
                $cep = isset($endereco['cep']) ? $endereco['cep'] : '';
                $logradouro = isset($endereco['logradouro']) ? $endereco['logradouro'] : '';
                $numero = isset($endereco['numero']) ? $endereco['numero'] : '';
                $complemento = isset($endereco['complemento']) ? $endereco['complemento'] : '';
                $bairro = isset($endereco['bairro']) ? $endereco['bairro'] : '';
                $cidade = isset($endereco['cidade']) ? $endereco['cidade'] : '';
                $estado = isset($endereco['estado']) ? $endereco['estado'] : '';
                
                $enderecoStmt = $db->prepare("
                    INSERT INTO lojas_endereco (
                        loja_id, cep, logradouro, numero, complemento, bairro, cidade, estado
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $enderecoResult = $enderecoStmt->execute([
                    $storeId,
                    $cep,
                    $logradouro,
                    $numero,
                    $complemento,
                    $bairro,
                    $cidade,
                    $estado
                ]);
                
                if (!$enderecoResult) {
                    $db->rollBack();
                    return ['status' => false, 'message' => 'Erro ao cadastrar endereço da loja.'];
                }
            }
            
            $db->commit();
            
            if (!empty($data['email']) && class_exists('Email')) {
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
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            
            error_log('Erro ao cadastrar loja: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao processar cadastro. Tente novamente.'];
            
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            
            error_log('Erro geral ao cadastrar loja: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro inesperado no sistema. Tente novamente.'];
        }
    }
    
    /**
     * Valida CNPJ
     */
    public static function validaCNPJ($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        if (strlen($cnpj) != 14) {
            return false;
        }
        
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        
        $soma = 0;
        $multiplicador = 5;
        
        for ($i = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $multiplicador;
            $multiplicador = ($multiplicador == 2) ? 9 : $multiplicador - 1;
        }
        
        $resto = $soma % 11;
        $dv1 = ($resto < 2) ? 0 : 11 - $resto;
        
        $soma = 0;
        $multiplicador = 6;
        
        for ($i = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $multiplicador;
            $multiplicador = ($multiplicador == 2) ? 9 : $multiplicador - 1;
        }
        
        $resto = $soma % 11;
        $dv2 = ($resto < 2) ? 0 : 11 - $resto;
        
        return ($cnpj[12] == $dv1 && $cnpj[13] == $dv2);
    }
    
    /**
     * Obtém lista de lojas
     */
    public static function getStores($filters = [], $page = 1) {
        try {
            $db = Database::getConnection();
            
            $query = "SELECT * FROM lojas WHERE 1=1";
            $params = [];
            
            if (!empty($filters)) {
                if (isset($filters['status']) && !empty($filters['status'])) {
                    $query .= " AND status = ?";
                    $params[] = $filters['status'];
                }
                
                if (isset($filters['categoria']) && !empty($filters['categoria'])) {
                    $query .= " AND categoria = ?";
                    $params[] = $filters['categoria'];
                }
                
                if (isset($filters['busca']) && !empty($filters['busca'])) {
                    $query .= " AND (nome_fantasia LIKE ? OR razao_social LIKE ? OR cnpj LIKE ?)";
                    $searchTerm = '%' . $filters['busca'] . '%';
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }
            }
            
            if (!AuthController::isAdmin()) {
                $query .= " AND status = ?";
                $params[] = STORE_APPROVED;
            }
            
            $orderBy = isset($filters['order_by']) ? $filters['order_by'] : 'nome_fantasia';
            $orderDir = isset($filters['order_dir']) && strtolower($filters['order_dir']) == 'desc' ? 'DESC' : 'ASC';
            $query .= " ORDER BY $orderBy $orderDir";
            
            $countQuery = str_replace('SELECT *', 'SELECT COUNT(*) as total', $query);
            $countStmt = $db->prepare($countQuery);
            $countStmt->execute($params);
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $perPage = defined('ITEMS_PER_PAGE') ? ITEMS_PER_PAGE : 10;
            $offset = ($page - 1) * $perPage;
            $query .= " LIMIT $offset, $perPage";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $categoriesStmt = $db->query("SELECT DISTINCT categoria FROM lojas WHERE categoria IS NOT NULL ORDER BY categoria");
            $categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
            
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

    /**
     * Lista funcionários usando AuthController
     */
    public static function getEmployees($filters = [], $page = 1) {
        try {
            // Usar AuthController em vez de métodos próprios
            if (!AuthController::hasStoreAccess()) {
                return ['status' => false, 'message' => 'Acesso negado.'];
            }
            
            $storeId = AuthController::getStoreId();
            if (!$storeId) {
                return ['status' => false, 'message' => 'Loja não encontrada.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se loja existe na tabela lojas e criar se necessário
            $checkLoja = $db->prepare("SELECT id FROM lojas WHERE id = ?");
            $checkLoja->execute([$storeId]);
            
            if ($checkLoja->rowCount() === 0) {
                // Criar loja se não existir (para resolver constraint)
                $lojista = $db->prepare("SELECT * FROM usuarios WHERE id = ? AND tipo = 'loja'");
                $lojista->execute([$storeId]);
                $lojistaData = $lojista->fetch();
                
                if ($lojistaData) {
                    $createLoja = $db->prepare("
                        INSERT INTO lojas (
                            usuario_id, nome_fantasia, razao_social, cnpj, 
                            email, telefone, porcentagem_cashback, status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'aprovado')
                    ");
                    
                    $createLoja->execute([
                        $storeId,
                        $lojistaData['nome'],
                        $lojistaData['nome'],
                        '00000000000191',
                        $lojistaData['email'],
                        $lojistaData['telefone'] ?? '11999999999',
                        10.00
                    ]);
                    
                    $storeId = $db->lastInsertId();
                }
            }
            
            $whereConditions = ["u.loja_vinculada_id = ? AND u.tipo = 'funcionario'"];
            $params = [$storeId];
            
            if (!empty($filters['subtipo']) && $filters['subtipo'] !== 'todos') {
                $whereConditions[] = "u.subtipo_funcionario = ?";
                $params[] = $filters['subtipo'];
            }
            
            if (!empty($filters['status']) && $filters['status'] !== 'todos') {
                $whereConditions[] = "u.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['busca'])) {
                $whereConditions[] = "(u.nome LIKE ? OR u.email LIKE ?)";
                $searchTerm = '%' . $filters['busca'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            $query = "
                SELECT 
                    u.id,
                    u.nome,
                    u.email,
                    u.telefone,
                    u.subtipo_funcionario,
                    u.status,
                    u.data_criacao,
                    u.ultimo_login
                FROM usuarios u
                $whereClause
                ORDER BY u.data_criacao DESC
            ";
            
            $countQuery = "SELECT COUNT(*) as total FROM usuarios u $whereClause";
            $countStmt = $db->prepare($countQuery);
            $countStmt->execute($params);
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $perPage = defined('ITEMS_PER_PAGE') ? ITEMS_PER_PAGE : 10;
            $page = max(1, (int)$page);
            $offset = ($page - 1) * $perPage;
            $query .= " LIMIT $offset, $perPage";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $statsQuery = "
                SELECT 
                    COUNT(*) as total_funcionarios,
                    SUM(CASE WHEN subtipo_funcionario = 'financeiro' THEN 1 ELSE 0 END) as total_financeiro,
                    SUM(CASE WHEN subtipo_funcionario = 'gerente' THEN 1 ELSE 0 END) as total_gerente,
                    SUM(CASE WHEN subtipo_funcionario = 'vendedor' THEN 1 ELSE 0 END) as total_vendedor,
                    SUM(CASE WHEN status = 'ativo' THEN 1 ELSE 0 END) as total_ativos,
                    SUM(CASE WHEN status = 'inativo' THEN 1 ELSE 0 END) as total_inativos
                FROM usuarios
                WHERE loja_vinculada_id = ? AND tipo = 'funcionario'
            ";
            
            $statsStmt = $db->prepare($statsQuery);
            $statsStmt->execute([$storeId]);
            $statistics = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            $totalPages = ceil($totalCount / $perPage);
            
            return [
                'status' => true,
                'data' => [
                    'funcionarios' => $employees,
                    'estatisticas' => $statistics,
                    'paginacao' => [
                        'total' => $totalCount,
                        'por_pagina' => $perPage,
                        'pagina_atual' => $page,
                        'total_paginas' => $totalPages
                    ]
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao listar funcionários: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao carregar funcionários.'];
        }
    }
    
    /**
     * Cria funcionário usando AuthController
     */
    public static function createEmployee($data) {
        try {
            if (!AuthController::hasStoreAccess()) {
                return ['status' => false, 'message' => 'Acesso negado.'];
            }
            
            if (!AuthController::canManageEmployees()) {
                return ['status' => false, 'message' => 'Apenas lojistas e gerentes podem criar funcionários.'];
            }
            
            $storeId = AuthController::getStoreId();
            if (!$storeId) {
                return ['status' => false, 'message' => 'Loja não encontrada.'];
            }
            
            $errors = [];
            
            if (empty($data['nome']) || strlen(trim($data['nome'])) < 3) {
                $errors[] = 'Nome deve ter pelo menos 3 caracteres.';
            }
            
            if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'E-mail inválido.';
            }
            
            if (empty($data['subtipo_funcionario']) || !in_array($data['subtipo_funcionario'], [
                EMPLOYEE_TYPE_MANAGER, 
                EMPLOYEE_TYPE_FINANCIAL, 
                EMPLOYEE_TYPE_SALESPERSON
            ])) {
                $errors[] = 'Tipo de funcionário inválido.';
            }
            
            if (empty($data['senha']) || strlen($data['senha']) < PASSWORD_MIN_LENGTH) {
                $errors[] = 'Senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres.';
            }
            
            if (!empty($errors)) {
                return ['status' => false, 'message' => implode(' ', $errors)];
            }
            
            $db = Database::getConnection();
            
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$data['email']]);
            
            if ($stmt->rowCount() > 0) {
                return ['status' => false, 'message' => 'Este e-mail já está cadastrado.'];
            }
            
            // Verificar/criar loja se necessário
            $checkLoja = $db->prepare("SELECT id FROM lojas WHERE id = ?");
            $checkLoja->execute([$storeId]);
            $lojaExists = $checkLoja->rowCount() > 0;
            
            $finalStoreId = $storeId;
            
            if (!$lojaExists) {
                $lojista = $db->prepare("SELECT * FROM usuarios WHERE id = ? AND tipo = 'loja'");
                $lojista->execute([$storeId]);
                $lojistaData = $lojista->fetch();
                
                if ($lojistaData) {
                    $createLoja = $db->prepare("
                        INSERT INTO lojas (
                            usuario_id, nome_fantasia, razao_social, cnpj, 
                            email, telefone, porcentagem_cashback, status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'aprovado')
                    ");
                    
                    $createLoja->execute([
                        $storeId,
                        $lojistaData['nome'],
                        $lojistaData['nome'],
                        '00000000000191',
                        $lojistaData['email'],
                        $lojistaData['telefone'] ?? '11999999999',
                        10.00
                    ]);
                    
                    $finalStoreId = $db->lastInsertId();
                }
            }
            
            $senhaHash = password_hash($data['senha'], PASSWORD_DEFAULT);
            
            $insertStmt = $db->prepare("
                INSERT INTO usuarios (
                    nome, email, telefone, senha_hash, tipo, 
                    subtipo_funcionario, loja_vinculada_id, status, data_criacao
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $success = $insertStmt->execute([
                trim($data['nome']),
                trim($data['email']),
                trim($data['telefone'] ?? ''),
                $senhaHash,
                USER_TYPE_EMPLOYEE,
                $data['subtipo_funcionario'],
                $finalStoreId,
                USER_ACTIVE
            ]);
            
            if ($success) {
                $funcionarioId = $db->lastInsertId();
                error_log("Funcionário criado - ID: {$funcionarioId}, Loja: {$finalStoreId}, Criado por: {$_SESSION['user_id']}");
                return ['status' => true, 'message' => 'Funcionário criado com sucesso!', 'id' => $funcionarioId];
            } else {
                return ['status' => false, 'message' => 'Erro ao criar funcionário.'];
            }
            
        } catch (Exception $e) {
            error_log('Erro ao criar funcionário: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro interno do servidor.'];
        }
    }
    
    /**
     * Atualiza funcionário
     */
    public static function updateEmployee($employeeId, $data) {
        try {
            if (!AuthController::hasStoreAccess()) {
                return ['status' => false, 'message' => 'Acesso negado.'];
            }
            
            if (!AuthController::canManageEmployees()) {
                return ['status' => false, 'message' => 'Apenas lojistas e gerentes podem editar funcionários.'];
            }
            
            $storeId = AuthController::getStoreId();
            if (!$storeId) {
                return ['status' => false, 'message' => 'Loja não encontrada.'];
            }
            
            $db = Database::getConnection();
            
            $checkStmt = $db->prepare("
                SELECT id FROM usuarios 
                WHERE id = ? AND loja_vinculada_id = ? AND tipo = 'funcionario'
            ");
            $checkStmt->execute([$employeeId, $storeId]);
            
            if ($checkStmt->rowCount() === 0) {
                return ['status' => false, 'message' => 'Funcionário não encontrado.'];
            }
            
            $updateFields = [];
            $params = [];
            
            if (!empty($data['nome'])) {
                $updateFields[] = "nome = ?";
                $params[] = $data['nome'];
            }
            
            if (!empty($data['email'])) {
                $emailCheckStmt = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
                $emailCheckStmt->execute([$data['email'], $employeeId]);
                
                if ($emailCheckStmt->rowCount() > 0) {
                    return ['status' => false, 'message' => 'Este e-mail já está em uso.'];
                }
                
                $updateFields[] = "email = ?";
                $params[] = $data['email'];
            }
            
            if (isset($data['telefone'])) {
                $updateFields[] = "telefone = ?";
                $params[] = $data['telefone'];
            }
            
            if (!empty($data['subtipo_funcionario'])) {
                $updateFields[] = "subtipo_funcionario = ?";
                $params[] = $data['subtipo_funcionario'];
            }
            
            if (!empty($data['status'])) {
                $updateFields[] = "status = ?";
                $params[] = $data['status'];
            }
            
            if (!empty($data['senha'])) {
                $updateFields[] = "senha_hash = ?";
                $params[] = password_hash($data['senha'], PASSWORD_DEFAULT);
            }
            
            if (empty($updateFields)) {
                return ['status' => false, 'message' => 'Nenhum dado para atualizar.'];
            }
            
            $params[] = $employeeId;
            $updateQuery = "UPDATE usuarios SET " . implode(', ', $updateFields) . " WHERE id = ?";
            
            $updateStmt = $db->prepare($updateQuery);
            $success = $updateStmt->execute($params);
            
            if ($success) {
                return ['status' => true, 'message' => 'Funcionário atualizado com sucesso!'];
            } else {
                return ['status' => false, 'message' => 'Erro ao atualizar funcionário.'];
            }
            
        } catch (PDOException $e) {
            error_log('Erro ao atualizar funcionário: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro interno do servidor.'];
        }
    }
    
    /**
     * Remove/desativa funcionário
     */
    public static function deleteEmployee($employeeId) {
        try {
            if (!AuthController::hasStoreAccess()) {
                return ['status' => false, 'message' => 'Acesso negado.'];
            }
            
            if (!AuthController::canManageEmployees()) {
                return ['status' => false, 'message' => 'Apenas lojistas e gerentes podem desativar funcionários.'];
            }
            
            $storeId = AuthController::getStoreId();
            if (!$storeId) {
                return ['status' => false, 'message' => 'Loja não encontrada.'];
            }
            
            $db = Database::getConnection();
            
            $checkStmt = $db->prepare("
                SELECT id FROM usuarios 
                WHERE id = ? AND loja_vinculada_id = ? AND tipo = 'funcionario'
            ");
            $checkStmt->execute([$employeeId, $storeId]);
            
            if ($checkStmt->rowCount() === 0) {
                return ['status' => false, 'message' => 'Funcionário não encontrado.'];
            }
            
            $updateStmt = $db->prepare("UPDATE usuarios SET status = 'inativo' WHERE id = ?");
            $success = $updateStmt->execute([$employeeId]);
            
            if ($success) {
                return ['status' => true, 'message' => 'Funcionário desativado com sucesso!'];
            } else {
                return ['status' => false, 'message' => 'Erro ao desativar funcionário.'];
            }
            
        } catch (PDOException $e) {
            error_log('Erro ao desativar funcionário: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro interno do servidor.'];
        }
    }
}

// Processar requisições diretas
if (basename($_SERVER['PHP_SELF']) === 'StoreController.php') {
    if (!AuthController::isAuthenticated()) {
        header('Location: ' . LOGIN_URL . '?error=' . urlencode('Você precisa fazer login para acessar esta página.'));
        exit;
    }
    
    $action = $_REQUEST['action'] ?? '';
    
    switch ($action) {
        case 'approve':
            if (!AuthController::isAdmin()) {
                echo json_encode(['status' => false, 'message' => 'Acesso restrito a administradores.']);
                exit;
            }
            
            $storeId = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $result = StoreController::approveStore($storeId);
            echo json_encode($result);
            break;
            
        case 'reject':
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
            if (AuthController::isAdmin()) {
                header('Location: ' . ADMIN_STORES_URL);
            } else {
                header('Location: ' . CLIENT_STORES_URL);
            }
            exit;
    }
}
?>