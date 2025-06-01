<?php
// views/stores/register.php - Versão 3.0 - Cadastro Linear Intuitivo

// Manter toda a lógica de processamento existente
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

function debug_log($message) {
    error_log("[STORE_REGISTER] " . $message);
    if (isset($_GET['debug'])) {
        echo "<!-- DEBUG: $message -->\n";
    }
}

debug_log("Iniciando nova versão do cadastro de loja");

// Carregamento dos arquivos necessários (mantendo a estrutura existente)
$required_files = [
    '../../config/constants.php' => 'Constantes do sistema',
    '../../config/database.php' => 'Conexão com banco de dados', 
    '../../config/email.php' => 'Configurações de email',
    '../../controllers/StoreController.php' => 'Controlador de lojas',
    '../../utils/Validator.php' => 'Validador de dados'
];

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

$uploadsDir = __DIR__ . '/../../uploads';
$storeLogosDir = $uploadsDir . '/store_logos';

createUploadDir($uploadsDir);
createUploadDir($storeLogosDir);

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        require_once $file;
        debug_log("✓ Carregado: $description");
    } else {
        die("❌ Erro crítico: Não foi possível carregar $description ($file)");
    }
}

$required_classes = ['StoreController', 'Validator', 'Database', 'Email'];
foreach ($required_classes as $class) {
    if (!class_exists($class)) {
        die("❌ Erro crítico: Classe $class não encontrada");
    }
    debug_log("✓ Classe $class verificada");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    debug_log("Sessão iniciada com sucesso");
}

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['user_type']) && $_SESSION['user_type'] == USER_TYPE_ADMIN;

$error = '';
$success = '';
$data = [];

// Função de processamento do upload (mantendo a lógica existente)
function processLogoUpload($file, $storeLogosDir) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['status' => true, 'filename' => null, 'message' => 'Nenhum arquivo enviado'];
    }
    
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
    
    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $maxSize) {
        return ['status' => false, 'message' => 'Arquivo muito grande. Máximo: 2MB'];
    }
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['status' => false, 'message' => 'Tipo de arquivo não permitido. Use JPG, PNG ou GIF'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $uniqueName = 'logo_' . uniqid() . '_' . time() . '.' . strtolower($extension);
    $destinationPath = $storeLogosDir . '/' . $uniqueName;
    
    if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
        return ['status' => false, 'message' => 'Erro ao salvar arquivo no servidor'];
    }
    
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

// Processamento do formulário (mantendo toda a lógica de validação existente)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    debug_log("Processando envio do formulário com possível upload de logo");
    
    try {
        $logoResult = processLogoUpload($_FILES['logo'] ?? null, $storeLogosDir);
        
        if (!$logoResult['status'] && $logoResult['filename'] !== null) {
            $error = "Erro no upload da logo: " . $logoResult['message'];
            debug_log("Erro no upload: " . $logoResult['message']);
        } else {
            debug_log("Upload processado: " . ($logoResult['filename'] ? 'Arquivo salvo' : 'Nenhum arquivo'));
            
            if ($logoResult['filename']) {
                $data['logo'] = $logoResult['filename'];
                $data['logo_url'] = $logoResult['url'];
                debug_log("Logo será salva como: " . $logoResult['filename']);
            }
        }

        // Capturar e sanitizar dados (mantendo a lógica existente)
        $data = [
            'nome_fantasia' => trim(htmlspecialchars($_POST['nome_fantasia'] ?? '', ENT_QUOTES, 'UTF-8')),
            'razao_social' => trim(htmlspecialchars($_POST['razao_social'] ?? '', ENT_QUOTES, 'UTF-8')),
            'cnpj' => trim(htmlspecialchars($_POST['cnpj'] ?? '', ENT_QUOTES, 'UTF-8')),
            'email' => trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL)),
            'telefone' => trim(htmlspecialchars($_POST['telefone'] ?? '', ENT_QUOTES, 'UTF-8')),
            'senha' => $_POST['senha'] ?? '',
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
        
        // Validações (mantendo a lógica existente)
        $errors = [];
        
        if (empty($data['nome_fantasia'])) $errors[] = 'Nome fantasia é obrigatório';
        if (empty($data['razao_social'])) $errors[] = 'Razão social é obrigatória';
        if (empty($data['cnpj'])) $errors[] = 'CNPJ é obrigatório';
        
        if (empty($data['email'])) {
            $errors[] = 'Email é obrigatório';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido';
        }
        
        if (empty($data['telefone'])) $errors[] = 'Telefone é obrigatório';
        if (empty($data['categoria'])) $errors[] = 'Categoria é obrigatória';
        
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
        
        $endereco_obrigatorios = ['cep', 'logradouro', 'numero', 'bairro', 'cidade', 'estado'];
        foreach ($endereco_obrigatorios as $campo) {
            if (empty($data['endereco'][$campo])) {
                $errors[] = ucfirst($campo) . ' é obrigatório';
            }
        }
        
        debug_log("Validação concluída. Erros encontrados: " . count($errors));
        
        if (empty($errors)) {
            debug_log("Iniciando processo de registro da loja");
            
            $data['cnpj'] = preg_replace('/[^0-9]/', '', $data['cnpj']);
            
            $result = StoreController::registerStore($data);
            
            debug_log("Resultado do registro: " . ($result['status'] ? 'Sucesso' : 'Falha'));
            
            if ($result['status']) {
                $success = $result['message'];
                $data = [];
                debug_log("Cadastro realizado com sucesso, formulário limpo");
            } else {
                $error = $result['message'];
                debug_log("Erro no cadastro: " . $result['message']);
            }
        } else {
            $error = implode('<br>', $errors);
            debug_log("Erros de validação: " . implode(', ', $errors));
        }
        
    } catch (Exception $e) {
        if (isset($logoResult['path']) && file_exists($logoResult['path'])) {
            unlink($logoResult['path']);
            debug_log("Arquivo de logo removido devido a erro no cadastro");
        }
        
        $error = "Erro interno: " . $e->getMessage();
        debug_log("Exceção capturada: " . $e->getMessage());
        error_log("Erro no cadastro de loja: " . $e->getMessage());
    }
}

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

