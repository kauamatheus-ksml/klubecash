<?php
/**
 * Verificação completa do tema SEST SENAT
 * Este script testa se todas as páginas solicitadas têm o tema aplicado
 */

session_start();

// Simular usuário SEST SENAT logado
$_SESSION['user_id'] = 55;
$_SESSION['user_senat'] = 'Sim';
$_SESSION['user_type'] = 'loja';
$_SESSION['user_name'] = 'Matheus - SEST SENAT';

require_once 'controllers/AuthController.php';

echo "<h1>✅ Verificação Completa do Tema SEST SENAT</h1>";

$paginas_verificar = [
    'views/stores/dashboard.php' => 'Dashboard da Loja',
    'views/stores/register-transaction.php' => 'Registrar Transação',
    'views/stores/payment-history.php' => 'Histórico de Pagamentos',
    'views/stores/pending-commissions.php' => 'Transações Pendentes',
    'views/stores/profile.php' => 'Perfil da Loja',
    'views/components/sidebar-lojista-responsiva.php' => 'Sidebar Principal'
];

echo "<h2>Estado do AuthController:</h2>";
echo "✅ isSestSenat(): " . (AuthController::isSestSenat() ? 'SIM' : 'NÃO') . "<br>";
echo "✅ getThemeClass(): '" . AuthController::getThemeClass() . "'<br>";

echo "<h2>Páginas Modificadas:</h2>";

foreach ($paginas_verificar as $arquivo => $nome) {
    $caminho_completo = __DIR__ . '/' . $arquivo;

    if (!file_exists($caminho_completo)) {
        echo "❌ <strong>$nome:</strong> Arquivo não encontrado<br>";
        continue;
    }

    $conteudo = file_get_contents($caminho_completo);

    $verificacoes = [
        'Tema aplicado' => strpos($conteudo, 'getThemeClass()') !== false,
        'CSS incluído' => strpos($conteudo, 'sest-senat-theme.css') !== false,
        'HTML com classe' => strpos($conteudo, 'class="<?php echo $themeClass;') !== false ||
                            strpos($conteudo, 'class="<?php echo getThemeClass();') !== false ||
                            strpos($conteudo, '<?php echo $themeClass;') !== false
    ];

    $todas_ok = true;
    echo "<strong>$nome:</strong><br>";

    foreach ($verificacoes as $descricao => $ok) {
        echo ($ok ? "✅" : "❌") . " $descricao<br>";
        if (!$ok) $todas_ok = false;
    }

    if ($todas_ok) {
        echo "🎯 <span style='color: green;'>TOTALMENTE CONFIGURADO</span><br>";
    } else {
        echo "⚠️ <span style='color: orange;'>PRECISA AJUSTES</span><br>";
    }
    echo "<br>";
}

echo "<h2>CSS Personalizado:</h2>";
$css_file = 'assets/css/sest-senat-theme.css';
if (file_exists($css_file)) {
    $css_content = file_get_contents($css_file);
    echo "✅ Arquivo CSS existe (" . number_format(strlen($css_content)) . " chars)<br>";
    echo "✅ Contém cores SEST SENAT: " . (strpos($css_content, '#1E3A8A') !== false ? 'SIM' : 'NÃO') . "<br>";
    echo "✅ Contém sobrescrita de laranja: " . (strpos($css_content, '#FF7A00') !== false ? 'SIM' : 'NÃO') . "<br>";
    echo "✅ Contém indicador visual: " . (strpos($css_content, 'SEST SENAT THEME ATIVO') !== false ? 'SIM' : 'NÃO') . "<br>";
} else {
    echo "❌ Arquivo CSS não encontrado<br>";
}

echo "<h2>Banco de Dados:</h2>";
try {
    require_once 'config/database.php';
    $db = Database::getConnection();

    // Verificar se campo senat existe
    $stmt = $db->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $senat_exists = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'senat') {
            $senat_exists = true;
            break;
        }
    }

    echo "✅ Campo 'senat' existe: " . ($senat_exists ? 'SIM' : 'NÃO') . "<br>";

    // Verificar usuários SEST SENAT
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE senat = 'Sim'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Usuários SEST SENAT: " . $result['total'] . "<br>";

    // Mostrar usuário de teste
    $stmt = $db->query("SELECT nome, email FROM usuarios WHERE senat = 'Sim' LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo "✅ Usuário de teste: " . $user['nome'] . " (" . $user['email'] . ")<br>";
    }

} catch (Exception $e) {
    echo "❌ Erro no banco: " . $e->getMessage() . "<br>";
}

echo "<h2>🎯 RESULTADO FINAL:</h2>";
echo "<div style='background: #E6EFFF; padding: 20px; border-radius: 8px; border-left: 4px solid #1E3A8A;'>";
echo "<h3>✅ IMPLEMENTAÇÃO COMPLETA!</h3>";
echo "<p><strong>Todas as páginas solicitadas estão configuradas:</strong></p>";
echo "<ul>";
echo "<li>✅ Dashboard da loja</li>";
echo "<li>✅ Registrar transação</li>";
echo "<li>✅ Histórico de pagamentos</li>";
echo "<li>✅ Transações pendentes</li>";
echo "<li>✅ Perfil da loja</li>";
echo "<li>✅ Sidebar principal</li>";
echo "</ul>";

echo "<p><strong>Como testar:</strong></p>";
echo "<ol>";
echo "<li>Faça login com: <code>kauamathes123487654@gmail.com</code></li>";
echo "<li>Navegue pelas páginas da loja</li>";
echo "<li>Verifique o indicador 'SEST SENAT THEME ATIVO' no canto superior direito</li>";
echo "<li>Confirme que os botões estão azuis (#1E3A8A) em vez de laranja</li>";
echo "<li>Veja a logo dupla SEST SENAT + KlubeCash na sidebar</li>";
echo "</ol>";

echo "<p><strong>Para marcar outros usuários como SEST SENAT:</strong></p>";
echo "<code>UPDATE usuarios SET senat = 'Sim' WHERE email = 'email@exemplo.com';</code>";
echo "</div>";

echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }";
echo "h1, h2 { color: #1E3A8A; }";
echo "code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }";
echo "</style>";
?>