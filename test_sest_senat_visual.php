<?php
/**
 * Página de Teste Visual para SEST SENAT
 * Simula uma sessão SEST SENAT para testar visualmente o tema
 */

// Simular sessão SEST SENAT
session_start();
$_SESSION['user_id'] = 55; // ID do usuário Matheus que marcamos como SEST SENAT
$_SESSION['user_senat'] = 'Sim';
$_SESSION['user_type'] = 'loja';
$_SESSION['user_name'] = 'Matheus - SEST SENAT';

// Carregar AuthController
require_once 'controllers/AuthController.php';

$isSestSenat = AuthController::isSestSenat();
$themeClass = AuthController::getThemeClass();
?>
<!DOCTYPE html>
<html lang="pt-BR" class="<?php echo $themeClass; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Visual SEST SENAT - Klube Cash</title>

    <!-- CSS Base -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        :root {
            --primary-color: #FF7A00;
            --primary-light: #FFF0E6;
            --white: #FFFFFF;
            --dark-gray: #212529;
            --border-radius: 12px;
        }

        body {
            background: #f8f9fa;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--primary-light);
            border-radius: var(--border-radius);
        }

        .logo {
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        h1 {
            color: var(--dark-gray);
            margin-bottom: 10px;
        }

        .status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            margin: 10px 0;
        }

        .status.sest-senat {
            background: #1E3A8A;
            color: white;
        }

        .status.normal {
            background: #FF7A00;
            color: white;
        }

        .btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            margin: 10px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .test-section {
            margin: 20px 0;
            padding: 20px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
        }

        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }

        .debug-info {
            background: #000;
            color: #0f0;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 14px;
            margin: 20px 0;
        }
    </style>

    <!-- CSS SEST SENAT -->
    <?php if ($isSestSenat): ?>
    <link rel="stylesheet" href="assets/css/sest-senat-theme.css">
    <?php endif; ?>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <?php echo $isSestSenat ? 'SS' : 'KC'; ?>
            </div>
            <h1><?php echo $isSestSenat ? 'SEST SENAT' : 'Klube Cash'; ?></h1>
            <p>Sistema de Cashback</p>

            <div class="status <?php echo $isSestSenat ? 'sest-senat' : 'normal'; ?>">
                <?php echo $isSestSenat ? 'Cliente SEST SENAT' : 'Cliente Normal'; ?>
            </div>
        </div>

        <div class="test-section">
            <h2>Teste de Componentes</h2>

            <button class="btn btn-primary">Botão Primário</button>
            <button class="btn" type="submit">Botão Submit</button>
            <button class="btn submit-btn">Submit Class</button>

            <div class="info-box">
                <strong>Usuário:</strong> <?php echo $_SESSION['user_name']; ?><br>
                <strong>Tipo:</strong> <?php echo $_SESSION['user_type']; ?><br>
                <strong>SEST SENAT:</strong> <?php echo $_SESSION['user_senat']; ?>
            </div>
        </div>

        <div class="debug-info">
            <div>DEBUG INFO:</div>
            <div>AuthController::isSestSenat(): <?php echo $isSestSenat ? 'TRUE' : 'FALSE'; ?></div>
            <div>AuthController::getThemeClass(): "<?php echo $themeClass; ?>"</div>
            <div>HTML class: "<?php echo $themeClass; ?>"</div>
            <div>CSS carregado: <?php echo $isSestSenat ? 'SIM' : 'NÃO'; ?></div>
        </div>

        <div class="test-section">
            <h3>Instruções para Teste Completo:</h3>
            <ol>
                <li>Esta página simula um usuário SEST SENAT logado</li>
                <li>Se você vê "SEST SENAT THEME ATIVO" no canto superior direito, o tema está funcionando</li>
                <li>Os botões devem estar azuis (#1E3A8A) em vez de laranja</li>
                <li>Para testar login real, use: <strong>kauamathes123487654@gmail.com</strong></li>
                <li>Faça logout e login novamente para ver o tema aplicado em todo o sistema</li>
            </ol>
        </div>

        <div class="test-section">
            <h3>Links de Teste:</h3>
            <a href="views/auth/login.php?email=kauamathes123487654@gmail.com" class="btn">Login com Email SEST SENAT</a>
            <a href="views/stores/transactions.php" class="btn">Página de Transações</a>
            <a href="test_sest_senat_visual.php" class="btn">Recarregar Teste</a>
        </div>
    </div>

    <script>
        // Debug JavaScript
        console.log('SEST SENAT Debug:', {
            isSestSenat: <?php echo $isSestSenat ? 'true' : 'false'; ?>,
            themeClass: '<?php echo $themeClass; ?>',
            htmlClasses: document.documentElement.className,
            cssLoaded: document.querySelector('link[href*="sest-senat-theme.css"]') !== null
        });

        // Verificar se as variáveis CSS foram aplicadas
        const root = document.documentElement;
        const primaryColor = getComputedStyle(root).getPropertyValue('--primary-color');
        console.log('CSS --primary-color:', primaryColor);

        if (primaryColor.includes('1E3A8A') || primaryColor.includes('30, 58, 138')) {
            console.log('✅ SEST SENAT theme CSS aplicado com sucesso!');
        } else {
            console.log('❌ SEST SENAT theme CSS NÃO aplicado. Cor atual:', primaryColor);
        }
    </script>
</body>
</html>