debug_log("Dados de seleção preparados, iniciando renderização da nova página");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastre sua Loja - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    
    <!-- Fontes modernas -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* === RESET E VARIÁVEIS === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #FF7A00;
            --primary-dark: #E06E00;
            --primary-light: #FFF0E6;
            --success-color: #10B981;
            --error-color: #EF4444;
            --warning-color: #F59E0B;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
            --white: #FFFFFF;
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #FFF5E6 0%, #FFE5CC 100%);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* === CONTAINER PRINCIPAL === */
        .main-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
        }

        /* === HEADER === */
        .form-header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeInDown 0.6s ease-out;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-lg);
        }

        .logo i {
            font-size: 40px;
            color: white;
        }

        .form-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 12px;
        }

        .form-subtitle {
            font-size: 1.1rem;
            color: var(--gray-600);
            max-width: 600px;
            margin: 0 auto;
        }

        /* === PROGRESS BAR === */
        .progress-container {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            animation: fadeInUp 0.6s ease-out 0.1s both;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            position: relative;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gray-200);
            z-index: 1;
            transform: translateY(-50%);
        }

        .progress-line {
            position: absolute;
            top: 50%;
            left: 0;
            height: 3px;
            background: var(--primary-color);
            z-index: 2;
            transform: translateY(-50%);
            transition: width 0.5s ease;
            width: 0%;
        }

        .step-indicator {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            position: relative;
            z-index: 3;
            transition: var(--transition);
            color: var(--gray-500);
        }

        .step-indicator.active {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }

        .step-indicator.completed {
            background: var(--success-color);
            color: white;
        }

        .step-indicator.completed i {
            font-size: 20px;
        }

        .step-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        .step-label {
            text-align: center;
            font-size: 0.9rem;
            color: var(--gray-600);
            font-weight: 500;
            flex: 1;
            padding: 0 10px;
        }

        .step-label.active {
            color: var(--primary-color);
            font-weight: 600;
        }

        /* === FORMULÁRIO === */
        .form-container {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }

        .form-step {
            display: none;
            padding: 40px;
            animation: slideInRight 0.4s ease-out;
        }

        .form-step.active {
            display: block;
        }

        .step-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .step-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-md);
        }

        .step-icon i {
            font-size: 30px;
            color: white;
        }

        .step-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 8px;
        }

        .step-description {
            color: var(--gray-600);
            font-size: 1rem;
        }

        /* === CAMPOS DO FORMULÁRIO === */
        .form-grid {
            display: grid;
            gap: 25px;
            margin-bottom: 30px;
        }

        .form-grid.two-columns {
            grid-template-columns: 1fr 1fr;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            color: var(--primary-color);
            font-size: 16px;
        }

        .required {
            color: var(--error-color);
            margin-left: 4px;
        }

        .form-input {
            width: 100%;
            padding: 16px;
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--white);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.1);
            transform: translateY(-1px);
        }

        .form-input:valid {
            border-color: var(--success-color);
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 40px;
        }

        /* === UPLOAD DE ARQUIVO === */
        .file-upload-area {
            border: 2px dashed var(--gray-300);
            border-radius: var(--border-radius);
            padding: 40px 20px;
            text-align: center;
            background: var(--gray-50);
            transition: var(--transition);
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: var(--primary-color);
            background: var(--primary-light);
        }

        .file-upload-area.dragover {
            border-color: var(--primary-color);
            background: var(--primary-light);
            transform: scale(1.02);
        }

        .upload-icon {
            font-size: 48px;
            color: var(--gray-400);
            margin-bottom: 15px;
        }

        .upload-text {
            color: var(--gray-600);
            font-size: 1rem;
            margin-bottom: 10px;
        }

        .upload-hint {
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        .file-preview {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: var(--success-color);
            color: white;
            border-radius: var(--border-radius);
            text-align: center;
        }

        .file-preview.show {
            display: block;
            animation: fadeInUp 0.3s ease-out;
        }

        /* === INFORMAÇÕES ESPECIAIS === */
        .info-box {
            background: var(--primary-light);
            border: 1px solid var(--primary-color);
            border-radius: var(--border-radius);
            padding: 20px;
            margin: 25px 0;
        }

        .info-box-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .info-box-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .info-box-title {
            font-weight: 600;
            color: var(--gray-800);
            font-size: 1.1rem;
        }

        .info-box-content {
            color: var(--gray-700);
            line-height: 1.6;
        }

        .info-list {
            list-style: none;
            padding: 0;
        }

        .info-list li {
            padding: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-list li i {
            color: var(--primary-color);
            width: 16px;
        }

        /* === BOTÕES === */
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 30px 40px;
            background: var(--gray-50);
            border-top: 1px solid var(--gray-200);
        }

        .btn {
            padding: 16px 32px;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .btn-secondary:hover {
            background: var(--gray-300);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        /* === RESUMO FINAL === */
        .summary-section {
            margin-bottom: 30px;
        }

        .summary-card {
            background: var(--gray-50);
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 20px;
        }

        .summary-title {
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-title i {
            color: var(--primary-color);
        }

        .summary-grid {
            display: grid;
            gap: 12px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-label {
            color: var(--gray-600);
            font-weight: 500;
        }

        .summary-value {
            color: var(--gray-800);
            font-weight: 600;
        }

        /* === ALERTAS === */
        .alert {
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            animation: slideInDown 0.4s ease-out;
        }

        .alert-success {
            background: #ECFDF5;
            border: 1px solid var(--success-color);
            color: #065F46;
        }

        .alert-error {
            background: #FEF2F2;
            border: 1px solid var(--error-color);
            color: #991B1B;
        }

        .alert-icon {
            font-size: 20px;
            margin-top: 2px;
        }

        /* === RESPONSIVIDADE === */
        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }

            .form-title {
                font-size: 2rem;
            }

            .progress-container {
                padding: 20px;
            }

            .step-indicator {
                width: 40px;
                height: 40px;
                font-size: 14px;
            }

            .step-label {
                font-size: 0.8rem;
            }

            .form-step {
                padding: 25px;
            }

            .form-grid.two-columns {
                grid-template-columns: 1fr;
            }

            .form-actions {
                padding: 20px;
                flex-direction: column;
                gap: 15px;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .step-title {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .progress-steps {
                padding: 0 10px;
            }

            .step-label {
                display: none;
            }

            .form-input {
                padding: 14px;
            }

            .file-upload-area {
                padding: 30px 15px;
            }
        }

        /* === ANIMAÇÕES === */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* === ESTADOS DE VALIDAÇÃO === */
        .form-input.valid {
            border-color: var(--success-color);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2310B981'%3e%3cpath fill-rule='evenodd' d='M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z' clip-rule='evenodd'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 20px;
            padding-right: 40px;
        }

        .form-input.invalid {
            border-color: var(--error-color);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%23EF4444'%3e%3cpath fill-rule='evenodd' d='M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z' clip-rule='evenodd'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 20px;
            padding-right: 40px;
        }

        .error-message {
            color: var(--error-color);
            font-size: 0.875rem;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .success-message {
            color: var(--success-color);
            font-size: 0.875rem;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* === CARREGAMENTO === */
        .loading {
            display: none;
            align-items: center;
            gap: 10px;
        }

        .loading.show {
            display: flex;
        }

        .loading-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid var(--gray-300);
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <div class="main-container">
        <!-- Header -->
        <div class="form-header">
            <div class="logo">
                <i class="fas fa-handshake"></i>
            </div>
            <h1 class="form-title">Torne-se um Parceiro</h1>
            <p class="form-subtitle">
                Transforme sua loja em uma fonte de economia para seus clientes e aumente suas vendas com o programa de cashback mais inovador do Brasil.
            </p>
        </div>

        <!-- Alertas -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle alert-icon"></i>
                <div>
                    <strong>Ops! Algo deu errado:</strong><br>
                    <?php echo $error; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle alert-icon"></i>
                <div>
                    <strong>Sucesso!</strong><br>
                    <?php echo htmlspecialchars($success); ?>
                    <div style="margin-top: 15px; font-size: 0.9rem;">
                        <strong>Próximos passos:</strong><br>
                        • Sua solicitação foi recebida e está em análise<br>
                        • Você receberá um email quando sua loja for aprovada<br>
                        • Após aprovação, poderá fazer login no sistema com o email e senha cadastrados
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Progress Bar -->
        <div class="progress-container">
            <div class="progress-steps">
                <div class="progress-line"></div>
                <div class="step-indicator active" data-step="1">1</div>
                <div class="step-indicator" data-step="2">2</div>
                <div class="step-indicator" data-step="3">3</div>
                <div class="step-indicator" data-step="4">4</div>
                <div class="step-indicator" data-step="5">5</div>
            </div>
            <div class="step-labels">
                <div class="step-label active">Identificação</div>
                <div class="step-label">Contato</div>
                <div class="step-label">Acesso</div>
                <div class="step-label">Endereço</div>
                <div class="step-label">Confirmação</div>
            </div>
        </div>

        <!-- Formulário -->
        <form id="storeRegistrationForm" method="post" action="" enctype="multipart/form-data">
            <div class="form-container">
                <!-- Etapa 1: Identificação -->
                <div class="form-step active" data-step="1">
                    <div class="step-header">
                        <div class="step-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <h2 class="step-title">Identificação da Empresa</h2>
                        <p class="step-description">
                            Vamos começar com as informações básicas da sua empresa. Estes dados nos ajudam a entender melhor seu negócio.
                        </p>
                    </div>

                    <div class="form-grid two-columns">
                        <div class="form-group">
                            <label class="form-label" for="nome_fantasia">
                                <i class="fas fa-store"></i>
                                Nome Fantasia <span class="required">*</span>
                            </label>
                            <input type="text" id="nome_fantasia" name="nome_fantasia" class="form-input" 
                                   required placeholder="Como sua loja é conhecida pelos clientes"
                                   value="<?php echo isset($data['nome_fantasia']) ? htmlspecialchars($data['nome_fantasia']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="razao_social">
                                <i class="fas fa-file-contract"></i>
                                Razão Social <span class="required">*</span>
                            </label>
                            <input type="text" id="razao_social" name="razao_social" class="form-input" 
                                   required placeholder="Nome oficial registrado na Receita Federal"
                                   value="<?php echo isset($data['razao_social']) ? htmlspecialchars($data['razao_social']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="cnpj">
                                <i class="fas fa-id-card"></i>
                                CNPJ <span class="required">*</span>
                            </label>
                            <input type="text" id="cnpj" name="cnpj" class="form-input" 
                                   required placeholder="XX.XXX.XXX/XXXX-XX"
                                   value="<?php echo isset($data['cnpj']) ? htmlspecialchars($data['cnpj']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="categoria">
                                <i class="fas fa-tags"></i>
                                Categoria do Negócio <span class="required">*</span>
                            </label>
                            <select id="categoria" name="categoria" class="form-input form-select" required>
                                <option value="">Selecione a categoria...</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo htmlspecialchars($categoria); ?>" 
                                            <?php echo (isset($data['categoria']) && $data['categoria'] == $categoria) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="descricao">
                            <i class="fas fa-align-left"></i>
                            Descrição da Loja
                        </label>
                        <textarea id="descricao" name="descricao" class="form-input form-textarea" 
                                  placeholder="Conte um pouco sobre sua loja, produtos oferecidos, diferenciais..."><?php echo isset($data['descricao']) ? htmlspecialchars($data['descricao']) : ''; ?></textarea>
                        <div class="success-message" style="display: none;">
                            <i class="fas fa-info-circle"></i>
                            Esta descrição será exibida para os clientes no catálogo de lojas parceiras.
                        </div>
                    </div>

                    <!-- Upload da Logo -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-image"></i>
                            Logo da Loja
                        </label>
                        <div class="file-upload-area" id="logoUploadArea">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="upload-text">
                                <strong>Clique aqui para escolher uma imagem</strong> ou arraste e solte
                            </div>
                            <div class="upload-hint">
                                JPG, PNG ou GIF • Máximo 2MB • Recomendado: 300x300px
                            </div>
                            <input type="file" id="logo" name="logo" accept="image/*" style="display: none;">
                        </div>
                        <div class="file-preview" id="logoPreview">
                            <i class="fas fa-check-circle"></i>
                            Logo carregada com sucesso!
                        </div>
                    </div>
                </div>

                <!-- Etapa 2: Contato -->
                <div class="form-step" data-step="2">
                    <div class="step-header">
                        <div class="step-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h2 class="step-title">Informações de Contato</h2>
                        <p class="step-description">
                            Como podemos entrar em contato com você? Estas informações são essenciais para nossa comunicação.
                        </p>
                    </div>

                    <div class="form-grid two-columns">
                        <div class="form-group">
                            <label class="form-label" for="email">
                                <i class="fas fa-envelope"></i>
                                E-mail <span class="required">*</span>
                            </label>
                            <input type="email" id="email" name="email" class="form-input" 
                                   required placeholder="seu@email.com"
                                   value="<?php echo isset($data['email']) ? htmlspecialchars($data['email']) : ''; ?>">
                            <div class="success-message" style="display: none;">
                                <i class="fas fa-info-circle"></i>
                                Este será seu email de acesso ao sistema
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="telefone">
                                <i class="fas fa-phone"></i>
                                Telefone <span class="required">*</span>
                            </label>
                            <input type="tel" id="telefone" name="telefone" class="form-input" 
                                   required placeholder="(XX) XXXXX-XXXX"
                                   value="<?php echo isset($data['telefone']) ? htmlspecialchars($data['telefone']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="website">
                            <i class="fas fa-globe"></i>
                            Website da Loja
                        </label>
                        <input type="url" id="website" name="website" class="form-input" 
                               placeholder="https://www.suaempresa.com.br"
                               value="<?php echo isset($data['website']) ? htmlspecialchars($data['website']) : ''; ?>">
                        <div class="success-message" style="display: none;">
                            <i class="fas fa-info-circle"></i>
                            Opcional: Ajuda os clientes a conhecer melhor sua loja
                        </div>
                    </div>

                    <div class="info-box">
                        <div class="info-box-header">
                            <div class="info-box-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="info-box-title">Seus dados estão seguros</div>
                        </div>
                        <div class="info-box-content">
                            <p>Todas as informações fornecidas são criptografadas e protegidas. Utilizamos apenas para:</p>
                            <ul class="info-list">
                                <li><i class="fas fa-check"></i> Comunicação oficial sobre sua conta</li>
                                <li><i class="fas fa-check"></i> Suporte técnico quando necessário</li>
                                <li><i class="fas fa-check"></i> Informações sobre pagamentos e comissões</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Etapa 3: Dados de Acesso -->
                <div class="form-step" data-step="3">
                    <div class="step-header">
                        <div class="step-icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <h2 class="step-title">Dados de Acesso</h2>
                        <p class="step-description">
                            Crie uma senha segura para acessar seu painel de controle. Com ela você poderá gerenciar vendas e acompanhar seus resultados.
                        </p>
                    </div>

                    <div class="form-grid two-columns">
                        <div class="form-group">
                            <label class="form-label" for="senha">
                                <i class="fas fa-lock"></i>
                                Senha de Acesso <span class="required">*</span>
                            </label>
                            <input type="password" id="senha" name="senha" class="form-input" 
                                   required minlength="8" placeholder="Mínimo 8 caracteres">
                            <div class="success-message" style="display: none;">
                                <i class="fas fa-info-circle"></i>
                                Use letras, números e símbolos para maior segurança
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="confirma_senha">
                                <i class="fas fa-lock"></i>
                                Confirme a Senha <span class="required">*</span>
                            </label>
                            <input type="password" id="confirma_senha" name="confirma_senha" class="form-input" 
                                   required minlength="8" placeholder="Digite novamente sua senha">
                            <div class="success-message" style="display: none;">
                                <i class="fas fa-check"></i>
                                Senhas coincidem perfeitamente!
                            </div>
                        </div>
                    </div>

                    <div class="info-box">
                        <div class="info-box-header">
                            <div class="info-box-icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div class="info-box-title">Sobre sua conta</div>
                        </div>
                        <div class="info-box-content">
                            <p>Após a aprovação da sua loja, você terá acesso ao painel administrativo onde poderá:</p>
                            <ul class="info-list">
                                <li><i class="fas fa-chart-line"></i> Acompanhar vendas e resultados em tempo real</li>
                                <li><i class="fas fa-cash-register"></i> Registrar novas vendas individualmente ou em lote</li>
                                <li><i class="fas fa-credit-card"></i> Gerenciar pagamentos de comissões</li>
                                <li><i class="fas fa-users"></i> Ver estatísticas de clientes e cashback</li>
                                <li><i class="fas fa-file-alt"></i> Gerar relatórios detalhados</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Etapa 4: Endereço -->
                <div class="form-step" data-step="4">
                    <div class="step-header">
                        <div class="step-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h2 class="step-title">Endereço da Loja</h2>
                        <p class="step-description">
                            Informe o endereço completo da sua loja. Isso ajuda os clientes a encontrarem você e facilita nossos processos administrativos.
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="cep">
                            <i class="fas fa-map-pin"></i>
                            CEP <span class="required">*</span>
                        </label>
                        <input type="text" id="cep" name="cep" class="form-input" 
                               required placeholder="XXXXX-XXX"
                               value="<?php echo isset($data['endereco']['cep']) ? htmlspecialchars($data['endereco']['cep']) : ''; ?>">
                        <div class="success-message" style="display: none;">
                            <i class="fas fa-magic"></i>
                            Preenchimento automático ativado! Digite o CEP e os outros campos serão preenchidos.
                        </div>
                    </div>

                    <div class="form-grid two-columns">
                        <div class="form-group">
                            <label class="form-label" for="logradouro">
                                <i class="fas fa-road"></i>
                                Logradouro <span class="required">*</span>
                            </label>
                            <input type="text" id="logradouro" name="logradouro" class="form-input" 
                                   required placeholder="Rua, Avenida, etc."
                                   value="<?php echo isset($data['endereco']['logradouro']) ? htmlspecialchars($data['endereco']['logradouro']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="numero">
                                <i class="fas fa-hashtag"></i>
                                Número <span class="required">*</span>
                            </label>
                            <input type="text" id="numero" name="numero" class="form-input" 
                                   required placeholder="123"
                                   value="<?php echo isset($data['endereco']['numero']) ? htmlspecialchars($data['endereco']['numero']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="complemento">
                                <i class="fas fa-plus"></i>
                                Complemento
                            </label>
                            <input type="text" id="complemento" name="complemento" class="form-input" 
                                   placeholder="Sala, Andar, etc."
                                   value="<?php echo isset($data['endereco']['complemento']) ? htmlspecialchars($data['endereco']['complemento']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="bairro">
                                <i class="fas fa-home"></i>
                                Bairro <span class="required">*</span>
                            </label>
                            <input type="text" id="bairro" name="bairro" class="form-input" 
                                   required placeholder="Nome do bairro"
                                   value="<?php echo isset($data['endereco']['bairro']) ? htmlspecialchars($data['endereco']['bairro']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="cidade">
                                <i class="fas fa-city"></i>
                                Cidade <span class="required">*</span>
                            </label>
                            <input type="text" id="cidade" name="cidade" class="form-input" 
                                   required placeholder="Nome da cidade"
                                   value="<?php echo isset($data['endereco']['cidade']) ? htmlspecialchars($data['endereco']['cidade']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="estado">
                                <i class="fas fa-flag"></i>
                                Estado <span class="required">*</span>
                            </label>
                            <select id="estado" name="estado" class="form-input form-select" required>
                                <option value="">Selecione o estado...</option>
                                <?php foreach ($estados as $uf => $nomeEstado): ?>
                                    <option value="<?php echo $uf; ?>" 
                                            <?php echo (isset($data['endereco']['estado']) && $data['endereco']['estado'] == $uf) ? 'selected' : ''; ?>>
                                        <?php echo $nomeEstado; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Etapa 5: Confirmação -->
                <div class="form-step" data-step="5">
                    <div class="step-header">
                        <div class="step-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2 class="step-title">Revisão e Confirmação</h2>
                        <p class="step-description">
                            Revise todas as informações antes de enviar. Após o envio, nossa equipe analisará sua solicitação em até 24 horas.
                        </p>
                    </div>

                    <div class="summary-section">
                        <div class="summary-card">
                            <div class="summary-title">
                                <i class="fas fa-building"></i>
                                Dados da Empresa
                            </div>
                            <div class="summary-grid">
                                <div class="summary-item">
                                    <span class="summary-label">Nome Fantasia:</span>
                                    <span class="summary-value" id="summary-nome-fantasia">-</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Razão Social:</span>
                                    <span class="summary-value" id="summary-razao-social">-</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">CNPJ:</span>
                                    <span class="summary-value" id="summary-cnpj">-</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Categoria:</span>
                                    <span class="summary-value" id="summary-categoria">-</span>
                                </div>
                            </div>
                        </div>

                        <div class="summary-card">
                            <div class="summary-title">
                                <i class="fas fa-phone"></i>
                                Contato
                            </div>
                            <div class="summary-grid">
                                <div class="summary-item">
                                    <span class="summary-label">E-mail:</span>
                                    <span class="summary-value" id="summary-email">-</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Telefone:</span>
                                    <span class="summary-value" id="summary-telefone">-</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Website:</span>
                                    <span class="summary-value" id="summary-website">-</span>
                                </div>
                            </div>
                        </div>

                        <div class="summary-card">
                            <div class="summary-title">
                                <i class="fas fa-map-marker-alt"></i>
                                Endereço
                            </div>
                            <div class="summary-grid">
                                <div class="summary-item">
                                    <span class="summary-label">Endereço Completo:</span>
                                    <span class="summary-value" id="summary-endereco">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="info-box">
                        <div class="info-box-header">
                            <div class="info-box-icon">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <div class="info-box-title">Como funciona o Klube Cash</div>
                        </div>
                        <div class="info-box-content">
                            <p><strong>Sistema de comissão simples e transparente:</strong></p>
                            <ul class="info-list">
                                <li><i class="fas fa-percentage"></i> <strong>10% de comissão</strong> sobre cada venda processada</li>
                                <li><i class="fas fa-gift"></i> <strong>5% vai para o cliente</strong> como cashback</li>
                                <li><i class="fas fa-cogs"></i> <strong>5% fica para o Klube Cash</strong> (nossa receita)</li>
                                <li><i class="fas fa-heart"></i> <strong>0% para a loja</strong> - você não recebe comissão, mas ganha fidelização</li>
                                <li><i class="fas fa-recycle"></i> O cashback do cliente <strong>só pode ser usado na sua loja</strong></li>
                            </ul>
                            <p style="margin-top: 15px; font-weight: 600; color: var(--primary-color);">
                                Resultado: mais vendas, clientes fiéis e crescimento sustentável!
                            </p>
                        </div>
                    </div>

                    <div class="info-box">
                        <div class="info-box-header">
                            <div class="info-box-icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="info-box-title">Termos de Parceria</div>
                        </div>
                        <div class="info-box-content">
                            <p>Ao enviar este cadastro, você concorda que:</p>
                            <ul class="info-list">
                                <li><i class="fas fa-check"></i> Todas as informações fornecidas são verdadeiras</li>
                                <li><i class="fas fa-check"></i> Sua loja oferecerá cashback conforme as condições acordadas</li>
                                <li><i class="fas fa-check"></i> Processará vendas através do nosso sistema</li>
                                <li><i class="fas fa-check"></i> Pagará as comissões nos prazos estabelecidos</li>
                                <li><i class="fas fa-check"></i> Cumprirá nossas políticas de qualidade no atendimento</li>
                            </ul>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 15px; margin: 30px 0; padding: 20px; background: var(--gray-50); border-radius: var(--border-radius);">
                        <input type="checkbox" id="aceite_termos" name="aceite_termos" required 
                               style="width: 20px; height: 20px; accent-color: var(--primary-color);">
                        <label for="aceite_termos" style="flex: 1; font-weight: 500; color: var(--gray-700);">
                            Li e concordo com os termos de parceria e autorizo o Klube Cash a analisar minha solicitação <span class="required">*</span>
                        </label>
                    </div>
                </div>

                <!-- Botões de Navegação -->
                <div class="form-actions">
                    <button type="button" id="prevBtn" class="btn btn-secondary" style="display: none;">
                        <i class="fas fa-arrow-left"></i>
                        Voltar
                    </button>
                    <div style="flex: 1;"></div>
                    <button type="button" id="nextBtn" class="btn btn-primary">
                        Continuar
                        <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="submit" id="submitBtn" class="btn btn-primary" style="display: none;">
                        <span class="loading">
                            <div class="loading-spinner"></div>
                            Processando...
                        </span>
                        <span class="submit-text">
                            <i class="fas fa-paper-plane"></i>
                            Enviar Cadastro
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Sistema de Multi-Step Form
        class MultiStepForm {
            constructor() {
                this.currentStep = 1;
                this.totalSteps = 5;
                this.formData = {};
                
                this.init();
            }

            init() {
                this.setupEventListeners();
                this.setupValidation();
                this.setupFileUpload();
                this.setupMasks();
                this.setupAutoFill();
                this.updateProgress();
            }

            setupEventListeners() {
                // Botões de navegação
                document.getElementById('nextBtn').addEventListener('click', () => this.nextStep());
                document.getElementById('prevBtn').addEventListener('click', () => this.prevStep());
                
                // Submit do formulário
                document.getElementById('storeRegistrationForm').addEventListener('submit', (e) => this.handleSubmit(e));
                
                // Validação em tempo real
                const inputs = document.querySelectorAll('.form-input');
                inputs.forEach(input => {
                    input.addEventListener('blur', () => this.validateField(input));
                    input.addEventListener('input', () => this.clearFieldError(input));
                });
            }

            setupValidation() {
                // Validação de senha
                const senha = document.getElementById('senha');
                const confirmaSenha = document.getElementById('confirma_senha');
                
                confirmaSenha.addEventListener('input', () => {
                    if (confirmaSenha.value && senha.value !== confirmaSenha.value) {
                        this.showFieldError(confirmaSenha, 'As senhas não coincidem');
                    } else if (confirmaSenha.value && senha.value === confirmaSenha.value) {
                        this.showFieldSuccess(confirmaSenha, 'Senhas coincidem perfeitamente!');
                    }
                });

                // Validação de email
                const email = document.getElementById('email');
                email.addEventListener('blur', () => {
                    if (email.value && this.isValidEmail(email.value)) {
                        this.showFieldSuccess(email, 'Email válido!');
                    }
                });
            }

            setupFileUpload() {
                const uploadArea = document.getElementById('logoUploadArea');
                const fileInput = document.getElementById('logo');
                const preview = document.getElementById('logoPreview');

                // Click para abrir seletor
                uploadArea.addEventListener('click', () => fileInput.click());

                // Drag and drop
                uploadArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    uploadArea.classList.add('dragover');
                });

                uploadArea.addEventListener('dragleave', () => {
                    uploadArea.classList.remove('dragover');
                });

                uploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    uploadArea.classList.remove('dragover');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        fileInput.files = files;
                        this.handleFileSelect(files[0]);
                    }
                });

                // Mudança de arquivo
                fileInput.addEventListener('change', (e) => {
                    if (e.target.files.length > 0) {
                        this.handleFileSelect(e.target.files[0]);
                    }
                });
            }

            handleFileSelect(file) {
                // Validar arquivo
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                const maxSize = 2 * 1024 * 1024; // 2MB

                if (!allowedTypes.includes(file.type)) {
                    alert('Tipo de arquivo não permitido. Use JPG, PNG ou GIF.');
                    return;
                }

                if (file.size > maxSize) {
                    alert('Arquivo muito grande. Máximo: 2MB.');
                    return;
                }

                // Mostrar preview
                document.getElementById('logoPreview').classList.add('show');
            }

            setupMasks() {
                // Máscara de CNPJ
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

                // Máscara de telefone
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

                // Máscara de CEP
                document.getElementById('cep').addEventListener('input', function (e) {
                    let value = e.target.value.replace(/\D/g, '');
                    
                    if (value.length > 5) {
                        value = value.replace(/^(\d{5})(\d)/, '$1-$2');
                    }
                    
                    e.target.value = value;
                });
            }

            setupAutoFill() {
                // Preenchimento automático pelo CEP
                document.getElementById('cep').addEventListener('blur', function() {
                    const cep = this.value.replace(/\D/g, '');
                    
                    if (cep.length !== 8) return;
                    
                    // Indicador visual de carregamento
                    this.style.backgroundColor = '#f0f0f0';
                    
                    fetch(`https://viacep.com.br/ws/${cep}/json/`)
                        .then(response => response.json())
                        .then(data => {
                            if (!data.erro) {
                                document.getElementById('logradouro').value = data.logradouro || '';
                                document.getElementById('bairro').value = data.bairro || '';
                                document.getElementById('cidade').value = data.localidade || '';
                                document.getElementById('estado').value = data.uf || '';
                                
                                // Focar no próximo campo
                                if (data.logradouro) {
                                    document.getElementById('numero').focus();
                                }

                                // Mostrar sucesso
                                const successMsg = this.parentElement.querySelector('.success-message');
                                if (successMsg) {
                                    successMsg.style.display = 'flex';
                                    successMsg.innerHTML = '<i class="fas fa-check"></i> Endereço preenchido automaticamente!';
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
            }

            nextStep() {
                if (this.validateCurrentStep()) {
                    if (this.currentStep < this.totalSteps) {
                        this.currentStep++;
                        this.updateStep();
                        this.updateProgress();
                        
                        if (this.currentStep === this.totalSteps) {
                            this.updateSummary();
                        }
                    }
                }
            }

            prevStep() {
                if (this.currentStep > 1) {
                    this.currentStep--;
                    this.updateStep();
                    this.updateProgress();
                }
            }

            updateStep() {
                // Ocultar todas as etapas
                document.querySelectorAll('.form-step').forEach(step => {
                    step.classList.remove('active');
                });

                // Mostrar etapa atual
                document.querySelector(`[data-step="${this.currentStep}"]`).classList.add('active');

                // Atualizar botões
                const prevBtn = document.getElementById('prevBtn');
                const nextBtn = document.getElementById('nextBtn');
                const submitBtn = document.getElementById('submitBtn');

                prevBtn.style.display = this.currentStep === 1 ? 'none' : 'flex';
                
                if (this.currentStep === this.totalSteps) {
                    nextBtn.style.display = 'none';
                    submitBtn.style.display = 'flex';
                } else {
                    nextBtn.style.display = 'flex';
                    submitBtn.style.display = 'none';
                }

                // Scroll para o topo
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            updateProgress() {
                // Atualizar indicadores de step
                document.querySelectorAll('.step-indicator').forEach((indicator, index) => {
                    const stepNumber = index + 1;
                    indicator.classList.remove('active', 'completed');
                    
                    if (stepNumber < this.currentStep) {
                        indicator.classList.add('completed');
                        indicator.innerHTML = '<i class="fas fa-check"></i>';
                    } else if (stepNumber === this.currentStep) {
                        indicator.classList.add('active');
                        indicator.innerHTML = stepNumber;
                    } else {
                        indicator.innerHTML = stepNumber;
                    }
                });

                // Atualizar labels
                document.querySelectorAll('.step-label').forEach((label, index) => {
                    label.classList.remove('active');
                    if (index + 1 === this.currentStep) {
                        label.classList.add('active');
                    }
                });

                // Atualizar linha de progresso
                const progressLine = document.querySelector('.progress-line');
                const progressPercentage = ((this.currentStep - 1) / (this.totalSteps - 1)) * 100;
                progressLine.style.width = `${progressPercentage}%`;
            }

            validateCurrentStep() {
                const currentStepElement = document.querySelector(`[data-step="${this.currentStep}"]`);
                const requiredFields = currentStepElement.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!this.validateField(field)) {
                        isValid = false;
                    }
                });

                // Validações específicas
                if (this.currentStep === 3) {
                    const senha = document.getElementById('senha');
                    const confirmaSenha = document.getElementById('confirma_senha');
                    
                    if (senha.value !== confirmaSenha.value) {
                        this.showFieldError(confirmaSenha, 'As senhas não coincidem');
                        isValid = false;
                    }
                }

                if (this.currentStep === this.totalSteps) {
                    const termos = document.getElementById('aceite_termos');
                    if (!termos.checked) {
                        alert('Você deve aceitar os termos de parceria para continuar.');
                        isValid = false;
                    }
                }

                return isValid;
            }

            validateField(field) {
                const value = field.value.trim();
                
                // Campo obrigatório vazio
                if (field.hasAttribute('required') && !value) {
                    this.showFieldError(field, 'Este campo é obrigatório');
                    return false;
                }

                // Validação específica por tipo
                if (value) {
                    switch (field.type) {
                        case 'email':
                            if (!this.isValidEmail(value)) {
                                this.showFieldError(field, 'Email inválido');
                                return false;
                            }
                            break;
                        case 'url':
                            if (value && !this.isValidUrl(value)) {
                                this.showFieldError(field, 'URL inválida');
                                return false;
                            }
                            break;
                    }

                    // Validação de CNPJ
                    if (field.id === 'cnpj' && !this.isValidCNPJ(value)) {
                        this.showFieldError(field, 'CNPJ inválido');
                        return false;
                    }

                    // Validação de senha
                    if (field.id === 'senha' && value.length < 8) {
                        this.showFieldError(field, 'A senha deve ter pelo menos 8 caracteres');
                        return false;
                    }
                }

                this.showFieldSuccess(field);
                return true;
            }

            showFieldError(field, message) {
                field.classList.remove('valid');
                field.classList.add('invalid');
                
                let errorElement = field.parentElement.querySelector('.error-message');
                if (!errorElement) {
                    errorElement = document.createElement('div');
                    errorElement.className = 'error-message';
                    field.parentElement.appendChild(errorElement);
                }
                
                errorElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
                errorElement.style.display = 'flex';

                // Ocultar mensagem de sucesso se existir
                const successElement = field.parentElement.querySelector('.success-message');
                if (successElement) {
                    successElement.style.display = 'none';
                }
            }

            showFieldSuccess(field, message = null) {
                field.classList.remove('invalid');
                field.classList.add('valid');
                
                // Ocultar mensagem de erro se existir
                const errorElement = field.parentElement.querySelector('.error-message');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }

                // Mostrar mensagem de sucesso se fornecida
                if (message) {
                    const successElement = field.parentElement.querySelector('.success-message');
                    if (successElement) {
                        successElement.innerHTML = `<i class="fas fa-check"></i> ${message}`;
                        successElement.style.display = 'flex';
                    }
                }
            }

            clearFieldError(field) {
                field.classList.remove('invalid');
                const errorElement = field.parentElement.querySelector('.error-message');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            }

            updateSummary() {
                // Atualizar resumo dos dados
                document.getElementById('summary-nome-fantasia').textContent = 
                    document.getElementById('nome_fantasia').value || '-';
                document.getElementById('summary-razao-social').textContent = 
                    document.getElementById('razao_social').value || '-';
                document.getElementById('summary-cnpj').textContent = 
                    document.getElementById('cnpj').value || '-';
                document.getElementById('summary-categoria').textContent = 
                    document.getElementById('categoria').selectedOptions[0]?.text || '-';
                document.getElementById('summary-email').textContent = 
                    document.getElementById('email').value || '-';
                document.getElementById('summary-telefone').textContent = 
                    document.getElementById('telefone').value || '-';
                document.getElementById('summary-website').textContent = 
                    document.getElementById('website').value || 'Não informado';

                // Montar endereço completo
                const endereco = [
                    document.getElementById('logradouro').value,
                    document.getElementById('numero').value,
                    document.getElementById('complemento').value,
                    document.getElementById('bairro').value,
                    document.getElementById('cidade').value,
                    document.getElementById('estado').selectedOptions[0]?.text,
                    document.getElementById('cep').value
                ].filter(item => item && item.trim()).join(', ');

                document.getElementById('summary-endereco').textContent = endereco || '-';
            }

            handleSubmit(e) {
                e.preventDefault();
                
                if (!this.validateCurrentStep()) {
                    return;
                }

                // Mostrar loading
                const submitBtn = document.getElementById('submitBtn');
                const loadingElement = submitBtn.querySelector('.loading');
                const textElement = submitBtn.querySelector('.submit-text');
                
                loadingElement.classList.add('show');
                textElement.style.display = 'none';
                submitBtn.disabled = true;

                // Enviar formulário
                setTimeout(() => {
                    e.target.submit();
                }, 1000);
            }

            // Utility functions
            isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            isValidUrl(url) {
                try {
                    new URL(url);
                    return true;
                } catch {
                    return false;
                }
            }

            isValidCNPJ(cnpj) {
                // Remover caracteres especiais
                cnpj = cnpj.replace(/[^0-9]/g, '');
                
                // Verificar se tem 14 dígitos
                if (cnpj.length !== 14) return false;
                
                // Verificar se todos os dígitos são iguais
                if (/^(\d)\1{13}$/.test(cnpj)) return false;
                
                // Algoritmo de validação do CNPJ
                let tamanho = cnpj.length - 2;
                let numeros = cnpj.substring(0, tamanho);
                let digitos = cnpj.substring(tamanho);
                let soma = 0;
                let pos = tamanho - 7;
                
                for (let i = tamanho; i >= 1; i--) {
                    soma += numeros.charAt(tamanho - i) * pos--;
                    if (pos < 2) pos = 9;
                }
                
                let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
                if (resultado != digitos.charAt(0)) return false;
                
                tamanho = tamanho + 1;
                numeros = cnpj.substring(0, tamanho);
                soma = 0;
                pos = tamanho - 7;
                
                for (let i = tamanho; i >= 1; i--) {
                    soma += numeros.charAt(tamanho - i) * pos--;
                    if (pos < 2) pos = 9;
                }
                
                resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
                return resultado == digitos.charAt(1);
            }
        }

        // Inicializar o formulário quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            new MultiStepForm();
        });
    </script>

    <?php debug_log("Nova página renderizada com sucesso"); ?>
</body>
</html>