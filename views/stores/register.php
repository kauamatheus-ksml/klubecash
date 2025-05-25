<?php
// views/stores/register.php - Versão robusta e funcional
// Como um sistema de segurança em camadas, vamos ativar todos os mecanismos de proteção e debug

// Primeira camada: Ativar exibição de erros para desenvolvimento
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Segunda camada: Função de log personalizada para rastrear cada passo
function debug_log($message) {
    error_log("[STORE_REGISTER] " . $message);
    if (isset($_GET['debug'])) {
        echo "<!-- DEBUG: $message -->\n";
    }
}

debug_log("Iniciando carregamento da página de registro de loja");

// Terceira camada: Carregamento seguro dos arquivos essenciais
$required_files = [
    '../../config/constants.php' => 'Constantes do sistema',
    '../../config/database.php' => 'Conexão com banco de dados', 
    '../../config/email.php' => 'Configurações de email',
    '../../controllers/StoreController.php' => 'Controlador de lojas',
    '../../utils/Validator.php' => 'Validador de dados'
];

// Função para criar diretório se não existir
function createUploadDir($path) {
    if (!file_exists($path)) {
        if (!mkdir($path, 0755, true)) {
            error_log("Não foi possível criar diretório: $path");
            return false;
        }
        debug_log("Diretório criado: $path");
    }
    return true;
}

// Configurar diretórios de upload
$uploadsDir = __DIR__ . '/../../uploads';
$storeLogosDir = $uploadsDir . '/store_logos';

// Criar diretórios se não existirem
createUploadDir($uploadsDir);
createUploadDir($storeLogosDir);

debug_log("Diretórios de upload preparados");

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        require_once $file;
        debug_log("✓ Carregado: $description");
    } else {
        die("❌ Erro crítico: Não foi possível carregar $description ($file)");
    }
}

// Quarta camada: Verificação de classes essenciais
$required_classes = ['StoreController', 'Validator', 'Database', 'Email'];
foreach ($required_classes as $class) {
    if (!class_exists($class)) {
        die("❌ Erro crítico: Classe $class não encontrada");
    }
    debug_log("✓ Classe $class verificada");
}

// Quinta camada: Inicialização segura da sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    debug_log("Sessão iniciada com sucesso");
}

// Sexta camada: Verificação de estado de autenticação
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['user_type']) && $_SESSION['user_type'] == USER_TYPE_ADMIN;

debug_log("Estado de autenticação - Logado: " . ($isLoggedIn ? 'Sim' : 'Não') . ", Admin: " . ($isAdmin ? 'Sim' : 'Não'));

// Sétima camada: Inicialização de variáveis de controle
$error = '';
$success = '';
$data = []; // Array para manter dados do formulário

debug_log("Variáveis de controle inicializadas");
    /**
    * Processa upload de logo da loja
    * Como um fotógrafo profissional que verifica, processa e arquiva fotos
    * 
    * @param array $file Array $_FILES['logo']
    * @param string $storeLogosDir Diretório onde salvar
    * @return array Resultado com status e dados
    */
    function processLogoUpload($file, $storeLogosDir) {
        // Verificar se arquivo foi enviado
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['status' => true, 'filename' => null, 'message' => 'Nenhum arquivo enviado'];
        }
        
        // Verificar erros no upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'Arquivo muito grande (limite do servidor)',
                UPLOAD_ERR_FORM_SIZE => 'Arquivo muito grande (limite do formulário)',
                UPLOAD_ERR_PARTIAL => 'Upload incompleto',
                UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não encontrado',
                UPLOAD_ERR_CANT_WRITE => 'Erro de escrita no disco',
                UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão'
            ];
            
            $message = isset($errorMessages[$file['error']]) ? $errorMessages[$file['error']] : 'Erro desconhecido no upload';
            return ['status' => false, 'message' => $message];
        }
        
        // Validar tamanho (2MB máximo)
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxSize) {
            return ['status' => false, 'message' => 'Arquivo muito grande. Máximo: 2MB'];
        }
        
        // Validar tipo de arquivo
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return ['status' => false, 'message' => 'Tipo de arquivo não permitido. Use JPG, PNG ou GIF'];
        }
        
        // Gerar nome único para o arquivo
        // Como dar um número de protocolo único para cada documento
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueName = 'logo_' . uniqid() . '_' . time() . '.' . strtolower($extension);
        $destinationPath = $storeLogosDir . '/' . $uniqueName;
        
        // Mover arquivo para destino final
        if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
            return ['status' => false, 'message' => 'Erro ao salvar arquivo no servidor'];
        }
        
        // Verificar se arquivo foi salvo corretamente
        if (!file_exists($destinationPath)) {
            return ['status' => false, 'message' => 'Arquivo não foi salvo corretamente'];
        }
        
        return [
            'status' => true, 
            'filename' => $uniqueName,
            'path' => $destinationPath,
            'url' => '/uploads/store_logos/' . $uniqueName,
            'message' => 'Logo enviada com sucesso'
        ];
    }
