<?php
date_default_timezone_set('America/Sao_Paulo');
// controllers/AuthController.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../utils/Email.php';
require_once __DIR__ . '/../utils/Validator.php';

/**
 * Controlador de Autenticação
 * Gerencia login, registro, recuperação de senha e logout
 */
class AuthController {

    public static function requireStoreAccess() {
        if (!self::hasStoreAccess()) {
            header("Location: " . LOGIN_URL . "?error=access_denied");
            exit;
        }
    }
    // Adicionar no AuthController.php
    public static function setupEmployeeSession($employeeData) {
        $_SESSION['user_id'] = $employeeData['id'];
        $_SESSION['user_type'] = USER_TYPE_EMPLOYEE;
        $_SESSION['user_name'] = $employeeData['nome'];
        $_SESSION['user_email'] = $employeeData['email'];
        $_SESSION['employee_subtype'] = $employeeData['subtipo_funcionario'];
        $_SESSION['store_id'] = $employeeData['loja_vinculada_id'];
        
        // Definir permissões baseadas no subtipo
        $permissions = EMPLOYEE_PERMISSIONS[$employeeData['subtipo_funcionario']] ?? [];
        $_SESSION['employee_permissions'] = $permissions;
    }
    /**
     * Método de login atualizado e corrigido para funcionários
     * Esta versão resolve o problema de escopo de variável que impedia
     * as variáveis de sessão específicas de funcionários de serem definidas
     */
    public static function login($email, $senha, $remember = false) {
        try {
            $db = Database::getConnection();
            
            // Primeira etapa: Buscar o usuário no banco de dados
            // Esta consulta inclui todos os campos necessários para funcionários
            $stmt = $db->prepare("
                SELECT id, nome, email, senha_hash, tipo, status, loja_vinculada_id, subtipo_funcionario
                FROM usuarios 
                WHERE email = ? AND tipo IN ('cliente', 'admin', 'loja', 'funcionario')
            ");
            $stmt->execute([$email]);
            
            // Verificar se o usuário existe
            if ($stmt->rowCount() === 0) {
                return ['status' => false, 'message' => 'E-mail ou senha incorretos.'];
            }
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Segunda etapa: Validar a senha fornecida
            // O password_verify compara a senha em texto plano com o hash armazenado
            if (!password_verify($senha, $user['senha_hash'])) {
                return ['status' => false, 'message' => 'E-mail ou senha incorretos.'];
            }
            
            // Terceira etapa: Verificar se a conta está ativa
            if ($user['status'] !== 'ativo') {
                return ['status' => false, 'message' => 'Sua conta está inativa. Entre em contato com o suporte.'];
            }
            
            // CORREÇÃO PRINCIPAL: Inicializar a variável $storeData
            // Isso garante que a variável sempre exista, evitando erros de "variável não definida"
            $storeData = null;
            
            // Quarta etapa: Validações específicas para funcionários
            // Para funcionários, precisamos verificar se a loja vinculada está ativa
            if ($user['tipo'] === 'funcionario') {
                // Buscar dados da loja vinculada
                $storeStmt = $db->prepare("
                    SELECT status, nome_fantasia 
                    FROM lojas 
                    WHERE id = ? AND status = 'aprovado'
                ");
                $storeStmt->execute([$user['loja_vinculada_id']]);
                
                // Se a loja não estiver aprovada, impedir o login
                if ($storeStmt->rowCount() === 0) {
                    return ['status' => false, 'message' => 'A loja vinculada não está ativa.'];
                }
                
                // IMPORTANTE: Definir $storeData aqui garante que ela esteja disponível depois
                $storeData = $storeStmt->fetch(PDO::FETCH_ASSOC);
            }

            // Quinta etapa: Configurar a sessão PHP
            // Garantir que a sessão esteja iniciada antes de definir variáveis
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Definir variáveis básicas de sessão (para todos os tipos de usuário)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = $user['tipo'];
            $_SESSION['last_activity'] = time();

            // Sexta etapa: Configurar dados específicos para funcionários
            // CORREÇÃO: Agora verificamos se $storeData não é null para evitar erros
            if ($user['tipo'] === 'funcionario' && $storeData !== null) {
                
                // Definir informações básicas do funcionário
                $_SESSION['employee_subtype'] = $user['subtipo_funcionario'];
                $_SESSION['store_id'] = $user['loja_vinculada_id'];
                $_SESSION['store_name'] = $storeData['nome_fantasia'];
                
                // Definir permissões baseadas no subtipo do funcionário
                // Este sistema de permissões permite controle granular de acesso futuro
                switch($user['subtipo_funcionario']) {
                    case 'gerente':
                        // Gerentes têm acesso completo às operações da loja
                        $_SESSION['employee_permissions'] = [
                            'dashboard', 
                            'transacoes', 
                            'funcionarios', 
                            'relatorios'
                        ];
                        break;
                        
                    case 'financeiro':
                        // Funcionários financeiros focam em aspectos monetários
                        $_SESSION['employee_permissions'] = [
                            'dashboard', 
                            'comissoes', 
                            'pagamentos', 
                            'relatorios'
                        ];
                        break;
                        
                    case 'vendedor':
                        // Vendedores têm acesso básico para registrar vendas
                        $_SESSION['employee_permissions'] = [
                            'dashboard', 
                            'transacoes'
                        ];
                        break;
                        
                    default:
                        // Fallback para subtipos não reconhecidos
                        $_SESSION['employee_permissions'] = ['dashboard'];
                }
            }
            
            // Sétima etapa: Registrar o login no banco de dados
            // Atualizar o timestamp do último login para fins de auditoria
            $updateStmt = $db->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            // Oitava etapa: Preparar dados de retorno
            // Estas informações podem ser usadas pela aplicação após o login
            $result = [
                'status' => true,
                'message' => 'Login realizado com sucesso!',
                'user_data' => [
                    'id' => $user['id'],
                    'nome' => $user['nome'],
                    'email' => $user['email'],
                    'tipo' => $user['tipo']
                ]
            ];
            
            // Adicionar informações específicas de funcionário ao resultado
            // Isso permite que a aplicação saiba detalhes sobre o funcionário logado
            if ($user['tipo'] === 'funcionario' && $storeData !== null) {
                // Buscar o nome de exibição do subtipo usando as constantes definidas
                $subtypeDisplay = EMPLOYEE_SUBTYPES[$user['subtipo_funcionario']] ?? 'Não definido';
                
                $result['employee_info'] = [
                    'subtipo' => $user['subtipo_funcionario'],
                    'subtipo_display' => $subtypeDisplay,
                    'loja_id' => $user['loja_vinculada_id'],
                    'loja_nome' => $storeData['nome_fantasia']
                ];
            }
            
            // Retornar resultado de sucesso com todas as informações
            return $result;
            
        } catch (PDOException $e) {
            // Tratamento de erro: registrar o erro e retornar mensagem genérica
            // Isso evita exposição de detalhes técnicos sensíveis ao usuário final
            error_log('Erro no login: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro interno do sistema. Tente novamente.'];
        }
    }

    /**
     * Verifica se o usuário logado tem acesso à área da loja
     */
    public static function hasStoreAccess() {
        return self::isStore() || self::isEmployee();
    }

    /**
     * Verifica se é lojista (não funcionário)
     */
    public static function isStoreOwner() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'loja';
    }

    /**
     * Verifica se é funcionário
     */
    public static function isEmployee() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === USER_TYPE_EMPLOYEE;
    }
    public static function canManageEmployees() {
        if (self::isStore()) {
            return true;
        }
        
        if (self::isEmployee()) {
            return isset($_SESSION['employee_subtype']) && $_SESSION['employee_subtype'] === EMPLOYEE_TYPE_MANAGER;
        }
        
        return false;
    }

