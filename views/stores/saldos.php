<?php
// views/store/saldos.php
session_start();
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';

// Verificar autenticação
if (!AuthController::isAuthenticated()) {
    header('Location: ../auth/login.php');
    exit;
}

// Verificar se é loja
if (!AuthController::isStore()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = AuthController::getUser();
$storeId = $user['loja_id'];

$activeMenu = 'saldos'; // Definir o menu ativo
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saldos - Klube Cash</title>
    
    <!-- CSS -->
    <link href="../../assets/css/main.css" rel="stylesheet">
    <link href="../../assets/css/store.css" rel="stylesheet">
    <link href="../../assets/css/saldos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <!-- Incluir Header -->
    <?php 
    // Usar a sidebar do documento que você mostrou
    require_once '../../config/constants.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Include da sidebar
    include_once './sidebar_store.php'; // Criar um arquivo específico com a sidebar
    ?>
    
    <!-- Container Principal -->
    <div class="main-content">
        
        <
        
        <!-- Área de Conteúdo -->
        <div class="page-wrapper">
            <div class="container-fluid">
                
                <!-- Cabeçalho da Página -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-wallet"></i>
                        Saldos
                    </h1>
                    <p class="page-subtitle">Acompanhe os saldos disponíveis e devoluções</p>
                </div>

                <!-- Alertas -->
                <div id="alertContainer"></div>

                <!-- Cards de Saldos -->
                <div class="row mb-4">
                    
                    <!-- Saldo Disponível com Clientes -->
                    <div class="col-xl-6 col-lg-6 col-md-12 mb-4">
                        <div class="card saldo-card saldo-clientes">
                            <div class="card-header">
                                <div class="card-title-wrapper">
                                    <h5 class="card-title">
                                        <i class="fas fa-users text-primary"></i>
                                        Saldo Disponível com Clientes
                                    </h5>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="saldo-valor-container">
                                    <div class="saldo-valor" id="saldoClientes">
                                        <span class="loading-text">Carregando...</span>
                                    </div>
                                    <div class="saldo-descricao">
                                        Total de cashback que os clientes têm disponível para usar em sua loja
                                    </div>
                                </div>
                                
                                <div class="saldo-detalhes mt-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="metric">
                                                <span class="metric-label">Total de Clientes</span>
                                                <span class="metric-value" id="totalClientes">-</span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="metric">
                                                <span class="metric-label">Saldo Médio</span>
                                                <span class="metric-value" id="saldoMedio">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Saldo de Devolução da Plataforma -->
                    <div class="col-xl-6 col-lg-6 col-md-12 mb-4">
                        <div class="card saldo-card saldo-devolucao">
                            <div class="card-header">
                                <div class="card-title-wrapper">
                                    <h5 class="card-title">
                                        <i class="fas fa-undo text-success"></i>
                                        Saldo de Devolução da Plataforma
                                    </h5>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="saldo-valor-container">
                                    <div class="saldo-valor" id="saldoDevolucao">
                                        <span class="loading-text">Carregando...</span>
                                    </div>
                                    <div class="saldo-descricao">
                                        Valor que você receberá de volta quando os clientes utilizarem o cashback em sua loja
                                    </div>
                                </div>
                                
                                <div class="saldo-detalhes mt-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="metric">
                                                <span class="metric-label">Este Mês</span>
                                                <span class="metric-value" id="devolucaoMes">-</span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="metric">
                                                <span class="metric-label">Total Devolvido</span>
                                                <span class="metric-value" id="totalDevolvido">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informações Adicionais -->
                <div class="row">
                    <div class="col-12">
                        <div class="card info-card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-info-circle"></i>
                                    Como Funcionam os Saldos
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <h6><i class="fas fa-users text-primary"></i> Saldo Disponível com Clientes</h6>
                                            <p>Este é o valor total de cashback que todos os seus clientes acumularam e podem usar exclusivamente em sua loja. Quando um cliente utiliza esse cashback para fazer uma compra, você recebe o valor de volta.</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <h6><i class="fas fa-undo text-success"></i> Saldo de Devolução</h6>
                                            <p>Este é o valor que você já tem direito a receber de volta da plataforma. Esse saldo é formado quando os clientes usam o cashback em sua loja, e é creditado automaticamente em sua conta.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Histórico Recente -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-history"></i>
                                    Movimentação Recente
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="historicoTable">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Cliente</th>
                                                <th>Tipo</th>
                                                <th>Valor</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="historicoTableBody">
                                            <tr>
                                                <td colspan="5" class="text-center">
                                                    <span class="loading-text">Carregando movimentações...</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Incluir Footer -->
    <?php include_once '../components/footer.php'; ?>

    <!-- Scripts -->
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/saldos.js"></script>
</body>
</html>