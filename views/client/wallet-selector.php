<?php
// views/client/wallet-selector.php
session_start();

require_once '../../config/constants.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_CLIENT) {
    header('Location: ' . LOGIN_URL . '?error=acesso_restrito');
    exit;
}

function resolveSestSenatUrl(): string
{
    return trim((string) CLIENT_SESTSENAT_PORTAL_URL);
}

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedWallet = $_POST['wallet'] ?? '';

    if ($selectedWallet === 'klubecash') {
        $_SESSION['client_selected_wallet'] = 'klubecash';
        header('Location: ' . CLIENT_DASHBOARD_URL);
        exit;
    }

    if ($selectedWallet === 'sestsenat') {
        $_SESSION['client_selected_wallet'] = 'sestsenat';

        // Verificar se o usuário é do Senat
        $userSenat = $_SESSION['user_senat'] ?? 'Não';
        if ($userSenat !== 'Sim') {
            $errors[] = 'Acesso negado: Apenas usuários do Senat podem acessar esta carteira.';
        } else {
            $targetUrl = resolveSestSenatUrl();
            if ($targetUrl !== '') {
                // Preparar dados do usuário para o SestSenat
                $userData = [
                    'id' => $_SESSION['user_id'],
                    'nome' => $_SESSION['user_name'],
                    'email' => $_SESSION['user_email'],
                    'tipo' => $_SESSION['user_type'],
                    'senat' => $userSenat,
                    'status' => 'ativo'
                ];

                // Redirecionar com JavaScript para passar dados via localStorage
                echo '<script>
                    localStorage.setItem("senat_user", ' . json_encode(json_encode($userData)) . ');
                    window.location.href = "' . $targetUrl . '";
                </script>';
                exit;
            }

            $successMessage = 'Redirecionamento para a carteira SestSenat em breve. Aguarde a disponibilizacao do novo link.';
        }
    }

    if (!in_array($selectedWallet, ['klubecash', 'sestsenat'], true)) {
        $errors[] = 'Selecione uma carteira valida para continuar.';
    }
}

