<?php
// views/auth/verify-2fa.php
session_start();

// Verificar se tem usuário pendente de 2FA
if (!isset($_SESSION['pending_2fa_user_id'])) {
    header('Location: ' . LOGIN_URL);
    exit;
}

$userData = $_SESSION['pending_2fa_user_data'] ?? [];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Segurança - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #FF7A00 0%, #FFA500 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .verify-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }

        .logo {
            max-width: 120px;
            margin-bottom: 30px;
        }

        .verify-header h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .verify-header p {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .email-info {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
        }

        .email-info strong {
            color: #FF7A00;
        }

        .code-input {
            width: 100%;
            padding: 15px;
            font-size: 18px;
            text-align: center;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            margin-bottom: 20px;
            letter-spacing: 3px;
            font-family: monospace;
            transition: border-color 0.3s;
        }

        .code-input:focus {
            outline: none;
            border-color: #FF7A00;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: #FF7A00;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-bottom: 15px;
        }

        .btn:hover:not(:disabled) {
            background: #E06E00;
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .btn-link {
            background: none;
            color: #FF7A00;
            border: none;
            font-size: 14px;
            cursor: pointer;
            text-decoration: underline;
            padding: 5px;
        }

        .btn-link:disabled {
            color: #ccc;
            cursor: not-allowed;
        }

        .timer {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .success-message {
            background: #efe;
            color: #363;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .back-link {
            margin-top: 20px;
        }

        .back-link a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            color: #FF7A00;
        }

        .security-info {
            background: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 14px;
            color: #2c5aa0;
        }

        @media (max-width: 480px) {
            .verify-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .verify-header h1 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <img src="../../assets/images/logo.png" alt="Klube Cash" class="logo">
        
        <div class="verify-header">
            <h1>🔐 Verificação de Segurança</h1>
            <p>Para sua segurança, enviamos um código de verificação para seu email.</p>
        </div>

        <div class="email-info">
            Código enviado para: <strong><?php echo htmlspecialchars($userData['email'] ?? ''); ?></strong>
        </div>

        <div id="messageContainer"></div>

        <form id="verifyForm">
            <input type="text" 
                   id="codigo" 
                   class="code-input" 
                   placeholder="000000" 
                   maxlength="6" 
                   pattern="[0-9]{6}"
                   autocomplete="one-time-code"
                   required>
            
            <button type="submit" class="btn" id="verifyBtn">
                Verificar Código
            </button>
        </form>

        <div style="text-align: center;">
            <p style="color: #666; font-size: 14px; margin-bottom: 10px;">
                Não recebeu o código?
            </p>
            <button type="button" class="btn-link" id="resendBtn">
                Reenviar código
            </button>
            <div class="timer" id="timer" style="display: none;"></div>
        </div>

        <div class="security-info">
            <strong>💡 Dica de Segurança:</strong> Nunca compartilhe este código com ninguém. 
            Nossa equipe nunca solicitará este código por telefone ou email.
        </div>

        <div class="back-link">
            <a href="<?php echo LOGIN_URL; ?>">← Voltar para o login</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('verifyForm');
            const codigoInput = document.getElementById('codigo');
            const verifyBtn = document.getElementById('verifyBtn');
            const resendBtn = document.getElementById('resendBtn');
            const messageContainer = document.getElementById('messageContainer');
            const timer = document.getElementById('timer');
            
            let resendTimer = null;
            let resendCountdown = 60;

            // Auto-focus no campo de código
            codigoInput.focus();

            // Formatar entrada do código
            codigoInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                e.target.value = value;
                
                // Auto-submit quando tiver 6 dígitos
                if (value.length === 6) {
                    form.dispatchEvent(new Event('submit'));
                }
            });

            // Submit do formulário
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const codigo = codigoInput.value.trim();
                
                if (codigo.length !== 6) {
                    showMessage('Por favor, digite o código de 6 dígitos.', 'error');
                    return;
                }

                verifyBtn.disabled = true;
                verifyBtn.textContent = 'Verificando...';

                fetch('../../controllers/AuthController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=verify_2fa&codigo=${encodeURIComponent(codigo)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        showMessage('Código verificado! Redirecionando...', 'success');
                        
                        // Redirecionar baseado no tipo de usuário
                        setTimeout(() => {
                            const userType = '<?php echo $userData['type'] ?? 'cliente'; ?>';
                            let redirectUrl = '<?php echo CLIENT_DASHBOARD_URL; ?>';
                            
                            if (userType === 'admin') {
                                redirectUrl = '<?php echo ADMIN_DASHBOARD_URL; ?>';
                            } else if (userType === 'loja') {
                                redirectUrl = '<?php echo STORE_DASHBOARD_URL; ?>';
                            }
                            
                            window.location.href = redirectUrl;
                        }, 1000);
                    } else {
                        showMessage(data.message, 'error');
                        codigoInput.value = '';
                        codigoInput.focus();
                    }
                })
                .catch(error => {
                    showMessage('Erro de conexão. Tente novamente.', 'error');
                })
                .finally(() => {
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Verificar Código';
                });
            });

            // Reenviar código
            resendBtn.addEventListener('click', function() {
                resendBtn.disabled = true;
                
                fetch('../../controllers/AuthController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=resend_2fa'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        showMessage('Novo código enviado!', 'success');
                        startResendTimer();
                    } else {
                        showMessage(data.message, 'error');
                        resendBtn.disabled = false;
                    }
                })
                .catch(error => {
                    showMessage('Erro ao reenviar código.', 'error');
                    resendBtn.disabled = false;
                });
            });

            // Timer de reenvio
            function startResendTimer() {
                resendCountdown = 60;
                timer.style.display = 'block';
                updateTimer();
                
                resendTimer = setInterval(() => {
                    resendCountdown--;
                    if (resendCountdown <= 0) {
                        clearInterval(resendTimer);
                        resendBtn.disabled = false;
                        timer.style.display = 'none';
                        resendBtn.textContent = 'Reenviar código';
                    } else {
                        updateTimer();
                    }
                }, 1000);
            }

            function updateTimer() {
                timer.textContent = `Aguarde ${resendCountdown}s para reenviar`;
                resendBtn.textContent = `Reenviar código (${resendCountdown}s)`;
            }

            // Mostrar mensagens
            function showMessage(message, type) {
                messageContainer.innerHTML = `
                    <div class="${type === 'error' ? 'error-message' : 'success-message'}">
                        ${message}
                    </div>
                `;
                
                // Limpar mensagem após 5 segundos
                setTimeout(() => {
                    messageContainer.innerHTML = '';
                }, 5000);
            }

            // Iniciar timer de reenvio
            startResendTimer();
        });
    </script>
</body>
</html>