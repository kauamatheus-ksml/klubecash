<?php
date_default_timezone_set('America/Sao_Paulo');
// controllers/AuthController.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../utils/Email.php';
require_once __DIR__ . '/../utils/Validator.php';

/**
 * Controlador de AutenticaÃ§Ã£o
 * Gerencia login, registro, recuperaÃ§Ã£o de senha e logout
 */
class AuthController {

    public static function requireStoreAccess() {
        // CORREÃ‡ÃƒO: Bypass para funcionÃ¡rios
        if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'funcionario' && isset($_SESSION['store_id'])) {
            return; // FuncionÃ¡rio com store_id = OK
        }
        
        if (!self::hasStoreAccess()) {
            header("Location: " . LOGIN_URL . "?error=acesso_restrito");
            exit;
        }
    }
    // Adicionar no AuthController.php
    
    /**
 * MÃ©todo de login COM LOGS FORÃ‡ADOS para debug
 */
public static function login($email, $senha, $remember = false) {
    // LOG INICIAL FORÃ‡ADO
    error_log("=== LOGIN INICIADO === Email: {$email}");
    
    try {
        $db = Database::getConnection();
        
        // Buscar usuÃ¡rio
        $stmt = $db->prepare("
            SELECT id, nome, email, senha_hash, tipo, senat, status, loja_vinculada_id, subtipo_funcionario
            FROM usuarios
            WHERE email = ? AND tipo IN ('cliente', 'admin', 'loja', 'funcionario')
        ");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() === 0) {
            error_log("LOGIN ERRO: UsuÃ¡rio nÃ£o encontrado - {$email}");
            return ['status' => false, 'message' => 'E-mail ou senha incorretos.'];
        }
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("LOGIN: UsuÃ¡rio encontrado - ID: {$user['id']}, Tipo: {$user['tipo']}");
        
        // Validar senha
        if (!password_verify($senha, $user['senha_hash'])) {
            error_log("LOGIN ERRO: Senha incorreta para {$email}");
            return ['status' => false, 'message' => 'E-mail ou senha incorretos.'];
        }
        
        // Verificar status
        if ($user['status'] !== 'ativo') {
            error_log("LOGIN ERRO: Conta inativa - {$email}");
            return ['status' => false, 'message' => 'Sua conta estÃ¡ inativa. Entre em contato com o suporte.'];
        }
        
        error_log("LOGIN: ValidaÃ§Ãµes OK, configurando sessÃ£o...");
        
        // Configurar sessÃ£o
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Definir variÃ¡veis bÃ¡sicas
        $_SESSION['user_id'] = intval($user['id']);
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_type'] = $user['tipo'];
        $_SESSION['user_senat'] = $user['senat'] ?? 'NÃ£o';
        $_SESSION['last_activity'] = time();

        
        error_log("LOGIN: SessÃ£o bÃ¡sica definida - User ID: {$user['id']}");

        // === VERIFICAÃ‡ÃƒO CRÃTICA DE LOJA ===
        if ($user['tipo'] === 'loja') {
            error_log("LOGIN: ENTRANDO na configuraÃ§Ã£o de LOJA para User ID: {$user['id']}");
            
            try {
                // Buscar loja
                $storeStmt = $db->prepare("SELECT * FROM lojas WHERE usuario_id = ? AND status = 'aprovado' ORDER BY id ASC LIMIT 1");
                $storeStmt->execute([$user['id']]);
                $loja = $storeStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($loja) {
                    error_log("LOGIN: LOJA ENCONTRADA - ID: {$loja['id']}, Nome: {$loja['nome_fantasia']}");
                    
                    // FORÃ‡AR DEFINIÃ‡ÃƒO
                    $_SESSION['store_id'] = intval($loja['id']);
                    $_SESSION['store_name'] = $loja['nome_fantasia'];
                    $_SESSION['loja_vinculada_id'] = intval($loja['id']);
                    
                    error_log("LOGIN: VARIÃVEIS DEFINIDAS - store_id: {$_SESSION['store_id']}, store_name: {$_SESSION['store_name']}");
                    
                    // VERIFICAR SE FOI SALVO
                    if (isset($_SESSION['store_id']) && $_SESSION['store_id'] > 0) {
                        error_log("LOGIN: âœ… SUCESSO! store_id salvo corretamente: {$_SESSION['store_id']}");
                    } else {
                        error_log("LOGIN: âŒ ERRO! store_id NÃƒO foi salvo");
                        return ['status' => false, 'message' => 'Erro ao salvar dados da loja na sessÃ£o.'];
                    }
                    
                } else {
                    error_log("LOGIN: âŒ NENHUMA LOJA ENCONTRADA para User ID: {$user['id']}");
                    return ['status' => false, 'message' => 'Nenhuma loja aprovada encontrada para sua conta.'];
                }
                
            } catch (Exception $e) {
                error_log("LOGIN: EXCEÃ‡ÃƒO na configuraÃ§Ã£o da loja: " . $e->getMessage());
                return ['status' => false, 'message' => 'Erro ao configurar dados da loja: ' . $e->getMessage()];
            }
        } else {
            error_log("LOGIN: UsuÃ¡rio NÃƒO Ã© lojista, tipo: {$user['tipo']}");
        }
        
        // === FUNCIONÃRIOS ===
        if ($user['tipo'] === 'funcionario') {
            error_log("LOGIN: CONFIGURANDO FUNCIONÃRIO - User ID: {$user['id']}");
            
            if (empty($user['loja_vinculada_id'])) {
                error_log("LOGIN ERRO: FuncionÃ¡rio {$user['id']} sem loja_vinculada_id");
                return ['status' => false, 'message' => 'FuncionÃ¡rio sem loja vinculada. Entre em contato com o suporte.'];
            }
            
            // Buscar dados COMPLETOS da loja vinculada
            $storeStmt = $db->prepare("SELECT * FROM lojas WHERE id = ? AND status = 'aprovado'");
            $storeStmt->execute([$user['loja_vinculada_id']]);
            $storeData = $storeStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$storeData) {
                error_log("LOGIN ERRO: Loja {$user['loja_vinculada_id']} nÃ£o encontrada ou nÃ£o aprovada");
                return ['status' => false, 'message' => 'A loja vinculada nÃ£o estÃ¡ ativa.'];
            }
            
            // SISTEMA SIMPLIFICADO: FuncionÃ¡rios tÃªm acesso igual ao lojista
            $_SESSION['employee_subtype'] = $user['subtipo_funcionario'] ?? 'funcionario';
            $_SESSION['store_id'] = intval($storeData['id']); // USAR ID DA LOJA, NÃƒO DO USUÃRIO
            $_SESSION['store_name'] = $storeData['nome_fantasia'];
            $_SESSION['loja_vinculada_id'] = intval($storeData['id']);
            $_SESSION['subtipo_funcionario'] = $user['subtipo_funcionario'] ?? 'funcionario';
            
            // VERIFICAÃ‡ÃƒO FORÃ‡ADA
            session_write_close();
            session_start();
            
            error_log("LOGIN: FUNCIONÃRIO CONFIGURADO - Store ID: {$_SESSION['store_id']}, Nome: {$storeData['nome_fantasia']}");
            
            // VERIFICAÃ‡ÃƒO FINAL CRÃTICA
            if (!isset($_SESSION['store_id']) || empty($_SESSION['store_id']) || $_SESSION['store_id'] != $storeData['id']) {
                error_log("LOGIN ERRO CRÃTICO: store_id nÃ£o foi salvo corretamente para funcionÃ¡rio {$user['id']}");
                error_log("Esperado: {$storeData['id']}, Atual: " . ($_SESSION['store_id'] ?? 'NULL'));
                return ['status' => false, 'message' => 'Erro crÃ­tico ao configurar acesso Ã  loja.'];
            }
        }

        // Atualizar Ãºltimo login
        $updateStmt = $db->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        
        error_log("LOGIN: Ãšltimo login atualizado");

        // LOG FINAL COMPLETO
        error_log("=== LOGIN CONCLUÃDO ===");
        error_log("User ID: {$user['id']}");
        error_log("User Type: {$user['tipo']}");
        error_log("Store ID na sessÃ£o: " . ($_SESSION['store_id'] ?? 'NÃƒO DEFINIDO'));
        error_log("SessÃ£o completa: " . json_encode($_SESSION));

        return [
            'status' => true,
            'message' => 'Login realizado com sucesso!',
            'user_data' => [
                'id' => intval($user['id']),
                'nome' => $user['nome'],
                'email' => $user['email'],
                'tipo' => $user['tipo']
            ]
        ];

    } catch (Exception $e) {
        error_log('LOGIN ERRO CRÃTICO: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Erro: ' . $e->getMessage()];
    }
}
public static function debugStoreAccess() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $userType = $_SESSION['user_type'] ?? null;
    $storeId = $_SESSION['store_id'] ?? null;
    
    // Log detalhado
    error_log("DEBUG STORE ACCESS: " . json_encode([
        'user_type' => $userType,
        'store_id' => $storeId,
        'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
    ]));
    
    // Para funcionÃ¡rios, garantir acesso direto
    if ($userType === 'funcionario' && !empty($storeId)) {
        return true;
    }
    
    return false;
}
    /**
     * Verifica se o usuÃ¡rio logado tem acesso Ã  Ã¡rea da loja
     */
    public static function hasStoreAccess() {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        $userType = $_SESSION['user_type'];
        
        // CORREÃ‡ÃƒO: Incluir 'funcionario' explicitamente
        $allowedTypes = ['loja', 'funcionario'];
        if (defined('USER_TYPE_STORE')) $allowedTypes[] = USER_TYPE_STORE;
        if (defined('USER_TYPE_EMPLOYEE')) $allowedTypes[] = USER_TYPE_EMPLOYEE;
        
        if (!in_array($userType, $allowedTypes)) {
            return false;
        }
        
        // CORREÃ‡ÃƒO: Para funcionÃ¡rios, verificar store_id diretamente
        if ($userType === 'funcionario') {
            return !empty($_SESSION['store_id']);
        }
        
        // Para outros tipos
        $storeId = self::getStoreId();
        return !empty($storeId);
    }

    /**
     * Verifica se Ã© lojista (nÃ£o funcionÃ¡rio)
     */
    public static function isStoreOwner() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'loja';
    }

    /**
     * Verifica se Ã© funcionÃ¡rio
     */
    public static function isEmployee() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === USER_TYPE_EMPLOYEE;
    }
    

    public static function getStoreId() {
        // Verificar se sessÃ£o estÃ¡ ativa
        if (!isset($_SESSION['user_type'])) {
            return null;
        }
        
        $userType = $_SESSION['user_type'];
        
        // Para lojistas, usar store_id
        if ($userType === USER_TYPE_STORE || $userType === 'loja') {
            return $_SESSION['store_id'] ?? null;
        }
        
        // Para funcionÃ¡rios, usar loja_vinculada_id OU store_id (ambos devem ter o mesmo valor)
        if ($userType === USER_TYPE_EMPLOYEE || $userType === 'funcionario') {
            return $_SESSION['store_id'] ?? $_SESSION['loja_vinculada_id'] ?? null;
        }
        
        return null;
    }

    public static function getStoreData() {
        $storeId = self::getStoreId();
        if (!$storeId) {
            return null;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM lojas WHERE id = ? LIMIT 1");
            $stmt->execute([$storeId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                error_log("ERRO: Loja nÃ£o encontrada - Store ID: {$storeId}");
                return null;
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao buscar dados da loja: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica se o usuÃ¡rio pode gerenciar funcionÃ¡rios (sistema simplificado)
     */
    public static function canManageEmployees() {
        // Verificar se tem acesso Ã  Ã¡rea da loja
        if (!self::hasStoreAccess()) {
            return false;
        }
        
        // Verificar se tem store_id definido (crÃ­tico!)
        $storeId = self::getStoreId();
        if (!$storeId) {
            return false;
        }
        
        // Sistema simplificado: lojistas e funcionÃ¡rios tÃªm mesmo acesso
        $userType = $_SESSION['user_type'];
        return in_array($userType, [USER_TYPE_STORE, USER_TYPE_EMPLOYEE, 'loja', 'funcionario']);
    }


    /**
     * ObtÃ©m o nome de exibiÃ§Ã£o do subtipo do funcionÃ¡rio
     * 
     * @return string|null Nome formatado do subtipo
     */
    public static function getEmployeeSubtypeDisplay() {
        $subtype = self::getEmployeeSubtype();
        if (!$subtype) {
            return null;
        }
        
        return EMPLOYEE_SUBTYPES[$subtype] ?? 'NÃ£o definido';
    }
    // Adicione este cÃ³digo no AuthController.php apÃ³s o login bem-sucedido
    /**
    * Processa registro via Google OAuth (similar ao login, mas com validaÃ§Ãµes especÃ­ficas)
    */
    public static function googleRegister($code, $state) {
        try {
            // Verificar o state para seguranÃ§a
            if (!GoogleAuth::verifyState($state)) {
                error_log('Google OAuth Register: State invÃ¡lido recebido');
                return ['status' => false, 'message' => 'Estado de OAuth invÃ¡lido. Tente novamente.'];
            }
            
            // Trocar cÃ³digo por token de acesso
            $tokenData = GoogleAuth::getAccessToken($code);
            
            if (!$tokenData || !isset($tokenData['access_token'])) {
                error_log('Google OAuth Register: Erro ao obter token - ' . json_encode($tokenData));
                return ['status' => false, 'message' => 'Erro ao obter token do Google.'];
            }
            
            // Buscar informaÃ§Ãµes do usuÃ¡rio
            $userInfo = GoogleAuth::getUserInfo($tokenData['access_token']);
            
            if (!$userInfo || !isset($userInfo['email'])) {
                error_log('Google OAuth Register: Erro ao obter dados do usuÃ¡rio - ' . json_encode($userInfo));
                return ['status' => false, 'message' => 'Erro ao obter dados do usuÃ¡rio do Google.'];
            }
            
            // Log para debug
            error_log('Google OAuth Register: Dados do usuÃ¡rio recebidos - ' . json_encode($userInfo));
            
            $db = Database::getConnection();
            
            // Verificar se usuÃ¡rio jÃ¡ existe (REGISTRO nÃ£o deve permitir usuÃ¡rio existente)
            $stmt = $db->prepare("
                SELECT * FROM usuarios 
                WHERE email = :email OR google_id = :google_id
            ");
            $stmt->bindParam(':email', $userInfo['email']);
            $stmt->bindParam(':google_id', $userInfo['id']);
            $stmt->execute();
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingUser) {
                // Para registro, usuÃ¡rio nÃ£o deve existir
                error_log('Google OAuth Register: UsuÃ¡rio jÃ¡ existe - ' . $userInfo['email']);
                return [
                    'status' => false, 
                    'message' => 'Uma conta com este email jÃ¡ existe. FaÃ§a login em vez de se registrar.',
                    'redirect_to_login' => true
                ];
            }
            
            // Validar dados obrigatÃ³rios do Google
            if (empty($userInfo['name'])) {
                return ['status' => false, 'message' => 'Nome nÃ£o fornecido pelo Google. Tente o registro manual.'];
            }
            
            if (empty($userInfo['email'])) {
                return ['status' => false, 'message' => 'Email nÃ£o fornecido pelo Google. Tente o registro manual.'];
            }
            
            // Verificar se o email do Google estÃ¡ verificado
            if (!isset($userInfo['verified_email']) || !$userInfo['verified_email']) {
                error_log('Google OAuth Register: Email nÃ£o verificado no Google para ' . $userInfo['email']);
                // Continuar mesmo assim, mas marcar como nÃ£o verificado
            }
            
            // Criar novo usuÃ¡rio
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
            $telefone = ''; // Google nÃ£o fornece telefone por padrÃ£o
            $emailVerified = isset($userInfo['verified_email']) && $userInfo['verified_email'] ? 1 : 0;
            
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':google_id', $userInfo['id']);
            $stmt->bindParam(':avatar_url', $userInfo['picture'] ?? null);
            $stmt->bindParam(':telefone', $telefone);
            $stmt->bindParam(':email_verified', $emailVerified);
            
            if ($stmt->execute()) {
                $userId = $db->lastInsertId();
                
                error_log('Google OAuth Register: Novo usuÃ¡rio registrado - ID: ' . $userId);
                
                // Iniciar sessÃ£o automaticamente apÃ³s registro
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $nome;
                $_SESSION['user_type'] = 'cliente';

                // Registrar sessÃ£o
                self::registerSession($userId);
                
                // Limpar state da sessÃ£o
                unset($_SESSION['google_oauth_state']);
                unset($_SESSION['google_action']);
                
                // Enviar email de boas-vindas
                try {
                    Email::sendWelcome($email, $nome);
                } catch (Exception $e) {
                    error_log('Erro ao enviar email de boas-vindas (registro Google): ' . $e->getMessage());
                    // NÃ£o falhar o registro por causa do email
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
                error_log('Google OAuth Register: Erro ao criar usuÃ¡rio no banco');
                return ['status' => false, 'message' => 'Erro ao criar conta. Tente novamente.'];
            }
            
        } catch (Exception $e) {
            error_log('Erro no registro Google: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao processar registro com Google. Tente novamente.'];
        }
    }
    /**
    * Verifica se o usuÃ¡rio Ã© funcionÃ¡rio e obtÃ©m dados da loja vinculada
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
            error_log('Erro ao obter dados do funcionÃ¡rio: ' . $e->getMessage());
            return false;
        }
    }
    /**
     * Processa login via Google OAuth
     */
    public static function googleLogin($code, $state) {
        try {
            // Verificar o state para seguranÃ§a
            if (!GoogleAuth::verifyState($state)) {
                error_log('Google OAuth: State invÃ¡lido recebido');
                return ['status' => false, 'message' => 'Estado de OAuth invÃ¡lido. Tente novamente.'];
            }
            
            // Trocar cÃ³digo por token de acesso
            $tokenData = GoogleAuth::getAccessToken($code);
            
            if (!$tokenData || !isset($tokenData['access_token'])) {
                error_log('Google OAuth: Erro ao obter token - ' . json_encode($tokenData));
                return ['status' => false, 'message' => 'Erro ao obter token do Google.'];
            }
            
            // Buscar informaÃ§Ãµes do usuÃ¡rio
            $userInfo = GoogleAuth::getUserInfo($tokenData['access_token']);
            
            if (!$userInfo || !isset($userInfo['email'])) {
                error_log('Google OAuth: Erro ao obter dados do usuÃ¡rio - ' . json_encode($userInfo));
                return ['status' => false, 'message' => 'Erro ao obter dados do usuÃ¡rio do Google.'];
            }
            
            // Log para debug
            error_log('Google OAuth: Dados do usuÃ¡rio recebidos - ' . json_encode($userInfo));
            
            $db = Database::getConnection();
            
            // Verificar se usuÃ¡rio jÃ¡ existe (por email ou google_id)
            $stmt = $db->prepare("
                SELECT * FROM usuarios 
                WHERE email = :email OR google_id = :google_id
            ");
            $stmt->bindParam(':email', $userInfo['email']);
            $stmt->bindParam(':google_id', $userInfo['id']);
            $stmt->execute();
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingUser) {
                // UsuÃ¡rio existe - atualizar informaÃ§Ãµes do Google se necessÃ¡rio
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
                
                // Verificar status do usuÃ¡rio
                if ($userStatus !== USER_ACTIVE) {
                    return ['status' => false, 'message' => 'Sua conta estÃ¡ ' . $userStatus . '. Entre em contato com o suporte.'];
                }
                
                error_log('Google OAuth: UsuÃ¡rio existente atualizado - ID: ' . $userId);
                
            } else {
                // Criar novo usuÃ¡rio
                $stmt = $db->prepare("
                    INSERT INTO usuarios (
                        nome, email, google_id, avatar_url, telefone,
                        provider, email_verified, tipo, status, data_criacao
                    ) VALUES (
                        :nome, :email, :google_id, :avatar_url, :telefone,
                        'google', 1, 'cliente', 'ativo', NOW()
                    )
                ");
                
                // Usar o nome do Google ou extrair do email se nÃ£o disponÃ­vel
                $nome = $userInfo['name'] ?? explode('@', $userInfo['email'])[0];
                $telefone = ''; // Google nÃ£o fornece telefone por padrÃ£o
                
                $stmt->bindParam(':nome', $nome);
                $stmt->bindParam(':email', $userInfo['email']);
                $stmt->bindParam(':google_id', $userInfo['id']);
                $stmt->bindParam(':avatar_url', $userInfo['picture'] ?? null);
                $stmt->bindParam(':telefone', $telefone);
                
                if ($stmt->execute()) {
                    $userId = $db->lastInsertId();
                    $userName = $nome;
                    $userType = 'cliente';
                    
                    error_log('Google OAuth: Novo usuÃ¡rio criado - ID: ' . $userId);
                    
                    // Enviar email de boas-vindas
                    try {
                        Email::sendWelcome($userInfo['email'], $nome);
                    } catch (Exception $e) {
                        error_log('Erro ao enviar email de boas-vindas: ' . $e->getMessage());
                        // NÃ£o falhar o login por causa do email
                    }
                } else {
                    error_log('Google OAuth: Erro ao criar usuÃ¡rio no banco');
                    return ['status' => false, 'message' => 'Erro ao criar usuÃ¡rio.'];
                }
            }
            
            // Iniciar sessÃ£o
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $userName;
            $_SESSION['user_type'] = $userType;

            // Registrar sessÃ£o
            self::registerSession($userId);
            
            // Limpar state da sessÃ£o
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
     * Associa um usuÃ¡rio do tipo loja Ã  sua respectiva loja
     * 
     * @param int $userId ID do usuÃ¡rio
     * @param string $userEmail Email do usuÃ¡rio
     * @return bool Verdadeiro se associado com sucesso
     */
    private static function associateStoreUser($userId, $userEmail) {
        try {
            $db = Database::getConnection();
            
            // Verificar se o usuÃ¡rio jÃ¡ estÃ¡ associado a alguma loja
            $checkStmt = $db->prepare("SELECT id FROM lojas WHERE usuario_id = :usuario_id");
            $checkStmt->bindParam(':usuario_id', $userId);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                return true; // JÃ¡ estÃ¡ associado
            }
            
            // Encontrar loja com o mesmo email do usuÃ¡rio
            $storeStmt = $db->prepare("SELECT id FROM lojas WHERE email = :email AND usuario_id IS NULL");
            $storeStmt->bindParam(':email', $userEmail);
            $storeStmt->execute();
            
            if ($storeStmt->rowCount() > 0) {
                $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
                
                // Associar usuÃ¡rio Ã  loja
                $updateStmt = $db->prepare("UPDATE lojas SET usuario_id = :usuario_id WHERE id = :loja_id");
                $updateStmt->bindParam(':usuario_id', $userId);
                $updateStmt->bindParam(':loja_id', $store['id']);
                return $updateStmt->execute();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log('Erro ao associar usuÃ¡rio Ã  loja: ' . $e->getMessage());
            return false;
        }
    }
    /**
    * Registra um novo usuÃ¡rio
    * 
    * @param string $nome Nome do usuÃ¡rio
    * @param string $email Email do usuÃ¡rio
    * @param string $telefone Telefone do usuÃ¡rio
    * @param string $senha Senha do usuÃ¡rio
    * @param string $tipo Tipo do usuÃ¡rio
    * @return array Resultado da operaÃ§Ã£o com status e mensagem
    */
    public static function register($nome, $email, $telefone, $senha, $tipo = null) {
        try {
            // Validar dados
            $errors = [];
            
            if (empty($email) || !Validator::validaEmail($email)) {
                $errors[] = 'Email invÃ¡lido';
            }
            
            if (empty($nome) || !Validator::validaNome($nome)) {
                $errors[] = 'Nome precisa ter pelo menos 3 caracteres';
            }
            
            if (empty($telefone) || !Validator::validaTelefone($telefone)) {
                $errors[] = 'Telefone invÃ¡lido';
            }
            
            if (empty($senha) || !Validator::validaSenha($senha, PASSWORD_MIN_LENGTH)) {
                $errors[] = 'A senha deve ter no mÃ­nimo ' . PASSWORD_MIN_LENGTH . ' caracteres';
            }
            
            if (!empty($errors)) {
                return ['status' => false, 'message' => implode('. ', $errors)];
            }
            
            $db = Database::getConnection();
            
            // Verificar se o email jÃ¡ existe
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return ['status' => false, 'message' => 'Este email jÃ¡ estÃ¡ cadastrado. Por favor, use outro ou faÃ§a login.'];
            }
            
            // Se for usuÃ¡rio do tipo loja, verificar se hÃ¡ uma loja aprovada com este email
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
            
            // Definir tipo de usuÃ¡rio (cliente Ã© o padrÃ£o, mas admin pode alterar)
            $tipoUsuario = $tipo ?? USER_TYPE_CLIENT;
            
            // Iniciar transaÃ§Ã£o
            $db->beginTransaction();
            
            // Inserir novo usuÃ¡rio
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
                
                // Se for usuÃ¡rio do tipo loja e encontrou uma loja correspondente, vincular
                if ($tipoUsuario === USER_TYPE_STORE && $storeId) {
                    $linkStmt = $db->prepare("UPDATE lojas SET usuario_id = :usuario_id WHERE id = :loja_id");
                    $linkStmt->bindParam(':usuario_id', $user_id);
                    $linkStmt->bindParam(':loja_id', $storeId);
                    
                    if (!$linkStmt->execute()) {
                        $db->rollBack();
                        return ['status' => false, 'message' => 'Erro ao vincular usuÃ¡rio Ã  loja.'];
                    }
                }
                
                // Commit da transaÃ§Ã£o
                $db->commit();
                
                // Enviar email de boas-vindas
                Email::sendWelcome($email, $nome);
                
                return [
                    'status' => true, 
                    'message' => 'Cadastro realizado com sucesso!' . 
                                ($storeId ? ' UsuÃ¡rio vinculado Ã  loja automaticamente.' : ''), 
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
     * Solicita recuperaÃ§Ã£o de senha
     * 
     * @param string $email Email do usuÃ¡rio
     * @return array Resultado da operaÃ§Ã£o com status e mensagem
     */
    public static function recoverPassword($email) {
        try {
            if (empty($email) || !Validator::validaEmail($email)) {
                return ['status' => false, 'message' => 'Por favor, informe um email vÃ¡lido.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se o email existe
            $stmt = $db->prepare("SELECT id, nome, status FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                // NÃ£o informar ao usuÃ¡rio que o email nÃ£o existe (seguranÃ§a)
                return ['status' => true, 'message' => 'Se o email estiver cadastrado, enviaremos instruÃ§Ãµes para recuperar sua senha.'];
            }
            
            if ($user['status'] !== USER_ACTIVE) {
                return ['status' => false, 'message' => 'Sua conta estÃ¡ ' . $user['status'] . '. Entre em contato com o suporte.'];
            }
            
            // Gerar token Ãºnico
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+24 hours')); // 24 horas ao invÃ©s de 2
            
            // Verificar se jÃ¡ existe tabela recuperacao_senha, se nÃ£o, criar
            self::createRecoveryTableIfNotExists($db);
            
            // Primeiro excluir tokens antigos deste usuÃ¡rio
            $deleteStmt = $db->prepare("DELETE FROM recuperacao_senha WHERE usuario_id = :user_id");
            $deleteStmt->bindParam(':user_id', $user['id']);
            $deleteStmt->execute();
            
            // Inserir novo token
            $insertStmt = $db->prepare("INSERT INTO recuperacao_senha (usuario_id, token, data_expiracao) VALUES (:user_id, :token, :expiry)");
            $insertStmt->bindParam(':user_id', $user['id']);
            $insertStmt->bindParam(':token', $token);
            $insertStmt->bindParam(':expiry', $expiry);
            
            if ($insertStmt->execute()) {
                // Enviar email de recuperaÃ§Ã£o
                if (Email::sendPasswordRecovery($email, $user['nome'], $token)) {
                    return ['status' => true, 'message' => 'Enviamos instruÃ§Ãµes para recuperar sua senha para o email informado.'];
                } else {
                    return ['status' => false, 'message' => 'NÃ£o foi possÃ­vel enviar o email. Por favor, tente novamente mais tarde.'];
                }
            } else {
                return ['status' => false, 'message' => 'Erro ao gerar token de recuperaÃ§Ã£o. Por favor, tente novamente.'];
            }
        } catch (Exception $e) {
            error_log('Erro na recuperaÃ§Ã£o de senha: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao processar a solicitaÃ§Ã£o. Tente novamente.'];
        }
    }
    
    /**
     * Redefine a senha do usuÃ¡rio atravÃ©s de token
     * 
     * @param string $token Token de recuperaÃ§Ã£o
     * @param string $newPassword Nova senha
     * @return array Resultado da operaÃ§Ã£o com status e mensagem
     */
    public static function resetPassword($token, $newPassword) {
        try {
            if (empty($token) || empty($newPassword)) {
                return ['status' => false, 'message' => 'Dados invÃ¡lidos para redefiniÃ§Ã£o de senha.'];
            }
            
            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                return ['status' => false, 'message' => 'A senha deve ter no mÃ­nimo ' . PASSWORD_MIN_LENGTH . ' caracteres.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se o token Ã© vÃ¡lido
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
                return ['status' => false, 'message' => 'Token invÃ¡lido ou expirado. Por favor, solicite uma nova recuperaÃ§Ã£o de senha.'];
            }
            
            // Atualizar a senha do usuÃ¡rio
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $db->prepare("UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :id");
            $updateStmt->bindParam(':senha_hash', $passwordHash);
            $updateStmt->bindParam(':id', $tokenInfo['usuario_id']);
            
            if ($updateStmt->execute()) {
                // Marcar o token como usado
                $usedStmt = $db->prepare("UPDATE recuperacao_senha SET usado = 1 WHERE id = :id");
                $usedStmt->bindParam(':id', $tokenInfo['id']);
                $usedStmt->execute();
                
                return ['status' => true, 'message' => 'Sua senha foi atualizada com sucesso! VocÃª jÃ¡ pode fazer login.'];
            } else {
                return ['status' => false, 'message' => 'Erro ao atualizar a senha. Por favor, tente novamente.'];
            }
        } catch (PDOException $e) {
            error_log('Erro na redefiniÃ§Ã£o de senha: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao processar a solicitaÃ§Ã£o. Tente novamente.'];
        }
    }
    
    /**
     * Realiza o logout do usuÃ¡rio
     * 
     * @return array Resultado da operaÃ§Ã£o com status e mensagem
     */
    public static function logout() {
        // Iniciar sessÃ£o se nÃ£o estiver iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Limpar variÃ¡veis de sessÃ£o
        $_SESSION = array();
        
        // Destruir a sessÃ£o
        session_destroy();
        
        return ['status' => true, 'message' => 'Logout efetuado com sucesso.'];
    }
    
    /**
     * Registra informaÃ§Ãµes da sessÃ£o atual
     * 
     * @param int $userId ID do usuÃ¡rio
     * @return void
     */
    private static function registerSession($userId) {
        try {
            $db = Database::getConnection();
            
            // Gerar ID Ãºnico para a sessÃ£o
            $sessionId = session_id();
            
            // Obter informaÃ§Ãµes do cliente
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Data de expiraÃ§Ã£o (24 horas por padrÃ£o)
            $expiry = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
            
            // Registrar sessÃ£o
            $stmt = $db->prepare("INSERT INTO sessoes (id, usuario_id, data_inicio, data_expiracao, ip, user_agent) 
                                  VALUES (:id, :usuario_id, NOW(), :expiracao, :ip, :user_agent)");
            $stmt->bindParam(':id', $sessionId);
            $stmt->bindParam(':usuario_id', $userId);
            $stmt->bindParam(':expiracao', $expiry);
            $stmt->bindParam(':ip', $ip);
            $stmt->bindParam(':user_agent', $userAgent);
            $stmt->execute();
            
        } catch (PDOException $e) {
            error_log('Erro ao registrar sessÃ£o: ' . $e->getMessage());
        }
    }
    
    /**
     * Cria a tabela de recuperaÃ§Ã£o de senha se nÃ£o existir
     * 
     * @param PDO $db ConexÃ£o com o banco de dados
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
            error_log('Erro ao criar tabela de recuperaÃ§Ã£o de senha: ' . $e->getMessage());
        }
    }
    
    /**
     * Verifica se o usuÃ¡rio estÃ¡ autenticado
     * 
     * @return bool Verdadeiro se o usuÃ¡rio estiver autenticado
     */
    public static function isAuthenticated() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
    }
    
    /**
     * Verifica se o usuÃ¡rio tem permissÃ£o de administrador
     * 
     * @return bool Verdadeiro se o usuÃ¡rio for administrador
     */
    public static function isAdmin() {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        return $_SESSION['user_type'] === USER_TYPE_ADMIN;
    }
    
    /**
     * Verifica se o usuÃ¡rio tem permissÃ£o de loja
     * 
     * @return bool Verdadeiro se o usuÃ¡rio for loja
     */
    public static function isStore() {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        return $_SESSION['user_type'] === USER_TYPE_STORE;
    }
    
    /**
     * ObtÃ©m o ID do usuÃ¡rio atual
     * 
     * @return int|null ID do usuÃ¡rio ou null se nÃ£o estiver logado
     */
    public static function getCurrentUserId() {
        if (!self::isAuthenticated()) {
            return null;
        }
        
        return $_SESSION['user_id'];
    }
}

// Processar requisiÃ§Ãµes diretas de acesso ao controlador
if (basename($_SERVER['PHP_SELF']) === 'AuthController.php') {
    $action = $_REQUEST['action'] ?? '';
    
    switch ($action) {
         case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                
                $result = AuthController::login($email, $password);
                
                if ($result['status']) {
                    // CORREÃ‡ÃƒO: Redirecionar baseado no tipo correto
                    $userType = $_SESSION['user_type'] ?? '';
                    
                    if ($userType == 'admin') {
                        header('Location: ' . ADMIN_DASHBOARD_URL);
                    } else if ($userType == 'loja') {
                        header('Location: ' . STORE_DASHBOARD_URL);
                    } else if ($userType == 'funcionario') {
                        // FUNCIONÃRIO VAI PARA STORE (SISTEMA SIMPLIFICADO)
                        header('Location: ' . STORE_DASHBOARD_URL);
                    } else {
                        header('Location: ' . CLIENT_DASHBOARD_URL);
                    }
                    exit;
                } else {
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
                    $tipo = $_POST['tipo'] ?? 'cliente'; // CORREÃ‡ÃƒO
                    
                    $result = AuthController::register($nome, $email, $telefone, $senha, $tipo);
                    
                    // Verificar se Ã© uma requisiÃ§Ã£o AJAX
                    $isAjax = isset($_POST['ajax']) || 
                             (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
                    
                    if ($isAjax) {
                        // Responder com JSON para requisiÃ§Ãµes AJAX
                        header('Content-Type: application/json');
                        echo json_encode($result);
                        exit;
                    } else {
                        // Redirecionar para pÃ¡ginas normais
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
                    // Redirecionar com base no tipo de usuÃ¡rio
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
            
            // Redirecionar para a pÃ¡gina de login
            header('Location: ' . LOGIN_URL);
            exit;
            break;
            
        default:
            // Acesso invÃ¡lido ao controlador
            header('Location: ' . SITE_URL);
            exit;
    }
}
?>





