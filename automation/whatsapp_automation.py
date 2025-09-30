# automation/whatsapp_automation.py
"""
Sistema de Automação de Envio WhatsApp via Evolution API
Klube Cash - Monitoramento contínuo e envio automático

Este script monitora o banco de dados em busca de novas transações
de cashback e envia notificações automáticas via WhatsApp usando Evolution API.

Autor: Klube Cash Team
Data: 2025-09-30
"""

import sys
import time
import logging
from pathlib import Path
from datetime import datetime, timedelta
from logging.handlers import RotatingFileHandler

# Importar módulos locais
from config_evolution import (
    DB_CONFIG, EVOLUTION_CONFIG, MONITORING_CONFIG, 
    LOG_CONFIG, SITE_URL, TEST_MODE, MESSAGE_TEMPLATES,
    validate_config
)
from database import DatabaseManager
from evolution_client import EvolutionAPIClient

# ==========================================
# CONFIGURAÇÃO DE LOGGING
# ==========================================
def setup_logging():
    """Configura sistema de logs com rotação"""
    Path('logs').mkdir(exist_ok=True)
    
    # Configurar handler com rotação
    handler = RotatingFileHandler(
        LOG_CONFIG['log_file'],
        maxBytes=LOG_CONFIG['max_log_size'],
        backupCount=LOG_CONFIG['backup_count'],
        encoding='utf-8'
    )
    
    # Formato do log
    formatter = logging.Formatter(
        '%(asctime)s - %(name)s - %(levelname)s - %(message)s',
        datefmt='%Y-%m-%d %H:%M:%S'
    )
    handler.setFormatter(formatter)
    
    # Configurar logger raiz
    logger = logging.getLogger()
    logger.setLevel(getattr(logging, LOG_CONFIG['log_level']))
    logger.addHandler(handler)
    
    # Também exibir no console
    console_handler = logging.StreamHandler(sys.stdout)
    console_handler.setFormatter(formatter)
    logger.addHandler(console_handler)
    
    return logger

logger = setup_logging()

