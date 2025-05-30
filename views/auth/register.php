<?php
// Arquivo: views/auth/register.php
// Incluir arquivos de configuração
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../config/email.php';
require_once '../../utils/Validator.php';

// Verificar se já existe uma sessão ativa
session_start();
if (isset($_SESSION['user_id'])) {
    // Redirecionar com base no tipo de usuário
    if ($_SESSION['user_type'] == USER_TYPE_ADMIN) {
        header('Location: ' . ADMIN_DASHBOARD_URL);
    } else {
        header('Location: ' . CLIENT_DASHBOARD_URL);
    }
    exit;
}

// Processar o formulário de registro
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturar e sanitizar dados do formulário
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
    $senha = $_POST['senha'] ?? '';
    
    // Validar campos
    $errors = [];
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido';
    }
    
    if (empty($nome) || strlen($nome) < 3) {
        $errors[] = 'Nome precisa ter pelo menos 3 caracteres';
    }
    
    if (empty($telefone) || strlen($telefone) < 10) {
        $errors[] = 'Telefone inválido';
    }
    
    if (empty($senha) || strlen($senha) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'A senha deve ter no mínimo ' . PASSWORD_MIN_LENGTH . ' caracteres';
    }
    
    // Se não houver erros, prosseguir com o registro
    if (empty($errors)) {
        try {
            $db = Database::getConnection();
            
            // Verificar se o email já existe
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = 'Este email já está cadastrado. Por favor, use outro ou faça login.';
            } else {
                // Hash da senha
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                
                // Inserir novo usuário
                $stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha_hash, tipo, telefone) VALUES (:nome, :email, :senha_hash, :tipo, :telefone)");
                $stmt->bindParam(':nome', $nome);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':senha_hash', $senha_hash);
                $tipo = USER_TYPE_CLIENT;
                $stmt->bindParam(':tipo', $tipo);
                $stmt->bindParam(':telefone', $telefone);
                
                if ($stmt->execute()) {
                    $user_id = $db->lastInsertId();
                    
                    // Enviar email de boas-vindas
                    Email::sendWelcome($email, $nome);
                    
                    // Redirecionar para página de sucesso ou login
                    $success = 'Cadastro realizado com sucesso! Você já pode fazer login.';
                    
                    // Opcionalmente, fazer login automático
                    // $_SESSION['user_id'] = $user_id;
                    // $_SESSION['user_name'] = $nome;
                    // $_SESSION['user_type'] = $tipo;
                    // header('Location: ' . CLIENT_DASHBOARD_URL);
                    // exit;
                } else {
                    $error = 'Erro ao cadastrar. Por favor, tente novamente.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Erro ao processar o cadastro. Tente novamente.';
            // Log do erro para debug
            error_log('Erro no registro: ' . $e->getMessage());
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Klube Cash</title>
    <link rel="stylesheet" href="../../assets/css/auth.css">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="../../assets/css/views/auth/register.css">
</head>
<body>
    <!-- Versão Mobile -->
    <div class="logo-container">
        <img src="../../assets/images/logobranco.png" alt="KlubeCash">
    </div>

    <!-- Versão Desktop -->
    <div class="register-page">
        <div class="left-panel">
            <div class="logo-container-desktop">
                <img src="../../assets/images/logobranco.png" alt="KlubeCash">
            </div>
            <div class="illustrations">
                <img src="../../assets/images/illustrations/businessman-coin.svg" alt="" class="illustration-left">
            </div>
        </div>

        <div class="right-panel">
            <div class="register-container">
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($success); ?>
                        <p><a href="<?php echo LOGIN_URL; ?>">Clique aqui para fazer login</a></p>
                    </div>
                <?php endif; ?>

                <div class="register-header">
                    <div class="login-link">
                        <span>Já tem conta?</span>
                        <a href="<?php echo LOGIN_URL; ?>">Login</a>
                    </div>
                    <h1>Seja <span>BEM VINDO</span></h1>
                    <h2>Registro</h2>
                </div>

                <form method="post" action="" id="register-form">
                    <div class="input-group">
                        <label for="email">Entre com seu email</label>
                        <input type="email" id="email" name="email" placeholder="Email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label for="nome">Nome</label>
                            <input type="text" id="nome" name="nome" placeholder="Nome" required value="<?php echo isset($nome) ? htmlspecialchars($nome) : ''; ?>">
                        </div>

                        <div class="input-group">
                            <label for="telefone">Telefone</label>
                            <input type="tel" id="telefone" name="telefone" placeholder="Telefone" required value="<?php echo isset($telefone) ? htmlspecialchars($telefone) : ''; ?>">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="senha">Digite sua senha</label>
                        <div class="password-field">
                            <input type="password" id="senha" name="senha" placeholder="Senha" required>
                            <span class="password-toggle" onclick="togglePassword()">
                                <img src="../../assets/images/icons/eye.svg" alt="Mostrar senha">
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="register-btn">Registre-se</button>
                </form>
            </div>
            
            <div class="illustration-right">
                <img src="../../assets/images/illustrations/woman-phone.svg" alt="">
            </div>

            <div class="or-divider">Ou</div>

            <div class="social-login">
                <button class="social-btn google-btn" onclick="registerWithGoogle()">
                    <img src="../../assets/images/icons/google.svg" alt="Google">
                    <span class="hide-on-mobile">Registre-se com Google</span>
                </button>
                <button class="social-btn facebook-btn" onclick="registerWithFacebook()">
                    <img src="../../assets/images/icons/facebook.svg" alt="Facebook">
                </button>
                <button class="social-btn apple-btn" onclick="registerWithApple()">
                    <img src="../../assets/images/icons/apple.svg" alt="Apple">
                </button>
            </div>
        </div>
    </div>

    <script>
        // Função específica para registro com Google
        function registerWithGoogle() {
            // Mostrar indicador de carregamento
            const googleBtn = document.querySelector('.google-btn');
            const originalText = googleBtn.innerHTML;
            googleBtn.innerHTML = '<img src="../../assets/images/icons/google.svg" alt="Google"> Conectando...';
            googleBtn.disabled = true;
            
            // Fazer requisição para registro com Google (diferente do login)
            fetch('<?php echo SITE_URL; ?>/auth/google/register', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na requisição: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.status && data.auth_url) {
                    // Redirecionar para o Google
                    window.location.href = data.auth_url;
                } else {
                    throw new Error(data.message || 'Erro desconhecido');
                }
            })
            .catch(error => {
                console.error('Erro no registro Google:', error);
                alert('Erro ao conectar com o Google: ' + error.message);
                
                // Restaurar botão
                googleBtn.innerHTML = originalText;
                googleBtn.disabled = false;
            });
        }

        // Funções placeholder para outros provedores
        function registerWithFacebook() {
            alert('Registro com Facebook será implementado com a API do Facebook.');
        }

        function registerWithApple() {
            alert('Registro com Apple será implementado com a API da Apple.');
        }

        // Verificar mensagens na URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const successMsg = urlParams.get('success');
            const errorMsg = urlParams.get('error');
            
            if (successMsg) {
                // Mostrar mensagem de sucesso
                const successDiv = document.createElement('div');
                successDiv.className = 'success-message';
                successDiv.textContent = successMsg;
                
                const form = document.getElementById('register-form');
                form.parentNode.insertBefore(successDiv, form);
            }
            
            if (errorMsg) {
                // Mostrar erro do registro com Google
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = errorMsg;
                
                const form = document.getElementById('register-form');
                form.parentNode.insertBefore(errorDiv, form);
            }
        });
        // Função para alternar a visibilidade da senha
        function togglePassword() {
            const passwordField = document.getElementById('senha');
            const passwordToggle = document.querySelector('.password-toggle img');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordToggle.src = '../../assets/images/icons/eye-slash.svg';
            } else {
                passwordField.type = 'password';
                passwordToggle.src = '../../assets/images/icons/eye.svg';
            }
        }

        // Máscara para o campo de telefone
        document.getElementById('telefone').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove não-dígitos
            
            // Aplicar máscara (XX) XXXXX-XXXX
            if (value.length <= 11) {
                if (value.length > 2) {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2);
                }
                if (value.length > 10) {
                    value = value.substring(0, 10) + '-' + value.substring(10);
                }
            }
            
            e.target.value = value;
        });

        // Validação do formulário no lado do cliente
        document.getElementById('register-form').addEventListener('submit', function(event) {
            const email = document.getElementById('email').value;
            const nome = document.getElementById('nome').value;
            const telefone = document.getElementById('telefone').value;
            const senha = document.getElementById('senha').value;
            
            let isValid = true;
            let errorMessage = '';
            
            if (!email) {
                errorMessage = 'Por favor, informe seu email.';
                isValid = false;
            } else if (!isValidEmail(email)) {
                errorMessage = 'Por favor, informe um email válido.';
                isValid = false;
            }
            
            if (!nome || nome.length < 3) {
                errorMessage = 'Por favor, informe seu nome completo (mínimo 3 caracteres).';
                isValid = false;
            }
            
            if (!telefone || telefone.replace(/\D/g, '').length < 10) {
                errorMessage = 'Por favor, informe um telefone válido.';
                isValid = false;
            }
            
            if (!senha || senha.length < <?php echo PASSWORD_MIN_LENGTH; ?>) {
                errorMessage = 'A senha deve ter no mínimo <?php echo PASSWORD_MIN_LENGTH; ?> caracteres.';
                isValid = false;
            }
            
            if (!isValid) {
                event.preventDefault();
                alert(errorMessage);
            }
        });
        
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    </script>
</body>
</html>