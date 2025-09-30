# automation/config_evolution.py
"""
Configurações específicas para Evolution API
Klube Cash - Sistema de Automação WhatsApp
"""

import os
from dotenv import load_dotenv

load_dotenv()

# ==========================================
# BANCO DE DADOS
# ==========================================
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USER', 'u383946504_klubecash'),
    'password': os.getenv('DB_PASSWORD', ''),
    'database': os.getenv('DB_NAME', 'u383946504_klubecash'),
    'port': int(os.getenv('DB_PORT', 3306)),
    'charset': 'utf8mb4',
    'autocommit': True
}

# ==========================================
# EVOLUTION API
# ==========================================
EVOLUTION_CONFIG = {
    'api_url': os.getenv('EVOLUTION_API_URL', 'http://localhost:8080'),
    'api_key': os.getenv('EVOLUTION_API_KEY', 'klubecash-2024-secret-key-change-this'),
    'instance_name': os.getenv('EVOLUTION_INSTANCE_NAME', 'klubecash'),
    'timeout': int(os.getenv('EVOLUTION_TIMEOUT', 30)),
    'retry_attempts': int(os.getenv('EVOLUTION_RETRY_ATTEMPTS', 3)),
    'retry_delay': int(os.getenv('EVOLUTION_RETRY_DELAY', 5))
}

# ==========================================
# CONFIGURAÇÕES DE MONITORAMENTO
# ==========================================
MONITORING_CONFIG = {
    'check_interval': int(os.getenv('CHECK_INTERVAL', 5)),  # Verificar a cada 5 segundos
    'batch_size': int(os.getenv('BATCH_SIZE', 10)),  # Processar 10 notificações por vez
    'max_daily_messages': int(os.getenv('MAX_DAILY_MESSAGES', 1000)),  # Limite diário
    'message_delay': float(os.getenv('MESSAGE_DELAY', 2.0))  # Delay entre mensagens (segundos)
}

# ==========================================
# LOGS
# ==========================================
LOG_CONFIG = {
    'log_file': 'logs/evolution_automation.log',
    'log_level': os.getenv('LOG_LEVEL', 'INFO'),
    'max_log_size': int(os.getenv('MAX_LOG_SIZE', 10485760)),  # 10MB
    'backup_count': int(os.getenv('LOG_BACKUP_COUNT', 5))
}

# ==========================================
# SITE
# ==========================================
SITE_URL = os.getenv('SITE_URL', 'https://klubecash.com')

# ==========================================
# MODO TESTE
# ==========================================
TEST_MODE = os.getenv('TEST_MODE', 'False').lower() == 'true'
DRY_RUN = os.getenv('DRY_RUN', 'False').lower() == 'true'

# ==========================================
# WEBHOOKS
# ==========================================
WEBHOOK_CONFIG = {
    'enabled': os.getenv('WEBHOOK_ENABLED', 'True').lower() == 'true',
    'url': os.getenv('WEBHOOK_URL', f'{SITE_URL}/api/webhook/whatsapp'),
    'secret': os.getenv('WEBHOOK_SECRET', 'klube-cash-webhook-secret')
}

# ==========================================
# TEMPLATES DE MENSAGEM
# ==========================================
MESSAGE_TEMPLATES = {
    'cashback_aprovado': """⭐ *{client_name}*, sua compra foi registrada!

🏪 {store_name}
💰 Compra: R$ {purchase_value}
🎁 Cashback: R$ {cashback_value}

⏰ Liberação em até 7 dias úteis
📅 Previsão: {release_date}

💳 Código: {transaction_code}

Acesse sua conta: {site_url}

📱 *Klube Cash - Dinheiro de volta em suas compras!*""",

    'cashback_instantaneo': """🎉 *{client_name}*, cashback creditado!

✨ CASHBACK INSTANTÂNEO ✨

🏪 {store_name}
💰 Compra: R$ {purchase_value}
🎁 Cashback: R$ {cashback_value}

✅ *Já disponível em sua conta!*

Você pode usar seu cashback agora mesmo em {store_name}!

Acesse: {site_url}

📱 *Klube Cash - Seu dinheiro volta na hora!*""",

    'cashback_liberado': """✅ *{client_name}*, seu cashback foi liberado!

🏪 {store_name}
🎁 Valor liberado: R$ {cashback_value}

💳 Use agora em {store_name}!

Acesse: {site_url}

📱 *Klube Cash*"""
}

# ==========================================
# VALIDAÇÕES
# ==========================================
def validate_config():
    """Valida se todas as configurações necessárias estão presentes"""
    errors = []
    
    # Validar Evolution API
    if not EVOLUTION_CONFIG['api_url']:
        errors.append("EVOLUTION_API_URL não configurada")
    if not EVOLUTION_CONFIG['api_key']:
        errors.append("EVOLUTION_API_KEY não configurada")
    if not EVOLUTION_CONFIG['instance_name']:
        errors.append("EVOLUTION_INSTANCE_NAME não configurada")
    
    # Validar Banco de Dados
    if not DB_CONFIG['password']:
        errors.append("DB_PASSWORD não configurada")
    
    if errors:
        raise ValueError(f"Configuração inválida: {', '.join(errors)}")
    
    return True