$clientName = $_SESSION['user_name'] ?? 'Cliente';
$sestSenatAvailable = resolveSestSenatUrl() !== '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolha sua carteira | Klube Cash</title>
    <link rel="shortcut icon" href="../../assets/images/icons/KlubeCashLOGO.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --orange: #ff7a00;
            --orange-dark: #e96f00;
            --blue: #1d4ed8;
            --surface: #ffffff;
            --muted: #6b7280;
            --shadow: 0 25px 55px -30px rgba(17, 24, 39, 0.4);
            --radius-lg: 28px;
            --radius-md: 18px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: radial-gradient(circle at top, rgba(255, 122, 0, 0.1), #f8fafc 55%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(1.5rem, 4vw, 3rem);
            color: #0f172a;
        }

        .wrapper {
            width: min(980px, 100%);
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            overflow: hidden;
        }

        .hero {
            position: relative;
            padding: clamp(2.5rem, 5vw, 3.5rem);
            background: linear-gradient(135deg, rgba(255, 122, 0, 0.95) 0%, rgba(255, 122, 0, 0.78) 55%, rgba(255, 255, 255, 0.2) 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(160deg, rgba(255, 255, 255, 0.18), transparent 60%);
            pointer-events: none;
        }

        .hero > * {
            position: relative;
            z-index: 1;
        }

        .badge {
            align-self: flex-start;
            padding: 0.45rem 1.2rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .hero h1 {
            font-size: clamp(2rem, 4vw, 2.9rem);
            margin: 0;
            line-height: 1.05;
        }

        .hero p {
            margin: 0;
            font-size: clamp(1rem, 2vw, 1.15rem);
            opacity: 0.92;
        }

        .hero-list {
            display: grid;
            gap: 0.75rem;
            font-size: 0.98rem;
            opacity: 0.92;
        }

        .panel {
            padding: clamp(2.5rem, 5vw, 3.5rem);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .alert {
            border-radius: 16px;
            padding: 1rem 1.2rem;
            font-size: 0.95rem;
            display: flex;
            gap: 0.8rem;
            align-items: flex-start;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            color: #b91c1c;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.12);
            color: #047857;
        }

        label {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--muted);
        }

        .select-wrapper {
            position: relative;
        }

        select {
            width: 100%;
            padding: 1.1rem 1.2rem;
            padding-right: 3.4rem;
            border-radius: var(--radius-md);
            border: 1px solid #e2e8f0;
            font-size: 1rem;
            font-weight: 500;
            color: #1f2937;
            appearance: none;
            background-color: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        select:focus {
            outline: none;
            border-color: rgba(255, 122, 0, 0.5);
            box-shadow: 0 0 0 4px rgba(255, 122, 0, 0.18);
        }

        .select-wrapper::after {
            content: '';
            position: absolute;
            right: 1.1rem;
            top: 50%;
            transform: translateY(-50%);
            border-width: 6px 5px 0 5px;
            border-style: solid;
            border-color: var(--orange) transparent transparent transparent;
        }

        .wallets {
            display: grid;
            gap: 1.2rem;
        }

        .wallet-card {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 1rem;
            padding: 1.4rem;
            border-radius: var(--radius-md);
            border: 1px solid #e2e8f0;
            background: linear-gradient(120deg, rgba(248, 250, 252, 0.88), rgba(255, 255, 255, 0.92));
            transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
            cursor: pointer;
        }

        .wallet-card[data-active="true"] {
            border-color: rgba(255, 122, 0, 0.55);
            box-shadow: 0 18px 42px -28px rgba(255, 122, 0, 0.65);
            transform: translateY(-4px);
        }

        .wallet-logo {
            width: clamp(58px, 11vw, 74px);
            height: clamp(58px, 11vw, 74px);
            border-radius: 20px;
            background: rgba(15, 23, 42, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .wallet-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .wallet-info {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
        }

        .wallet-info h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
        }

        .wallet-info p {
            margin: 0;
            font-size: 0.95rem;
            color: var(--muted);
            line-height: 1.55;
        }

        .wallet-status {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: #047857;
        }

        .wallet-status svg {
            width: 18px;
            height: 18px;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        button {
            border: none;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, var(--orange) 0%, var(--orange-dark) 100%);
            color: #fff;
            font-weight: 600;
            font-size: 1rem;
            padding: 1.05rem 1.2rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 48px -28px rgba(255, 122, 0, 0.7);
        }

        .helper-link {
            text-align: center;
            font-size: 0.95rem;
            color: var(--muted);
        }

        .helper-link a {
            color: var(--orange);
            font-weight: 600;
            text-decoration: none;
        }

        @media (max-width: 960px) {
            .wrapper {
                grid-template-columns: minmax(0, 1fr);
            }

            .hero {
                min-height: 240px;
            }
        }

        @media (max-width: 640px) {
            body {
                padding: 1rem;
            }

            .wrapper {
                border-radius: 22px;
            }

            .wallet-card {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <section class="hero">
            <span class="badge">Ola, <?php echo htmlspecialchars($clientName); ?></span>
            <div>
                <p style="letter-spacing: 0.12em; text-transform: uppercase; font-weight: 600; font-size: 0.88rem; opacity: 0.85;">acesso rapido</p>
                <h1>Qual carteira deseja acessar?</h1>
                <p>Escolha abaixo a experiencia desejada. Em poucos instantes voce sera direcionado para o ambiente correspondente.</p>
            </div>
            <div class="hero-list">
                <div>&bull; KlubeCash: acompanhe saldo, extrato e beneficios.</div>
                <div>&bull; SestSenat: novidades exclusivas para colaboradores e parceiros.</div>
            </div>
        </section>

        <section class="panel">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>Ops!</strong>
                    <div>
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($successMessage): ?>
                <div class="alert alert-success">
                    <strong>Tudo certo!</strong>
                    <div><?php echo htmlspecialchars($successMessage); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" class="form" id="wallet-form">
                <label for="wallet">Selecione a carteira</label>
                <div class="select-wrapper">
                    <select name="wallet" id="wallet" required>
                        <option value="" disabled selected>Escolha uma opcao</option>
                        <option value="klubecash">KlubeCash</option>
                        <option value="sestsenat">SestSenat</option>
                    </select>
                </div>

                <div class="wallets">
                    <article class="wallet-card" data-wallet="klubecash" data-active="false">
                        <div class="wallet-logo">
                            <img src="../../assets/images/logo.png" alt="Logo KlubeCash">
                        </div>
                        <div class="wallet-info">
                            <h2>KlubeCash</h2>
                            <p>Acesse sua area tradicional, acompanhe cashback disponivel e aproveite oportunidades em lojas parceiras.</p>
                            <span class="wallet-status">
                                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M16.6667 5L7.50001 14.1667L3.33334 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Disponivel agora
                            </span>
                        </div>
                    </article>

                    <article class="wallet-card" data-wallet="sestsenat" data-active="false">
                        <div class="wallet-logo">
                            <img src="../../assets/images/sestlogosenac.png" alt="Logo SestSenat">
                        </div>
                        <div class="wallet-info">
                            <h2>SestSenat</h2>
                            <p>Integracao exclusiva em React com vantagens dedicadas. Recurso disponivel assim que o novo ambiente for publicado.</p>
                            <span class="wallet-status" style="color: <?php echo $sestSenatAvailable ? '#047857' : '#b45309'; ?>;">
                                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10 3.33333L10 16.6667" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M3.33333 10H16.6667" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                <?php echo $sestSenatAvailable ? 'Disponivel agora' : 'Disponivel em breve'; ?>
                            </span>
                        </div>
                    </article>
                </div>

                <div class="actions">
                    <button type="submit">
                        Continuar
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 5L19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <p class="helper-link">Precisa trocar de conta? <a href="<?php echo LOGOUT_URL; ?>">Sair</a></p>
                </div>
            </form>
        </section>
    </div>

    <script>
        const select = document.getElementById('wallet');
        const cards = document.querySelectorAll('.wallet-card');

        function syncCards(selectedValue) {
            cards.forEach(card => {
                const isActive = card.dataset.wallet === selectedValue;
                card.setAttribute('data-active', isActive ? 'true' : 'false');
            });
        }

        select.addEventListener('change', event => {
            syncCards(event.target.value);
        });

        cards.forEach(card => {
            card.addEventListener('click', () => {
                const wallet = card.dataset.wallet;
                select.value = wallet;
                syncCards(wallet);
            });
        });

        syncCards(select.value);
    </script>
</body>
</html>
