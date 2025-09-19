<?php
// Script simples para corrigir páginas

$pages = [
    'views/stores/register-transaction.php',
    'views/stores/payment-history.php',
    'views/stores/pending-commissions.php',
    'views/stores/profile.php'
];

foreach ($pages as $page) {
    echo "Verificando: $page\n";

    if (!file_exists($page)) {
        echo "Arquivo não existe\n";
        continue;
    }

    $content = file_get_contents($page);

    // Verificar se precisa adicionar lógica do tema
    if (strpos($content, 'getThemeClass()') === false) {
        echo "Adicionando lógica do tema...\n";

        // Encontrar posição do <!DOCTYPE
        $pos = strpos($content, '<!DOCTYPE');
        if ($pos !== false) {
            $before = substr($content, 0, $pos);
            $after = substr($content, $pos);

            $tema_code = "
// Carregar tema SEST SENAT
require_once '../../controllers/AuthController.php';
\$isSestSenat = AuthController::isSestSenat();
\$themeClass = AuthController::getThemeClass();
?>

";

            $new_content = $before . $tema_code . $after;
            file_put_contents($page, $new_content);
            echo "Lógica adicionada!\n";
        }
    } else {
        echo "Já tem lógica do tema\n";
    }

    echo "---\n";
}

echo "Concluído!\n";
?>