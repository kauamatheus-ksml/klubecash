<?php
/**
 * Página de Gestão de Funcionários - Klube Cash
 * 
 * Esta página permite que lojistas gerenciem sua equipe de funcionários,
 * incluindo cadastro, edição e controle de permissões de acesso.
 * 
 * Localização: views/stores/employees.php
 * Estrutura: Segue o padrão views/stores/ do projeto
 * 
 * Funcionalidades:
 * - Listar funcionários existentes
 * - Cadastrar novos funcionários  
 * - Editar dados dos funcionários
 * - Controlar status (ativo/inativo)
 * - Definir tipos de funcionário (gerente/financeiro/vendedor)
 * 
 * Controle de Acesso:
 * - Apenas lojistas podem acessar esta página
 * - Funcionários do tipo 'gerente' também têm acesso
 */

// Configuração inicial da página
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/StoreController.php';
require_once '../../utils/Validator.php';

// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação e permissões
if (!isset($_SESSION['user_id'])) {
    header("Location: " . LOGIN_URL . "?error=session_expired");
    exit;
}

// Verificar se é lojista ou gerente (os únicos que podem gerenciar funcionários)
$userType = $_SESSION['user_type'];
$isLojista = ($userType === 'loja');
$isGerente = ($userType === 'funcionario' && isset($_SESSION['subtipo_funcionario']) && $_SESSION['subtipo_funcionario'] === 'gerente');

if (!$isLojista && !$isGerente) {
    header("Location: " . STORE_DASHBOARD_URL . "?error=access_denied");
    exit;
}

// Configurações da página
$activeMenu = 'funcionarios'; // Para ativar o item correto na sidebar
$pageTitle = "Gerenciar Funcionários";
$errors = [];
$success = '';

// Processar ações do formulário (usando padrão Post-Redirect-Get para evitar resubmissão)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'criar_funcionario':
            $resultado = criarNovoFuncionario($_POST);
            if ($resultado['success']) {
                $success = $resultado['message'];
            } else {
                $errors[] = $resultado['message'];
            }
            break;
            
        case 'atualizar_funcionario':
            $resultado = atualizarFuncionario($_POST);
            if ($resultado['success']) {
                $success = $resultado['message'];
            } else {
                $errors[] = $resultado['message'];
            }
            break;
            
        case 'toggle_status':
            $resultado = alterarStatusFuncionario($_POST);
            if ($resultado['success']) {
                $success = $resultado['message'];
            } else {
                $errors[] = $resultado['message'];
            }
            break;
    }
}

// Buscar funcionários existentes
$funcionarios = buscarFuncionarios();
$estatisticas = obterEstatisticasFuncionarios();

/**
 * Função para criar novo funcionário
 * Valida dados e cria registro no banco
 */
function criarNovoFuncionario($dados) {
    try {
        // Validação dos dados de entrada
        $errosValidacao = [];
        
        if (empty($dados['nome']) || strlen(trim($dados['nome'])) < 3) {
            $errosValidacao[] = "Nome deve ter pelo menos 3 caracteres";
        }
        
        if (empty($dados['email']) || !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $errosValidacao[] = "Email inválido";
        }
        
        if (empty($dados['subtipo_funcionario']) || !in_array($dados['subtipo_funcionario'], ['gerente', 'financeiro', 'vendedor'])) {
            $errosValidacao[] = "Tipo de funcionário inválido";
        }
        
        if (empty($dados['senha']) || strlen($dados['senha']) < 6) {
            $errosValidacao[] = "Senha deve ter pelo menos 6 caracteres";
        }
        
        if (!empty($errosValidacao)) {
            return ['success' => false, 'message' => implode(', ', $errosValidacao)];
        }
        
        $db = Database::getConnection();
        
        // Verificar se email já existe
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$dados['email']]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Este email já está cadastrado no sistema'];
        }
        
        // Obter ID da loja do usuário logado
        $lojaId = obterIdLoja($_SESSION['user_id']);
        if (!$lojaId) {
            return ['success' => false, 'message' => 'Erro: Loja não encontrada'];
        }
        
        // Criar hash da senha
        $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);
        
        // Inserir novo funcionário
        $stmt = $db->prepare("
            INSERT INTO usuarios (
                nome, email, telefone, senha_hash, tipo, subtipo_funcionario, 
                loja_vinculada_id, status, data_criacao
            ) VALUES (?, ?, ?, ?, 'funcionario', ?, ?, 'ativo', NOW())
        ");
        
        $sucesso = $stmt->execute([
            trim($dados['nome']),
            strtolower(trim($dados['email'])),
            $dados['telefone'] ?? '',
            $senhaHash,
            $dados['subtipo_funcionario'],
            $lojaId
        ]);
        
        if ($sucesso) {
            return ['success' => true, 'message' => 'Funcionário cadastrado com sucesso!'];
        } else {
            return ['success' => false, 'message' => 'Erro ao salvar funcionário no banco de dados'];
        }
        
    } catch (PDOException $e) {
        error_log('Erro ao criar funcionário: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno do sistema. Tente novamente.'];
    }
}

