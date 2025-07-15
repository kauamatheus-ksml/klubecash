<?php
/**
 * Script de correção imediata para redirecionamento de funcionários
 * Execute este arquivo para corrigir o redirecionamento específico
 */
session_start();

// Verificar se é funcionário tentando acessar área do cliente
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'funcionario') {
    $currentUrl = $_SERVER['REQUEST_URI'];
    
    // Se estiver na área do cliente, redirecionar imediatamente
    if (strpos($currentUrl, '/cliente/') !== false) {
        error_log("CORREÇÃO AUTOMÁTICA - Funcionário {$_SESSION['user_name']} redirecionado de {$currentUrl} para " . STORE_DASHBOARD_URL);
        
        header("Location: " . STORE_DASHBOARD_URL);
        exit;
    }
}

// Se chegou até aqui, fazer verificação manual
if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    require_once 'config/constants.php';
    
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT tipo, subtipo_funcionario FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData && $userData['tipo'] === 'funcionario') {
            echo "<script>window.location.href = '" . STORE_DASHBOARD_URL . "';</script>";
            echo "<meta http-equiv='refresh' content='0;url=" . STORE_DASHBOARD_URL . "'>";
            echo "<p>Redirecionando funcionário para área correta...</p>";
            exit;
        }
    } catch (Exception $e) {
        error_log("Erro na correção: " . $e->getMessage());
    }
}

echo "<p>Redirecionamento não necessário ou já está correto.</p>";
?>