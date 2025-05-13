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
    
    /**
     * Registra um novo usuário
     * 
     * @param string $nome Nome do usuário
     * @param string $email Email do usuário
     * @param string $telefone Telefone do usuário
     * @param string $senha Senha do usuário
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
            
            // Hash da senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            // Definir tipo de usuário (cliente é o padrão, mas admin pode alterar)
            $tipoUsuario = $tipo ?? USER_TYPE_CLIENT;
            
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
                
                // Enviar email de boas-vindas
                Email::sendWelcome($email, $nome);
                
                return [
                    'status' => true, 
                    'message' => 'Cadastro realizado com sucesso!', 
                    'user_id' => $user_id
                ];
            } else {
                return ['status' => false, 'message' => 'Erro ao cadastrar. Por favor, tente novamente.'];
            }
        } catch (PDOException $e) {
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
                    // Redirecionar com mensagem de sucesso
                    header('Location: ' . RECOVER_PASSWORD_URL . '?success=' . urlencode($result['message']));
                    exit;
                } else {
                    // Redirecionar com mensagem de erro
                    header('Location: ' . SITE_URL . '/recuperar-senha?error=' . urlencode($result['message']));
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