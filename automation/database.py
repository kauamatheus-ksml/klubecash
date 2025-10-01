# automation/database.py
"""
Gerenciador de conexão com banco de dados MySQL
"""

import mysql.connector
from mysql.connector import Error
import logging
from config import DB_CONFIG

logger = logging.getLogger(__name__)

class DatabaseManager:
    """Gerencia conexões e consultas ao banco de dados"""
    
    def __init__(self):
        self.connection = None
        self.connect()
    
    def connect(self):
        """Estabelece conexão com o banco de dados"""
        try:
            self.connection = mysql.connector.connect(**DB_CONFIG)
            if self.connection.is_connected():
                logger.info("✅ Conectado ao banco de dados MySQL")
                return True
        except Error as e:
            logger.error(f"❌ Erro ao conectar ao banco de dados: {e}")
            return False
    
    def get_pending_notifications(self):
        """
        Busca transações que precisam de notificação WhatsApp
        
        Retorna lista de transações com dados do cliente e loja
        """
        try:
            cursor = self.connection.cursor(dictionary=True)
            
            # Query para buscar transações aprovadas que ainda não foram notificadas
            query = """
                SELECT 
                    t.id as transaction_id,
                    t.valor_total,
                    t.valor_cliente as cashback_value,
                    t.codigo_transacao,
                    t.data_transacao,
                    u.id as user_id,
                    u.nome as client_name,
                    u.telefone as client_phone,
                    u.email as client_email,
                    l.nome_fantasia as store_name,
                    l.porcentagem_cashback as cashback_percentage
                FROM transacoes_cashback t
                JOIN usuarios u ON t.usuario_id = u.id
                JOIN lojas l ON t.loja_id = l.id
                LEFT JOIN whatsapp_logs w ON w.additional_data LIKE CONCAT('%"transaction_id":', t.id, '%')
                WHERE t.status = 'aprovado'
                AND u.telefone IS NOT NULL
                AND u.telefone != ''
                AND (w.id IS NULL OR (w.success = 0 AND w.created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)))
                ORDER BY t.data_transacao DESC
                LIMIT 50
            """
            
            cursor.execute(query)
            results = cursor.fetchall()
            
            logger.info(f"📊 Encontradas {len(results)} transações pendentes de notificação")
            return results
            
        except Error as e:
            logger.error(f"❌ Erro ao buscar notificações pendentes: {e}")
            return []
        finally:
            if cursor:
                cursor.close()
    
    def log_whatsapp_message(self, phone, message, success, transaction_id, error=None):
        """
        Registra o envio da mensagem WhatsApp no banco de dados
        """
        try:
            cursor = self.connection.cursor()
            
            query = """
                INSERT INTO whatsapp_logs 
                (type, phone, message_preview, success, error_message, 
                additional_data, ip_address, user_agent, created_at)
                VALUES 
                (%s, %s, %s, %s, %s, %s, %s, %s, NOW())
            """
            
            import json
            additional_data = json.dumps({
                'transaction_id': transaction_id,
                'automation': 'python',
                'timestamp': str(datetime.now())
            })
            
            message_preview = message[:200] if len(message) > 200 else message
            
            cursor.execute(query, (
                'cashback_notification',
                phone,
                message_preview,
                1 if success else 0,
                error if error else None,
                additional_data,
                'automation_script',
                'Python WhatsApp Automation',
            ))
            
            self.connection.commit()
            logger.info(f"✅ Log registrado no banco de dados para transação #{transaction_id}")
            
        except Error as e:
            logger.error(f"❌ Erro ao registrar log: {e}")
        finally:
            if cursor:
                cursor.close()
    
    def close(self):
        """Fecha a conexão com o banco de dados"""
        if self.connection and self.connection.is_connected():
            self.connection.close()
            logger.info("🔌 Conexão com banco de dados fechada")