// Oitava camada: Processamento do formulário (quando enviado)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    debug_log("Processando envio do formulário com possível upload de logo");
    
    try {
        $logoResult = processLogoUpload($_FILES['logo'] ?? null, $storeLogosDir);
        
        if (!$logoResult['status'] && $logoResult['filename'] !== null) {
            // Se houve erro no upload (mas não por ausência de arquivo)
            $error = "Erro no upload da logo: " . $logoResult['message'];
            debug_log("Erro no upload: " . $logoResult['message']);
        } else {
            debug_log("Upload processado: " . ($logoResult['filename'] ? 'Arquivo salvo' : 'Nenhum arquivo'));
            
            // Adicionar informações da logo aos dados
            if ($logoResult['filename']) {
                $data['logo'] = $logoResult['filename'];
                $data['logo_url'] = $logoResult['url'];
                debug_log("Logo será salva como: " . $logoResult['filename']);
            }
        }

        // Como um filtro de água que remove impurezas, vamos limpar e validar cada campo
        // Capturar e sanitizar dados - usando métodos modernos como um sistema de segurança atualizado
        $data = [
            'nome_fantasia' => trim(htmlspecialchars($_POST['nome_fantasia'] ?? '', ENT_QUOTES, 'UTF-8')),
            'razao_social' => trim(htmlspecialchars($_POST['razao_social'] ?? '', ENT_QUOTES, 'UTF-8')),
            'cnpj' => trim(htmlspecialchars($_POST['cnpj'] ?? '', ENT_QUOTES, 'UTF-8')),
            'email' => trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL)),
            'telefone' => trim(htmlspecialchars($_POST['telefone'] ?? '', ENT_QUOTES, 'UTF-8')),
            'senha' => $_POST['senha'] ?? '', // Senhas não devem ser sanitizadas para preservar caracteres especiais
            'confirma_senha' => $_POST['confirma_senha'] ?? '',
            'categoria' => trim(htmlspecialchars($_POST['categoria'] ?? '', ENT_QUOTES, 'UTF-8')),
            'descricao' => trim(htmlspecialchars($_POST['descricao'] ?? '', ENT_QUOTES, 'UTF-8')),
            'website' => trim(filter_var($_POST['website'] ?? '', FILTER_SANITIZE_URL)),
            'endereco' => [
                'cep' => trim(htmlspecialchars($_POST['cep'] ?? '', ENT_QUOTES, 'UTF-8')),
                'logradouro' => trim(htmlspecialchars($_POST['logradouro'] ?? '', ENT_QUOTES, 'UTF-8')),
                'numero' => trim(htmlspecialchars($_POST['numero'] ?? '', ENT_QUOTES, 'UTF-8')),
                'complemento' => trim(htmlspecialchars($_POST['complemento'] ?? '', ENT_QUOTES, 'UTF-8')),
                'bairro' => trim(htmlspecialchars($_POST['bairro'] ?? '', ENT_QUOTES, 'UTF-8')),
                'cidade' => trim(htmlspecialchars($_POST['cidade'] ?? '', ENT_QUOTES, 'UTF-8')),
                'estado' => trim(htmlspecialchars($_POST['estado'] ?? '', ENT_QUOTES, 'UTF-8'))
            ]
        ];
        
        debug_log("Dados do formulário capturados e sanitizados");
        
        // Como um checklist de segurança, vamos validar cada campo obrigatório
        $errors = [];
        
        // Validações básicas - como verificar se todos os ingredientes estão na receita
        if (empty($data['nome_fantasia'])) $errors[] = 'Nome fantasia é obrigatório';
        if (empty($data['razao_social'])) $errors[] = 'Razão social é obrigatória';
        if (empty($data['cnpj'])) $errors[] = 'CNPJ é obrigatório';
        
        // Validação de email - como verificar se um endereço postal está no formato correto
        if (empty($data['email'])) {
            $errors[] = 'Email é obrigatório';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido';
        }
        
        if (empty($data['telefone'])) $errors[] = 'Telefone é obrigatório';
        if (empty($data['categoria'])) $errors[] = 'Categoria é obrigatória';
        
        // Validações de senha - como verificar se uma chave tem os elementos de segurança necessários
        if (empty($data['senha'])) {
            $errors[] = 'Senha é obrigatória';
        } elseif (strlen($data['senha']) < 8) {
            $errors[] = 'A senha deve ter pelo menos 8 caracteres';
        }
        
        if (empty($data['confirma_senha'])) {
            $errors[] = 'Confirmação de senha é obrigatória';
        } elseif ($data['senha'] !== $data['confirma_senha']) {
            $errors[] = 'As senhas não coincidem';
        }
        
        // Validações de endereço - como verificar se um endereço está completo
        $endereco_obrigatorios = ['cep', 'logradouro', 'numero', 'bairro', 'cidade', 'estado'];
        foreach ($endereco_obrigatorios as $campo) {
            if (empty($data['endereco'][$campo])) {
                $errors[] = ucfirst($campo) . ' é obrigatório';
            }
        }
        
        debug_log("Validação concluída. Erros encontrados: " . count($errors));
        
        // Se passou por todas as validações, como um carro que passou pela inspeção
        if (empty($errors)) {
            debug_log("Iniciando processo de registro da loja");
            
            // Limpar CNPJ - como remover pontuação de um documento
            $data['cnpj'] = preg_replace('/[^0-9]/', '', $data['cnpj']);
            
            // Chamar o controlador para registrar a loja
            $result = StoreController::registerStore($data);
            
            debug_log("Resultado do registro: " . ($result['status'] ? 'Sucesso' : 'Falha'));
            
            if ($result['status']) {
                $success = $result['message'];
                // Limpar dados do formulário após sucesso - como limpar a mesa depois de comer
                $data = [];
                debug_log("Cadastro realizado com sucesso, formulário limpo");
            } else {
                $error = $result['message'];
                debug_log("Erro no cadastro: " . $result['message']);
            }
        } else {
            // Juntar todos os erros em uma mensagem - como um relatório de problemas encontrados
            $error = implode('<br>', $errors);
            debug_log("Erros de validação: " . implode(', ', $errors));
        }
        
    } catch (Exception $e) {
        // Limpar arquivo de logo se algo der errado
        if (isset($logoResult['path']) && file_exists($logoResult['path'])) {
            unlink($logoResult['path']);
            debug_log("Arquivo de logo removido devido a erro no cadastro");
        }
        
        $error = "Erro interno: " . $e->getMessage();
        debug_log("Exceção capturada: " . $e->getMessage());
        error_log("Erro no cadastro de loja: " . $e->getMessage());
    }
}

