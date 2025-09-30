# automation/whatsapp_sender.py
"""
Sistema de Automação de Envio de WhatsApp para Klube Cash

Este script monitora constantemente o banco de dados em busca de novas
transações de cashback e envia notificações automáticas via WhatsApp.

Autor: Klube Cash Team
Data: 2025
"""

import time
import logging
import sys
from datetime import datetime
from pathlib import Path

# Importar módulos locais
from config import *
from database import DatabaseManager
from message_templates import generate_cashback_message, format_phone

# Configurar logging
Path('logs').mkdir(exist_ok=True)
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(LOG_FILE, encoding='utf-8'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)

class WhatsAppSender:
    """Classe principal para envio de mensagens WhatsApp"""
    
    def __init__(self):
        self.db = DatabaseManager()
        self.method = WHATSAPP_METHOD
        logger.info(f"🚀 WhatsApp Sender iniciado - Método: {self.method}")
        
        # Inicializar cliente baseado no método escolhido
        if self.method == 'twilio':
            self._init_twilio()
        elif self.method == 'evolution':
            self._init_evolution()
        elif self.method == 'wppconnect':
            self._init_wppconnect()
    
    def _init_twilio(self):
        """Inicializa cliente Twilio"""
        try:
            from twilio.rest import Client
            self.twilio_client = Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN)
            logger.info("✅ Cliente Twilio inicializado")
        except Exception as e:
            logger.error(f"❌ Erro ao inicializar Twilio: {e}")
            self.twilio_client = None
    
    def _init_evolution(self):
        """Inicializa configuração Evolution API"""
        import requests
        self.evolution_headers = {
            'apikey': EVOLUTION_API_KEY,
            'Content-Type': 'application/json'
        }
        logger.info("✅ Evolution API configurada")
    
    def _init_wppconnect(self):
        """Inicializa configuração WPPConnect"""
        self.wppconnect_headers = {
            'Authorization': f'Bearer {WPPCONNECT_SECRET_KEY}',
            'Content-Type': 'application/json'
        }
        logger.info("✅ WPPConnect configurado")
    
    def send_via_twilio(self, phone, message):
        """
        Envia mensagem via Twilio (método mais confiável)
        """
        try:
            if not self.twilio_client:
                return False, "Cliente Twilio não inicializado"
            
            formatted_phone = f"whatsapp:+{format_phone(phone)}"
            
            message_obj = self.twilio_client.messages.create(
                from_=TWILIO_WHATSAPP_NUMBER,
                body=message,
                to=formatted_phone
            )
            
            logger.info(f"✅ Mensagem enviada via Twilio - SID: {message_obj.sid}")
            return True, message_obj.sid
            
        except Exception as e:
            logger.error(f"❌ Erro ao enviar via Twilio: {e}")
            return False, str(e)
    
    def send_via_evolution(self, phone, message):
        """
        Envia mensagem via Evolution API
        """
        try:
            import requests
            
            url = f"{EVOLUTION_API_URL}/message/sendText/{EVOLUTION_INSTANCE_NAME}"
            
            payload = {
                "number": format_phone(phone),
                "text": message
            }
            
            response = requests.post(
                url,
                json=payload,
                headers=self.evolution_headers,
                timeout=30
            )
            
            if response.status_code == 200 or response.status_code == 201:
                logger.info(f"✅ Mensagem enviada via Evolution API")
                return True, response.json().get('key', {}).get('id', 'sent')
            else:
                logger.error(f"❌ Erro Evolution API: {response.status_code} - {response.text}")
                return False, f"HTTP {response.status_code}"
                
        except Exception as e:
            logger.error(f"❌ Erro ao enviar via Evolution: {e}")
            return False, str(e)
    
    def send_via_wppconnect(self, phone, message):
        """
        Envia mensagem via WPPConnect
        """
        try:
            import requests
            
            url = f"{WPPCONNECT_URL}/api/{WPPCONNECT_SESSION}/send-message"
            
            payload = {
                "phone": format_phone(phone),
                "message": message,
                "isGroup": False
            }
            
            response = requests.post(
                url,
                json=payload,
                headers=self.wppconnect_headers,
                timeout=30
            )
            
            if response.status_code == 200 or response.status_code == 201:
                logger.info(f"✅ Mensagem enviada via WPPConnect")
                return True, 'sent'
            else:
                logger.error(f"❌ Erro WPPConnect: {response.status_code} - {response.text}")
                return False, f"HTTP {response.status_code}"
                
        except Exception as e:
            logger.error(f"❌ Erro ao enviar via WPPConnect: {e}")
            return False, str(e)
    
    def send_message(self, phone, message, transaction_id):
        """
        Envia mensagem usando o método configurado
        
        Args:
            phone: Telefone do destinatário
            message: Texto da mensagem
            transaction_id: ID da transação (para log)
        
        Returns:
            Tuple (sucesso, erro/id_mensagem)
        """
        if TEST_MODE:
            logger.info(f"🧪 MODO TESTE - Mensagem não enviada para {phone}")
            logger.info(f"Mensagem: {message[:100]}...")
            return True, "test_mode"
        
        # Tentar enviar com retry
        for attempt in range(MAX_RETRIES):
            try:
                if self.method == 'twilio':
                    success, result = self.send_via_twilio(phone, message)
                elif self.method == 'evolution':
                    success, result = self.send_via_evolution(phone, message)
                elif self.method == 'wppconnect':
                    success, result = self.send_via_wppconnect(phone, message)
                else:
                    success, result = False, "Método de envio não configurado"
                
                if success:
                    # Registrar no banco de dados
                    self.db.log_whatsapp_message(phone, message, True, transaction_id)
                    return True, result
                else:
                    logger.warning(f"⚠️ Tentativa {attempt + 1}/{MAX_RETRIES} falhou: {result}")
                    if attempt < MAX_RETRIES - 1:
                        time.sleep(RETRY_DELAY)
                    
            except Exception as e:
                logger.error(f"❌ Erro na tentativa {attempt + 1}: {e}")
                if attempt < MAX_RETRIES - 1:
                    time.sleep(RETRY_DELAY)
        
        # Todas as tentativas falharam
        self.db.log_whatsapp_message(phone, message, False, transaction_id, result)
        return False, result
    
    def process_pending_notifications(self):
        """
        Processa todas as notificações pendentes
        """
        try:
            pending = self.db.get_pending_notifications()
            
            if not pending:
                logger.info("✨ Nenhuma notificação pendente")
                return
            
            logger.info(f"📬 Processando {len(pending)} notificações...")
            
            for transaction in pending:
                try:
                    # Gerar mensagem
                    message = generate_cashback_message(transaction)
                    phone = transaction['client_phone']
                    transaction_id = transaction['transaction_id']
                    client_name = transaction['client_name']
                    
                    logger.info(f"📤 Enviando para {client_name} ({phone}) - Transação #{transaction_id}")
                    
                    # Enviar mensagem
                    success, result = self.send_message(phone, message, transaction_id)
                    
                    if success:
                        logger.info(f"✅ Notificação enviada com sucesso para {client_name}")
                    else:
                        logger.error(f"❌ Falha ao enviar para {client_name}: {result}")
                    
                    # Pequeno delay entre mensagens para não sobrecarregar
                    time.sleep(2)
                    
                except Exception as e:
                    logger.error(f"❌ Erro ao processar transação: {e}")
                    continue
            
            logger.info(f"✅ Processamento concluído - {len(pending)} notificações processadas")
            
        except Exception as e:
            logger.error(f"❌ Erro ao processar notificações: {e}")
    
    def run(self):
        """
        Loop principal - monitora constantemente o banco de dados
        """
        logger.info("🔄 Iniciando monitoramento contínuo...")
        logger.info(f"⏱️  Verificando a cada {CHECK_INTERVAL} segundos")
        logger.info(f"🧪 Modo teste: {'ATIVADO' if TEST_MODE else 'DESATIVADO'}")
        
        try:
            while True:
                self.process_pending_notifications()
                time.sleep(CHECK_INTERVAL)
                
        except KeyboardInterrupt:
            logger.info("\n⏹️  Parando o serviço...")
            self.db.close()
            logger.info("👋 Serviço encerrado")

if __name__ == "__main__":
    try:
        sender = WhatsAppSender()
        sender.run()
    except Exception as e:
        logger.error(f"❌ Erro fatal: {e}")
        sys.exit(1)