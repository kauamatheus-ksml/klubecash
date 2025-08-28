<?php
// views/admin/users.php
// Definir o menu ativo na sidebar
$activeMenu = 'usuarios';

// Incluir conexão com o banco de dados e arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
    // Redirecionar para a página de login com mensagem de erro
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Inicializar variáveis de paginação e filtros
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$filters = [];

// Processar filtros se enviados
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET)) {
    if (!empty($_GET['tipo']) && $_GET['tipo'] !== 'todos') {
        $filters['tipo'] = $_GET['tipo'];
    }
    if (!empty($_GET['status']) && $_GET['status'] !== 'todos') {
        $filters['status'] = $_GET['status'];
    }
    if (!empty($_GET['busca'])) {
        $filters['busca'] = trim($_GET['busca']);
    }
}

try {
    // Obter dados dos usuários com filtros aplicados
    $result = AdminController::manageUsers($filters, $page);

    // Verificar se houve erro
    $hasError = !$result['status'];
    $errorMessage = $hasError ? $result['message'] : '';

    // Dados para exibição na página
    $users = $hasError ? [] : $result['data']['usuarios'];
    $statistics = $hasError ? [] : $result['data']['estatisticas'];
    $pagination = $hasError ? [] : $result['data']['paginacao'];
} catch (Exception $e) {
    $hasError = true;
    $errorMessage = "Erro ao processar a requisição: " . $e->getMessage();
    $users = [];
    $statistics = [];
    $pagination = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    
    <!-- CSS Completo Inline para Design Perfeito -->
    <style>
        /* Font Awesome Icons Inline */
        @font-face { font-family: 'FontAwesome'; src: url('data:application/font-woff2;charset=utf-8;base64,') format('woff2'); }
        .fas, .fa { font-family: 'FontAwesome', sans-serif; }
        .fa-users::before { content: '👥'; }
        .fa-user::before { content: '👤'; }
        .fa-plus::before { content: '+'; }
        .fa-edit::before { content: '✏️'; }
        .fa-eye::before { content: '👁️'; }
        .fa-times::before { content: '✕'; }
        .fa-save::before { content: '💾'; }
        .fa-check::before { content: '✓'; }
        .fa-pause::before { content: '⏸️'; }
        .fa-ban::before { content: '🚫'; }
        .fa-star::before { content: '⭐'; }
        .fa-store::before { content: '🏪'; }
        .fa-user-shield::before { content: '🛡️'; }
        .fa-user-tie::before { content: '👔'; }
        .fa-search::before { content: '🔍'; }
        .fa-download::before { content: '⬇️'; }
        .fa-spinner::before { content: '⏳'; animation: fa-spin 1s infinite linear; }
        @keyframes fa-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        /* Reset e Base */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }
        
        /* Sidebar Inline Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            z-index: 1000;
            transform: translateX(0);
            transition: transform 0.3s ease;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }
        
        .sidebar-content {
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: 0;
        }
        
        .sidebar-header {
            padding: 30px 25px;
            background: rgba(0,0,0,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-logo {
            max-width: 120px;
            height: auto;
            filter: brightness(0) invert(1);
        }
        
        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto;
        }
        
        .sidebar-nav-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            position: relative;
        }
        
        .sidebar-nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #3498db;
        }
        
        .sidebar-nav-item.active {
            background: rgba(52, 152, 219, 0.2);
            color: white;
            border-left-color: #3498db;
            font-weight: 600;
        }
        
        .sidebar-nav-item svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
        }
        
        .sidebar-footer {
            padding: 20px 25px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }
        
        /* Mobile Sidebar */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
            transition: all 0.3s ease;
        }
        
        .sidebar-toggle:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        @media (max-width: 768px) {
            .sidebar-toggle { display: block; }
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0 !important;
            }
        }
        
        /* Modal */
        .modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal.show { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: 8px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 20px; }
        .modal-footer { padding: 20px; border-top: 1px solid #eee; display: flex; gap: 10px; justify-content: flex-end; }
        .modal-close { background: none; border: none; font-size: 20px; cursor: pointer; }
        
        /* Formulário */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-control, .form-select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .form-control:focus, .form-select:focus { outline: none; border-color: #007bff; box-shadow: 0 0 0 2px rgba(0,123,255,0.25); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-text { font-size: 12px; color: #666; margin-top: 5px; }
        
        /* Botões */
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn:hover { opacity: 0.9; }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        
        /* Tabela Modern */
        .table-container { 
            overflow-x: auto;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .table { 
            width: 100%; 
            border-collapse: collapse; 
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .table th, .table td { 
            padding: 16px; 
            text-align: left; 
            border-bottom: 1px solid #f1f3f4;
            vertical-align: middle;
        }
        
        .table th { 
            background: linear-gradient(135deg, #f8f9fc 0%, #f1f3f6 100%);
            font-weight: 600;
            color: #495057;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background: rgba(102, 126, 234, 0.02);
            transform: scale(1.001);
        }
        
        .table-actions { 
            display: flex; 
            gap: 8px;
            justify-content: center;
        }
        
        .action-btn { 
            padding: 10px 12px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
        }
        
        .action-btn.edit { 
            background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
            color: #2d3436;
        }
        
        .action-btn.view { 
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            color: white;
        }
        
        .action-btn.activate { 
            background: linear-gradient(135deg, #55efc4 0%, #00b894 100%);
            color: white;
        }
        
        .action-btn.deactivate { 
            background: linear-gradient(135deg, #fdcb6e 0%, #e17055 100%);
            color: white;
        }
        
        .action-btn:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        /* User Info na Tabela */
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .user-details {
            min-width: 0;
        }
        
        .user-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 15px;
            margin-bottom: 2px;
        }
        
        .user-email {
            color: #6c757d;
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Type Badges */
        .type-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .type-badge.type-cliente {
            background: linear-gradient(135deg, #55efc4 0%, #00b894 100%);
            color: white;
        }
        
        .type-badge.type-loja {
            background: linear-gradient(135deg, #fd79a8 0%, #e84393 100%);
            color: white;
        }
        
        .type-badge.type-admin {
            background: linear-gradient(135deg, #fdcb6e 0%, #e17055 100%);
            color: white;
        }
        
        .type-badge.type-funcionario {
            background: linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%);
            color: white;
        }
        
        /* Date Info */
        .date-info {
            text-align: center;
        }
        
        .date-primary {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
            margin-bottom: 2px;
        }
        
        .date-secondary {
            color: #6c757d;
            font-size: 12px;
        }
        
        /* Checkbox Styling */
        .checkbox-wrapper {
            position: relative;
            display: inline-block;
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid #dee2e6;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .checkbox-wrapper input[type="checkbox"]:checked {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
        }
        
        .checkbox-wrapper input[type="checkbox"]:checked::after {
            content: '\2713';
            position: absolute;
            top: -2px;
            left: 3px;
            color: white;
            font-size: 14px;
            font-weight: bold;
        }
        
        /* No Data State */
        .no-data {
            text-align: center;
            padding: 60px 20px;
        }
        
        .no-data-content {
            color: #6c757d;
        }
        
        .no-data-content i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .no-data-content h4 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #495057;
        }
        
        .no-data-content p {
            font-size: 14px;
            margin: 0;
        }
        
        /* Status Badges */
        .badge { 
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        
        .badge-success { 
            background: linear-gradient(135deg, #55efc4 0%, #00b894 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 184, 148, 0.3);
        }
        
        .badge-warning { 
            background: linear-gradient(135deg, #fdcb6e 0%, #e17055 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(225, 112, 85, 0.3);
        }
        
        .badge-danger { 
            background: linear-gradient(135deg, #fd79a8 0%, #e84393 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(232, 67, 147, 0.3);
        }
        
        /* MVP Badge Special */
        .badge.badge-warning .fa-star {
            color: #fff200;
            text-shadow: 0 0 8px rgba(255, 242, 0, 0.5);
        }
        
        /* Campo MVP específico */
        #mvpFieldGroup { margin: 20px 0; }
        .text-warning { color: #ffc107; }
        
        /* Loading */
        .loading-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.3); z-index: 20000; }
        .loading-spinner { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; }
        
        /* Layout Principal */
        .main-content { 
            margin-left: 280px; 
            min-height: 100vh;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px 0 0 20px;
            padding: 40px;
            transition: all 0.3s ease;
        }
        
        .page-header { 
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .page-title h1 { 
            font-size: 32px; 
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        
        .page-title p {
            color: #6c757d;
            font-size: 16px;
            margin: 0;
        }
        
        .page-actions { 
            margin-top: 0;
        }
        
        .card { 
            background: white; 
            border-radius: 16px; 
            padding: 30px; 
            margin-bottom: 30px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
        }
        
        /* Estatísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 45px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-icon.client { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-icon.store { background: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%); }
        .stat-icon.admin { background: linear-gradient(135deg, #fdbb2d 0%, #22c1c3 100%); }
        .stat-icon.employee { background: linear-gradient(135deg, #ee9ca7 0%, #ffdde1 100%); }
        
        .stat-content h3 {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-content p {
            color: #6c757d;
            font-size: 14px;
            font-weight: 500;
            margin: 0;
        }
        
        .stat-content small {
            color: #95a5a6;
            font-size: 12px;
            display: block;
            margin-top: 8px;
        }
        
        /* Filtros */
        .filters-section {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .filters-form {
            display: flex;
            gap: 20px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .search-bar {
            position: relative;
            flex: 2;
        }
        
        .search-bar input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .search-bar input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            transform: translateY(-50%) scale(1.05);
        }
        
        /* Pagination */
        .pagination-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .pagination-info {
            color: #6c757d;
            font-size: 14px;
        }
        
        .pagination {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .pagination a {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            text-decoration: none;
            color: #495057;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .pagination a:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .pagination a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
            font-weight: 600;
        }
        
        .pagination-arrow {
            padding: 8px 10px !important;
        }
        
        /* Bulk Actions */
        .bulk-action-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
        }
        
        .bulk-info {
            font-weight: 600;
        }
        
        .bulk-actions {
            display: flex;
            gap: 10px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #ff7675 0%, #d63031 100%);
            color: white;
            box-shadow: 0 8px 32px rgba(214, 48, 49, 0.3);
        }
        
        .alert-success {
            background: linear-gradient(135deg, #55efc4 0%, #00b894 100%);
            color: white;
            box-shadow: 0 8px 32px rgba(0, 184, 148, 0.3);
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .main-content {
                margin-left: 280px;
                padding: 30px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
                border-radius: 0;
            }
            
            .page-header {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            
            .page-title h1 {
                font-size: 24px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filters-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                min-width: auto;
            }
            
            .form-row { 
                grid-template-columns: 1fr; 
            }
            
            .modal-content { 
                margin: 20px; 
                width: calc(100% - 40px);
                max-height: 90vh;
            }
            
            .table-container {
                font-size: 14px;
            }
            
            .table th,
            .table td {
                padding: 12px 8px;
            }
            
            .user-info {
                flex-direction: column;
                text-align: center;
                gap: 8px;
            }
            
            .pagination-wrapper {
                flex-direction: column;
                text-align: center;
            }
            
            .bulk-action-bar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
        
        @media (max-width: 480px) {
            .main-content {
                padding: 15px;
            }
            
            .page-title h1 {
                font-size: 20px;
            }
            
            .card {
                padding: 20px;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .table {
                font-size: 12px;
            }
            
            .action-btn {
                min-width: 35px;
                height: 35px;
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Toggle Button (Mobile) -->
    <div class="sidebar-toggle" id="sidebarToggle">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </div>

    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>

    <!-- Sidebar Inline -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-content">
            <!-- Header da Sidebar -->
            <div class="sidebar-header">
                <img src="../../assets/images/logo.png" alt="KlubeCash" class="sidebar-logo">
            </div>
            
            <!-- Navegação Principal -->
            <nav class="sidebar-nav">
                <a href="<?php echo ADMIN_DASHBOARD_URL; ?>" 
                   class="sidebar-nav-item <?php echo ($activeMenu == 'painel') ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    Painel
                </a>
                
                <a href="<?php echo ADMIN_USERS_URL; ?>" 
                   class="sidebar-nav-item <?php echo ($activeMenu == 'usuarios') ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Usuários
                </a>
                
                <a href="<?php echo ADMIN_BALANCE_URL; ?>" 
                   class="sidebar-nav-item <?php echo ($activeMenu == 'saldo') ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    Saldo
                </a>
                
                <a href="<?php echo ADMIN_STORES_URL; ?>" 
                   class="sidebar-nav-item <?php echo ($activeMenu == 'lojas') ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3h18l-2 13H5L3 3z"></path>
                        <path d="M16 16a4 4 0 0 1-8 0"></path>
                    </svg>
                    Lojas
                </a>
                
                <a href="<?php echo ADMIN_PAYMENTS_URL; ?>" 
                   class="sidebar-nav-item <?php echo ($activeMenu == 'pagamentos') ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    Pagamentos
                </a>
                
                <a href="<?php echo ADMIN_TRANSACTIONS_URL; ?>" 
                   class="sidebar-nav-item <?php echo ($activeMenu == 'compras') ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    Compras
                </a>
                
                <a href="<?php echo SITE_URL; ?>/admin/relatorios" 
                   class="sidebar-nav-item <?php echo ($activeMenu == 'relatorios') ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    Relatórios
                </a>
                
                <a href="<?php echo ADMIN_SETTINGS_URL; ?>" 
                   class="sidebar-nav-item <?php echo ($activeMenu == 'configuracoes') ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    Configurações
                </a>
            </nav>
        </div>
        
        <!-- Footer da Sidebar -->
        <div class="sidebar-footer">
            <a href="<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout" 
               class="logout-btn" 
               onclick="return confirm('Tem certeza que deseja sair?')">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Sair
            </a>
        </div>
    </div>
    
    <!-- Conteúdo Principal -->
    <div class="main-content" id="mainContent">
        <div class="page-wrapper">
            <!-- Cabeçalho da Página -->
            <div class="page-header">
                <div class="page-title">
                    <h1><i class="fas fa-users"></i> Gerenciar Usuários</h1>
                    <p>Visualize e gerencie todos os usuários do sistema</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="showUserModal()">
                        <i class="fas fa-plus"></i> Novo Usuário
                    </button>
                </div>
            </div>

            <!-- Estatísticas Rápidas -->
            <?php if (!$hasError && !empty($statistics)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($statistics['total_usuarios']); ?></h3>
                        <p>Total de Usuários</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon client">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($statistics['total_clientes']); ?></h3>
                        <p>Clientes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon store">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($statistics['total_lojas']); ?></h3>
                        <p>Lojas</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon admin">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($statistics['total_admins']); ?></h3>
                        <p>Administradores</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon employee">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($statistics['total_funcionarios'] ?? 0); ?></h3>
                        <p>Funcionários</p>
                        <small>
                            Financeiro: <?php echo $statistics['total_funcionarios_financeiro'] ?? 0; ?> |
                            Gerente: <?php echo $statistics['total_funcionarios_gerente'] ?? 0; ?> |
                            Vendedor: <?php echo $statistics['total_funcionarios_vendedor'] ?? 0; ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Container de mensagens -->
            <div id="messageContainer" class="alert-container"></div>
            
            <!-- Filtros e Busca -->
            <div class="filters-section">
                <form method="GET" class="filters-form" id="filtersForm">
                    <div class="filter-group">
                        <div class="search-bar">
                            <input type="text" 
                                   name="busca" 
                                   id="searchInput"
                                   placeholder="Buscar por nome ou email..." 
                                   value="<?php echo htmlspecialchars($_GET['busca'] ?? ''); ?>">
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <select name="tipo" id="tipoFilter">
                            <option value="todos">Todos os tipos</option>
                            <option value="cliente" <?php echo (($_GET['tipo'] ?? '') === 'cliente') ? 'selected' : ''; ?>>Clientes</option>
                            <option value="loja" <?php echo (($_GET['tipo'] ?? '') === 'loja') ? 'selected' : ''; ?>>Lojas</option>
                            <option value="admin" <?php echo (($_GET['tipo'] ?? '') === 'admin') ? 'selected' : ''; ?>>Administradores</option>
                            <option value="funcionario" <?php echo (isset($_GET['tipo']) && $_GET['tipo'] === 'funcionario') ? 'selected' : ''; ?>>Funcionário</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <select name="status" id="statusFilter">
                            <option value="todos">Todos os status</option>
                            <option value="ativo" <?php echo (($_GET['status'] ?? '') === 'ativo') ? 'selected' : ''; ?>>Ativo</option>
                            <option value="inativo" <?php echo (($_GET['status'] ?? '') === 'inativo') ? 'selected' : ''; ?>>Inativo</option>
                            <option value="bloqueado" <?php echo (($_GET['status'] ?? '') === 'bloqueado') ? 'selected' : ''; ?>>Bloqueado</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Limpar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Barra de Ações em Massa -->
            <div id="bulkActionBar" class="bulk-action-bar" style="display: none;">
                <div class="bulk-info">
                    <span id="selectedCount">0</span> usuários selecionados
                </div>
                <div class="bulk-actions">
                    <button class="btn btn-sm btn-success" onclick="bulkAction('ativo')">
                        <i class="fas fa-check"></i> Ativar
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="bulkAction('inativo')">
                        <i class="fas fa-pause"></i> Desativar
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="bulkAction('bloqueado')">
                        <i class="fas fa-ban"></i> Bloquear
                    </button>
                </div>
            </div>
            
            <?php if ($hasError): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php else: ?>
            
            <!-- Tabela de Usuários -->
            <div class="card">
                <div class="card-header">
                    <h3>Lista de Usuários</h3>
                    <div class="card-actions">
                        <button class="btn btn-sm btn-outline" onclick="exportUsers()">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="checkbox-column">
                                    <div class="checkbox-wrapper">
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                        <span class="checkmark"></span>
                                    </div>
                                </th>
                                <th>Usuário</th>
                                <th>Tipo/Subtipo</th>
                                <th>MVP</th>
                                <th>Loja Vinculada</th>
                                <th>Status</th>
                                <th>Cadastro</th>
                                <th>Último Login</th>
                                <th class="actions-column">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="9" class="no-data">
                                        <div class="no-data-content">
                                            <i class="fas fa-users"></i>
                                            <h4>Nenhum usuário encontrado</h4>
                                            <p>Não há usuários que atendam aos critérios de busca.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="checkbox-wrapper">
                                                <input type="checkbox" 
                                                       class="user-checkbox" 
                                                       value="<?php echo $user['id']; ?>" 
                                                       onchange="toggleUserSelection(this, <?php echo $user['id']; ?>)">
                                                <span class="checkmark"></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div class="user-details">
                                                    <div class="user-name"><?php echo htmlspecialchars($user['nome']); ?></div>
                                                    <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="type-badge type-<?php echo $user['tipo']; ?>">
                                                <?php 
                                                switch($user['tipo']) {
                                                    case 'cliente':
                                                        echo 'Cliente';
                                                        break;
                                                    case 'admin':
                                                        echo 'Administrador';
                                                        break;
                                                    case 'loja':
                                                        echo 'Loja';
                                                        break;
                                                    case 'funcionario':
                                                        echo 'Funcionário';
                                                        if (!empty($user['subtipo_funcionario'])) {
                                                            echo '<br><small class="text-muted">' . ucfirst($user['subtipo_funcionario']) . '</small>';
                                                        }
                                                        break;
                                                    default:
                                                        echo ucfirst($user['tipo']);
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($user['tipo'] === 'loja'): ?>
                                                <!-- Debug: verificar valor MVP -->
                                                <!-- MVP: <?php echo isset($user['mvp']) ? $user['mvp'] : 'não definido'; ?> -->
                                                <?php if (isset($user['mvp']) && $user['mvp'] === 'sim'): ?>
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-star"></i> MVP
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['tipo'] === 'funcionario' && !empty($user['nome_loja_vinculada'])): ?>
                                                <span class="text-sm">
                                                    <i class="fas fa-store text-muted"></i>
                                                    <?php echo htmlspecialchars($user['nome_loja_vinculada']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                                $statusClass = '';
                                                $statusIcon = '';
                                                switch ($user['status']) {
                                                    case 'ativo':
                                                        $statusClass = 'badge-success';
                                                        $statusIcon = 'fas fa-check';
                                                        break;
                                                    case 'inativo':
                                                        $statusClass = 'badge-warning';
                                                        $statusIcon = 'fas fa-pause';
                                                        break;
                                                    case 'bloqueado':
                                                        $statusClass = 'badge-danger';
                                                        $statusIcon = 'fas fa-ban';
                                                        break;
                                                }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <i class="<?php echo $statusIcon; ?>"></i>
                                                <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <?php
                                                    // Calcula o timestamp com 3 horas a menos para a data de criação
                                                    $timestamp_criacao = strtotime($user['data_criacao']) - (3 * 60 * 60);
                                                ?>
                                                <div class="date-primary">
                                                    <?php echo date('d/m/Y', $timestamp_criacao); ?>
                                                </div>
                                                <div class="date-secondary">
                                                    <?php echo date('H:i', $timestamp_criacao); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <?php if ($user['ultimo_login']): ?>
                                                    <?php
                                                        // Calcula o timestamp com 3 horas a menos para o último login
                                                        $timestamp_login = strtotime($user['ultimo_login']) - (3 * 60 * 60);
                                                    ?>
                                                    <div class="date-primary">
                                                        <?php echo date('d/m/Y', $timestamp_login); ?>
                                                    </div>
                                                    <div class="date-secondary">
                                                        <?php echo date('H:i', $timestamp_login); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Nunca</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="action-btn edit" 
                                                        onclick="editUser(<?php echo $user['id']; ?>)"
                                                        title="Editar usuário">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="action-btn view" 
                                                        onclick="viewUser(<?php echo $user['id']; ?>)"
                                                        title="Visualizar usuário">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($user['status'] === 'ativo'): ?>
                                                    <button class="action-btn deactivate" 
                                                            onclick="changeUserStatus(<?php echo $user['id']; ?>, 'inativo', '<?php echo addslashes($user['nome']); ?>')"
                                                            title="Desativar usuário">
                                                        <i class="fas fa-pause"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="action-btn activate" 
                                                            onclick="changeUserStatus(<?php echo $user['id']; ?>, 'ativo', '<?php echo addslashes($user['nome']); ?>')"
                                                            title="Ativar usuário">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <?php if (!empty($pagination) && $pagination['total_paginas'] > 1): ?>
                    <div class="pagination-wrapper">
                        <div class="pagination-info">
                            Mostrando <?php echo (($page - 1) * $pagination['por_pagina']) + 1; ?>-<?php echo min($page * $pagination['por_pagina'], $pagination['total']); ?> 
                            de <?php echo $pagination['total']; ?> usuários
                        </div>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=1<?php echo http_build_query($_GET, '', '&amp;', PHP_QUERY_RFC3986); ?>" class="pagination-arrow" title="Primeira página">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="?page=<?php echo max(1, $page - 1); ?><?php echo http_build_query($_GET, '', '&amp;', PHP_QUERY_RFC3986); ?>" class="pagination-arrow" title="Página anterior">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php 
                                $startPage = max(1, $page - 2);
                                $endPage = min($pagination['total_paginas'], $startPage + 4);
                                if ($endPage - $startPage < 4) {
                                    $startPage = max(1, $endPage - 4);
                                }
                                
                                for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <a href="?page=<?php echo $i; ?><?php echo http_build_query($_GET, '', '&amp;', PHP_QUERY_RFC3986); ?>" 
                                   class="pagination-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $pagination['total_paginas']): ?>
                                <a href="?page=<?php echo min($pagination['total_paginas'], $page + 1); ?><?php echo http_build_query($_GET, '', '&amp;', PHP_QUERY_RFC3986); ?>" class="pagination-arrow" title="Próxima página">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                                <a href="?page=<?php echo $pagination['total_paginas']; ?><?php echo http_build_query($_GET, '', '&amp;', PHP_QUERY_RFC3986); ?>" class="pagination-arrow" title="Última página">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal de Adicionar/Editar Usuário -->
    <div class="modal" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="userModalTitle">
                    <i class="fas fa-user-plus"></i> Adicionar Usuário
                </h3>
                <button class="modal-close" onclick="hideUserModal()" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <form id="userForm" onsubmit="submitUserForm(event)">
                    <input type="hidden" id="userId" name="id" value="">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="userType">Tipo de Usuário</label>
                            <select class="form-select" id="userType" name="tipo" required>
                                <option value="">Selecione o tipo...</option>
                                <option value="cliente">Cliente</option>
                                <option value="loja">Loja</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required" for="userStatus">Status</label>
                            <select class="form-select" id="userStatus" name="status" required>
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                                <option value="bloqueado">Bloqueado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Campo MVP - só aparece para usuários tipo Loja -->
                    <div class="form-row" id="mvpFieldGroup" style="display: none;">
                        <div class="form-group">
                            <label class="form-label" for="userMvp">
                                <i class="fas fa-star text-warning"></i> Status MVP
                            </label>
                            <select class="form-select" id="userMvp" name="mvp">
                                <option value="nao">Não</option>
                                <option value="sim">Sim</option>
                            </select>
                            <small class="form-text text-muted">
                                Lojas MVP têm privilégios especiais na plataforma
                            </small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="userName">Nome Completo</label>
                        <input type="text" 
                               class="form-control" 
                               id="userName" 
                               name="nome" 
                               required 
                               placeholder="Digite o nome completo">
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="userEmail">E-mail</label>
                        <div id="emailSelectContainer" style="display: none;">
                            <select class="form-select" id="userEmailSelect" name="email_select">
                                <option value="">Selecione uma loja...</option>
                            </select>
                        </div>
                        <input type="email" 
                               class="form-control" 
                               id="userEmail" 
                               name="email" 
                               required 
                               placeholder="Digite o e-mail">
                    </div>
                    
                    <!-- Campos que serão preenchidos automaticamente quando for loja -->
                    <div id="storeDataFields" style="display: none;">
                        <div class="form-group">
                            <label class="form-label" for="storeName">Nome da Loja</label>
                            <input type="text" class="form-control" id="storeName" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="storeDocument">CNPJ</label>
                            <input type="text" class="form-control" id="storeDocument" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="storeCategory">Categoria</label>
                            <input type="text" class="form-control" id="storeCategory" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="userPhone">Telefone</label>
                        <input type="tel" 
                               class="form-control" 
                               id="userPhone" 
                               name="telefone" 
                               placeholder="(00) 00000-0000">
                    </div>
                    
                    <div class="form-group" id="passwordGroup">
                        <label class="form-label required" for="userPassword">Senha</label>
                        <div class="password-input">
                            <input type="password" 
                                   class="form-control" 
                                   id="userPassword" 
                                   name="senha"
                                   autocomplete="new-password"
                                   placeholder="Digite a senha">
                            <button type="button" class="password-toggle" onclick="togglePassword('userPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small id="passwordHelp" class="form-text">
                            Mínimo de 8 caracteres (deixe em branco para manter a senha atual ao editar)
                        </small>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="hideUserModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" form="userForm" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Visualização de Usuário -->
    <div class="modal" id="viewUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-eye"></i> Detalhes do Usuário
                </h3>
                <button class="modal-close" onclick="hideViewUserModal()" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div id="userViewContent">
                    <!-- Conteúdo será carregado dinamicamente -->
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="hideViewUserModal()">
                    <i class="fas fa-times"></i> Fechar
                </button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Carregando...</p>
        </div>
    </div>
    
    <!-- JavaScript Inline Robusto -->
    <script>
    // Variáveis globais
    let currentUserId = null;
    
    // Inicialização
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Página carregada - Inicializando funcionalidades...');
        
        // Verificar se elementos existem
        const mvpField = document.getElementById('mvpFieldGroup');
        console.log('Campo MVP encontrado:', mvpField ? 'SIM' : 'NÃO');
    });
    
    // Função para mostrar modal de edição
    function editUser(userId) {
        if (!userId) return;
        
        console.log('Editando usuário:', userId);
        currentUserId = userId;
        
        const modal = document.getElementById('userModal');
        const title = document.getElementById('userModalTitle');
        const form = document.getElementById('userForm');
        
        if (!modal || !form) {
            console.error('Modal ou formulário não encontrado!');
            return;
        }
        
        // Configurar modal
        if (title) title.innerHTML = '<i class="fas fa-user-edit"></i> Editar Usuário';
        form.reset();
        
        // Mostrar modal
        modal.classList.add('show');
        
        // Carregar dados do usuário
        fetch('/controllers/AdminController.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=getUserDetails&user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            console.log('Resposta do servidor:', data);
            
            if (data.status && data.data && data.data.usuario) {
                fillUserForm(data.data.usuario);
            } else {
                alert('Erro ao carregar dados do usuário: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar dados do usuário');
        });
    }
    
    // Função para preencher formulário
    function fillUserForm(userData) {
        console.log('Preenchendo formulário com:', userData);
        
        // Preencher campos básicos
        document.getElementById('userId').value = userData.id || '';
        document.getElementById('userName').value = userData.nome || '';
        document.getElementById('userEmail').value = userData.email || '';
        document.getElementById('userType').value = userData.tipo || '';
        document.getElementById('userStatus').value = userData.status || '';
        document.getElementById('userPhone').value = userData.telefone || '';
        document.getElementById('userPassword').value = '';
        
        // Campo MVP - FORÇAR exibição para lojas
        const mvpFieldGroup = document.getElementById('mvpFieldGroup');
        const mvpSelect = document.getElementById('userMvp');
        
        if (userData.tipo === 'loja' && mvpFieldGroup && mvpSelect) {
            console.log('Exibindo campo MVP para loja');
            
            // Forçar exibição
            mvpFieldGroup.style.display = 'block';
            mvpFieldGroup.style.visibility = 'visible';
            
            // Definir valor
            mvpSelect.value = userData.mvp || 'nao';
            
            console.log('Campo MVP configurado:', userData.mvp || 'nao');
        } else if (mvpFieldGroup) {
            mvpFieldGroup.style.display = 'none';
        }
    }
    
    // Função para esconder modal
    function hideUserModal() {
        const modal = document.getElementById('userModal');
        if (modal) {
            modal.classList.remove('show');
        }
        currentUserId = null;
    }
    
    // Função para submeter formulário
    function submitUserForm(event) {
        event.preventDefault();
        
        console.log('Submetendo formulário...');
        
        const form = document.getElementById('userForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (!form) return;
        
        // Desabilitar botão
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        }
        
        // Coletar dados
        const formData = new FormData(form);
        const userId = formData.get('id');
        const isEditing = userId !== '';
        
        // Preparar dados para envio
        const data = new URLSearchParams();
        data.append('action', 'update_user');
        data.append('user_id', userId);
        
        // Adicionar todos os campos do formulário
        for (let [key, value] of formData.entries()) {
            if (key !== 'id') {
                data.append(key, value);
                if (key === 'mvp') {
                    console.log('Campo MVP sendo enviado:', value);
                }
            }
        }
        
        // Enviar dados
        fetch('/controllers/AdminController.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: data
        })
        .then(response => response.json())
        .then(result => {
            console.log('Resultado:', result);
            
            if (result.status) {
                alert('Usuário atualizado com sucesso!');
                hideUserModal();
                location.reload(); // Recarregar página para mostrar mudanças
            } else {
                alert('Erro: ' + (result.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao salvar usuário');
        })
        .finally(() => {
            // Reabilitar botão
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Salvar';
            }
        });
    }
    
    // Event listeners
    document.addEventListener('click', function(event) {
        // Fechar modal ao clicar fora
        if (event.target.classList.contains('modal')) {
            hideUserModal();
        }
    });
    
    // Submissão do formulário
    document.addEventListener('submit', function(event) {
        if (event.target.id === 'userForm') {
            submitUserForm(event);
        }
    });
    
    // Funções vazias para evitar erros de funções não encontradas
    function viewUser(userId) { console.log('Ver usuário:', userId); }
    function changeUserStatus(userId, status) { console.log('Mudar status:', userId, status); }
    function showUserModal() { console.log('Mostrar modal de novo usuário'); }
    function toggleSelectAll() { console.log('Selecionar todos'); }
    function toggleUserSelection() { console.log('Selecionar usuário'); }
    function clearFilters() { location.href = location.pathname; }
    function exportUsers() { console.log('Exportar usuários'); }
    function bulkAction() { console.log('Ação em massa'); }
    
    // === SIDEBAR FUNCTIONALITY ===
    // Elementos da DOM
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const body = document.body;
    
    // Variável para controlar o estado da sidebar
    let sidebarOpen = false;
    
    // Evento para mostrar/ocultar a sidebar em dispositivos móveis
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    
    // Fechar sidebar com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebarOpen) {
            closeSidebar();
        }
    });
    
    function toggleSidebar() {
        if (sidebarOpen) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }
    
    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        body.style.overflow = 'hidden';
        sidebarOpen = true;
        
        if (sidebarToggle) {
            sidebarToggle.style.opacity = '0';
            sidebarToggle.style.pointerEvents = 'none';
        }
    }
    
    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        body.style.overflow = '';
        sidebarOpen = false;
        
        if (sidebarToggle) {
            sidebarToggle.style.opacity = '1';
            sidebarToggle.style.pointerEvents = 'auto';
        }
    }
    
    function checkScreenSize() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    }
    
    // Verificar o tamanho da tela ao carregar e redimensionar
    window.addEventListener('resize', checkScreenSize);
    checkScreenSize();
    </script>
    
</body>
</html>