// Nona camada: Preparar dados para os elementos de seleção
$categorias = [
    'Alimentação', 'Vestuário', 'Eletrônicos', 'Casa e Decoração', 
    'Beleza e Saúde', 'Serviços', 'Educação', 'Entretenimento', 'Outros'
];

$estados = [
    'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
    'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
    'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
    'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
    'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
    'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
    'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins'
];

debug_log("Dados de seleção preparados, iniciando renderização da página");

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Loja Parceira - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <style>
        /* Estilos CSS mantidos exatamente como estavam - funcionam perfeitamente */
        :root {
            --primary-color: #FF7A00;
            --primary-light: #FFF0E6;
            --white: #FFFFFF;
            --light-gray: #F5F5F5;
            --dark-gray: #333333;
            --medium-gray: #666666;
            --success-color: #4CAF50;
            --danger-color: #F44336;
            --warning-color: #FF9800;
            --border-radius: 15px;
            --shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-family);
        }
        
        body {
            background-color: #FFF9F2;
            color: var(--dark-gray);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 80px auto 40px;
            padding: 0 20px;
        }
        
        .page-header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .page-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            color: var(--dark-gray);
        }
        
        .page-header p {
            font-size: 18px;
            color: var(--medium-gray);
        }
        
        .form-container {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 30px;
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 20px;
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--primary-light);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark-gray);
        }
        
        .required {
            color: var(--danger-color);
        }
        
        .form-control, .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 122, 0, 0.2);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .form-text {
            font-size: 12px;
            color: var(--medium-gray);
            margin-top: 5px;
        }
        
        .form-terms {
            margin: 25px 0;
            padding: 15px;
            background-color: var(--light-gray);
            border-radius: 8px;
        }
        
        .form-terms h3 {
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 15px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-top: 2px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .btn:hover {
            background-color: #E06E00;
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            background-color: var(--medium-gray);
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background-color: #FFEAE6;
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }
        
        .alert-success {
            background-color: #E6F7E6;
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }
        
        .form-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .commission-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .commission-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            background-color: white;
            border-radius: 6px;
            border-left: 3px solid #FF7A00;
        }
        
        .commission-icon {
            font-size: 1.5rem;
            margin-top: 2px;
        }
        
        .commission-details strong {
            color: #2A3F54;
            display: block;
            margin-bottom: 5px;
        }
        
        .commission-details ul {
            margin: 8px 0 0 20px;
            padding: 0;
        }
        
        .commission-details li {
            margin-bottom: 4px;
        }
        

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .form-control[type="file"] {
            position: relative;
            background: white;
            border: 2px dashed var(--primary-color);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .form-control[type="file"]:hover {
            background-color: var(--primary-light);
            border-color: #E06E00;
        }

        .form-control[type="file"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.2);
        }

        #logo-preview {
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        #logo-preview-img {
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .upload-info {
            background-color: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 6px;
            padding: 12px;
            margin-top: 10px;
        }

        .upload-info h4 {
            margin: 0 0 8px 0;
            color: #0066cc;
            font-size: 14px;
        }

        .upload-info ul {
            margin: 0;
            padding-left: 20px;
            font-size: 13px;
            color: #555;
        }
        

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .container {
                margin: 60px auto 20px;
                padding: 0 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Incluir navbar de forma segura -->
    <?php 
    $navbar_path = '../components/navbar.php';
    if (file_exists($navbar_path)) {
        include_once $navbar_path; 
        debug_log("Navbar carregada com sucesso");
    } else {
        debug_log("Navbar não encontrada, continuando sem ela");
        // Criar uma navbar básica temporária
        echo '<nav style="background: #FF7A00; padding: 15px; color: white; text-align: center; margin-bottom: 20px;">';
        echo '<h2>Klube Cash - Cadastro de Loja Parceira</h2>';
        echo '</nav>';
    }
    ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Torne-se uma Loja Parceira</h1>
            <p>Aumente suas vendas oferecendo cashback aos seus clientes</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <strong>Atenção:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <strong>Sucesso!</strong> <?php echo htmlspecialchars($success); ?>
                <p style="margin-top: 10px;">
                    <strong>Próximos passos:</strong><br>
                    • Sua solicitação foi recebida e está em análise<br>
                    • Você receberá um email quando sua loja for aprovada<br>
                    • Após aprovação, poderá fazer login no sistema com o email e senha cadastrados
                </p>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="post" action="" id="store-form" enctype="multipart/form-data">
                <h2 class="section-title">Informações da Empresa</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="nome_fantasia">Nome Fantasia <span class="required">*</span></label>
                        <input type="text" id="nome_fantasia" name="nome_fantasia" class="form-control" required 
                               value="<?php echo isset($data['nome_fantasia']) ? htmlspecialchars($data['nome_fantasia']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="razao_social">Razão Social <span class="required">*</span></label>
                        <input type="text" id="razao_social" name="razao_social" class="form-control" required 
                               value="<?php echo isset($data['razao_social']) ? htmlspecialchars($data['razao_social']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="cnpj">CNPJ <span class="required">*</span></label>
                        <input type="text" id="cnpj" name="cnpj" class="form-control" required 
                               value="<?php echo isset($data['cnpj']) ? htmlspecialchars($data['cnpj']) : ''; ?>" 
                               placeholder="XX.XXX.XXX/XXXX-XX">
                        <small class="form-text">Digite apenas números ou formato XX.XXX.XXX/XXXX-XX</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="categoria">Categoria/Segmento <span class="required">*</span></label>
                        <select id="categoria" name="categoria" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo htmlspecialchars($categoria); ?>" 
                                        <?php echo (isset($data['categoria']) && $data['categoria'] == $categoria) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <h2 class="section-title">Contato</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="email">E-mail <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" required 
                               value="<?php echo isset($data['email']) ? htmlspecialchars($data['email']) : ''; ?>">
                        <small class="form-text">Este será seu email de acesso ao sistema</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="telefone">Telefone <span class="required">*</span></label>
                        <input type="tel" id="telefone" name="telefone" class="form-control" required 
                               value="<?php echo isset($data['telefone']) ? htmlspecialchars($data['telefone']) : ''; ?>" 
                               placeholder="(XX) XXXXX-XXXX">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="website">Website</label>
                    <input type="url" id="website" name="website" class="form-control" 
                           value="<?php echo isset($data['website']) ? htmlspecialchars($data['website']) : ''; ?>" 
                           placeholder="https://www.suaempresa.com.br">
                </div>
                <div class="form-group">
                    <label class="form-label" for="logo">Logo da Loja</label>
                    
                    <div class="file-input-wrapper">
                        <input type="file" id="logo" name="logo" class="form-control" accept="image/*">
                    </div>
                    
                    <div class="upload-info">
                        <h4>📎 Informações sobre a Logo:</h4>
                        <ul>
                            <li>Formatos aceitos: JPG, PNG, GIF</li>
                            <li>Tamanho máximo: 2MB</li>
                            <li>Dimensões recomendadas: 300x300px (quadrada) ou 400x200px (retangular)</li>
                            <li>A logo será exibida no catálogo de lojas parceiras</li>
                        </ul>
                    </div>
                    
                    <div id="logo-preview" style="display: none;">
                        <h4>Preview da Logo:</h4>
                        <img id="logo-preview-img" alt="Preview da logo">
                    </div>
                </div>
                <h2 class="section-title">Dados de Acesso</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="senha">Senha de Acesso <span class="required">*</span></label>
                        <input type="password" id="senha" name="senha" class="form-control" required minlength="8">
                        <small class="form-text">Mínimo de 8 caracteres. Use letras, números e símbolos para maior segurança.</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="confirma_senha">Confirme a Senha <span class="required">*</span></label>
                        <input type="password" id="confirma_senha" name="confirma_senha" class="form-control" required minlength="8">
                        <small class="form-text">Digite novamente sua senha para confirmação.</small>
                    </div>
                </div>
                
                <h2 class="section-title">Endereço</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="cep">CEP <span class="required">*</span></label>
                        <input type="text" id="cep" name="cep" class="form-control" required 
                               value="<?php echo isset($data['endereco']['cep']) ? htmlspecialchars($data['endereco']['cep']) : ''; ?>" 
                               placeholder="XXXXX-XXX">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="logradouro">Logradouro <span class="required">*</span></label>
                        <input type="text" id="logradouro" name="logradouro" class="form-control" required 
                               value="<?php echo isset($data['endereco']['logradouro']) ? htmlspecialchars($data['endereco']['logradouro']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="numero">Número <span class="required">*</span></label>
                        <input type="text" id="numero" name="numero" class="form-control" required 
                               value="<?php echo isset($data['endereco']['numero']) ? htmlspecialchars($data['endereco']['numero']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="complemento">Complemento</label>
                        <input type="text" id="complemento" name="complemento" class="form-control" 
                               value="<?php echo isset($data['endereco']['complemento']) ? htmlspecialchars($data['endereco']['complemento']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="bairro">Bairro <span class="required">*</span></label>
                        <input type="text" id="bairro" name="bairro" class="form-control" required 
                               value="<?php echo isset($data['endereco']['bairro']) ? htmlspecialchars($data['endereco']['bairro']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="cidade">Cidade <span class="required">*</span></label>
                        <input type="text" id="cidade" name="cidade" class="form-control" required 
                               value="<?php echo isset($data['endereco']['cidade']) ? htmlspecialchars($data['endereco']['cidade']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="estado">Estado <span class="required">*</span></label>
                        <select id="estado" name="estado" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($estados as $uf => $nomeEstado): ?>
                                <option value="<?php echo $uf; ?>" 
                                        <?php echo (isset($data['endereco']['estado']) && $data['endereco']['estado'] == $uf) ? 'selected' : ''; ?>>
                                    <?php echo $nomeEstado; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <h2 class="section-title">Configurações de Cashback</h2>
                
                <div class="form-info">
                    <h3>📊 Informações sobre Comissão</h3>
                    <div class="commission-info">
                        <div class="commission-item">
                            <span class="commission-icon">💳</span>
                            <div class="commission-details">
                                <strong>Comissão: 10% por venda</strong>
                                <p>Você paga 10% sobre cada venda que será distribuído:</p>
                                <ul>
                                    <li>5% para o cliente (cashback)</li>
                                    <li>5% para o Klube Cash (nossa receita)</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="commission-item">
                            <span class="commission-icon">🔄</span>
                            <div class="commission-details">
                                <strong>Saldo do cliente</strong>
                                <p>O cashback que o cliente recebe só pode ser usado na sua loja, gerando fidelização e novas vendas.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="descricao">Descrição da Loja</label>
                    <textarea id="descricao" name="descricao" class="form-control" rows="4" 
                              placeholder="Conte um pouco sobre sua loja, produtos oferecidos, diferenciais..."><?php echo isset($data['descricao']) ? htmlspecialchars($data['descricao']) : ''; ?></textarea>
                    <small class="form-text">Esta descrição será exibida para os clientes no catálogo de lojas parceiras.</small>
                </div>
                
                <div class="form-terms">
                    <h3>Termos e Condições</h3>
                    <p>Ao se cadastrar como loja parceira, você concorda com os seguintes termos:</p>
                    <ul>
                        <li>O Klube Cash analisará sua solicitação e pode aprová-la ou rejeitá-la de acordo com nossos critérios.</li>
                        <li>Você se compromete a oferecer o cashback conforme a porcentagem cadastrada (10%).</li>
                        <li>Sua loja será exibida no catálogo de parceiros após aprovação.</li>
                        <li>Todas as transações de cashback devem ser processadas através do nosso sistema.</li>
                        <li>Você terá acesso a um painel para gerenciar suas transações e relatórios.</li>
                        <li>Sua conta de usuário será ativada automaticamente quando a loja for aprovada.</li>
                        <li>O Klube Cash se reserva o direito de cancelar a parceria em caso de violação dos termos.</li>
                    </ul>
                    <div class="checkbox-group">
                        <input type="checkbox" id="aceite_termos" name="aceite_termos" required>
                        <label for="aceite_termos">Li e concordo com os termos e condições acima <span class="required">*</span></label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-block" id="submit-btn">Cadastrar Loja</button>
            </form>
        </div>
    </div>
    
    <!-- JavaScript para máscaras e validações mantido exatamente como estava -->
    <script>
        // Como um tradutor que converte a linguagem do usuário para a linguagem do computador
        document.getElementById('logo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('logo-preview');
            const previewImg = document.getElementById('logo-preview-img');
            
            if (file) {
                // Verificar tamanho do arquivo (2MB = 2 * 1024 * 1024 bytes)
                const maxSize = 2 * 1024 * 1024;
                
                if (file.size > maxSize) {
                    alert('Arquivo muito grande! O tamanho máximo é 2MB.');
                    this.value = ''; // Limpar seleção
                    preview.style.display = 'none';
                    return;
                }
                
                // Verificar tipo de arquivo
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Tipo de arquivo não permitido! Use apenas JPG, PNG ou GIF.');
                    this.value = ''; // Limpar seleção
                    preview.style.display = 'none';
                    return;
                }
                
                // Mostrar preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
        // Máscara para o CNPJ - formatação automática enquanto digita
        document.getElementById('cnpj').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length <= 14) {
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            }
            
            e.target.value = value;
        });
        
        // Máscara para o telefone - formatação automática
        document.getElementById('telefone').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length <= 11) {
                if (value.length > 2) {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2);
                }
                if (value.length > 10) {
                    value = value.substring(0, 10) + '-' + value.substring(10);
                }
            }
            
            e.target.value = value;
        });
        
        // Máscara para o CEP - formatação automática
        document.getElementById('cep').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 5) {
                value = value.replace(/^(\d{5})(\d)/, '$1-$2');
            }
            
            e.target.value = value;
        });
        
        // Preenchimento automático do endereço pelo CEP - como um GPS que encontra o local
        document.getElementById('cep').addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            
            if (cep.length !== 8) return;
            
            // Indicador visual de carregamento
            this.style.backgroundColor = '#f0f0f0';
            
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (!data.erro) {
                        // Preenchimento automático dos campos como um assistente pessoal
                        document.getElementById('logradouro').value = data.logradouro || '';
                        document.getElementById('bairro').value = data.bairro || '';
                        document.getElementById('cidade').value = data.localidade || '';
                        document.getElementById('estado').value = data.uf || '';
                        
                        // Focar no próximo campo lógico
                        if (data.logradouro) {
                            document.getElementById('numero').focus();
                        }
                    } else {
                        alert('CEP não encontrado. Verifique se o CEP está correto.');
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar CEP:', error);
                    alert('Erro ao buscar CEP. Verifique sua conexão e tente novamente.');
                })
                .finally(() => {
                    this.style.backgroundColor = '';
                });
        });
        
        // Validação em tempo real das senhas - como um verificador de segurança
        function validatePasswords() {
            const senha = document.getElementById('senha').value;
            const confirmaSenha = document.getElementById('confirma_senha').value;
            
            if (confirmaSenha.length === 0) {
                document.getElementById('confirma_senha').setCustomValidity('');
                return true;
            }
            
            if (senha !== confirmaSenha) {
                document.getElementById('confirma_senha').setCustomValidity('As senhas não coincidem');
                return false;
            } else {
                document.getElementById('confirma_senha').setCustomValidity('');
                return true;
            }
        }

        // Aplicar validação em tempo real
        document.getElementById('confirma_senha').addEventListener('input', validatePasswords);
        document.getElementById('senha').addEventListener('input', validatePasswords);

        // Validação final antes do envio - como uma última verificação antes de uma viagem
        document.getElementById('store-form').addEventListener('submit', function(event) {
            const submitBtn = document.getElementById('submit-btn');
            
            // Verificar senhas
            if (!validatePasswords()) {
                event.preventDefault();
                alert('Por favor, certifique-se de que as senhas coincidem.');
                return false;
            }
            
            // Verificar campos obrigatórios
            const requiredFields = this.querySelectorAll('[required]');
            for (let field of requiredFields) {
                if (!field.value.trim()) {
                    field.focus();
                    alert(`Por favor, preencha o campo: ${field.previousElementSibling.textContent.replace(' *', '')}`);
                    event.preventDefault();
                    return false;
                }
            }
            
            // Desabilitar botão para evitar envios duplos - como trancar a porta depois de sair
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processando...';
            
            return true;
        });
    </script>

    <?php debug_log("Página renderizada com sucesso"); ?>
</body>
</html>