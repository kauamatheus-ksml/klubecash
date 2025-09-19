<?php
/**
 * Componente Global para Tema SEST SENAT
 *
 * Este arquivo deve ser incluído em todas as páginas para aplicar
 * automaticamente o tema SEST SENAT quando necessário.
 */

// Verificar se AuthController já foi carregado
if (!class_exists('AuthController')) {
    require_once __DIR__ . '/../controllers/AuthController.php';
}

// Variáveis globais para o tema
$isSestSenat = false;
$themeClass = '';

// Verificar se há sessão ativa e usuário logado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    // Usuário logado - verificar se é SEST SENAT
    $isSestSenat = AuthController::isSestSenat();
    $themeClass = AuthController::getThemeClass();
} else {
    // Usuário não logado - verificar se há email na URL/POST para pré-carregar tema
    $emailToCheck = '';

    // Verificar POST (formulário de login)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
        $emailToCheck = $_POST['email'];
    }
    // Verificar GET (URL com email)
    elseif (isset($_GET['email'])) {
        $emailToCheck = $_GET['email'];
    }

    // Se temos email, verificar se é usuário SEST SENAT
    if (!empty($emailToCheck)) {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT senat FROM usuarios WHERE email = ? LIMIT 1");
            $stmt->execute([$emailToCheck]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['senat'] === 'Sim') {
                $isSestSenat = true;
                $themeClass = 'sest-senat-theme';
            }
        } catch (Exception $e) {
            // Em caso de erro, manter tema padrão
            $isSestSenat = false;
            $themeClass = '';
        }
    }
}

/**
 * Função helper para incluir CSS do tema
 */
function includeThemeCSS($basePath = '../../') {
    global $isSestSenat;

    if ($isSestSenat) {
        echo '<link rel="stylesheet" href="' . $basePath . 'assets/css/sest-senat-theme.css">' . "\n";
    }
}

/**
 * Função helper para obter a classe do tema
 */
function getThemeClass() {
    global $themeClass;
    return $themeClass;
}

/**
 * Função helper para verificar se é SEST SENAT
 */
function isSestSenat() {
    global $isSestSenat;
    return $isSestSenat;
}

/**
 * Função helper para obter o logo apropriado
 */
function getLogoHTML($basePath = '../../') {
    global $isSestSenat;

    if ($isSestSenat) {
        return '
            <div class="logo-container">
                <img src="' . $basePath . 'assets/images/sest-senat-logo.png" alt="SEST SENAT" class="logo-sest-senat">
                <img src="' . $basePath . 'assets/images/logo-icon.png" alt="Klube Cash" class="logo-klubecash">
                <span class="logo-text">SEST SENAT</span>
            </div>';
    } else {
        return '
            <div class="logo-container">
                <img src="' . $basePath . 'assets/images/logo-icon.png" alt="Klube Cash" class="logo-standard">
                <span class="logo-text">Klube Cash</span>
            </div>';
    }
}
?>