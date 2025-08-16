<?php
// api/whatsapp-completar-cadastro.php
// API para gerenciar o fluxo de completar/atualizar cadastro via WhatsApp

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../classes/SaldoConsulta.php';

class WhatsAppCadastroAPI {
    private $db;
    private $saldoConsulta;
    
    public function __construct() {
        $this->db = getConnection();
        $this->saldoConsulta = new SaldoConsulta($this->db);
    }
    
    public function handleRequest() {
        try {
            // Verificar secret
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !isset($input['secret']) || $input['secret'] !== WHATSAPP_BOT_SECRET) {
                return $this->errorResponse('Acesso não autorizado', 401);
            }
            
            $phone = $this->cleanPhone($input['phone'] ?? '');
            $action = $input['action'] ?? '';
            $message = $input['message'] ?? '';
            
            if (empty($phone)) {
                return $this->errorResponse('Telefone é obrigatório');
            }
            
            switch ($action) {
                case 'iniciar':
                    return $this->iniciarCadastro($phone);
                case 'processar':
                    return $this->processarMensagem($phone, $message);
                case 'cancelar':
                    return $this->cancelarCadastro($phone);
                default:
                    return $this->errorResponse('Ação inválida');
            }
            
        } catch (Exception $e) {
            error_log("ERRO WhatsApp Cadastro API: " . $e->getMessage());
            return $this->errorResponse('Erro interno do servidor');
        }
    }
    
    private function iniciarCadastro($phone) {
        // Buscar usuário
        $usuario = $this->saldoConsulta->buscarUsuarioPorTelefone($phone);
        
        if (!$usuario) {
            return $this->errorResponse('Usuário não encontrado. Entre em contato com nosso suporte.');
        }
        
        // Verificar tipo de cliente
        $tipoCliente = $this->determinarTipoCliente($usuario);
        
        if ($tipoCliente === CLIENT_TYPE_VISITANTE) {
            return $this->iniciarCompletarCadastro($phone, $usuario);
        } else {
            return $this->iniciarAtualizarCadastro($phone, $usuario);
        }
    }
    
    private function determinarTipoCliente($usuario) {
        // Cliente completo precisa ter: nome, telefone, email e senha
        if (!empty($usuario['email']) && !empty($usuario['senha_hash'])) {
            return CLIENT_TYPE_COMPLETO;
        }
        return CLIENT_TYPE_VISITANTE;
    }
    
    private function iniciarCompletarCadastro($phone, $usuario) {
        // Verificar o que falta no cadastro
        $camposFaltantes = [];
        
        if (empty($usuario['email'])) {
            $camposFaltantes[] = 'email';
        }
        
        if (empty($usuario['senha_hash'])) {
            $camposFaltantes[] = 'senha';
        }
        
        if (empty($camposFaltantes)) {
            // Usuário já está completo, atualizar tipo
            $this->atualizarTipoCliente($usuario['id'], CLIENT_TYPE_COMPLETO);
            return $this->successResponse('✅ Seu cadastro já está completo!');
        }
        
        // Iniciar processo de cadastro
        $proximoCampo = $camposFaltantes[0];
        $this->salvarEstadoCadastro($phone, 'aguardando_' . $proximoCampo, [
            'usuario_id' => $usuario['id'],
            'campos_faltantes' => $camposFaltantes,
            'dados_temporarios' => []
        ]);
        
        if ($proximoCampo === 'email') {
            $mensagem = "📧 Para completar seu cadastro, preciso do seu *e-mail*.\n\nDigite seu e-mail:";
        } else {
            $mensagem = "🔒 Para completar seu cadastro, preciso criar uma *senha*.\n\nDigite uma senha (mínimo 6 caracteres):";
        }
        
        return $this->successResponse($mensagem);
    }
    
    private function iniciarAtualizarCadastro($phone, $usuario) {
        $this->salvarEstadoCadastro($phone, 'selecionando_campo_atualizacao', [
            'usuario_id' => $usuario['id'],
            'dados_atuais' => $usuario
        ]);
        
        $mensagem = "📝 *Atualizar Cadastro*\n\n";
        $mensagem .= "Escolha o que deseja atualizar:\n\n";
        $mensagem .= "1️⃣ Nome: " . ($usuario['nome'] ?: 'Não informado') . "\n";
        $mensagem .= "2️⃣ E-mail: " . ($usuario['email'] ?: 'Não informado') . "\n";
        $mensagem .= "3️⃣ Senha: ••••••••\n";
        $mensagem .= "4️⃣ CPF: " . ($usuario['cpf'] ? $this->mascaraCPF($usuario['cpf']) : 'Não informado') . "\n\n";
        $mensagem .= "Digite o *número* da opção ou *0* para cancelar:";
        
        return $this->successResponse($mensagem);
    }
    
    private function processarMensagem($phone, $message) {
        $estado = $this->obterEstadoCadastro($phone);
        
        if (!$estado) {
            return $this->errorResponse('Sessão de cadastro não encontrada. Digite *2* para iniciar novamente.');
        }
        
        switch ($estado['estado']) {
            case 'aguardando_email':
                return $this->processarEmail($phone, $message, $estado);
            case 'confirmando_email':
                return $this->confirmarEmail($phone, $message, $estado);
            case 'aguardando_senha':
                return $this->processarSenha($phone, $message, $estado);
            case 'confirmando_senha':
                return $this->confirmarSenha($phone, $message, $estado);
            case 'aguardando_cpf':
                return $this->processarCPF($phone, $message, $estado);
            case 'selecionando_campo_atualizacao':
                return $this->processarSelecaoAtualizacao($phone, $message, $estado);
            default:
                return $this->errorResponse('Estado inválido.');
        }
    }
    
    private function processarEmail($phone, $email, $estado) {
        if (!Validator::validateEmail($email)) {
            return $this->successResponse('❌ E-mail inválido. Digite um e-mail válido:');
        }
        
        // Verificar se e-mail já existe
        if ($this->emailJaExiste($email, $estado['dados']['usuario_id'])) {
            return $this->successResponse('❌ Este e-mail já está cadastrado. Digite outro e-mail:');
        }
        
        // Salvar temporariamente
        $dados = $estado['dados'];
        $dados['dados_temporarios']['email'] = $email;
        
        $this->salvarEstadoCadastro($phone, 'confirmando_email', $dados);
        
        return $this->successResponse("📧 Confirme seu e-mail:\n*{$email}*\n\nDigite *1* para confirmar ou *2* para corrigir:");
    }
    
    private function confirmarEmail($phone, $message, $estado) {
        $message = trim($message);
        
        if ($message === '1') {
            // E-mail confirmado, próximo passo
            $camposFaltantes = $estado['dados']['campos_faltantes'];
            $proximoCampo = null;
            
            foreach ($camposFaltantes as $campo) {
                if ($campo !== 'email') {
                    $proximoCampo = $campo;
                    break;
                }
            }
            
            if ($proximoCampo === 'senha') {
                $dados = $estado['dados'];
                $this->salvarEstadoCadastro($phone, 'aguardando_senha', $dados);
                return $this->successResponse("🔒 Agora crie uma *senha* para sua conta.\n\nDigite uma senha (mínimo 6 caracteres):");
            } else {
                // Finalizar cadastro
                return $this->finalizarCadastro($phone, $estado);
            }
        } elseif ($message === '2') {
            // Corrigir e-mail
            $dados = $estado['dados'];
            unset($dados['dados_temporarios']['email']);
            $this->salvarEstadoCadastro($phone, 'aguardando_email', $dados);
            return $this->successResponse("📧 Digite seu e-mail novamente:");
        } else {
            return $this->successResponse("Digite *1* para confirmar ou *2* para corrigir o e-mail:");
        }
    }
    
    private function processarSenha($phone, $senha, $estado) {
        if (strlen($senha) < 6) {
            return $this->successResponse('❌ A senha deve ter pelo menos 6 caracteres. Digite novamente:');
        }
        
        // Salvar temporariamente
        $dados = $estado['dados'];
        $dados['dados_temporarios']['senha'] = $senha;
        
        $this->salvarEstadoCadastro($phone, 'confirmando_senha', $dados);
        
        return $this->successResponse("🔒 Confirme sua senha digitando-a novamente:");
    }
    
    private function confirmarSenha($phone, $senha, $estado) {
        $senhaAnterior = $estado['dados']['dados_temporarios']['senha'] ?? '';
        
        if ($senha !== $senhaAnterior) {
            $dados = $estado['dados'];
            unset($dados['dados_temporarios']['senha']);
            $this->salvarEstadoCadastro($phone, 'aguardando_senha', $dados);
            return $this->successResponse("❌ Senhas não coincidem. Digite sua senha novamente:");
        }
        
        // Senhas coincidem, perguntar sobre CPF
        $dados = $estado['dados'];
        $this->salvarEstadoCadastro($phone, 'aguardando_cpf', $dados);
        
        return $this->successResponse("📄 Deseja informar seu *CPF*? (opcional)\n\nDigite seu CPF ou *pular* para finalizar:");
    }
    
    private function processarCPF($phone, $message, $estado) {
        $message = strtolower(trim($message));
        
        if ($message === 'pular') {
            return $this->finalizarCadastro($phone, $estado);
        }
        
        $cpf = preg_replace('/[^0-9]/', '', $message);
        
        if (!Validator::validateCPF($cpf)) {
            return $this->successResponse("❌ CPF inválido. Digite um CPF válido ou *pular*:");
        }
        
        // Verificar se CPF já existe
        if ($this->cpfJaExiste($cpf, $estado['dados']['usuario_id'])) {
            return $this->successResponse("❌ Este CPF já está cadastrado. Digite outro CPF ou *pular*:");
        }
        
        // Salvar CPF e finalizar
        $dados = $estado['dados'];
        $dados['dados_temporarios']['cpf'] = $cpf;
        
        return $this->finalizarCadastro($phone, ['dados' => $dados]);
    }
    
    private function processarSelecaoAtualizacao($phone, $message, $estado) {
        $opcao = trim($message);
        
        if ($opcao === '0') {
            $this->limparEstadoCadastro($phone);
            return $this->successResponse("❌ Atualização cancelada.");
        }
        
        $campos = ['1' => 'nome', '2' => 'email', '3' => 'senha', '4' => 'cpf'];
        
        if (!isset($campos[$opcao])) {
            return $this->successResponse("Opção inválida. Digite um número de 1 a 4 ou *0* para cancelar:");
        }
        
        $campoSelecionado = $campos[$opcao];
        $dados = $estado['dados'];
        $dados['campo_atualizando'] = $campoSelecionado;
        
        switch ($campoSelecionado) {
            case 'nome':
                $this->salvarEstadoCadastro($phone, 'aguardando_nome', $dados);
                return $this->successResponse("📝 Digite seu *nome completo*:");
                
            case 'email':
                $this->salvarEstadoCadastro($phone, 'aguardando_email_atualizacao', $dados);
                return $this->successResponse("📧 Digite seu *novo e-mail*:");
                
            case 'senha':
                $this->salvarEstadoCadastro($phone, 'aguardando_senha_atualizacao', $dados);
                return $this->successResponse("🔒 Digite sua *nova senha* (mínimo 6 caracteres):");
                
            case 'cpf':
                $this->salvarEstadoCadastro($phone, 'aguardando_cpf_atualizacao', $dados);
                return $this->successResponse("📄 Digite seu *CPF*:");
        }
    }
    
    private function finalizarCadastro($phone, $estado) {
        try {
            $this->db->beginTransaction();
            
            $userId = $estado['dados']['usuario_id'];
            $dadosTemporarios = $estado['dados']['dados_temporarios'] ?? [];
            
            // Atualizar usuário
            $sql = "UPDATE usuarios SET ";
            $params = [];
            $updates = [];
            
            if (!empty($dadosTemporarios['email'])) {
                $updates[] = "email = :email";
                $params[':email'] = $dadosTemporarios['email'];
            }
            
            if (!empty($dadosTemporarios['senha'])) {
                $updates[] = "senha_hash = :senha";
                $params[':senha'] = password_hash($dadosTemporarios['senha'], PASSWORD_BCRYPT);
            }
            
            if (!empty($dadosTemporarios['cpf'])) {
                $updates[] = "cpf = :cpf";
                $params[':cpf'] = $dadosTemporarios['cpf'];
            }
            
            // Atualizar tipo_cliente para completo
            $updates[] = "tipo_cliente = :tipo_cliente";
            $params[':tipo_cliente'] = CLIENT_TYPE_COMPLETO;
            
            $sql .= implode(', ', $updates) . " WHERE id = :id";
            $params[':id'] = $userId;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $this->db->commit();
            $this->limparEstadoCadastro($phone);
            
            return $this->successResponse("🎉 *Cadastro completado com sucesso!*\n\nAgora você tem acesso completo ao Klube Cash. Digite *1* para consultar seu saldo!");
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("ERRO ao finalizar cadastro: " . $e->getMessage());
            return $this->errorResponse("Erro ao finalizar cadastro. Tente novamente.");
        }
    }
    
    // Métodos auxiliares
    private function salvarEstadoCadastro($phone, $estado, $dados) {
        $cacheFile = sys_get_temp_dir() . "/whatsapp_cadastro_{$phone}.json";
        $estadoData = [
            'estado' => $estado,
            'dados' => $dados,
            'timestamp' => time()
        ];
        file_put_contents($cacheFile, json_encode($estadoData));
    }
    
    private function obterEstadoCadastro($phone) {
        $cacheFile = sys_get_temp_dir() . "/whatsapp_cadastro_{$phone}.json";
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($cacheFile), true);
        
        // Verificar timeout (10 minutos)
        if (time() - $data['timestamp'] > WHATSAPP_TIMEOUT_CADASTRO) {
            unlink($cacheFile);
            return null;
        }
        
        return $data;
    }
    
    private function limparEstadoCadastro($phone) {
        $cacheFile = sys_get_temp_dir() . "/whatsapp_cadastro_{$phone}.json";
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
    
    private function emailJaExiste($email, $userId) {
        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id");
        $stmt->execute([':email' => $email, ':id' => $userId]);
        return $stmt->fetch() !== false;
    }
    
    private function cpfJaExiste($cpf, $userId) {
        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE cpf = :cpf AND id != :id");
        $stmt->execute([':cpf' => $cpf, ':id' => $userId]);
        return $stmt->fetch() !== false;
    }
    
    private function atualizarTipoCliente($userId, $tipo) {
        $stmt = $this->db->prepare("UPDATE usuarios SET tipo_cliente = :tipo WHERE id = :id");
        $stmt->execute([':tipo' => $tipo, ':id' => $userId]);
    }
    
    private function mascaraCPF($cpf) {
        return substr($cpf, 0, 3) . '.***.**' . substr($cpf, -2);
    }
    
    private function cleanPhone($phone) {
        return preg_replace('/[^0-9]/', '', $phone);
    }
    
    private function successResponse($message) {
        return ['success' => true, 'message' => $message];
    }
    
    private function errorResponse($message, $code = 400) {
        return ['success' => false, 'message' => $message, 'code' => $code];
    }
}

// Processar requisição
$api = new WhatsAppCadastroAPI();
$response = $api->handleRequest();

header('Content-Type: application/json');
echo json_encode($response);
?>