# ==========================================
# CLASSE PRINCIPAL
# ==========================================
class WhatsAppAutomation:
    """Gerenciador principal da automação de WhatsApp"""
    
    def __init__(self):
        logger.info("=" * 60)
        logger.info("🚀 KLUBE CASH - WHATSAPP AUTOMATION")
        logger.info("=" * 60)
        logger.info(f"Versão: 1.0.0")
        logger.info(f"Data: {datetime.now().strftime('%d/%m/%Y %H:%M:%S')}")
        logger.info(f"Modo Teste: {'ATIVADO' if TEST_MODE else 'DESATIVADO'}")
        logger.info("=" * 60)
        
        # Validar configurações
        try:
            validate_config()
            logger.info("✅ Configurações validadas")
        except ValueError as e:
            logger.error(f"❌ Erro na configuração: {e}")
            sys.exit(1)
        
        # Inicializar componentes
        self.db = DatabaseManager()
        self.evolution = EvolutionAPIClient()
        
        # Estatísticas
        self.stats = {
            'messages_sent': 0,
            'messages_failed': 0,
            'total_processed': 0,
            'start_time': datetime.now()
        }
        
        # Verificar conexão com WhatsApp
        if not TEST_MODE and not self.evolution.check_connection():
            logger.error("❌ WhatsApp não está conectado!")
            logger.error("Execute: ./evolution-create-instance.sh")
            sys.exit(1)
    
    def format_currency(self, value: float) -> str:
        """Formata valor monetário"""
        return f"{value:,.2f}".replace(',', 'X').replace('.', ',').replace('X', '.')
    
    def generate_message(self, transaction: dict) -> str:
        """
        Gera mensagem personalizada baseada no tipo de transação
        
        Args:
            transaction: Dados da transação
        
        Returns:
            str: Mensagem formatada
        """
        # Determinar tipo de mensagem
        is_instant = transaction.get('is_mvp', False)
        template_key = 'cashback_instantaneo' if is_instant else 'cashback_aprovado'
        
        # Calcular data de liberação (7 dias úteis ≈ 10 dias corridos)
        release_date = (datetime.now() + timedelta(days=10)).strftime('%d/%m/%Y')
        
        # Dados para template
        data = {
            'client_name': transaction.get('client_name', 'Cliente'),
            'store_name': transaction.get('store_name', 'Loja parceira'),
            'purchase_value': self.format_currency(transaction.get('valor_total', 0)),
            'cashback_value': self.format_currency(transaction.get('cashback_value', 0)),
            'transaction_code': transaction.get('codigo_transacao', 'N/A'),
            'release_date': release_date,
            'site_url': SITE_URL
        }
        
        # Gerar mensagem do template
        message = MESSAGE_TEMPLATES[template_key].format(**data)
        
        return message
    
    def process_transaction(self, transaction: dict) -> bool:
        """
        Processa uma transação e envia notificação
        
        Args:
            transaction: Dados da transação
        
        Returns:
            bool: True se processado com sucesso
        """
        transaction_id = transaction['transaction_id']
        client_name = transaction['client_name']
        phone = transaction['client_phone']
        
        try:
            logger.info(f"📋 Processando transação #{transaction_id} - Cliente: {client_name}")
            
            # Gerar mensagem
            message = self.generate_message(transaction)
            
            # Enviar via Evolution API
            success, result = self.evolution.send_message_with_retry(phone, message)
            
            # Registrar no banco de dados
            self.db.log_whatsapp_message(
                phone=phone,
                message=message,
                success=success,
                transaction_id=transaction_id,
                error=None if success else result
            )
            
            # Atualizar estatísticas
            if success:
                self.stats['messages_sent'] += 1
                logger.info(f"✅ Notificação enviada com sucesso para {client_name}")
            else:
                self.stats['messages_failed'] += 1
                logger.error(f"❌ Falha ao enviar notificação para {client_name}: {result}")
            
            self.stats['total_processed'] += 1
            
            return success
            
        except Exception as e:
            logger.error(f"❌ Erro ao processar transação #{transaction_id}: {e}")
            self.stats['messages_failed'] += 1
            self.stats['total_processed'] += 1
            return False
    
    def process_pending_notifications(self):
        """Processa todas as notificações pendentes"""
        try:
            # Buscar notificações pendentes
            pending = self.db.get_pending_notifications()
            
            if not pending:
                logger.debug("✨ Nenhuma notificação pendente")
                return
            
            logger.info(f"📬 Encontradas {len(pending)} notificações pendentes")
            
            # Processar cada notificação
            for transaction in pending:
                self.process_transaction(transaction)
                
                # Delay entre mensagens para não sobrecarregar
                time.sleep(MONITORING_CONFIG['message_delay'])
            
            # Exibir estatísticas
            self.print_stats()
            
        except Exception as e:
            logger.error(f"❌ Erro ao processar notificações: {e}")
    
    def print_stats(self):
        """Exibe estatísticas do processamento"""
        runtime = datetime.now() - self.stats['start_time']
        
        logger.info("")
        logger.info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━")
        logger.info("📊 ESTATÍSTICAS")
        logger.info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━")
        logger.info(f"✅ Mensagens enviadas: {self.stats['messages_sent']}")
        logger.info(f"❌ Mensagens falhadas: {self.stats['messages_failed']}")
        logger.info(f"📊 Total processado: {self.stats['total_processed']}")
        logger.info(f"⏱️  Tempo de execução: {runtime}")
        
        if self.stats['total_processed'] > 0:
            success_rate = (self.stats['messages_sent'] / self.stats['total_processed']) * 100
            logger.info(f"📈 Taxa de sucesso: {success_rate:.1f}%")
        
        logger.info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━")
        logger.info("")
    
    def run(self):
        """Loop principal - monitoramento contínuo"""
        logger.info("🔄 Iniciando monitoramento contínuo...")
        logger.info(f"⏱️  Intervalo de verificação: {MONITORING_CONFIG['check_interval']}s")
        logger.info(f"📦 Tamanho do lote: {MONITORING_CONFIG['batch_size']}")
        logger.info("")
        
        try:
            while True:
                self.process_pending_notifications()
                time.sleep(MONITORING_CONFIG['check_interval'])
                
        except KeyboardInterrupt:
            logger.info("")
            logger.info("⏹️  Encerrando serviço...")
            self.print_stats()
            self.db.close()
            logger.info("👋 Serviço encerrado com sucesso")
        
        except Exception as e:
            logger.error(f"❌ Erro fatal: {e}")
            self.db.close()
            sys.exit(1)

# ==========================================
# EXECUÇÃO
# ==========================================
if __name__ == "__main__":
    try:
        automation = WhatsAppAutomation()
        automation.run()
    except Exception as e:
        logger.error(f"❌ Erro ao iniciar automação: {e}")
        sys.exit(1)