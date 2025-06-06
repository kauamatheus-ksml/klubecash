<?php

// controllers/AuthController.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../utils/Validator.php';

/**
 * Controlador de Autenticação
 * Gerencia login, registro, recuperação de senha e logout
 */
class AuthController {
    /**
     * Verifica se o 2FA está habilitado no sistema
     * 
     * @return bool
     */
    public static function is2FAEnabled() {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT habilitado FROM configuracoes_2fa WHERE id = 1");
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            return $config ? (bool)$config['habilitado'] : false;
        } catch (Exception $e) {
            error_log('Erro ao verificar status 2FA: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gera e envia código de verificação 2FA
     * 
     * @param int $userId ID do usuário
     * @return array Resultado da operação
     */
    public static function send2FACode($userId) {
        try {
            if (!self::is2FAEnabled()) {
                return ['status' => false, 'message' => '2FA não está habilitado no sistema.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se o usuário existe
            $userStmt = $db->prepare("SELECT id, nome, email FROM usuarios WHERE id = ? AND status = 'ativo'");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['status' => false, 'message' => 'Usuário não encontrado ou inativo.'];
            }
            
            // Verificar se não está bloqueado
            if (self::isUser2FABlocked($userId)) {
                return ['status' => false, 'message' => 'Muitas tentativas. Tente novamente em alguns minutos.'];
            }
            
            // Verificar limite de envio (máximo 1 código por minuto)
            $lastSentStmt = $db->prepare("SELECT ultimo_2fa_enviado FROM usuarios WHERE id = ?");
            $lastSentStmt->execute([$userId]);
            $lastSent = $lastSentStmt->fetchColumn();
            
            if ($lastSent && (time() - strtotime($lastSent)) < 60) {
                return ['status' => false, 'message' => 'Aguarde 1 minuto antes de solicitar um novo código.'];
            }
            
            // Gerar código de 6 dígitos
            $codigo = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            // Obter configurações
            $configStmt = $db->query("SELECT tempo_expiracao_minutos FROM configuracoes_2fa WHERE id = 1");
            $config = $configStmt->fetch(PDO::FETCH_ASSOC);
            $tempoExpiracao = $config['tempo_expiracao_minutos'] ?? 5;
            
            $dataExpiracao = date('Y-m-d H:i:s', strtotime("+{$tempoExpiracao} minutes"));
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Invalidar códigos anteriores não utilizados
            $invalidateStmt = $db->prepare("UPDATE verificacao_2fa SET usado = 1 WHERE usuario_id = ? AND usado = 0");
            $invalidateStmt->execute([$userId]);
            
            // Inserir novo código
            $insertStmt = $db->prepare("
                INSERT INTO verificacao_2fa (usuario_id, codigo, data_expiracao, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([$userId, $codigo, $dataExpiracao, $ipAddress, $userAgent]);
            
            // Atualizar último envio
            $updateStmt = $db->prepare("UPDATE usuarios SET ultimo_2fa_enviado = NOW() WHERE id = ?");
            $updateStmt->execute([$userId]);
            
            // Enviar email
            if (Email::send2FACode($user['email'], $user['nome'], $codigo, $ipAddress)) {
                return [
                    'status' => true, 
                    'message' => 'Código de verificação enviado para seu email.',
                    'expira_em' => $tempoExpiracao
                ];
            } else {
                return ['status' => false, 'message' => 'Erro ao enviar email. Tente novamente.'];
            }
            
        } catch (Exception $e) {
            error_log('Erro ao enviar código 2FA: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro interno. Tente novamente.'];
        }
    }
    
    /**
     * Verifica código de verificação 2FA
     * 
     * @param int $userId ID do usuário
     * @param string $codigo Código fornecido pelo usuário
     * @return array Resultado da verificação
     */
    public static function verify2FACode($userId, $codigo) {
        try {
            if (!self::is2FAEnabled()) {
                return ['status' => false, 'message' => '2FA não está habilitado no sistema.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se o usuário não está bloqueado
            if (self::isUser2FABlocked($userId)) {
                return ['status' => false, 'message' => 'Muitas tentativas. Tente novamente em alguns minutos.'];
            }
            
            // Buscar código válido
            $stmt = $db->prepare("
                SELECT id, codigo, data_expiracao 
                FROM verificacao_2fa 
                WHERE usuario_id = ? AND codigo = ? AND usado = 0 AND data_expiracao > NOW()
                ORDER BY data_criacao DESC 
                LIMIT 1
            ");
            $stmt->execute([$userId, $codigo]);
            $verificacao = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$verificacao) {
                // Incrementar tentativas
                self::increment2FAAttempts($userId);
                return ['status' => false, 'message' => 'Código inválido ou expirado.'];
            }
            
            // Marcar código como usado
            $updateStmt = $db->prepare("UPDATE verificacao_2fa SET usado = 1 WHERE id = ?");
            $updateStmt->execute([$verificacao['id']]);
            
            // Resetar tentativas
            $resetStmt = $db->prepare("UPDATE usuarios SET tentativas_2fa = 0, bloqueado_2fa_ate = NULL WHERE id = ?");
            $resetStmt->execute([$userId]);
            
            return ['status' => true, 'message' => 'Código verificado com sucesso.'];
            
        } catch (Exception $e) {
            error_log('Erro ao verificar código 2FA: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro interno. Tente novamente.'];
        }
    }
    
    /**
     * Incrementa tentativas de 2FA e bloqueia se necessário
     * 
     * @param int $userId ID do usuário
     */
    private static function increment2FAAttempts($userId) {
        try {
            $db = Database::getConnection();
            
            // Obter configurações
            $configStmt = $db->query("SELECT max_tentativas FROM configuracoes_2fa WHERE id = 1");
            $config = $configStmt->fetch(PDO::FETCH_ASSOC);
            $maxTentativas = $config['max_tentativas'] ?? 3;
            
            // Incrementar tentativas
            $incrementStmt = $db->prepare("UPDATE usuarios SET tentativas_2fa = tentativas_2fa + 1 WHERE id = ?");
            $incrementStmt->execute([$userId]);
            
            // Verificar se atingiu o limite
            $checkStmt = $db->prepare("SELECT tentativas_2fa FROM usuarios WHERE id = ?");
            $checkStmt->execute([$userId]);
            $tentativas = $checkStmt->fetchColumn();
            
            if ($tentativas >= $maxTentativas) {
                // Bloquear por 15 minutos
                $bloqueioAte = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                $blockStmt = $db->prepare("UPDATE usuarios SET bloqueado_2fa_ate = ? WHERE id = ?");
                $blockStmt->execute([$bloqueioAte, $userId]);
            }
            
        } catch (Exception $e) {
            error_log('Erro ao incrementar tentativas 2FA: ' . $e->getMessage());
        }
    }
    
    /**
     * Verifica se o usuário está bloqueado para 2FA
     * 
     * @param int $userId ID do usuário
     * @return bool
     */
    private static function isUser2FABlocked($userId) {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT bloqueado_2fa_ate FROM usuarios WHERE id = ?");
            $stmt->execute([$userId]);
            $bloqueioAte = $stmt->fetchColumn();
            
            if ($bloqueioAte && strtotime($bloqueioAte) > time()) {
                return true;
            }
            
            // Se o bloqueio expirou, limpar
            if ($bloqueioAte) {
                $clearStmt = $db->prepare("UPDATE usuarios SET tentativas_2fa = 0, bloqueado_2fa_ate = NULL WHERE id = ?");
                $clearStmt->execute([$userId]);
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('Erro ao verificar bloqueio 2FA: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza configurações de 2FA (apenas admin)
     * 
     * @param array $config Configurações
     * @return array Resultado da operação
     */
    public static function update2FASettings($config) {
        try {
            if (!self::isAdmin()) {
                return ['status' => false, 'message' => 'Acesso restrito a administradores.'];
            }
            
            $db = Database::getConnection();
            
            $stmt = $db->prepare("
                UPDATE configuracoes_2fa 
                SET habilitado = ?, tempo_expiracao_minutos = ?, max_tentativas = ?
                WHERE id = 1
            ");
            
            $habilitado = isset($config['habilitado']) ? 1 : 0;
            $tempoExpiracao = max(1, min(60, intval($config['tempo_expiracao_minutos'] ?? 5)));
            $maxTentativas = max(1, min(10, intval($config['max_tentativas'] ?? 3)));
            
            $stmt->execute([$habilitado, $tempoExpiracao, $maxTentativas]);
            
            return ['status' => true, 'message' => 'Configurações de 2FA atualizadas com sucesso.'];
            
        } catch (Exception $e) {
            error_log('Erro ao atualizar configurações 2FA: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao atualizar configurações.'];
        }
    }
    
    /**
     * Obtém configurações de 2FA
     * 
     * @return array Configurações
     */
    public static function get2FASettings() {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT * FROM configuracoes_2fa WHERE id = 1");
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$config) {
                return [
                    'habilitado' => false,
                    'tempo_expiracao_minutos' => 5,
                    'max_tentativas' => 3
                ];
            }
            
            return $config;
            
        } catch (Exception $e) {
            error_log('Erro ao obter configurações 2FA: ' . $e->getMessage());
            return [
                'habilitado' => false,
                'tempo_expiracao_minutos' => 5,
                'max_tentativas' => 3
            ];
        }
    }
    
    /**
     * Login modificado para incluir 2FA
     * 
     * @param string $email Email do usuário
     * @param string $password Senha do usuário
     * @return array Resultado da operação
     */
    public static function loginWith2FA($email, $password) {
        try {
            // Primeiro, fazer login normal
            if (empty($email) || empty($password)) {
                return ['status' => false, 'message' => 'Por favor, preencha todos os campos.'];
            }
            
            $db = Database::getConnection();
            
            $stmt = $db->prepare("SELECT id, nome, senha_hash, tipo, status FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['senha_hash'])) {
                if ($user['status'] !== USER_ACTIVE) {
                    return ['status' => false, 'message' => 'Sua conta está ' . $user['status'] . '. Entre em contato com o suporte.'];
                }
                
                // Se 2FA estiver habilitado, enviar código
                if (self::is2FAEnabled()) {
                    $codeResult = self::send2FACode($user['id']);
                    
                    if ($codeResult['status']) {
                        // Guardar dados do usuário em sessão temporária
                        session_start();
                        $_SESSION['pending_2fa_user_id'] = $user['id'];
                        $_SESSION['pending_2fa_user_data'] = [
                            'id' => $user['id'],
                            'name' => $user['nome'],
                            'email' => $email,
                            'type' => $user['tipo']
                        ];
                        
                        return [
                            'status' => true,
                            'requires_2fa' => true,
                            'message' => $codeResult['message'],
                            'expira_em' => $codeResult['expira_em']
                        ];
                    } else {
                        return $codeResult;
                    }
                } else {
                    // Se 2FA não estiver habilitado, fazer login normal
                    return self::completeLogin($user);
                }
            } else {
                return ['status' => false, 'message' => 'Email ou senha incorretos.'];
            }
            
        } catch (PDOException $e) {
            error_log('Erro no login com 2FA: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao processar o login. Tente novamente.'];
        }
    }
    
    /**
     * Completa o login após verificação 2FA
     * 
     * @param string $codigo Código de verificação
     * @return array Resultado da operação
     */
    public static function complete2FALogin($codigo) {
        try {
            session_start();
            
            if (!isset($_SESSION['pending_2fa_user_id'])) {
                return ['status' => false, 'message' => 'Sessão expirada. Faça login novamente.'];
            }
            
            $userId = $_SESSION['pending_2fa_user_id'];
            $userData = $_SESSION['pending_2fa_user_data'];
            
            // Verificar código
            $verifyResult = self::verify2FACode($userId, $codigo);
            
            if ($verifyResult['status']) {
                // Limpar dados temporários
                unset($_SESSION['pending_2fa_user_id']);
                unset($_SESSION['pending_2fa_user_data']);
                
                // Buscar dados completos do usuário
                $db = Database::getConnection();
                $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    return self::completeLogin($user);
                } else {
                    return ['status' => false, 'message' => 'Erro ao carregar dados do usuário.'];
                }
            } else {
                return $verifyResult;
            }
            
        } catch (Exception $e) {
            error_log('Erro ao completar login 2FA: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro interno. Tente novamente.'];
        }
    }
    
    /**
     * Completa o processo de login
     * 
     * @param array $user Dados do usuário
     * @return array Resultado da operação
     */
    private static function completeLogin($user) {
        try {
            $db = Database::getConnection();
            
            // Iniciar sessão
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['user_type'] = $user['tipo'];
            $_SESSION['user_email'] = $user['email'];
            
            // Atualizar último login
            $updateStmt = $db->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            // Registrar sessão
            self::registerSession($user['id']);
            
            // Associar loja se necessário
            if ($user['tipo'] === USER_TYPE_STORE) {
                self::associateStoreUser($user['id'], $user['email']);
            }
            
            // Enviar alerta de login (opcional)
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
            Email::sendLoginAlert($user['email'], $user['nome'], $ipAddress);
            
            return [
                'status' => true,
                'message' => 'Login efetuado com sucesso.',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['nome'],
                    'type' => $user['tipo']
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Erro ao completar login: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao finalizar login.'];
        }
    }
    /**
     * Processa login de usuário
     * 
     * @param string $email Email do usuário
     * @param string $password Senha do usuário
     * @return array Resultado da operação com status e mensagem
     */
    public static function login($email, $password) {
        try {
            // Validar dados
            if (empty($email) || empty($password)) {
                return ['status' => false, 'message' => 'Por favor, preencha todos os campos.'];
            }
            
            $db = Database::getConnection();
            
            // Buscar usuário pelo email
            $stmt = $db->prepare("SELECT id, nome, senha_hash, tipo, status FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['senha_hash'])) {
                // Verificar status do usuário
                if ($user['status'] !== USER_ACTIVE) {
                    return ['status' => false, 'message' => 'Sua conta está ' . $user['status'] . '. Entre em contato com o suporte.'];
                }
                
                // Login bem-sucedido
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_type'] = $user['tipo'];
                
                // Atualizar último login
                $updateStmt = $db->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id");
                $updateStmt->bindParam(':id', $user['id']);
                $updateStmt->execute();
                
                // Registrar a sessão no banco de dados
                self::registerSession($user['id']);
                
                // Associar usuário à loja se for do tipo loja
                if ($user['tipo'] === USER_TYPE_STORE) {
                    self::associateStoreUser($user['id'], $email);
                }

                return [
                    'status' => true, 
                    'message' => 'Login efetuado com sucesso.', 
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['nome'],
                        'type' => $user['tipo']
                    ]
                ];
            } else {
                return ['status' => false, 'message' => 'Email ou senha incorretos.'];
            }
        } catch (PDOException $e) {
            error_log('Erro no login: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao processar o login. Tente novamente.'];
        }
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
     * Testa o envio de email 2FA para o administrador
     * 
     * @return array Resultado do teste
     */
    public static function test2FAEmail() {
        try {
            // Verificar se é admin
            if (!self::isAdmin()) {
                return ['status' => false, 'message' => 'Acesso restrito a administradores.'];
            }
            
            // Obter dados do admin atual
            $adminId = $_SESSION['user_id'];
            $adminName = $_SESSION['user_name'] ?? 'Administrador';
            $adminEmail = $_SESSION['user_email'] ?? ADMIN_EMAIL;
            
            if (empty($adminEmail)) {
                return ['status' => false, 'message' => 'Email do administrador não encontrado.'];
            }
            
            // Gerar código de teste
            $codigoTeste = '123456';
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            
            // Enviar email de teste 2FA
            $emailSent = Email::send2FACode($adminEmail, $adminName, $codigoTeste, $ipAddress);
            
            if ($emailSent) {
                return [
                    'status' => true,
                    'message' => 'Email de teste 2FA enviado com sucesso para: ' . $adminEmail
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Falha ao enviar email. Verifique as configurações SMTP e tente novamente.'
                ];
            }
            
        } catch (Exception $e) {
            error_log('Erro no teste de email 2FA: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Envia email de teste simples
     * 
     * @return array Resultado do teste
     */
    public static function sendTestEmail() {
        try {
            if (!self::isAdmin()) {
                return ['status' => false, 'message' => 'Acesso restrito a administradores.'];
            }
            
            $adminEmail = $_SESSION['user_email'] ?? ADMIN_EMAIL;
            $adminName = $_SESSION['user_name'] ?? 'Administrador';
            
            if (empty($adminEmail)) {
                return ['status' => false, 'message' => 'Email do administrador não encontrado.'];
            }
            
            return Email::sendTestEmail($adminEmail, $adminName);
            
        } catch (Exception $e) {
            error_log('Erro no envio de email de teste: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro ao enviar email de teste: ' . $e->getMessage()
            ];
        }
    }
    /**
     * Testa a conexão com o servidor de email
     * 
     * @return array Resultado do teste de conexão
     */
    public static function testEmailConnection() {
        try {
            if (!self::isAdmin()) {
                return ['status' => false, 'message' => 'Acesso restrito a administradores.'];
            }
            
            // Usar o método corrigido da classe Email
            return Email::testEmailConnection();
            
        } catch (Exception $e) {
            error_log('Erro no teste de conexão de email: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Erro ao testar conexão: ' . $e->getMessage()
            ];
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
            $expiry = date('Y-m-d H:i:s', strtotime('+2 hours'));
            
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
// Processar requisições AJAX para 2FA
if (isset($_POST['action']) || isset($_GET['action'])) {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    // Sempre retornar JSON
    header('Content-Type: application/json; charset=UTF-8');
    
    try {
        switch ($action) {
            case 'login_with_2fa':
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                $result = AuthController::loginWith2FA($email, $password);
                echo json_encode($result);
                exit;
                
            case 'verify_2fa':
                $codigo = $_POST['codigo'] ?? '';
                $result = AuthController::complete2FALogin($codigo);
                echo json_encode($result);
                exit;
                
            case 'resend_2fa':
                session_start();
                if (isset($_SESSION['pending_2fa_user_id'])) {
                    $result = AuthController::send2FACode($_SESSION['pending_2fa_user_id']);
                } else {
                    $result = ['status' => false, 'message' => 'Sessão expirada.'];
                }
                echo json_encode($result);
                exit;
                
            case 'test_2fa_email':
                $result = AuthController::test2FAEmail();
                echo json_encode($result);
                exit;
                
            case 'test_email_connection':
                $result = AuthController::testEmailConnection();
                echo json_encode($result);
                exit;
                
            case 'send_test_email':
                $result = AuthController::sendTestEmail();
                echo json_encode($result);
                exit;
                
            default:
                echo json_encode(['status' => false, 'message' => 'Ação não reconhecida: ' . $action]);
                exit;
        }
    } catch (Exception $e) {
        error_log('Erro no processamento de ação 2FA: ' . $e->getMessage());
        echo json_encode([
            'status' => false,
            'message' => 'Erro interno no servidor: ' . $e->getMessage()
        ]);
        exit;
    }
}
?>