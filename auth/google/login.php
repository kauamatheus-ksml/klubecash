<!-- Substituir o JavaScript da função loginWithGoogle -->
<script>
// Função real para login com Google
function loginWithGoogle() {
    // Mostrar indicador de carregamento (opcional)
    const googleBtn = document.querySelector('.google-btn');
    const originalText = googleBtn.innerHTML;
    googleBtn.innerHTML = '<img src="../../assets/images/icons/google.svg" alt="Google"> Conectando...';
    googleBtn.disabled = true;
    
    // Fazer requisição para obter a URL de autorização do Google
    fetch('<?php echo SITE_URL; ?>/auth/google/auth', {
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
        console.error('Erro no login Google:', error);
        alert('Erro ao conectar com o Google: ' + error.message);
        
        // Restaurar botão
        googleBtn.innerHTML = originalText;
        googleBtn.disabled = false;
    });
}

// Verificar se há mensagens na URL (success ou error)
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const successMsg = urlParams.get('success');
    const errorMsg = urlParams.get('error');
    
    if (successMsg) {
        // Mostrar mensagem de sucesso (opcional)
        console.log('Sucesso:', successMsg);
    }
    
    if (errorMsg) {
        // Mostrar erro do login com Google
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = errorMsg;
        
        const form = document.getElementById('login-form');
        form.parentNode.insertBefore(errorDiv, form);
    }
});

// Manter as outras funções como estão
function loginWithFacebook() {
    alert('Login com Facebook será implementado com a API do Facebook.');
}

function loginWithApple() {
    alert('Login com Apple será implementado com a API da Apple.');
}
</script>