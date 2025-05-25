<?php
// views/stores/register.php - Versão corrigida
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- Debug: Iniciando carregamento da página -->\n";

// Estratégia múltipla para encontrar os arquivos
function smart_include($relative_path) {
    // Lista de possíveis caminhos base
    $base_paths = [
        dirname(dirname(__DIR__)), // Dois níveis acima (/klube-cash/)
        $_SERVER['DOCUMENT_ROOT'], // Raiz do servidor web
        dirname(dirname(dirname(__FILE__))) // Três níveis acima via __FILE__
    ];
    
    foreach ($base_paths as $base) {
        $full_path = $base . '/' . $relative_path;
        if (file_exists($full_path)) {
            require_once $full_path;
            echo "<!-- Debug: Carregado $relative_path de $full_path -->\n";
            return true;
        }
    }
    
    // Se não encontrou, mostrar erro detalhado
    echo "<!-- Erro: Não foi possível encontrar $relative_path -->\n";
    echo "<!-- Tentou nos caminhos: " . implode(', ', array_map(function($base) use ($relative_path) {
        return $base . '/' . $relative_path;
    }, $base_paths)) . " -->\n";
    
    return false;
}

// Carregar arquivos essenciais
echo "<!-- Debug: Carregando arquivos de configuração -->\n";

$required_files = [
    'config/constants.php',
    'config/database.php',
    'config/email.php',
    'controllers/StoreController.php',
    'utils/Validator.php'
];

$missing_files = [];
foreach ($required_files as $file) {
    if (!smart_include($file)) {
        $missing_files[] = $file;
    }
}

