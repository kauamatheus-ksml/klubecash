<?php
// api/whatsapp-completar-cadastro.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../utils/Validator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['secret']) || $data['secret'] !== WHATSAPP_BOT_SECRET) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Acesso não autorizado']);
        exit;
    }
    
    $phone = $data['phone'] ?? '';
    $message = $data['message'] ?? '';
    $action = $data['action'] ?? 'iniciar';
    
    if (empty($phone)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Telefone obrigatório']);
        exit;
    }
    
    $cadastroProcessor = new WhatsAppCadastroProcessor();
    $resultado = $cadastroProcessor->processarMensagem($phone, $message, $action);
    
    echo json_encode($resultado);
    
} catch (Exception $e) {
    error_log('WhatsApp Cadastro API - Erro: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor',
        'message' => 'Tente novamente em alguns instantes.'
    ]);
}

class WhatsAppCadastroProcessor {
    private $db;
    private $sessionTable = 'whatsapp_cadastro_sessions';
    
    public function __construct() {
        $this->db = Database::getConnection();
        $this->createSessionTableIfNotExists();
    }
    
    private function createSessionTableIfNotExists() {
        $sql = "
            CREATE TABLE IF NOT EXISTS {$this->sessionTable} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                phone VARCHAR(20) UNIQUE,
                user_id INT,
                state VARCHAR(50),
                data JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                expires_at TIMESTAMP
            )
        ";
        $this->db->exec($sql);
    }
    
    public function processarMensagem($phone, $message, $action) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        // Buscar usuário
        $user = $this->buscarUsuario($cleanPhone);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuário não encontrado. Consulte primeiro seu saldo.'
            ];
        }
        
        // Verificar se já é cadastrado
        if ($user['tipo_cliente'] === 'completo') {
            return $this->processarAtualizarCadastro($user, $message, $action);
        }
        
        // Processar completar cadastro
        return $this->processarCompletarCadastro($user, $cleanPhone, $message, $action);
    }
    
    private function buscarUsuario($phone) {
        $stmt = $this->db->prepare("
            SELECT id, nome, email, telefone, tipo_cliente, cpf 
            FROM usuarios 
            WHERE telefone = ? AND tipo = 'cliente' AND status = 'ativo'
        ");
        $stmt->execute([$phone]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function processarCompletarCadastro($user, $phone, $message, $action) {
        if ($action === 'iniciar') {
            // Iniciar processo
            $this->salvarSessao($phone, $user['id'], 'aguardando_email', [
                'nome' => $user['nome']
            ]);
            
            return [
                'success' => true,
                'message' => "📝 *Completar Cadastro*

Olá, {$user['nome']}! 👋

Vamos completar seu cadastro para ter acesso total ao Klube Cash.

📧 Primeiro, preciso do seu e-mail.
Digite seu melhor e-mail:",
                'state' => 'aguardando_email'
            ];
        }
        
        // Recuperar sessão
        $session = $this->recuperarSessao($phone);
        if (!$session || $session['expires_at'] < date('Y-m-d H:i:s')) {
            return [
                'success' => false,
                'message' => '⏰ Sessão expirada. Digite *2* novamente para reiniciar.'
            ];
        }
        
        $state = $session['state'];
        $sessionData = json_decode($session['data'], true);
        
        switch ($state) {
            case 'aguardando_email':
                return $this->processarEmail($phone, $message, $sessionData);
                
            case 'confirmando_email':
                return $this->confirmarEmail($phone, $message, $sessionData);
                
            case 'aguardando_senha':
                return $this->processarSenha($phone, $message, $sessionData);
                
            case 'confirmando_senha':
                return $this->confirmarSenha($phone, $message, $sessionData);
                
            case 'aguardando_cpf':
                return $this->processarCPF($phone, $message, $sessionData);
                
            default:
                return [
                    'success' => false,
                    'message' => '❌ Estado inválido. Digite *2* para reiniciar.'
                ];
        }
    }
    
    private function processarEmail($phone, $email, $sessionData) {
        if (!Validator::isValidEmail($email)) {
            return [
                'success' => true,
                'message' => '❌ E-mail inválido. Digite um e-mail válido:',
                'state' => 'aguardando_email'
            ];
        }
        
        // Verificar se email já existe
        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $sessionData['user_id'] ?? 0]);
        if ($stmt->rowCount() > 0) {
            return [
                'success' => true,
                'message' => '❌ Este e-mail já está em uso. Digite outro e-mail:',
                'state' => 'aguardando_email'
            ];
        }
        
        $sessionData['email'] = $email;
        $this->salvarSessao($phone, $sessionData['user_id'], 'confirmando_email', $sessionData);
        
        return [
            'success' => true,
            'message' => "✅ E-mail: {$email}

Confirma que este e-mail está correto?

Digite *SIM* para confirmar ou *NÃO* para digitar novamente:",
            'state' => 'confirmando_email'
        ];
    }
    
    private function confirmarEmail($phone, $message, $sessionData) {
        $resposta = strtoupper(trim($message));
        
        if ($resposta === 'SIM' || $resposta === 'S') {
            $this->salvarSessao($phone, $sessionData['user_id'], 'aguardando_senha', $sessionData);
            
            return [
                'success' => true,
                'message' => "🔐 *Criar Senha*

Agora crie uma senha segura para sua conta.

A senha deve ter pelo menos 8 caracteres.

Digite sua senha:",
                'state' => 'aguardando_senha'
            ];
        } elseif ($resposta === 'NÃO' || $resposta === 'NAO' || $resposta === 'N') {
            $this->salvarSessao($phone, $sessionData['user_id'], 'aguardando_email', $sessionData);
            
            return [
                'success' => true,
                'message' => "📧 Digite seu e-mail novamente:",
                'state' => 'aguardando_email'
            ];
        } else {
            return [
                'success' => true,
                'message' => "❌ Digite *SIM* para confirmar ou *NÃO* para corrigir:",
                'state' => 'confirmando_email'
            ];
        }
    }
    
    private function processarSenha($phone, $senha, $sessionData) {
        if (strlen($senha) < 8) {
            return [
                'success' => true,
                'message' => '❌ Senha deve ter pelo menos 8 caracteres. Digite novamente:',
                'state' => 'aguardando_senha'
            ];
        }
        
        $sessionData['senha'] = $senha;
        $this->salvarSessao($phone, $sessionData['user_id'], 'confirmando_senha', $sessionData);
        
        return [
            'success' => true,
            'message' => "🔐 *Confirmar Senha*

Digite a senha novamente para confirmar:",
            'state' => 'confirmando_senha'
        ];
    }
    
    private function confirmarSenha($phone, $senhaConfirma, $sessionData) {
        if ($sessionData['senha'] !== $senhaConfirma) {
            $this->salvarSessao($phone, $sessionData['user_id'], 'aguardando_senha', $sessionData);
            
            return [
                'success' => true,
                'message' => "❌ Senhas não coincidem. Digite sua senha novamente:",
                'state' => 'aguardando_senha'
            ];
        }
        
        $this->salvarSessao($phone, $sessionData['user_id'], 'aguardando_cpf', $sessionData);
        
        return [
            'success' => true,
            'message' => "📄 *CPF (Opcional)*

Deseja adicionar seu CPF? (Recomendado para maior segurança)

Digite seu CPF ou *PULAR* para finalizar:",
            'state' => 'aguardando_cpf'
        ];
    }
    
    private function processarCPF($phone, $message, $sessionData) {
        $message = strtoupper(trim($message));
        
        if ($message === 'PULAR') {
            return $this->finalizarCadastro($phone, $sessionData, null);
        }
        
        $cpf = preg_replace('/[^0-9]/', '', $message);
        
        if (!Validator::isValidCPF($cpf)) {
            return [
                'success' => true,
                'message' => '❌ CPF inválido. Digite um CPF válido ou *PULAR*:',
                'state' => 'aguardando_cpf'
            ];
        }
        
        // Verificar se CPF já existe
        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE cpf = ? AND id != ?");
        $stmt->execute([$cpf, $sessionData['user_id']]);
        if ($stmt->rowCount() > 0) {
            return [
                'success' => true,
                'message' => '❌ Este CPF já está em uso. Digite outro CPF ou *PULAR*:',
                'state' => 'aguardando_cpf'
            ];
        }
        
        return $this->finalizarCadastro($phone, $sessionData, $cpf);
    }
    
    private function finalizarCadastro($phone, $sessionData, $cpf) {
        try {
            $this->db->beginTransaction();
            
            // Atualizar usuário
            $sql = "UPDATE usuarios SET email = ?, senha_hash = ?, tipo_cliente = ?, cpf = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $sessionData['email'],
                password_hash($sessionData['senha'], PASSWORD_DEFAULT),
                'completo',
                $cpf,
                $sessionData['user_id']
            ]);
            
            $this->db->commit();
            $this->limparSessao($phone);
            
            return [
                'success' => true,
                'message' => "🎉 *Cadastro Completo!*

Parabéns! Seu cadastro foi finalizado com sucesso.

✅ Agora você tem acesso total ao Klube Cash:
- Login no site e app
- Notificações por email  
- Maior segurança
- Todas as funcionalidades

🌐 Acesse: https://klubecash.com

─────────────────────────
✅ *Processo finalizado!*

Para nova consulta, envie qualquer mensagem.",
                'user_upgraded' => true
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Erro ao finalizar cadastro: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => '❌ Erro ao salvar cadastro. Tente novamente mais tarde.'
            ];
        }
    }
    
    private function processarAtualizarCadastro($user, $message, $action) {
        // Implementar atualização de cadastro
        return [
            'success' => true,
            'message' => "🔧 *Atualizar Cadastro*

Em breve você poderá atualizar:
- E-mail
- Senha  
- CPF
- Dados pessoais

Esta funcionalidade estará disponível em breve!

Para nova consulta, envie qualquer mensagem."
        ];
    }
    
    private function salvarSessao($phone, $userId, $state, $data) {
        $expiresAt = date('Y-m-d H:i:s', time() + WHATSAPP_TIMEOUT_CADASTRO);
        
        $sql = "
            INSERT INTO {$this->sessionTable} (phone, user_id, state, data, expires_at)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                state = VALUES(state),
                data = VALUES(data),
                expires_at = VALUES(expires_at),
                updated_at = CURRENT_TIMESTAMP
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$phone, $userId, $state, json_encode($data), $expiresAt]);
    }
    
    private function recuperarSessao($phone) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->sessionTable} 
            WHERE phone = ? 
            ORDER BY updated_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$phone]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function limparSessao($phone) {
        $stmt = $this->db->prepare("DELETE FROM {$this->sessionTable} WHERE phone = ?");
        $stmt->execute([$phone]);
    }
}
?>