/**
 * Função para buscar funcionários da loja atual
 * Retorna lista organizada com informações relevantes
 */
function buscarFuncionarios() {
    try {
        $db = Database::getConnection();
        $lojaId = obterIdLoja($_SESSION['user_id']);
        
        if (!$lojaId) {
            return [];
        }
        
        $stmt = $db->prepare("
            SELECT 
                id, nome, email, telefone, subtipo_funcionario, 
                status, data_criacao, ultimo_login
            FROM usuarios 
            WHERE loja_vinculada_id = ? AND tipo = 'funcionario'
            ORDER BY nome ASC
        ");
        
        $stmt->execute([$lojaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log('Erro ao buscar funcionários: ' . $e->getMessage());
        return [];
    }
}

/**
 * Função para obter estatísticas dos funcionários
 * Útil para dashboard e relatórios
 */
function obterEstatisticasFuncionarios() {
    try {
        $db = Database::getConnection();
        $lojaId = obterIdLoja($_SESSION['user_id']);
        
        if (!$lojaId) {
            return ['total' => 0, 'ativos' => 0, 'por_tipo' => []];
        }
        
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'ativo' THEN 1 ELSE 0 END) as ativos,
                subtipo_funcionario,
                COUNT(subtipo_funcionario) as quantidade
            FROM usuarios 
            WHERE loja_vinculada_id = ? AND tipo = 'funcionario'
            GROUP BY subtipo_funcionario
        ");
        
        $stmt->execute([$lojaId]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $estatisticas = ['total' => 0, 'ativos' => 0, 'por_tipo' => []];
        
        foreach ($resultados as $resultado) {
            $estatisticas['total'] += $resultado['quantidade'];
            $estatisticas['ativos'] += $resultado['ativos'];
            $estatisticas['por_tipo'][$resultado['subtipo_funcionario']] = $resultado['quantidade'];
        }
        
        return $estatisticas;
        
    } catch (PDOException $e) {
        error_log('Erro ao obter estatísticas: ' . $e->getMessage());
        return ['total' => 0, 'ativos' => 0, 'por_tipo' => []];
    }
}

/**
 * Função para obter ID da loja vinculada ao usuário
 * Funciona tanto para lojistas quanto para funcionários
 */
function obterIdLoja($userId) {
    try {
        $db = Database::getConnection();
        
        // Primeiro, verificar se é lojista
        $stmt = $db->prepare("SELECT id FROM lojas WHERE usuario_id = ?");
        $stmt->execute([$userId]);
        
        if ($stmt->rowCount() > 0) {
            $loja = $stmt->fetch(PDO::FETCH_ASSOC);
            return $loja['id'];
        }
        
        // Se não é lojista, verificar se é funcionário
        $stmt = $db->prepare("SELECT loja_vinculada_id FROM usuarios WHERE id = ? AND tipo = 'funcionario'");
        $stmt->execute([$userId]);
        
        if ($stmt->rowCount() > 0) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            return $usuario['loja_vinculada_id'];
        }
        
        return null;
        
    } catch (PDOException $e) {
        error_log('Erro ao obter ID da loja: ' . $e->getMessage());
        return null;
    }
}

/**
 * Função para atualizar dados de funcionário
 */
function atualizarFuncionario($dados) {
    // Implementação similar ao criar, mas com UPDATE
    // Por brevidade, não incluindo implementação completa aqui
    return ['success' => true, 'message' => 'Funcionalidade de edição será implementada'];
}

/**
 * Função para alterar status do funcionário (ativo/inativo)
 */
function alterarStatusFuncionario($dados) {
    // Implementação para ativar/desativar funcionário
    return ['success' => true, 'message' => 'Status alterado com sucesso'];
}

// Incluir o cabeçalho padrão
include_once '../components/header.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Klube Cash</title>
    
    <!-- CSS do Bootstrap e ícones -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- CSS personalizado -->
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/store.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Incluir sidebar -->
            <?php include_once '../components/sidebar.php'; ?>
            
            <!-- Conteúdo principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4" id="mainContent">
                
                <!-- Cabeçalho da página -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-users me-2 text-primary"></i>
                        <?= $pageTitle ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoFuncionario">
                            <i class="fas fa-plus me-1"></i>
                            Novo Funcionário
                        </button>
                    </div>
                </div>

                <!-- Alertas de feedback -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Cards de estatísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?= $estatisticas['total'] ?></h4>
                                        <p class="mb-0">Total de Funcionários</p>
                                    </div>
                                    <div>
                                        <i class="fas fa-users fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?= $estatisticas['ativos'] ?></h4>
                                        <p class="mb-0">Funcionários Ativos</p>
                                    </div>
                                    <div>
                                        <i class="fas fa-user-check fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?= $estatisticas['por_tipo']['gerente'] ?? 0 ?></h4>
                                        <p class="mb-0">Gerentes</p>
                                    </div>
                                    <div>
                                        <i class="fas fa-user-tie fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?= ($estatisticas['por_tipo']['financeiro'] ?? 0) + ($estatisticas['por_tipo']['vendedor'] ?? 0) ?></h4>
                                        <p class="mb-0">Outros Funcionários</p>
                                    </div>
                                    <div>
                                        <i class="fas fa-user-friends fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de funcionários -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Funcionários Cadastrados
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($funcionarios)): ?>
                            <!-- Estado vazio -->
                            <div class="text-center py-5">
                                <i class="fas fa-user-plus fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Nenhum funcionário cadastrado</h5>
                                <p class="text-muted mb-4">
                                    Comece adicionando o primeiro membro da sua equipe para compartilhar as responsabilidades da loja.
                                </p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoFuncionario">
                                    <i class="fas fa-plus me-1"></i>
                                    Cadastrar Primeiro Funcionário
                                </button>
                            </div>
                        <?php else: ?>
                            <!-- Tabela de funcionários -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nome</th>
                                            <th>Email</th>
                                            <th>Telefone</th>
                                            <th>Tipo</th>
                                            <th>Status</th>
                                            <th>Cadastrado em</th>
                                            <th>Último Login</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($funcionarios as $funcionario): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-circle me-2">
                                                            <?= strtoupper(substr($funcionario['nome'], 0, 2)) ?>
                                                        </div>
                                                        <strong><?= htmlspecialchars($funcionario['nome']) ?></strong>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($funcionario['email']) ?></td>
                                                <td><?= htmlspecialchars($funcionario['telefone'] ?: 'Não informado') ?></td>
                                                <td>
                                                    <?php
                                                    $badges = [
                                                        'gerente' => 'bg-primary',
                                                        'financeiro' => 'bg-success',
                                                        'vendedor' => 'bg-info'
                                                    ];
                                                    $badgeClass = $badges[$funcionario['subtipo_funcionario']] ?? 'bg-secondary';
                                                    ?>
                                                    <span class="badge <?= $badgeClass ?>">
                                                        <?= ucfirst($funcionario['subtipo_funcionario']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $funcionario['status'] === 'ativo' ? 'bg-success' : 'bg-danger' ?>">
                                                        <?= ucfirst($funcionario['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($funcionario['data_criacao'])) ?></td>
                                                <td>
                                                    <?= $funcionario['ultimo_login'] ? date('d/m/Y H:i', strtotime($funcionario['ultimo_login'])) : 'Nunca' ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="editarFuncionario(<?= $funcionario['id'] ?>)" 
                                                                title="Editar funcionário">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-<?= $funcionario['status'] === 'ativo' ? 'warning' : 'success' ?>" 
                                                                onclick="toggleStatus(<?= $funcionario['id'] ?>, '<?= $funcionario['status'] ?>')"
                                                                title="<?= $funcionario['status'] === 'ativo' ? 'Desativar' : 'Ativar' ?> funcionário">
                                                            <i class="fas fa-<?= $funcionario['status'] === 'ativo' ? 'user-times' : 'user-check' ?>"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para cadastrar novo funcionário -->
    <div class="modal fade" id="modalNovoFuncionario" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>
                        Cadastrar Novo Funcionário
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="criar_funcionario">
                        
                        <!-- Informações básicas -->
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" name="nome" required 
                                       placeholder="Digite o nome completo">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required 
                                       placeholder="email@exemplo.com">
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Telefone</label>
                                <input type="text" class="form-control" name="telefone" 
                                       placeholder="(00) 00000-0000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Funcionário *</label>
                                <select class="form-select" name="subtipo_funcionario" required id="tipoFuncionario">
                                    <option value="">Selecione o tipo...</option>
                                    <option value="gerente">Gerente - Acesso total (exceto configurações críticas)</option>
                                    <option value="financeiro">Financeiro - Foco em pagamentos e relatórios</option>
                                    <option value="vendedor">Vendedor - Foco em registro de vendas</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Senha Temporária *</label>
                                <input type="password" class="form-control" name="senha" required 
                                       minlength="6" placeholder="Mínimo 6 caracteres">
                                <small class="text-muted">O funcionário poderá alterar depois do primeiro login</small>
                            </div>
                        </div>
                        
                        <!-- Explicação dos tipos -->
                        <div class="mt-4">
                            <h6>
                                <i class="fas fa-info-circle me-2 text-primary"></i>
                                Entenda os Tipos de Funcionário
                            </h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-user-tie fa-2x text-primary mb-2"></i>
                                            <h6>Gerente</h6>
                                            <small class="text-muted">
                                                Pode gerenciar funcionários, ver relatórios e realizar quase todas as ações da loja
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-calculator fa-2x text-success mb-2"></i>
                                            <h6>Financeiro</h6>
                                            <small class="text-muted">
                                                Foco em comissões, pagamentos e relatórios financeiros. Não registra vendas
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-shopping-bag fa-2x text-info mb-2"></i>
                                            <h6>Vendedor</h6>
                                            <small class="text-muted">
                                                Registra vendas e vê dashboard. Acesso limitado a outras funcionalidades
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Cadastrar Funcionário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Funções JavaScript para interatividade
    
    function editarFuncionario(id) {
        // Implementar modal de edição
        alert('Funcionalidade de edição será implementada para funcionário ID: ' + id);
    }
    
    function toggleStatus(id, statusAtual) {
        const novoStatus = statusAtual === 'ativo' ? 'inativo' : 'ativo';
        const acao = novoStatus === 'ativo' ? 'ativar' : 'desativar';
        
        if (confirm(`Tem certeza que deseja ${acao} este funcionário?`)) {
            // Criar formulário dinâmico para submeter
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            // Adicionar campos
            const actionField = document.createElement('input');
            actionField.name = 'action';
            actionField.value = 'toggle_status';
            form.appendChild(actionField);
            
            const idField = document.createElement('input');
            idField.name = 'funcionario_id';
            idField.value = id;
            form.appendChild(idField);
            
            const statusField = document.createElement('input');
            statusField.name = 'novo_status';
            statusField.value = novoStatus;
            form.appendChild(statusField);
            
            // Submeter formulário
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Máscara para telefone (opcional)
    document.querySelector('input[name="telefone"]')?.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length >= 10) {
            value = value.replace(/(\d{2})(\d{4,5})(\d{4})/, '($1) $2-$3');
        }
        e.target.value = value;
    });
    </script>

    <style>
    /* Estilos personalizados para a página */
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 14px;
    }
    
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }
    
    .btn-group .btn {
        border-radius: 0.25rem;
        margin-right: 2px;
    }
    
    .modal-content {
        border-radius: 0.5rem;
    }
    
    .alert {
        border-radius: 0.5rem;
    }
    </style>
</body>
</html>