// Se algum arquivo essencial não foi encontrado, mostrar erro amigável
if (!empty($missing_files)) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Erro de Configuração</title>
        <style>
            body { font-family: Arial; padding: 20px; background: #f5f5f5; }
            .error-box { background: #fff; border-left: 4px solid #dc3545; padding: 20px; margin: 20px 0; }
            .file-list { background: #f8f9fa; padding: 10px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <h1>Erro de Configuração do Sistema</h1>
        <div class="error-box">
            <h3>Arquivos não encontrados:</h3>
            <div class="file-list">
                <?php foreach ($missing_files as $file): ?>
                    <div>❌ <?php echo htmlspecialchars($file); ?></div>
                <?php endforeach; ?>
            </div>
            <p><strong>Solução:</strong> Verifique se os arquivos existem na estrutura do projeto e se os caminhos estão corretos.</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

echo "<!-- Debug: Todos os arquivos carregados com sucesso -->\n";

// Iniciar sessão de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['user_type']) && $_SESSION['user_type'] == USER_TYPE_ADMIN;

// Verificar se as classes foram carregadas
if (!class_exists('StoreController')) {
    die("Erro: Classe StoreController não foi carregada corretamente.");
}

if (!class_exists('Validator')) {
    die("Erro: Classe Validator não foi carregada corretamente.");
}

echo "<!-- Debug: Classes verificadas com sucesso -->\n";

// Processar o formulário de cadastro de loja
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<!-- Debug: Processando POST -->\n";
    
    // Capturar e sanitizar dados do formulário
    $data = [
        'nome_fantasia' => filter_input(INPUT_POST, 'nome_fantasia', FILTER_SANITIZE_STRING),
        'razao_social' => filter_input(INPUT_POST, 'razao_social', FILTER_SANITIZE_STRING),
        'cnpj' => filter_input(INPUT_POST, 'cnpj', FILTER_SANITIZE_STRING),
        'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
        'telefone' => filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING),
        'senha' => $_POST['senha'] ?? '',
        'confirma_senha' => $_POST['confirma_senha'] ?? '',
        'categoria' => filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_STRING),
        'descricao' => filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING),
        'website' => filter_input(INPUT_POST, 'website', FILTER_SANITIZE_URL),
        'endereco' => [
            'cep' => filter_input(INPUT_POST, 'cep', FILTER_SANITIZE_STRING),
            'logradouro' => filter_input(INPUT_POST, 'logradouro', FILTER_SANITIZE_STRING),
            'numero' => filter_input(INPUT_POST, 'numero', FILTER_SANITIZE_STRING),
            'complemento' => filter_input(INPUT_POST, 'complemento', FILTER_SANITIZE_STRING),
            'bairro' => filter_input(INPUT_POST, 'bairro', FILTER_SANITIZE_STRING),
            'cidade' => filter_input(INPUT_POST, 'cidade', FILTER_SANITIZE_STRING),
            'estado' => filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING)
        ]
    ];
    
    // Validação básica
    $errors = [];
    
    if (empty($data['nome_fantasia'])) $errors[] = 'Nome fantasia é obrigatório';
    if (empty($data['razao_social'])) $errors[] = 'Razão social é obrigatória';
    if (empty($data['cnpj'])) $errors[] = 'CNPJ é obrigatório';
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido';
    if (empty($data['telefone'])) $errors[] = 'Telefone é obrigatório';
    if (empty($data['senha'])) $errors[] = 'Senha é obrigatória';
    if (strlen($data['senha']) < 8) $errors[] = 'A senha deve ter pelo menos 8 caracteres';
    if ($data['senha'] !== $data['confirma_senha']) $errors[] = 'As senhas não coincidem';
    if (empty($data['categoria'])) $errors[] = 'Categoria é obrigatória';
    
    // Validações de endereço
    if (empty($data['endereco']['cep'])) $errors[] = 'CEP é obrigatório';
    if (empty($data['endereco']['logradouro'])) $errors[] = 'Logradouro é obrigatório';
    if (empty($data['endereco']['numero'])) $errors[] = 'Número é obrigatório';
    if (empty($data['endereco']['bairro'])) $errors[] = 'Bairro é obrigatório';
    if (empty($data['endereco']['cidade'])) $errors[] = 'Cidade é obrigatória';
    if (empty($data['endereco']['estado'])) $errors[] = 'Estado é obrigatório';
    
    if (empty($errors)) {
        try {
            // Formatar CNPJ
            $data['cnpj'] = preg_replace('/[^0-9]/', '', $data['cnpj']);
            
            // Registrar a loja
            $result = StoreController::registerStore($data);
            
            if ($result['status']) {
                $success = $result['message'];
                $data = []; // Limpar formulário
            } else {
                $error = $result['message'];
            }
        } catch (Exception $e) {
            $error = "Erro interno: " . $e->getMessage();
            error_log("Erro no cadastro de loja: " . $e->getMessage());
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// Dados para os selects
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

echo "<!-- Debug: Página pronta para renderizar HTML -->\n";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Loja Parceira - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <style>
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
        
        .alert-warning {
            background-color: #FFF3E0;
            color: var(--warning-color);
            border: 1px solid var(--warning-color);
        }
        
        .benefits-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }
        
        .benefit-card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 25px;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .benefit-card:hover {
            transform: translateY(-5px);
        }
        
        .benefit-icon {
            width: 60px;
            height: 60px;
            background-color: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: var(--primary-color);
            font-size: 24px;
        }
        
        .benefit-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .password-strength {
            margin-top: 5px;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .password-strength.weak {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .password-strength.medium {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .password-strength.strong {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .benefits-section {
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
    <!-- Incluir navbar -->
    <!-- Incluir navbar com verificação -->
    <?php 
    $navbar_path = ROOT_PATH . '/views/components/navbar.php';
    if (file_exists($navbar_path)) {
        include_once $navbar_path; 
    } else {
        echo "<!-- Navbar não encontrada: $navbar_path -->";
    }
    ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Torne-se uma Loja Parceira</h1>
            <p>Aumente suas vendas oferecendo cashback aos seus clientes</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <?php if (isset($result['data']['awaiting_approval']) && $result['data']['awaiting_approval']): ?>
                    <p>Sua solicitação foi recebida e está aguardando aprovação. Você receberá um email quando sua loja for analisada.</p>
                <?php else: ?>
                    <p>Sua loja foi aprovada e já está ativa no sistema!</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="post" action="" id="store-form">
                <h2 class="section-title">Informações da Empresa</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="nome_fantasia">Nome Fantasia <span class="required">*</span></label>
                        <input type="text" id="nome_fantasia" name="nome_fantasia" class="form-control" required value="<?php echo isset($data['nome_fantasia']) ? htmlspecialchars($data['nome_fantasia']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="razao_social">Razão Social <span class="required">*</span></label>
                        <input type="text" id="razao_social" name="razao_social" class="form-control" required value="<?php echo isset($data['razao_social']) ? htmlspecialchars($data['razao_social']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="cnpj">CNPJ <span class="required">*</span></label>
                        <input type="text" id="cnpj" name="cnpj" class="form-control" required value="<?php echo isset($data['cnpj']) ? htmlspecialchars($data['cnpj']) : ''; ?>" placeholder="XX.XXX.XXX/XXXX-XX">
                        <small class="form-text">Digite apenas números ou formato XX.XXX.XXX/XXXX-XX</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="categoria">Categoria/Segmento <span class="required">*</span></label>
                        <select id="categoria" name="categoria" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo htmlspecialchars($categoria); ?>" <?php echo (isset($data['categoria']) && $data['categoria'] == $categoria) ? 'selected' : ''; ?>>
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
                        <input type="email" id="email" name="email" class="form-control" required value="<?php echo isset($data['email']) ? htmlspecialchars($data['email']) : ''; ?>">
                        <small class="form-text">Este será seu email de acesso ao sistema</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="telefone">Telefone <span class="required">*</span></label>
                        <input type="tel" id="telefone" name="telefone" class="form-control" required value="<?php echo isset($data['telefone']) ? htmlspecialchars($data['telefone']) : ''; ?>" placeholder="(XX) XXXXX-XXXX">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="website">Website</label>
                    <input type="url" id="website" name="website" class="form-control" value="<?php echo isset($data['website']) ? htmlspecialchars($data['website']) : ''; ?>" placeholder="https://www.suaempresa.com.br">
                </div>
                
                <h2 class="section-title">Dados de Acesso</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="senha">Senha de Acesso <span class="required">*</span></label>
                        <input type="password" id="senha" name="senha" class="form-control" required minlength="8">
                        <div id="password-feedback" class="password-strength" style="display: none;"></div>
                        <small class="form-text">Mínimo de 8 caracteres. Use letras, números e símbolos para maior segurança.</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="confirma_senha">Confirme a Senha <span class="required">*</span></label>
                        <input type="password" id="confirma_senha" name="confirma_senha" class="form-control" required minlength="8">
                        <div id="password-match-feedback" style="display: none; font-size: 12px; margin-top: 5px;"></div>
                        <small class="form-text">Digite novamente sua senha para confirmação.</small>
                    </div>
                </div>
                
                <h2 class="section-title">Endereço</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="cep">CEP <span class="required">*</span></label>
                        <input type="text" id="cep" name="cep" class="form-control" required value="<?php echo isset($data['endereco']['cep']) ? htmlspecialchars($data['endereco']['cep']) : ''; ?>" placeholder="XXXXX-XXX">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="logradouro">Logradouro <span class="required">*</span></label>
                        <input type="text" id="logradouro" name="logradouro" class="form-control" required value="<?php echo isset($data['endereco']['logradouro']) ? htmlspecialchars($data['endereco']['logradouro']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="numero">Número <span class="required">*</span></label>
                        <input type="text" id="numero" name="numero" class="form-control" required value="<?php echo isset($data['endereco']['numero']) ? htmlspecialchars($data['endereco']['numero']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="complemento">Complemento</label>
                        <input type="text" id="complemento" name="complemento" class="form-control" value="<?php echo isset($data['endereco']['complemento']) ? htmlspecialchars($data['endereco']['complemento']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="bairro">Bairro <span class="required">*</span></label>
                        <input type="text" id="bairro" name="bairro" class="form-control" required value="<?php echo isset($data['endereco']['bairro']) ? htmlspecialchars($data['endereco']['bairro']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="cidade">Cidade <span class="required">*</span></label>
                        <input type="text" id="cidade" name="cidade" class="form-control" required value="<?php echo isset($data['endereco']['cidade']) ? htmlspecialchars($data['endereco']['cidade']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="estado">Estado <span class="required">*</span></label>
                        <select id="estado" name="estado" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($estados as $uf => $nomeEstado): ?>
                                <option value="<?php echo $uf; ?>" <?php echo (isset($data['endereco']['estado']) && $data['endereco']['estado'] == $uf) ? 'selected' : ''; ?>>
                                    <?php echo $nomeEstado; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <h2 class="section-title">Configurações de Cashback</h2>
                
                <!-- REMOVIDO: Campo de porcentagem customizada -->
                <!-- Todas as lojas pagam 10% (5% cliente + 5% admin) -->

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
                    <textarea id="descricao" name="descricao" class="form-control" rows="4" placeholder="Conte um pouco sobre sua loja, produtos oferecidos, diferenciais..."><?php echo isset($data['descricao']) ? htmlspecialchars($data['descricao']) : ''; ?></textarea>
                    <small class="form-text">Esta descrição será exibida para os clientes no catálogo de lojas parceiras.</small>
                </div>
                
                <div class="form-terms">
                    <h3>Termos e Condições</h3>
                    <p>Ao se cadastrar como loja parceira, você concorda com os seguintes termos:</p>
                    <ul>
                        <li>O Klube Cash analisará sua solicitação e pode aprová-la ou rejeitá-la de acordo com nossos critérios.</li>
                        <li>Você se compromete a oferecer o cashback conforme a porcentagem cadastrada.</li>
                        <li>Sua loja será exibida no catálogo de parceiros após aprovação.</li>
                        <li>Todas as transações de cashback devem ser processadas através do nosso sistema.</li>
                        <li>Você terá acesso a um painel para gerenciar suas transações e relatórios.</li>
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
        
        <div class="benefits-section">
            <div class="benefit-card">
                <div class="benefit-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6L6 18"></path>
                        <path d="M6 6l12 12"></path>
                        <line x1="12" y1="2" x2="12" y2="6"></line>
                        <line x1="12" y1="18" x2="12" y2="22"></line>
                        <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                        <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                        <line x1="2" y1="12" x2="6" y2="12"></line>
                        <line x1="18" y1="12" x2="22" y2="12"></line>
                        <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                        <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                    </svg>
                </div>
                <h3 class="benefit-title">Aumente suas Vendas</h3>
                <p>O cashback é um forte incentivo para novos clientes escolherem sua loja e para fidelizar os atuais. Clientes tendem a retornar às lojas onde ganham benefícios.</p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <h3 class="benefit-title">Novos Clientes</h3>
                <p>Acesse nossa base crescente de usuários que procuram ativamente por lojas que oferecem cashback. Seja encontrado por clientes interessados em seus produtos.</p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <path d="M9 9h6v6H9z"></path>
                    </svg>
                </div>
                <h3 class="benefit-title">Fácil Implementação</h3>
                <p>Sistema simples e intuitivo para integrar com seu negócio. Painel administrativo completo para gerenciar transações e acompanhar relatórios de vendas.</p>
            </div>
        </div>
    </div>
    
    <script>
        // Máscara para o CNPJ
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
        
        // Máscara para o telefone
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
        
        // Máscara para o CEP
        document.getElementById('cep').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 5) {
                value = value.replace(/^(\d{5})(\d)/, '$1-$2');
            }
            
            e.target.value = value;
        });
        
        // Preenchimento automático do endereço pelo CEP
        document.getElementById('cep').addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            
            if (cep.length !== 8) {
                return;
            }
            
            // Mostrar indicador de carregamento
            this.style.backgroundColor = '#f0f0f0';
            
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (!data.erro) {
                        document.getElementById('logradouro').value = data.logradouro || '';
                        document.getElementById('bairro').value = data.bairro || '';
                        document.getElementById('cidade').value = data.localidade || '';
                        document.getElementById('estado').value = data.uf || '';
                        
                        // Focar no campo número
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
                    // Remover indicador de carregamento
                    this.style.backgroundColor = '';
                });
        });
        
        // Validação de força da senha
        document.getElementById('senha').addEventListener('input', function() {
            const senha = this.value;
            const feedback = document.getElementById('password-feedback');
            
            if (senha.length === 0) {
                feedback.style.display = 'none';
                return;
            }
            
            let score = 0;
            let texto = '';
            let classe = '';
            
            // Critérios de força da senha
            if (senha.length >= 8) score++;
            if (/[a-z]/.test(senha)) score++;
            if (/[A-Z]/.test(senha)) score++;
            if (/[0-9]/.test(senha)) score++;
            if (/[^A-Za-z0-9]/.test(senha)) score++;
            
            if (score < 3) {
                texto = 'Senha fraca';
                classe = 'weak';
            } else if (score < 4) {
                texto = 'Senha média';
                classe = 'medium';
            } else {
                texto = 'Senha forte';
                classe = 'strong';
            }
            
            feedback.textContent = texto;
            feedback.className = 'password-strength ' + classe;
            feedback.style.display = 'block';
        });
        
        // Validação de confirmação de senha
        function validatePasswords() {
            const senha = document.getElementById('senha').value;
            const confirmaSenha = document.getElementById('confirma_senha').value;
            const feedback = document.getElementById('password-match-feedback');
            
            if (confirmaSenha.length === 0) {
                feedback.style.display = 'none';
                document.getElementById('confirma_senha').setCustomValidity('');
                return true;
            }
            
            if (senha !== confirmaSenha) {
                feedback.textContent = 'As senhas não coincidem';
                feedback.style.color = '#c62828';
                feedback.style.display = 'block';
                document.getElementById('confirma_senha').setCustomValidity('As senhas não coincidem');
                return false;
            } else {
                feedback.textContent = 'Senhas coincidem';
                feedback.style.color = '#2e7d32';
                feedback.style.display = 'block';
                document.getElementById('confirma_senha').setCustomValidity('');
                return true;
            }
        }

        // Aplicar validação em tempo real
        document.getElementById('confirma_senha').addEventListener('input', validatePasswords);
        document.getElementById('senha').addEventListener('input', validatePasswords);

        // Validar antes do envio do formulário
        document.getElementById('store-form').addEventListener('submit', function(event) {
            const submitBtn = document.getElementById('submit-btn');
            
            // Verificar se as senhas coincidem
            if (!validatePasswords()) {
                event.preventDefault();
                alert('Por favor, certifique-se de que as senhas coincidem.');
                return false;
            }
            
            // Verificar se todos os campos obrigatórios estão preenchidos
            const requiredFields = this.querySelectorAll('[required]');
            for (let field of requiredFields) {
                if (!field.value.trim()) {
                    field.focus();
                    alert(`Por favor, preencha o campo: ${field.previousElementSibling.textContent.replace(' *', '')}`);
                    event.preventDefault();
                    return false;
                }
            }
            
            // Validar CNPJ
            const cnpj = document.getElementById('cnpj').value.replace(/\D/g, '');
            if (cnpj.length !== 14) {
                alert('Por favor, informe um CNPJ válido com 14 dígitos.');
                document.getElementById('cnpj').focus();
                event.preventDefault();
                return false;
            }
            
            // Validar email
            const email = document.getElementById('email').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Por favor, informe um email válido.');
                document.getElementById('email').focus();
                event.preventDefault();
                return false;
            }
            
            // Validar CEP
            const cep = document.getElementById('cep').value.replace(/\D/g, '');
            if (cep.length !== 8) {
                alert('Por favor, informe um CEP válido com 8 dígitos.');
                document.getElementById('cep').focus();
                event.preventDefault();
                return false;
            }
            
            // Desabilitar botão de envio para evitar cliques múltiplos
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processando...';
            
            // Se chegou até aqui, o formulário é válido
            return true;
        });

        // Validação de CNPJ (algoritmo básico)
        function validarCNPJ(cnpj) {
            cnpj = cnpj.replace(/[^\d]+/g, '');
            
            if (cnpj.length !== 14) return false;
            
            // Elimina CNPJs inválidos conhecidos
            if (/^(\d)\1+$/.test(cnpj)) return false;
            
            // Valida DVs
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
            if (resultado != digitos.charAt(1)) return false;
            
            return true;
        }

        // Validação em tempo real do CNPJ
        document.getElementById('cnpj').addEventListener('blur', function() {
            const cnpj = this.value;
            if (cnpj && !validarCNPJ(cnpj)) {
                this.setCustomValidity('CNPJ inválido');
                this.style.borderColor = '#c62828';
            } else {
                this.setCustomValidity('');
                this.style.borderColor = '';
            }
        });

        // Remover estilo de erro quando o usuário começar a digitar novamente
        document.getElementById('cnpj').addEventListener('input', function() {
            this.style.borderColor = '';
        });
    </script>
    <style>
/* Adicione no final do CSS existente */
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
</style>
</body>
</html>