    public static function getStoreId() {
        if (self::isStore()) {
            // Para lojistas, buscar ID na tabela lojas baseado no usuario_id
            try {
                $db = Database::getConnection();
                $stmt = $db->prepare("SELECT id FROM lojas WHERE usuario_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $loja = $stmt->fetch();
                
                if ($loja) {
                    return $loja['id'];
                } else {
                    // Se não existe, criar registro na tabela lojas
                    $userStmt = $db->prepare("SELECT * FROM usuarios WHERE id = ? AND tipo = 'loja'");
                    $userStmt->execute([$_SESSION['user_id']]);
                    $userData = $userStmt->fetch();
                    
                    if ($userData) {
                        $createStmt = $db->prepare("
                            INSERT INTO lojas (usuario_id, nome_fantasia, razao_social, cnpj, email, telefone, porcentagem_cashback, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'aprovado')
                        ");
                        $createStmt->execute([
                            $_SESSION['user_id'],
                            $userData['nome'],
                            $userData['nome'],
                            '00000000000191',
                            $userData['email'],
                            $userData['telefone'] ?? '11999999999',
                            10.00
                        ]);
                        return $db->lastInsertId();
                    }
                }
            } catch (Exception $e) {
                error_log('Erro getStoreId: ' . $e->getMessage());
            }
            
            return $_SESSION['user_id']; // Fallback
        } elseif (self::isEmployee()) {
            return $_SESSION['store_id'] ?? null;
        }
        return null;
    }

    public static function getStoreData() {
        $storeId = self::getStoreId();
        if (!$storeId) return null;

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = :id AND tipo = :tipo");
            $stmt->bindParam(':id', $storeId);
            $tipo = USER_TYPE_STORE;
            $stmt->bindParam(':tipo', $tipo);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    /**
     * Obtém o subtipo do funcionário atual
     * 
     * @return string|null Subtipo do funcionário ou null se não for funcionário
     */
    public static function getEmployeeSubtype() {
        if (!self::isEmployee()) {
            return null;
        }
        
        return $_SESSION['employee_subtype'] ?? null;
    }


    /**
     * Obtém o nome de exibição do subtipo do funcionário
     * 
     * @return string|null Nome formatado do subtipo
     */
    public static function getEmployeeSubtypeDisplay() {
        $subtype = self::getEmployeeSubtype();
        if (!$subtype) {
            return null;
        }
        
        return EMPLOYEE_SUBTYPES[$subtype] ?? 'Não definido';
    }
    // Adicione este código no AuthController.php após o login bem-sucedido
    /**
    * Processa registro via Google OAuth (similar ao login, mas com validações específicas)
    */
    public static function googleRegister($code, $state) {
        try {
            // Verificar o state para segurança
            if (!GoogleAuth::verifyState($state)) {
                error_log('Google OAuth Register: State inválido recebido');
                return ['status' => false, 'message' => 'Estado de OAuth inválido. Tente novamente.'];
            }
            
            // Trocar código por token de acesso
            $tokenData = GoogleAuth::getAccessToken($code);
            
            if (!$tokenData || !isset($tokenData['access_token'])) {
                error_log('Google OAuth Register: Erro ao obter token - ' . json_encode($tokenData));
                return ['status' => false, 'message' => 'Erro ao obter token do Google.'];
            }
            
            // Buscar informações do usuário
            $userInfo = GoogleAuth::getUserInfo($tokenData['access_token']);
            
            if (!$userInfo || !isset($userInfo['email'])) {
                error_log('Google OAuth Register: Erro ao obter dados do usuário - ' . json_encode($userInfo));
                return ['status' => false, 'message' => 'Erro ao obter dados do usuário do Google.'];
            }
            
            // Log para debug
            error_log('Google OAuth Register: Dados do usuário recebidos - ' . json_encode($userInfo));
            
            $db = Database::getConnection();
            
            // Verificar se usuário já existe (REGISTRO não deve permitir usuário existente)
            $stmt = $db->prepare("
                SELECT * FROM usuarios 
                WHERE email = :email OR google_id = :google_id
            ");
            $stmt->bindParam(':email', $userInfo['email']);
            $stmt->bindParam(':google_id', $userInfo['id']);
            $stmt->execute();
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingUser) {
                // Para registro, usuário não deve existir
                error_log('Google OAuth Register: Usuário já existe - ' . $userInfo['email']);
                return [
                    'status' => false, 
                    'message' => 'Uma conta com este email já existe. Faça login em vez de se registrar.',
                    'redirect_to_login' => true
                ];
            }
            
            // Validar dados obrigatórios do Google
            if (empty($userInfo['name'])) {
                return ['status' => false, 'message' => 'Nome não fornecido pelo Google. Tente o registro manual.'];
            }
            
            if (empty($userInfo['email'])) {
                return ['status' => false, 'message' => 'Email não fornecido pelo Google. Tente o registro manual.'];
            }
            
            // Verificar se o email do Google está verificado
            if (!isset($userInfo['verified_email']) || !$userInfo['verified_email']) {
                error_log('Google OAuth Register: Email não verificado no Google para ' . $userInfo['email']);
                // Continuar mesmo assim, mas marcar como não verificado
            }
            
            // Criar novo usuário
            $stmt = $db->prepare("
                INSERT INTO usuarios (
                    nome, email, google_id, avatar_url, telefone,
                    provider, email_verified, tipo, status, data_criacao
                ) VALUES (
                    :nome, :email, :google_id, :avatar_url, :telefone,
                    'google', :email_verified, 'cliente', 'ativo', NOW()
                )
            ");
            
            // Preparar dados
            $nome = trim($userInfo['name']);
            $email = strtolower(trim($userInfo['email']));
            $telefone = ''; // Google não fornece telefone por padrão
            $emailVerified = isset($userInfo['verified_email']) && $userInfo['verified_email'] ? 1 : 0;
            
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':google_id', $userInfo['id']);
            $stmt->bindParam(':avatar_url', $userInfo['picture'] ?? null);
            $stmt->bindParam(':telefone', $telefone);
            $stmt->bindParam(':email_verified', $emailVerified);
            
            if ($stmt->execute()) {
                $userId = $db->lastInsertId();
                
                error_log('Google OAuth Register: Novo usuário registrado - ID: ' . $userId);
                
                // Iniciar sessão automaticamente após registro
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $nome;
                $_SESSION['user_type'] = 'cliente';
                
                // Registrar sessão
                self::registerSession($userId);
                
                // Limpar state da sessão
                unset($_SESSION['google_oauth_state']);
                unset($_SESSION['google_action']);
                
                // Enviar email de boas-vindas
                try {
                    Email::sendWelcome($email, $nome);
                } catch (Exception $e) {
                    error_log('Erro ao enviar email de boas-vindas (registro Google): ' . $e->getMessage());
                    // Não falhar o registro por causa do email
                }
                
                return [
                    'status' => true,
                    'message' => 'Registro realizado com sucesso! Bem-vindo ao Klube Cash!',
                    'user' => [
                        'id' => $userId,
                        'name' => $nome,
                        'type' => 'cliente'
                    ],
                    'is_new_user' => true
                ];
                
            } else {
                error_log('Google OAuth Register: Erro ao criar usuário no banco');
                return ['status' => false, 'message' => 'Erro ao criar conta. Tente novamente.'];
            }
            
        } catch (Exception $e) {
            error_log('Erro no registro Google: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao processar registro com Google. Tente novamente.'];
        }
    }
    /**
    * Verifica se o usuário é funcionário e obtém dados da loja vinculada
    */
    public static function getEmployeeStoreData($userId) {
        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("
                SELECT u.*, l.id as loja_id, l.nome_fantasia as loja_nome
                FROM usuarios u
                INNER JOIN lojas l ON u.loja_vinculada_id = l.id
                WHERE u.id = ? AND u.tipo = 'funcionario' AND u.status = 'ativo'
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log('Erro ao obter dados do funcionário: ' . $e->getMessage());
            return false;
        }
    }
    /**
     * Processa login via Google OAuth
     */
    public static function googleLogin($code, $state) {
        try {
            // Verificar o state para segurança
            if (!GoogleAuth::verifyState($state)) {
                error_log('Google OAuth: State inválido recebido');
                return ['status' => false, 'message' => 'Estado de OAuth inválido. Tente novamente.'];
            }
            
            // Trocar código por token de acesso
            $tokenData = GoogleAuth::getAccessToken($code);
            
            if (!$tokenData || !isset($tokenData['access_token'])) {
                error_log('Google OAuth: Erro ao obter token - ' . json_encode($tokenData));
                return ['status' => false, 'message' => 'Erro ao obter token do Google.'];
            }
            
            // Buscar informações do usuário
            $userInfo = GoogleAuth::getUserInfo($tokenData['access_token']);
            
            if (!$userInfo || !isset($userInfo['email'])) {
                error_log('Google OAuth: Erro ao obter dados do usuário - ' . json_encode($userInfo));
                return ['status' => false, 'message' => 'Erro ao obter dados do usuário do Google.'];
            }
            
            // Log para debug
            error_log('Google OAuth: Dados do usuário recebidos - ' . json_encode($userInfo));
            
            $db = Database::getConnection();
            
            // Verificar se usuário já existe (por email ou google_id)
            $stmt = $db->prepare("
                SELECT * FROM usuarios 
                WHERE email = :email OR google_id = :google_id
            ");
            $stmt->bindParam(':email', $userInfo['email']);
            $stmt->bindParam(':google_id', $userInfo['id']);
            $stmt->execute();
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingUser) {
                // Usuário existe - atualizar informações do Google se necessário
                $updateStmt = $db->prepare("
                    UPDATE usuarios 
                    SET google_id = :google_id, 
                        avatar_url = :avatar_url,
                        provider = 'google',
                        email_verified = 1,
                        ultimo_login = NOW()
                    WHERE id = :user_id
                ");
                $updateStmt->bindParam(':google_id', $userInfo['id']);
                $updateStmt->bindParam(':avatar_url', $userInfo['picture'] ?? null);
                $updateStmt->bindParam(':user_id', $existingUser['id']);
                $updateStmt->execute();
                
                $userId = $existingUser['id'];
                $userName = $existingUser['nome'];
                $userType = $existingUser['tipo'];
                $userStatus = $existingUser['status'];
                
                // Verificar status do usuário
                if ($userStatus !== USER_ACTIVE) {
                    return ['status' => false, 'message' => 'Sua conta está ' . $userStatus . '. Entre em contato com o suporte.'];
                }
                
                error_log('Google OAuth: Usuário existente atualizado - ID: ' . $userId);
                
            } else {
                // Criar novo usuário
                $stmt = $db->prepare("
                    INSERT INTO usuarios (
                        nome, email, google_id, avatar_url, telefone,
                        provider, email_verified, tipo, status, data_criacao
                    ) VALUES (
                        :nome, :email, :google_id, :avatar_url, :telefone,
                        'google', 1, 'cliente', 'ativo', NOW()
                    )
                ");
                
                // Usar o nome do Google ou extrair do email se não disponível
                $nome = $userInfo['name'] ?? explode('@', $userInfo['email'])[0];
                $telefone = ''; // Google não fornece telefone por padrão
                
                $stmt->bindParam(':nome', $nome);
                $stmt->bindParam(':email', $userInfo['email']);
                $stmt->bindParam(':google_id', $userInfo['id']);
                $stmt->bindParam(':avatar_url', $userInfo['picture'] ?? null);
                $stmt->bindParam(':telefone', $telefone);
                
                if ($stmt->execute()) {
                    $userId = $db->lastInsertId();
                    $userName = $nome;
                    $userType = 'cliente';
                    
                    error_log('Google OAuth: Novo usuário criado - ID: ' . $userId);
                    
                    // Enviar email de boas-vindas
                    try {
                        Email::sendWelcome($userInfo['email'], $nome);
                    } catch (Exception $e) {
                        error_log('Erro ao enviar email de boas-vindas: ' . $e->getMessage());
                        // Não falhar o login por causa do email
                    }
                } else {
                    error_log('Google OAuth: Erro ao criar usuário no banco');
                    return ['status' => false, 'message' => 'Erro ao criar usuário.'];
                }
            }
            
            // Iniciar sessão
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $userName;
            $_SESSION['user_type'] = $userType;
            
            // Registrar sessão
            self::registerSession($userId);
            
            // Limpar state da sessão
            unset($_SESSION['google_oauth_state']);
            
            error_log('Google OAuth: Login realizado com sucesso - User ID: ' . $userId);
            
            return [
                'status' => true,
                'message' => 'Login realizado com sucesso!',
                'user' => [
                    'id' => $userId,
                    'name' => $userName,
                    'type' => $userType
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Erro no login Google: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao processar login com Google. Tente novamente.'];
        }
    }
    /**
     * Associa um usuário do tipo loja à sua respectiva loja
     * 
     * @param int $userId ID do usuário
     * @param string $userEmail Email do usuário
     * @return bool Verdadeiro se associado com sucesso
     */
    private static function associateStoreUser($userId, $userEmail) {
        try {
            $db = Database::getConnection();
            
            // Verificar se o usuário já está associado a alguma loja
            $checkStmt = $db->prepare("SELECT id FROM lojas WHERE usuario_id = :usuario_id");
            $checkStmt->bindParam(':usuario_id', $userId);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                return true; // Já está associado
            }
            
            // Encontrar loja com o mesmo email do usuário
            $storeStmt = $db->prepare("SELECT id FROM lojas WHERE email = :email AND usuario_id IS NULL");
            $storeStmt->bindParam(':email', $userEmail);
            $storeStmt->execute();
            
            if ($storeStmt->rowCount() > 0) {
                $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
                
                // Associar usuário à loja
                $updateStmt = $db->prepare("UPDATE lojas SET usuario_id = :usuario_id WHERE id = :loja_id");
                $updateStmt->bindParam(':usuario_id', $userId);
                $updateStmt->bindParam(':loja_id', $store['id']);
                return $updateStmt->execute();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log('Erro ao associar usuário à loja: ' . $e->getMessage());
            return false;
        }
    }
    /**
    * Registra um novo usuário
    * 
    * @param string $nome Nome do usuário
    * @param string $email Email do usuário
    * @param string $telefone Telefone do usuário
    * @param string $senha Senha do usuário
    * @param string $tipo Tipo do usuário
    * @return array Resultado da operação com status e mensagem
    */
    public static function register($nome, $email, $telefone, $senha, $tipo = null) {
        try {
            // Validar dados
            $errors = [];
            
            if (empty($email) || !Validator::validaEmail($email)) {
                $errors[] = 'Email inválido';
            }
            
            if (empty($nome) || !Validator::validaNome($nome)) {
                $errors[] = 'Nome precisa ter pelo menos 3 caracteres';
            }
            
            if (empty($telefone) || !Validator::validaTelefone($telefone)) {
                $errors[] = 'Telefone inválido';
            }
            
            if (empty($senha) || !Validator::validaSenha($senha, PASSWORD_MIN_LENGTH)) {
                $errors[] = 'A senha deve ter no mínimo ' . PASSWORD_MIN_LENGTH . ' caracteres';
            }
            
            if (!empty($errors)) {
                return ['status' => false, 'message' => implode('. ', $errors)];
            }
            
            $db = Database::getConnection();
            
            // Verificar se o email já existe
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return ['status' => false, 'message' => 'Este email já está cadastrado. Por favor, use outro ou faça login.'];
            }
            
            // Se for usuário do tipo loja, verificar se há uma loja aprovada com este email
            $storeId = null;
            if ($tipo === USER_TYPE_STORE) {
                $storeStmt = $db->prepare("
                    SELECT id FROM lojas 
                    WHERE email = :email AND status = :status AND (usuario_id IS NULL OR usuario_id = 0)
                ");
                $storeStmt->bindParam(':email', $email);
                $storeStatus = STORE_APPROVED;
                $storeStmt->bindParam(':status', $storeStatus);
                $storeStmt->execute();
                
                if ($storeStmt->rowCount() > 0) {
                    $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
                    $storeId = $store['id'];
                }
            }
            
            // Hash da senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            // Definir tipo de usuário (cliente é o padrão, mas admin pode alterar)
            $tipoUsuario = $tipo ?? USER_TYPE_CLIENT;
            
            // Iniciar transação
            $db->beginTransaction();
            
            // Inserir novo usuário
            $stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha_hash, tipo, status, data_criacao) 
                                VALUES (:nome, :email, :senha_hash, :tipo, :status, NOW())");
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':senha_hash', $senha_hash);
            $stmt->bindParam(':tipo', $tipoUsuario);
            $status = USER_ACTIVE;
            $stmt->bindParam(':status', $status);
            
            if ($stmt->execute()) {
                $user_id = $db->lastInsertId();
                
                // Se for usuário do tipo loja e encontrou uma loja correspondente, vincular
                if ($tipoUsuario === USER_TYPE_STORE && $storeId) {
                    $linkStmt = $db->prepare("UPDATE lojas SET usuario_id = :usuario_id WHERE id = :loja_id");
                    $linkStmt->bindParam(':usuario_id', $user_id);
                    $linkStmt->bindParam(':loja_id', $storeId);
                    
                    if (!$linkStmt->execute()) {
                        $db->rollBack();
                        return ['status' => false, 'message' => 'Erro ao vincular usuário à loja.'];
                    }
                }
                
                // Commit da transação
                $db->commit();
                
                // Enviar email de boas-vindas
                Email::sendWelcome($email, $nome);
                
                return [
                    'status' => true, 
                    'message' => 'Cadastro realizado com sucesso!' . 
                                ($storeId ? ' Usuário vinculado à loja automaticamente.' : ''), 
                    'user_id' => $user_id,
                    'store_linked' => $storeId ? true : false
                ];
            } else {
                $db->rollBack();
                return ['status' => false, 'message' => 'Erro ao cadastrar. Por favor, tente novamente.'];
            }
        } catch (PDOException $e) {
            // Rollback em caso de erro
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            
            error_log('Erro no registro: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao processar o cadastro. Tente novamente.'];
        }
    }
    
    /**
     * Solicita recuperação de senha
     * 
     * @param string $email Email do usuário
     * @return array Resultado da operação com status e mensagem
     */
    public static function recoverPassword($email) {
        try {
            if (empty($email) || !Validator::validaEmail($email)) {
                return ['status' => false, 'message' => 'Por favor, informe um email válido.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se o email existe
            $stmt = $db->prepare("SELECT id, nome, status FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                // Não informar ao usuário que o email não existe (segurança)
                return ['status' => true, 'message' => 'Se o email estiver cadastrado, enviaremos instruções para recuperar sua senha.'];
            }
            
            if ($user['status'] !== USER_ACTIVE) {
                return ['status' => false, 'message' => 'Sua conta está ' . $user['status'] . '. Entre em contato com o suporte.'];
            }
            
            // Gerar token único
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+24 hours')); // 24 horas ao invés de 2
            
            // Verificar se já existe tabela recuperacao_senha, se não, criar
            self::createRecoveryTableIfNotExists($db);
            
            // Primeiro excluir tokens antigos deste usuário
            $deleteStmt = $db->prepare("DELETE FROM recuperacao_senha WHERE usuario_id = :user_id");
            $deleteStmt->bindParam(':user_id', $user['id']);
            $deleteStmt->execute();
            
            // Inserir novo token
            $insertStmt = $db->prepare("INSERT INTO recuperacao_senha (usuario_id, token, data_expiracao) VALUES (:user_id, :token, :expiry)");
            $insertStmt->bindParam(':user_id', $user['id']);
            $insertStmt->bindParam(':token', $token);
            $insertStmt->bindParam(':expiry', $expiry);
            
            if ($insertStmt->execute()) {
                // Enviar email de recuperação
                if (Email::sendPasswordRecovery($email, $user['nome'], $token)) {
                    return ['status' => true, 'message' => 'Enviamos instruções para recuperar sua senha para o email informado.'];
                } else {
                    return ['status' => false, 'message' => 'Não foi possível enviar o email. Por favor, tente novamente mais tarde.'];
                }
            } else {
                return ['status' => false, 'message' => 'Erro ao gerar token de recuperação. Por favor, tente novamente.'];
            }
        } catch (Exception $e) {
            error_log('Erro na recuperação de senha: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao processar a solicitação. Tente novamente.'];
        }
    }
    
    /**
     * Redefine a senha do usuário através de token
     * 
     * @param string $token Token de recuperação
     * @param string $newPassword Nova senha
     * @return array Resultado da operação com status e mensagem
     */
    public static function resetPassword($token, $newPassword) {
        try {
            if (empty($token) || empty($newPassword)) {
                return ['status' => false, 'message' => 'Dados inválidos para redefinição de senha.'];
            }
            
            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                return ['status' => false, 'message' => 'A senha deve ter no mínimo ' . PASSWORD_MIN_LENGTH . ' caracteres.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se o token é válido
            $stmt = $db->prepare("
                SELECT rs.*, u.nome, u.email 
                FROM recuperacao_senha rs
                JOIN usuarios u ON rs.usuario_id = u.id
                WHERE rs.token = :token 
                AND rs.usado = 0 
                AND rs.data_expiracao > NOW()
            ");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            $tokenInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tokenInfo) {
                return ['status' => false, 'message' => 'Token inválido ou expirado. Por favor, solicite uma nova recuperação de senha.'];
            }
            
            // Atualizar a senha do usuário
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $db->prepare("UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :id");
            $updateStmt->bindParam(':senha_hash', $passwordHash);
            $updateStmt->bindParam(':id', $tokenInfo['usuario_id']);
            
            if ($updateStmt->execute()) {
                // Marcar o token como usado
                $usedStmt = $db->prepare("UPDATE recuperacao_senha SET usado = 1 WHERE id = :id");
                $usedStmt->bindParam(':id', $tokenInfo['id']);
                $usedStmt->execute();
                
                return ['status' => true, 'message' => 'Sua senha foi atualizada com sucesso! Você já pode fazer login.'];
            } else {
                return ['status' => false, 'message' => 'Erro ao atualizar a senha. Por favor, tente novamente.'];
            }
        } catch (PDOException $e) {
            error_log('Erro na redefinição de senha: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao processar a solicitação. Tente novamente.'];
        }
    }
    
    /**
     * Realiza o logout do usuário
     * 
     * @return array Resultado da operação com status e mensagem
     */
    public static function logout() {
        // Iniciar sessão se não estiver iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Limpar variáveis de sessão
        $_SESSION = array();
        
        // Destruir a sessão
        session_destroy();
        
        return ['status' => true, 'message' => 'Logout efetuado com sucesso.'];
    }
    
    /**
     * Registra informações da sessão atual
     * 
     * @param int $userId ID do usuário
     * @return void
     */
    private static function registerSession($userId) {
        try {
            $db = Database::getConnection();
            
            // Gerar ID único para a sessão
            $sessionId = session_id();
            
            // Obter informações do cliente
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Data de expiração (24 horas por padrão)
            $expiry = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
            
            // Registrar sessão
            $stmt = $db->prepare("INSERT INTO sessoes (id, usuario_id, data_inicio, data_expiracao, ip, user_agent) 
                                  VALUES (:id, :usuario_id, NOW(), :expiracao, :ip, :user_agent)");
            $stmt->bindParam(':id', $sessionId);
            $stmt->bindParam(':usuario_id', $userId);
            $stmt->bindParam(':expiracao', $expiry);
            $stmt->bindParam(':ip', $ip);
            $stmt->bindParam(':user_agent', $userAgent);
            $stmt->execute();
            
        } catch (PDOException $e) {
            error_log('Erro ao registrar sessão: ' . $e->getMessage());
        }
    }
    
    /**
     * Cria a tabela de recuperação de senha se não existir
     * 
     * @param PDO $db Conexão com o banco de dados
     * @return void
     */
    private static function createRecoveryTableIfNotExists($db) {
        try {
            // Verificar se a tabela existe
            $stmt = $db->prepare("SHOW TABLES LIKE 'recuperacao_senha'");
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                // Criar a tabela
                $createTable = "CREATE TABLE recuperacao_senha (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    data_expiracao TIMESTAMP NOT NULL,
                    usado TINYINT(1) DEFAULT 0,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
                )";
                
                $db->exec($createTable);
            }
        } catch (PDOException $e) {
            error_log('Erro ao criar tabela de recuperação de senha: ' . $e->getMessage());
        }
    }
    
    /**
     * Verifica se o usuário está autenticado
     * 
     * @return bool Verdadeiro se o usuário estiver autenticado
     */
    public static function isAuthenticated() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Verifica se o usuário tem permissão de administrador
     * 
     * @return bool Verdadeiro se o usuário for administrador
     */
    public static function isAdmin() {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        return $_SESSION['user_type'] === USER_TYPE_ADMIN;
    }
    
    /**
     * Verifica se o usuário tem permissão de loja
     * 
     * @return bool Verdadeiro se o usuário for loja
     */
    public static function isStore() {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        return $_SESSION['user_type'] === USER_TYPE_STORE;
    }
    
    /**
     * Obtém o ID do usuário atual
     * 
     * @return int|null ID do usuário ou null se não estiver logado
     */
    public static function getCurrentUserId() {
        if (!self::isAuthenticated()) {
            return null;
        }
        
        return $_SESSION['user_id'];
    }
}

// Processar requisições diretas de acesso ao controlador
if (basename($_SERVER['PHP_SELF']) === 'AuthController.php') {
    $action = $_REQUEST['action'] ?? '';
    
    switch ($action) {
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                
                $result = AuthController::login($email, $password);
                
                if ($result['status']) {
                    // Redirecionar com base no tipo de usuário
                    if ($_SESSION['user_type'] == USER_TYPE_ADMIN) {
                        header('Location: ' . ADMIN_DASHBOARD_URL);
                    } else if ($_SESSION['user_type'] == USER_TYPE_STORE) {
                        header('Location: ' . STORE_DASHBOARD_URL);
                    } else {
                        header('Location: ' . CLIENT_DASHBOARD_URL);
                    }
                    exit;
                } else {
                    // Redirecionar de volta com mensagem de erro
                    header('Location: ' . SITE_URL . '/login?error=' . urlencode($result['message']));
                    exit;
                }
            }
            break;
            
            case 'register':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $nome = $_POST['nome'] ?? '';
                    $email = $_POST['email'] ?? '';
                    $telefone = $_POST['telefone'] ?? '';
                    $senha = $_POST['senha'] ?? '';
                    $tipo = $_POST['tipo'] ?? null; // Adicionado suporte para o tipo
                    
                    $result = AuthController::register($nome, $email, $telefone, $senha, $tipo);
                    
                    // Verificar se é uma requisição AJAX
                    $isAjax = isset($_POST['ajax']) || 
                             (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
                    
                    if ($isAjax) {
                        // Responder com JSON para requisições AJAX
                        header('Content-Type: application/json');
                        echo json_encode($result);
                        exit;
                    } else {
                        // Redirecionar para páginas normais
                        if ($result['status']) {
                            header('Location: ' . LOGIN_URL . '?success=' . urlencode($result['message']));
                        } else {
                            header('Location: ' . SITE_URL . '/registro?error=' . urlencode($result['message']));
                        }
                        exit;
                    }
                }
                break;
            
        case 'recover':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $email = $_POST['email'] ?? '';
                
                $result = AuthController::recoverPassword($email);
                
                if ($result['status']) {
                    // Redirecionar com base no tipo de usuário
                    if ($_SESSION['user_type'] == USER_TYPE_ADMIN) {
                        header('Location: ' . ADMIN_DASHBOARD_URL);
                    } else if ($_SESSION['user_type'] == USER_TYPE_STORE) {
                        header('Location: ' . STORE_DASHBOARD_URL); 
                    } else {
                        header('Location: ' . CLIENT_DASHBOARD_URL);
                    }
                    exit;
                }
            }
            break;
            
        case 'reset':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $token = $_POST['token'] ?? '';
                $password = $_POST['password'] ?? '';
                
                $result = AuthController::resetPassword($token, $password);
                
                if ($result['status']) {
                    // Redirecionar com mensagem de sucesso
                    header('Location: ' . LOGIN_URL . '?success=' . urlencode($result['message']));
                    exit;
                } else {
                    // Redirecionar com mensagem de erro
                    header('Location: ' . RECOVER_PASSWORD_URL . '?error=' . urlencode($result['message']) . '&token=' . urlencode($token));
                    exit;
                }
            }
            break;
            
        case 'logout':
            $result = AuthController::logout();
            
            // Redirecionar para a página de login
            header('Location: ' . LOGIN_URL);
            exit;
            break;
            
        default:
            // Acesso inválido ao controlador
            header('Location: ' . SITE_URL);
            exit;
    }
}
?>