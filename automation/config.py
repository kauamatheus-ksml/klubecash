# automation/config.py
"""
Configurações para o sistema de envio automático de WhatsApp
"""

import os
from dotenv import load_dotenv

# Carregar variáveis de ambiente
load_dotenv()

# ==========================================
# CONFIGURAÇÕES DO BANCO DE DADOS
# ==========================================
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USER', 'u383946504_klubecash'),
    'password': os.getenv('DB_PASSWORD', ''),
    'database': os.getenv('DB_NAME', 'u383946504_klubecash'),
    'port': int(os.getenv('DB_PORT', 3306))
}

# ==========================================
# CONFIGURAÇÕES DO WHATSAPP
# ==========================================

# Método de envio (escolha um):
# 'twilio' - API Oficial Twilio (RECOMENDADO - pago mas confiável)
# 'evolution' - Evolution API (gratuito, auto-hospedado)
# 'wppconnect' - WPPConnect (gratuito, auto-hospedado)
WHATSAPP_METHOD = os.getenv('WHATSAPP_METHOD', 'twilio')

# --- TWILIO (Recomendado para produção) ---
TWILIO_ACCOUNT_SID = os.getenv('TWILIO_ACCOUNT_SID', '')
TWILIO_AUTH_TOKEN = os.getenv('TWILIO_AUTH_TOKEN', '')
TWILIO_WHATSAPP_NUMBER = os.getenv('TWILIO_WHATSAPP_NUMBER', 'whatsapp:+14155238886')

# --- EVOLUTION API (Gratuito, auto-hospedado) ---
EVOLUTION_API_URL = os.getenv('EVOLUTION_API_URL', 'http://localhost:8080')
EVOLUTION_API_KEY = os.getenv('EVOLUTION_API_KEY', 'sua-api-key-aqui')
EVOLUTION_INSTANCE_NAME = os.getenv('EVOLUTION_INSTANCE_NAME', 'klubecash')

# --- WPPCONNECT (Gratuito, alternativa) ---
WPPCONNECT_URL = os.getenv('WPPCONNECT_URL', 'http://localhost:21465')
WPPCONNECT_SECRET_KEY = os.getenv('WPPCONNECT_SECRET_KEY', 'My53cr3tKY')
WPPCONNECT_SESSION = os.getenv('WPPCONNECT_SESSION', 'klubecash')

# ==========================================
# CONFIGURAÇÕES GERAIS
# ==========================================
LOG_FILE = 'logs/whatsapp_automation.log'
CHECK_INTERVAL = 5  # Segundos entre verificações
MAX_RETRIES = 3     # Tentativas de reenvio em caso de falha
RETRY_DELAY = 10    # Segundos entre tentativas

# Modo de teste (não envia mensagens reais)
TEST_MODE = os.getenv('TEST_MODE', 'False').lower() == 'true'

# URL do site (para links nas mensagens)
SITE_URL = os.getenv('SITE_URL', 'https://klubecash.com')