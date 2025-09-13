<?php
// termos-de-uso.php - Termos de Uso do Klube Cash
require_once './config/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? '') : '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos de Uso - Klube Cash</title>
    
    <!-- Meta tags -->
    <meta name="description" content="Termos de Uso do Klube Cash - Conheça os termos e condições para uso da nossa plataforma de cashback">
    <meta name="robots" content="index, follow">
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="assets/images/icons/KlubeCashLOGO.ico">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }

        .header {
            background: #fff;
            border-bottom: 1px solid #e0e0e0;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            height: 40px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #FF7A00;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .back-btn:hover {
            background: #e66a00;
        }

        .main-content {
            padding: 60px 0;
        }

        .legal-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .legal-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #333;
            margin-bottom: 15px;
        }

        .legal-header p {
            font-size: 1.1rem;
            color: #666;
        }

        .legal-content {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        .legal-section {
            margin-bottom: 40px;
        }

        .legal-section h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #FF7A00;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .legal-section h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin: 25px 0 15px;
        }

        .legal-section p {
            margin-bottom: 15px;
            line-height: 1.7;
        }

        .legal-section ul {
            margin: 15px 0;
            padding-left: 30px;
        }

        .legal-section li {
            margin-bottom: 8px;
            line-height: 1.6;
        }

        .highlight {
            background: rgba(255, 122, 0, 0.1);
            padding: 20px;
            border-left: 4px solid #FF7A00;
            border-radius: 8px;
            margin: 20px 0;
        }

        .contact-info {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin-top: 40px;
        }

        .contact-info h3 {
            color: #FF7A00;
            margin-bottom: 15px;
        }

        .footer {
            background: #1a1a1a;
            color: white;
            padding: 40px 0;
            text-align: center;
        }

        @media (max-width: 768px) {
            .legal-header h1 {
                font-size: 2rem;
            }

            .legal-content {
                padding: 30px 20px;
            }

            .header-content {
                flex-direction: column;
                gap: 20px;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <img src="assets/images/logolaranja.png" alt="Klube Cash" class="logo">
                <a href="<?php echo SITE_URL; ?>" class="back-btn">
                    ← Voltar ao Site
                </a>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="main-content">
        <div class="container">
            <div class="legal-header">
                <h1>Termos de Uso</h1>
                <p>Última atualização: <?php echo date('d/m/Y'); ?></p>
            </div>

            <div class="legal-content">
                <div class="legal-section">
                    <h2>1. Aceitação dos Termos</h2>
                    <p>Ao acessar e utilizar a plataforma Klube Cash, você concorda em cumprir e ficar vinculado a estes Termos de Uso. Se você não concorda com qualquer parte destes termos, não deve utilizar nossos serviços.</p>
                    
                    <div class="highlight">
                        <strong>Importante:</strong> Estes termos constituem um acordo legal entre você e a Klube Cash. Leia-os cuidadosamente antes de usar nossa plataforma.
                    </div>
                </div>

                <div class="legal-section">
                    <h2>2. Sobre o Klube Cash</h2>
                    <p>O Klube Cash é um programa de cashback que permite aos usuários receberem dinheiro de volta em suas compras realizadas em estabelecimentos parceiros.</p>
                    
                    <h3>2.1 Nossos Serviços</h3>
                    <ul>
                        <li>Programa de cashback em lojas parceiras</li>
                        <li>Plataforma digital para gerenciamento de créditos</li>
                        <li>Sistema de pagamentos e transferências</li>
                        <li>Suporte ao cliente especializado</li>
                    </ul>
                </div>

                <div class="legal-section">
                    <h2>3. Cadastro e Conta do Usuário</h2>
                    
                    <h3>3.1 Elegibilidade</h3>
                    <p>Para utilizar o Klube Cash, você deve:</p>
                    <ul>
                        <li>Ser maior de 18 anos ou ter autorização legal</li>
                        <li>Fornecer informações verdadeiras e atualizadas</li>
                        <li>Possuir documento de identificação válido</li>
                        <li>Ter acesso a email e telefone para verificação</li>
                    </ul>

                    <h3>3.2 Responsabilidades do Usuário</h3>
                    <ul>
                        <li>Manter a confidencialidade de suas credenciais de acesso</li>
                        <li>Notificar imediatamente sobre uso não autorizado da conta</li>
                        <li>Manter suas informações sempre atualizadas</li>
                        <li>Utilizar a plataforma apenas para fins legais</li>
                    </ul>
                </div>

                <div class="legal-section">
                    <h2>4. Como Funciona o Cashback</h2>
                    
                    <h3>4.1 Acúmulo de Cashback</h3>
                    <p>O cashback é acumulado quando você:</p>
                    <ul>
                        <li>Realiza compras em estabelecimentos parceiros</li>
                        <li>Se identifica como membro Klube Cash no momento da compra</li>
                        <li>A transação é confirmada pelo estabelecimento</li>
                    </ul>

                    <h3>4.2 Porcentagens e Limites</h3>
                    <ul>
                        <li>As porcentagens de cashback variam por estabelecimento</li>
                        <li>Podem existir limites mínimos e máximos de acúmulo</li>
                        <li>Promoções especiais podem alterar temporariamente as taxas</li>
                    </ul>

                    <h3>4.3 Processamento</h3>
                    <p>O cashback é processado em até 7 dias úteis após a confirmação da compra pelo estabelecimento parceiro.</p>
                </div>

                <div class="legal-section">
                    <h2>5. Uso da Plataforma</h2>
                    
                    <h3>5.1 Condutas Permitidas</h3>
                    <ul>
                        <li>Usar a plataforma para acumular e resgatar cashback</li>
                        <li>Compartilhar sua experiência positiva com outros usuários</li>
                        <li>Fornecer feedback construtivo sobre nossos serviços</li>
                    </ul>

                    <h3>5.2 Condutas Proibidas</h3>
                    <ul>
                        <li>Criar múltiplas contas para o mesmo usuário</li>
                        <li>Usar métodos automatizados para acessar a plataforma</li>
                        <li>Tentar fraudar o sistema de cashback</li>
                        <li>Compartilhar credenciais de acesso com terceiros</li>
                        <li>Usar a plataforma para atividades ilegais</li>
                    </ul>
                </div>

                <div class="legal-section">
                    <h2>6. Utilização do Cashback</h2>
                    
                    <h3>6.1 Como Usar Seu Saldo</h3>
                    <p>O saldo de cashback acumulado:</p>
                    <ul>
                        <li>Pode ser utilizado apenas na loja onde foi gerado</li>
                        <li>Funciona como crédito para futuras compras no mesmo estabelecimento</li>
                        <li>Não pode ser transferido entre diferentes lojas parceiras</li>
                        <li>Não há resgate em dinheiro ou transferência bancária</li>
                    </ul>

                    <div class="highlight">
                        <strong>Importante:</strong> Cada loja parceira mantém seu próprio saldo de cashback para você. O crédito gerado na Loja A só pode ser usado na Loja A.
                    </div>

                    <h3>6.2 Taxas</h3>
                    <p>O Klube Cash não cobra taxas para:</p>
                    <ul>
                        <li>Cadastro na plataforma</li>
                        <li>Acúmulo de cashback</li>
                        <li>Utilização do saldo nas compras</li>
                    </ul>
                </div>

                <div class="legal-section">
                    <h2>7. Privacidade e Proteção de Dados</h2>
                    <p>Respeitamos sua privacidade e estamos comprometidos com a proteção de seus dados pessoais, em conformidade com a Lei Geral de Proteção de Dados (LGPD).</p>
                    
                    <div class="highlight">
                        <strong>Seus dados são protegidos:</strong> Utilizamos criptografia de ponta e seguimos as melhores práticas de segurança para proteger suas informações.
                    </div>
                </div>

                <div class="legal-section">
                    <h2>8. Limitações e Responsabilidades</h2>
                    
                    <h3>8.1 Limitações do Serviço</h3>
                    <ul>
                        <li>A disponibilidade do cashback depende da participação dos estabelecimentos</li>
                        <li>Não garantimos a disponibilidade ininterrupta da plataforma</li>
                        <li>Valores de cashback podem ser alterados sem aviso prévio</li>
                    </ul>

                    <h3>8.2 Exclusão de Responsabilidade</h3>
                    <p>O Klube Cash não se responsabiliza por:</p>
                    <ul>
                        <li>Problemas com produtos ou serviços dos estabelecimentos parceiros</li>
                        <li>Perdas ou danos indiretos decorrentes do uso da plataforma</li>
                        <li>Interrupções temporárias do serviço</li>
                    </ul>
                </div>

                <div class="legal-section">
                    <h2>9. Alterações nos Termos</h2>
                    <p>Reservamo-nos o direito de modificar estes Termos de Uso a qualquer momento. As alterações serão comunicadas através da plataforma e entrarão em vigor imediatamente após a publicação.</p>
                </div>

                <div class="legal-section">
                    <h2>10. Encerramento da Conta</h2>
                    
                    <h3>10.1 Por Parte do Usuário</h3>
                    <p>Você pode encerrar sua conta a qualquer momento através do suporte ao cliente. Os saldos de cashback acumulados nas lojas parceiras permanecerão disponíveis para uso nas respectivas lojas, mesmo após o encerramento da conta na plataforma.</p>

                    <h3>10.2 Por Parte do Klube Cash</h3>
                    <p>Podemos suspender ou encerrar contas que violem estes termos, com notificação prévia quando possível. Saldos existentes poderão ser mantidos conforme análise do caso.</p>
                </div>

                <div class="legal-section">
                    <h2>11. Lei Aplicável e Foro</h2>
                    <p>Estes Termos são regidos pelas leis brasileiras. Qualquer controvérsia será resolvida no foro da comarca de Patos de Minas, MG.</p>
                </div>

                <div class="contact-info">
                    <h3>Dúvidas sobre os Termos?</h3>
                    <p>Entre em contato conosco:</p>
                    <p><strong>Email:</strong> contato@klubecash.com</p>
                    <p><strong>Telefone:</strong> (34) 9999-9999</p>
                    <p><strong>Endereço:</strong> Patos de Minas, MG</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Klube Cash. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>