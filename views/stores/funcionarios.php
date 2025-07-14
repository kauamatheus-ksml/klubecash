<?php
// views/store/funcionarios.php
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../utils/PermissionManager.php';

session_start();

// Verificar se está logado e tem acesso (lojista apenas)
if (!AuthController::isStoreOwner()) {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

$pageTitle = "Gerenciar Funcionários";
$errors = [];
$success = '';

// Processar ações do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'criar_funcionario':
                // Código para criar funcionário (já existe no StoreController)
                break;
                
            case 'atualizar_permissoes':
                $funcionarioId = (int)$_POST['funcionario_id'];
                $lojaId = $_SESSION['loja_vinculada_id'] ?? 0;
                
                // Atualizar todas as permissões
                foreach (PERMISSOES_MAPA as $modulo => $config) {
                    foreach ($config['acoes'] as $acao => $descricao) {
                        $key = "perm_{$modulo}_{$acao}";
                        $permitido = isset($_POST[$key]) ? true : false;
                        
                        PermissionManager::setPermission($funcionarioId, $lojaId, $modulo, $acao, $permitido);
                    }
                }
                
                $success = "Permissões atualizadas com sucesso!";
                break;
        }
    }
}

// Buscar funcionários da loja
$db = Database::getConnection();
$stmt = $db->prepare("
    SELECT id, nome, email, subtipo_funcionario, status, data_criacao
    FROM usuarios 
    WHERE loja_vinculada_id = ? AND tipo = 'funcionario'
    ORDER BY nome
");
$stmt->execute([$_SESSION['loja_vinculada_id']]);
$funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

include_once '../components/header.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Funcionários - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    
    <!-- CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- CSS Customizado -->
    <link rel="stylesheet" href="../../assets/css/sidebar-unified.css">
    <link rel="stylesheet" href="../../assets/css/employees-layout-fix.css">
</head>
<body class="employees-page">
    
<div class="container-fluid">
    <div class="row">
        <?php include_once '../components/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-users me-2"></i>
                    Gerenciar Funcionários
                </h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoFuncionario">
                    <i class="fas fa-plus me-1"></i>
                    Novo Funcionário
                </button>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Lista de Funcionários -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Funcionários Cadastrados
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($funcionarios)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nenhum funcionário cadastrado</h5>
                            <p class="text-muted">Clique em "Novo Funcionário" para adicionar o primeiro membro da sua equipe.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>E-mail</th>
                                        <th>Tipo</th>
                                        <th>Status</th>
                                        <th>Data Cadastro</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($funcionarios as $funcionario): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-user me-2"></i>
                                                <?= htmlspecialchars($funcionario['nome']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($funcionario['email']) ?></td>
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
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="editarPermissoes(<?= $funcionario['id'] ?>)">
                                                        <i class="fas fa-key"></i>
                                                        Permissões
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-edit"></i>
                                                        Editar
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

<!-- Modal Novo Funcionário -->
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
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" name="nome" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="telefone">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Funcionário *</label>
                            <select class="form-select" name="subtipo_funcionario" required id="subtipoSelect">
                                <option value="">Selecione...</option>
                                <option value="gerente">Gerente (Acesso total)</option>
                                <option value="financeiro">Financeiro (Foco em pagamentos)</option>
                                <option value="vendedor">Vendedor (Foco em vendas)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Senha Temporária *</label>
                            <input type="password" class="form-control" name="senha" required minlength="6">
                            <small class="text-muted">Mínimo 6 caracteres</small>
                        </div>
                    </div>
                    
                    <!-- Preview de Permissões -->
                    <div class="mt-4">
                        <h6>
                            <i class="fas fa-eye me-2"></i>
                            Prévia de Permissões
                        </h6>
                        <div id="permissionsPreview" class="border rounded p-3 bg-light">
                            <small class="text-muted">Selecione um tipo de funcionário para ver as permissões padrão</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        Cadastrar Funcionário
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Permissões -->
<div class="modal fade" id="modalPermissoes" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-key me-2"></i>
                    Editar Permissões
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="atualizar_permissoes">
                    <input type="hidden" name="funcionario_id" id="editFuncionarioId">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Como funciona:</strong> Marque as caixas para permitir que o funcionário acesse essas funcionalidades. 
                        Permissões desmarcadas serão negadas mesmo que o tipo de funcionário tenha acesso por padrão.
                    </div>
                    
                    <div id="permissionsForm">
                        <!-- Será preenchido via JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        Salvar Permissões
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Mapa de permissões para JavaScript
const permissoesMap = <?= json_encode(PERMISSOES_MAPA) ?>;

// Permissões padrão por subtipo
const permissoesPadrao = {
    'gerente': {
        'dashboard': ['ver'],
        'transacoes': ['ver', 'criar', 'upload_lote'],
        'comissoes': ['ver', 'pagar'],
        'funcionarios': ['ver', 'criar', 'editar', 'desativar'],
        'relatorios': ['ver'],
        'configuracoes': ['ver', 'editar']
    },
    'financeiro': {
        'dashboard': ['ver'],
        'transacoes': ['ver'],
        'comissoes': ['ver', 'pagar'],
        'relatorios': ['ver']
    },
    'vendedor': {
        'dashboard': ['ver'],
        'transacoes': ['ver', 'criar']
    }
};

// Preview de permissões no modal de criação
document.getElementById('subtipoSelect').addEventListener('change', function() {
    const subtipo = this.value;
    const preview = document.getElementById('permissionsPreview');
    
    if (!subtipo) {
        preview.innerHTML = '<small class="text-muted">Selecione um tipo de funcionário para ver as permissões padrão</small>';
        return;
    }
    
    const permissions = permissoesPadrao[subtipo];
    let html = '<div class="row">';
    
    Object.keys(permissions).forEach(modulo => {
        const config = permissoesMap[modulo];
        html += `<div class="col-md-6 mb-3">
            <h6 class="text-primary">${config.nome}</h6>
            <small class="text-muted d-block mb-2">${config.descricao}</small>
            <ul class="list-unstyled ms-3">`;
        
        permissions[modulo].forEach(acao => {
            const descricao = config.acoes[acao];
            html += `<li><i class="fas fa-check text-success me-2"></i>${descricao}</li>`;
        });
        
        html += '</ul></div>';
    });
    
    html += '</div>';
    preview.innerHTML = html;
});

// Função para editar permissões
function editarPermissoes(funcionarioId) {
    document.getElementById('editFuncionarioId').value = funcionarioId;
    
    // Carregar permissões atuais via AJAX
    fetch(`../../api/funcionarios.php?action=get_permissions&id=${funcionarioId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                preencherFormularioPermissoes(data.permissions);
                new bootstrap.Modal(document.getElementById('modalPermissoes')).show();
            }
        })
        .catch(error => {
            console.error('Erro ao carregar permissões:', error);
        });
}

// Função para preencher o formulário de permissões
function preencherFormularioPermissoes(permissions) {
    const form = document.getElementById('permissionsForm');
    let html = '';
    
    Object.keys(permissoesMap).forEach(modulo => {
        const config = permissoesMap[modulo];
        html += `
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">${config.nome}</h6>
                    <small class="text-muted">${config.descricao}</small>
                </div>
                <div class="card-body">
                    <div class="row">`;
        
        Object.keys(config.acoes).forEach(acao => {
            const descricao = config.acoes[acao];
            const checked = permissions[modulo] && permissions[modulo][acao] ? 'checked' : '';
            
            html += `
                <div class="col-md-6 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" 
                               name="perm_${modulo}_${acao}" ${checked}
                               id="perm_${modulo}_${acao}">
                        <label class="form-check-label" for="perm_${modulo}_${acao}">
                            ${descricao}
                        </label>
                    </div>
                </div>`;
        });
        
        html += `
                    </div>
                </div>
            </div>`;
    });
    
    form.innerHTML = html;
}
</script>
