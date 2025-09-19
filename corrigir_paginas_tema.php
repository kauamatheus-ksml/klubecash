<?php
/**
 * Script para corrigir as páginas que não têm o tema SEST SENAT completamente aplicado
 */

$paginas_corrigir = [
    'views/stores/register-transaction.php',
    'views/stores/payment-history.php',
    'views/stores/pending-commissions.php',
    'views/stores/profile.php'
];

foreach ($paginas_corrigir as $pagina) {
    $arquivo = __DIR__ . '/' . $pagina;

    if (!file_exists($arquivo)) {
        echo "❌ Arquivo não encontrado: $pagina\n";
        continue;
    }

    $conteudo = file_get_contents($arquivo);
    $original = $conteudo;

    // Verificar se já tem a lógica do tema
    if (strpos($conteudo, 'getThemeClass()') === false) {
        // Procurar por padrões onde podemos adicionar a lógica
        $temLogica = false;

        // Verificar se já existe ?> antes de <!DOCTYPE
        if (preg_match('/\?\>\s*\n\s*<!DOCTYPE/', $conteudo)) {
            $conteudo = preg_replace(
                '/(\?\>)\s*\n\s*(<!DOCTYPE)/',
                '$1

// Carregar tema SEST SENAT
require_once \'../../controllers/AuthController.php\';
$isSestSenat = AuthController::isSestSenat();
$themeClass = AuthController::getThemeClass();
?>

$2',
                $conteudo,
                1
            );
            $temLogica = true;
        }
        // Se não tem ?> antes, adicionar antes de <!DOCTYPE
        elseif (preg_match('/<!DOCTYPE/', $conteudo) && !$temLogica) {
            $conteudo = preg_replace(
                '/(<!DOCTYPE)/',
                '<?php
// Carregar tema SEST SENAT
require_once \'../../controllers/AuthController.php\';
$isSestSenat = AuthController::isSestSenat();
$themeClass = AuthController::getThemeClass();
?>

$1',
                $conteudo,
                1
            );
            $temLogica = true;
        }

        // Adicionar classe no HTML se não tem
        if (strpos($conteudo, 'class="<?php echo $themeClass;') === false) {
            $conteudo = str_replace(
                '<html lang="pt-BR">',
                '<html lang="pt-BR" class="<?php echo $themeClass; ?>">',
                $conteudo
            );
        }

        // Salvar se houve mudanças
        if ($conteudo !== $original) {
            file_put_contents($arquivo, $conteudo);
            echo "✅ Corrigido: $pagina\n";
        } else {
            echo "⚠️ Não foi possível corrigir automaticamente: $pagina\n";
        }
    } else {
        echo "✅ Já correto: $pagina\n";
    }
}

echo "\nConcluído!\n";
?>