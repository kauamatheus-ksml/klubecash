

<?php
// index-debug.php - Versão simplificada para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- INICIO DEBUG -->\n";

try {
    require_once './config/constants.php';
    require_once './config/database.php';
    
    echo "<!-- Constants e Database carregados -->\n";
    
    // Iniciar sessão
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    echo "<!-- Sessão iniciada -->\n";
    
    // Verificar usuário logado
    $isLoggedIn = isset($_SESSION['user_id']);
    $userType = $isLoggedIn ? ($_SESSION['user_type'] ?? '') : '';
    $userName = $isLoggedIn ? ($_SESSION['user_name'] ?? '') : '';
    
    echo "<!-- Usuario logado: " . ($isLoggedIn ? 'SIM' : 'NAO') . " -->\n";
    
    // Buscar lojas (simplificado)
    $partnerStores = [];
    try {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT nome_fantasia, categoria, porcentagem_cashback FROM lojas WHERE status = 'aprovado' LIMIT 3");
        $partnerStores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<!-- Lojas encontradas: " . count($partnerStores) . " -->\n";
    } catch (Exception $e) {
        echo "<!-- Erro nas lojas: " . $e->getMessage() . " -->\n";
        $partnerStores = [];
    }
    
} catch (Exception $e) {
    echo "<!-- ERRO GERAL: " . $e->getMessage() . " -->\n";
    die("Erro: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klube Cash - Debug</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .header {
            background: #FF7A00;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .debug {
            background: #333;
            color: #0f0;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="debug">
        <h3>🔧 DEBUG INFO:</h3>
        <p>✅ PHP Version: <?php echo PHP_VERSION; ?></p>
        <p>✅ SITE_URL: <?php echo defined('SITE_URL') ? SITE_URL : 'NÃO DEFINIDO'; ?></p>
        <p>✅ User Logado: <?php echo $isLoggedIn ? 'SIM (' . $userName . ')' : 'NÃO'; ?></p>
        <p>✅ Lojas Carregadas: <?php echo count($partnerStores); ?></p>
        <p>✅ Session Status: <?php echo session_status(); ?></p>
    </div>

    <div class="header">
        <h1>🎯 Klube Cash - Funcionando!</h1>
        <p>Se você está vendo isso, o PHP está funcionando perfeitamente.</p>
    </div>

    <div class="content">
        <h2>📊 Dashboard</h2>
        
        <?php if ($isLoggedIn): ?>
            <h3>Bem-vindo, <?php echo htmlspecialchars($userName); ?>!</h3>
            <p>Tipo de usuário: <?php echo htmlspecialchars($userType); ?></p>
        <?php else: ?>
            <h3>Visitante</h3>
            <p><a href="<?php echo LOGIN_URL; ?>">Fazer Login</a></p>
        <?php endif; ?>
        
        <h3>🏪 Lojas Parceiras (<?php echo count($partnerStores); ?>):</h3>
        <?php if (!empty($partnerStores)): ?>
            <ul>
                <?php foreach ($partnerStores as $store): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($store['nome_fantasia']); ?></strong>
                        - <?php echo htmlspecialchars($store['categoria'] ?? 'Sem categoria'); ?>
                        - <?php echo number_format($store['porcentagem_cashback'] ?? 0, 1); ?>% cashback
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Nenhuma loja parceira encontrada.</p>
        <?php endif; ?>
    </div>

    <div class="content">
        <h3>🔗 Links de Teste:</h3>
        <ul>
            <li><a href="<?php echo SITE_URL; ?>">Home Original</a></li>
            <li><a href="<?php echo LOGIN_URL; ?>">Login</a></li>
            <li><a href="<?php echo REGISTER_URL; ?>">Registro</a></li>
            <?php if ($isLoggedIn): ?>
                <li><a href="<?php echo 
                    $userType === 'admin' ? ADMIN_DASHBOARD_URL : 
                    ($userType === 'loja' ? STORE_DASHBOARD_URL : CLIENT_DASHBOARD_URL); 
                ?>">Meu Dashboard</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <script>
        console.log('🟢 JavaScript funcionando!');
        console.log('🟢 SITE_URL:', '<?php echo SITE_URL; ?>');
        console.log('🟢 Usuário logado:', <?php echo $isLoggedIn ? 'true' : 'false'; ?>);
        
        // Teste se consegue carregar os arquivos originais
        const cssTest = document.createElement('link');
        cssTest.rel = 'stylesheet';
        cssTest.href = '<?php echo SITE_URL; ?>/assets/css/index-v2.css';
        cssTest.onload = () => console.log('✅ CSS original carregado');
        cssTest.onerror = () => console.log('❌ CSS original FALHOU');
        document.head.appendChild(cssTest);
        
        const jsTest = document.createElement('script');
        jsTest.src = '<?php echo SITE_URL; ?>/assets/js/index-v2.js';
        jsTest.onload = () => console.log('✅ JS original carregado');
        jsTest.onerror = () => console.log('❌ JS original FALHOU');
        document.head.appendChild(jsTest);
    </script>
</body>
</html>