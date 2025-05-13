<?php
// views/stores/register.php
// Incluir arquivos de configuração
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../config/email.php';
require_once '../../controllers/StoreController.php';
require_once '../../utils/Validator.php';

// Verificar se já existe uma sessão ativa
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && $_SESSION['user_type'] == USER_TYPE_ADMIN;

// Processar o formulário de cadastro de loja
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturar e sanitizar dados do formulário
    $data = [
        'nome_fantasia' => filter_input(INPUT_POST, 'nome_fantasia', FILTER_SANITIZE_STRING),
        'razao_social' => filter_input(INPUT_POST, 'razao_social', FILTER_SANITIZE_STRING),
        'cnpj' => filter_input(INPUT_POST, 'cnpj', FILTER_SANITIZE_STRING),
        'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
        'telefone' => filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING),
        'categoria' => filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_STRING),
        'descricao' => filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING),
        'website' => filter_input(INPUT_POST, 'website', FILTER_SANITIZE_URL),
        'porcentagem_cashback' => filter_input(INPUT_POST, 'porcentagem_cashback', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
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
    
    // Validar campos
    $errors = [];
    
    if (empty($data['nome_fantasia'])) {
        $errors[] = 'Nome fantasia é obrigatório';
    }
    
    if (empty($data['razao_social'])) {
        $errors[] = 'Razão social é obrigatória';
    }
    
    if (empty($data['cnpj']) || !Validator::validaCNPJ($data['cnpj'])) {
        $errors[] = 'CNPJ inválido';
    }
    
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido';
    }
    
    if (empty($data['telefone'])) {
        $errors[] = 'Telefone é obrigatório';
    }
    
    // Se não houver erros, prosseguir com o cadastro
    if (empty($errors)) {
        // Formatar CNPJ (remover caracteres especiais)
        $data['cnpj'] = preg_replace('/[^0-9]/', '', $data['cnpj']);
        
        // Registrar a loja
        $result = StoreController::registerStore($data);
        
        if ($result['status']) {
            $success = $result['message'];
            
            // Limpar dados do formulário se cadastro bem-sucedido
            if (!$result['awaiting_approval']) {
                $data = [];
            }
        } else {
            $error = $result['message'];
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// Carregar lista de categorias disponíveis
$categorias = [
    'Alimentação', 'Vestuário', 'Eletrônicos', 'Casa e Decoração', 
    'Beleza e Saúde', 'Serviços', 'Educação', 'Entretenimento', 'Outros'
];
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
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
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
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
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
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .benefits-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Incluir navbar -->
    <?php include_once '../components/navbar.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Torne-se uma Loja Parceira</h1>
            <p>Aumente suas vendas oferecendo cashback aos seus clientes</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <?php if (isset($result['awaiting_approval']) && $result['awaiting_approval']): ?>
                    <p>Sua solicitação foi recebida e está aguardando aprovação.</p>
                <?php else: ?>
                    <p>Sua loja foi aprovada e já está ativa no sistema.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="post" action="" id="store-form">
                <h2 class="section-title">Informações da Empresa</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="nome_fantasia">Nome Fantasia*</label>
                        <input type="text" id="nome_fantasia" name="nome_fantasia" class="form-control" required value="<?php echo isset($data['nome_fantasia']) ? htmlspecialchars($data['nome_fantasia']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="razao_social">Razão Social*</label>
                        <input type="text" id="razao_social" name="razao_social" class="form-control" required value="<?php echo isset($data['razao_social']) ? htmlspecialchars($data['razao_social']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="cnpj">CNPJ*</label>
                        <input type="text" id="cnpj" name="cnpj" class="form-control" required value="<?php echo isset($data['cnpj']) ? htmlspecialchars($data['cnpj']) : ''; ?>">
                        <small class="form-text">Digite apenas números ou formato XX.XXX.XXX/XXXX-XX</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="categoria">Categoria/Segmento*</label>
                        <select id="categoria" name="categoria" class="form-control" required>
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
                        <label class="form-label" for="email">E-mail*</label>
                        <input type="email" id="email" name="email" class="form-control" required value="<?php echo isset($data['email']) ? htmlspecialchars($data['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="telefone">Telefone*</label>
                        <input type="tel" id="telefone" name="telefone" class="form-control" required value="<?php echo isset($data['telefone']) ? htmlspecialchars($data['telefone']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="website">Website</label>
                        <input type="url" id="website" name="website" class="form-control" value="<?php echo isset($data['website']) ? htmlspecialchars($data['website']) : ''; ?>">
                    </div>
                </div>
                
                <h2 class="section-title">Endereço</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="cep">CEP*</label>
                        <input type="text" id="cep" name="cep" class="form-control" required value="<?php echo isset($data['endereco']['cep']) ? htmlspecialchars($data['endereco']['cep']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="logradouro">Logradouro*</label>
                        <input type="text" id="logradouro" name="logradouro" class="form-control" required value="<?php echo isset($data['endereco']['logradouro']) ? htmlspecialchars($data['endereco']['logradouro']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="numero">Número*</label>
                        <input type="text" id="numero" name="numero" class="form-control" required value="<?php echo isset($data['endereco']['numero']) ? htmlspecialchars($data['endereco']['numero']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="complemento">Complemento</label>
                        <input type="text" id="complemento" name="complemento" class="form-control" value="<?php echo isset($data['endereco']['complemento']) ? htmlspecialchars($data['endereco']['complemento']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="bairro">Bairro*</label>
                        <input type="text" id="bairro" name="bairro" class="form-control" required value="<?php echo isset($data['endereco']['bairro']) ? htmlspecialchars($data['endereco']['bairro']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="cidade">Cidade*</label>
                        <input type="text" id="cidade" name="cidade" class="form-control" required value="<?php echo isset($data['endereco']['cidade']) ? htmlspecialchars($data['endereco']['cidade']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="estado">Estado*</label>
                        <input type="text" id="estado" name="estado" class="form-control" required value="<?php echo isset($data['endereco']['estado']) ? htmlspecialchars($data['endereco']['estado']) : ''; ?>">
                    </div>
                </div>
                
                <h2 class="section-title">Configurações de Cashback</h2>
                
                <div class="form-group">
                    <label class="form-label" for="porcentagem_cashback">Porcentagem de Cashback</label>
                    <input type="number" id="porcentagem_cashback" name="porcentagem_cashback" class="form-control" step="0.01" min="0" max="30" value="<?php echo isset($data['porcentagem_cashback']) ? htmlspecialchars($data['porcentagem_cashback']) : DEFAULT_CASHBACK_TOTAL; ?>">
                    <small class="form-text">Porcentagem que será devolvida aos clientes (padrão: <?php echo DEFAULT_CASHBACK_TOTAL; ?>%)</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="descricao">Descrição da Loja</label>
                    <textarea id="descricao" name="descricao" class="form-control" rows="4"><?php echo isset($data['descricao']) ? htmlspecialchars($data['descricao']) : ''; ?></textarea>
                    <small class="form-text">Descreva brevemente sua loja (produtos, diferenciais, etc)</small>
                </div>
                
                <div class="form-terms">
                    <h3>Termos e Condições</h3>
                    <p>Ao se cadastrar como loja parceira, você concorda com os seguintes termos:</p>
                    <ul>
                        <li>O Klube Cash poderá analisar e aprovar sua solicitação antes de ativá-la no sistema.</li>
                        <li>Você concorda em oferecer o cashback conforme a porcentagem cadastrada.</li>
                        <li>O Klube Cash poderá exibir sua loja no catálogo de parceiros.</li>
                        <li>Todas as transações serão processadas de acordo com as regras do sistema.</li>
                    </ul>
                    <div class="form-group" style="margin-top: 15px;">
                        <label>
                            <input type="checkbox" name="aceite_termos" required>
                            Concordo com os termos e condições acima
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-block">Cadastrar Loja</button>
            </form>
        </div>
        
        <div class="benefits-section">
            <div class="benefit-card">
                <div class="benefit-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M16 12l-4 4-4-4"></path>
                        <path d="M12 8v8"></path>
                    </svg>
                </div>
                <h3 class="benefit-title">Aumente suas Vendas</h3>
                <p>O cashback é um forte incentivo para novos clientes e fidelização dos atuais.</p>
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
                <p>Acesse nossa base de usuários procurando por lojas que oferecem cashback.</p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline>
                        <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>
                    </svg>
                </div>
                <h3 class="benefit-title">Fácil Implementação</h3>
                <p>Sistema simples de integrar com seu negócio, sem complicações técnicas.</p>
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
        
        // Preenchimento automático do endereço pelo CEP
        document.getElementById('cep').addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            
            if (cep.length !== 8) {
                return;
            }
            
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (!data.erro) {
                        document.getElementById('logradouro').value = data.logradouro;
                        document.getElementById('bairro').value = data.bairro;
                        document.getElementById('cidade').value = data.localidade;
                        document.getElementById('estado').value = data.uf;
                        document.getElementById('numero').focus();
                    }
                })
                .catch(error => console.error('Erro:', error));
        });
        
        // Máscara para o CEP
        document.getElementById('cep').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 5) {
                value = value.replace(/^(\d{5})(\d)/, '$1-$2');
            }
            
            e.target.value = value;
        });
        
        // Validação de formulário
        document.getElementById('store-form').addEventListener('submit', function(event) {
            // Validações adicionais podem ser implementadas aqui
        });
    </script>
</body>
</html>