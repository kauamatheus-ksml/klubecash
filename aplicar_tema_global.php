<?php
/**
 * Script para aplicar tema SEST SENAT em todas as páginas do sistema
 */

$paginas_para_modificar = [
    'views/stores/register-transaction.php',
    'views/stores/payment.php',
    'views/stores/payment-pix.php',
    'views/stores/payment-history.php',
    'views/stores/pending-commissions.php',
    'views/stores/profile.php',
    'views/stores/employees.php',
    'views/auth/register.php',
    'views/auth/recover-password.php',
    'views/client/dashboard.php',
    'views/client/balance.php',
    'views/client/profile.php',
    'views/admin/dashboard.php'
];

$modificacoes_feitas = 0;

foreach ($paginas_para_modificar as $pagina) {
    $arquivo = __DIR__ . '/' . $pagina;

    if (!file_exists($arquivo)) {
        echo "❌ Arquivo não encontrado: $pagina\n";
        continue;
    }

    $conteudo = file_get_contents($arquivo);
    $original = $conteudo;

    // 1. Verificar se já tem tema aplicado
    if (strpos($conteudo, 'AuthController::getThemeClass()') !== false) {
        echo "✅ Já modificado: $pagina\n";
        continue;
    }

    // 2. Adicionar lógica do tema antes do HTML
    $buscar_html = '<!DOCTYPE html>';
    if (strpos($conteudo, $buscar_html) !== false) {
        $adicionar_tema = '
// Carregar tema SEST SENAT
require_once \'../../controllers/AuthController.php\';
$isSestSenat = AuthController::isSestSenat();
$themeClass = AuthController::getThemeClass();
?>

';
        $conteudo = str_replace('?>' . "\n" . $buscar_html, $adicionar_tema . $buscar_html, $conteudo);

        // 3. Adicionar classe no HTML
        $conteudo = str_replace('<html lang="pt-BR">', '<html lang="pt-BR" class="<?php echo $themeClass; ?>">', $conteudo);

        // 4. Adicionar CSS antes de </head>
        $adicionar_css = '    <?php if ($isSestSenat): ?>
    <!-- CSS personalizado para SEST SENAT -->
    <link rel="stylesheet" href="../../assets/css/sest-senat-theme.css">
    <?php endif; ?>
</head>';

        $conteudo = str_replace('</head>', $adicionar_css, $conteudo);

        // 5. Salvar arquivo
        if ($conteudo !== $original) {
            file_put_contents($arquivo, $conteudo);
            echo "✅ Modificado: $pagina\n";
            $modificacoes_feitas++;
        }
    } else {
        echo "⚠️  HTML não encontrado em: $pagina\n";
    }
}

echo "\n🎯 Concluído! $modificacoes_feitas páginas modificadas.\n";
echo "\nPara testar:\n";
echo "1. Faça login com: kauamathes123487654@gmail.com\n";
echo "2. Navegue pelas páginas do sistema\n";
echo "3. Verifique se aparece 'SEST SENAT THEME ATIVO' no canto superior direito\n";
echo "4. Botões devem estar azuis (#1E3A8A) em vez de laranja\n";
?>