
# automation/evolution_client.py
"""
Cliente para interagir com Evolution API
Gerencia conexão, envio de mensagens e verificação de status
"""

import requests
import logging
import time
from typing import Dict, Tuple, Optional
from config_evolution import EVOLUTION_CONFIG, TEST_MODE

logger = logging.getLogger(__name__)

class EvolutionAPIClient:
    """Cliente para interação com Evolution API"""
    
    def __init__(self):
        self.base_url = EVOLUTION_CONFIG['api_url'].rstrip('/')
        self.api_key = EVOLUTION_CONFIG['api_key']
        self.instance_name = EVOLUTION_CONFIG['instance_name']
        self.timeout = EVOLUTION_CONFIG['timeout']
        self.headers = {
            'apikey': self.api_key,
            'Content-Type': 'application/json'
        }
        
        logger.info(f"Evolution API Client inicializado - URL: {self.base_url}, Instance: {self.instance_name}")
    
    def check_connection(self) -> bool:
        """
        Verifica se a instância WhatsApp está conectada
        
        Returns:
            bool: True se conectado, False caso contrário
        """
        try:
            url = f"{self.base_url}/instance/connectionState/{self.instance_name}"
            response = requests.get(url, headers=self.headers, timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                state = data.get('instance', {}).get('state', 'close')
                
                if state == 'open':
                    logger.info("✅ WhatsApp conectado e pronto")
                    return True
                else:
                    logger.warning(f"⚠️  WhatsApp não conectado - Estado: {state}")
                    return False
            else:
                logger.error(f"❌ Erro ao verificar conexão: HTTP {response.status_code}")
                return False
                
        except Exception as e:
            logger.error(f"❌ Erro ao verificar conexão: {e}")
            return False
    
    def send_text_message(self, phone: str, message: str) -> Tuple[bool, Optional[str]]:
        """
        Envia mensagem de texto via WhatsApp
        
        Args:
            phone: Número do telefone (com código do país)
            message: Texto da mensagem
        
        Returns:
            Tuple[bool, Optional[str]]: (sucesso, message_id ou erro)
        """
        try:
            # Modo teste
            if TEST_MODE:
                logger.info(f"🧪 MODO TESTE - Mensagem simulada para {phone}")
                logger.info(f"Conteúdo: {message[:100]}...")
                return True, "test_mode_message_id"
            
            # Formatar telefone
            formatted_phone = self._format_phone(phone)
            
            # Preparar payload
            url = f"{self.base_url}/message/sendText/{self.instance_name}"
            payload = {
                "number": formatted_phone,
                "text": message
            }
            
            # Enviar requisição
            logger.info(f"📤 Enviando mensagem para {formatted_phone}...")
            response = requests.post(
                url,
                json=payload,
                headers=self.headers,
                timeout=self.timeout
            )
            
            # Verificar resposta
            if response.status_code in [200, 201]:
                data = response.json()
                message_id = data.get('key', {}).get('id', 'sent')
                
                logger.info(f"✅ Mensagem enviada com sucesso - ID: {message_id}")
                return True, message_id
            else:
                error_msg = f"HTTP {response.status_code}: {response.text}"
                logger.error(f"❌ Erro ao enviar mensagem: {error_msg}")
                return False, error_msg
                
        except requests.exceptions.Timeout:
            error_msg = "Timeout ao enviar mensagem"
            logger.error(f"❌ {error_msg}")
            return False, error_msg
            
        except Exception as e:
            error_msg = str(e)
            logger.error(f"❌ Erro ao enviar mensagem: {error_msg}")
            return False, error_msg
    
    def send_message_with_retry(self, phone: str, message: str) -> Tuple[bool, Optional[str]]:
        """
        Envia mensagem com tentativas de retry
        
        Args:
            phone: Número do telefone
            message: Texto da mensagem
        
        Returns:
            Tuple[bool, Optional[str]]: (sucesso, message_id ou erro)
        """
        max_attempts = EVOLUTION_CONFIG['retry_attempts']
        retry_delay = EVOLUTION_CONFIG['retry_delay']
        
        for attempt in range(1, max_attempts + 1):
            logger.info(f"📬 Tentativa {attempt}/{max_attempts} para {phone}")
            
            success, result = self.send_text_message(phone, message)
            
            if success:
                return True, result
            
            if attempt < max_attempts:
                logger.warning(f"⚠️  Tentativa {attempt} falhou. Aguardando {retry_delay}s...")
                time.sleep(retry_delay)
        
        logger.error(f"❌ Todas as {max_attempts} tentativas falharam para {phone}")
        return False, result
    
    def _format_phone(self, phone: str) -> str:
        """
        Formata número de telefone para padrão WhatsApp
        Remove caracteres não numéricos e adiciona código do Brasil se necessário
        
        Args:
            phone: Telefone no formato brasileiro
        
        Returns:
            str: Telefone formatado (ex: 5534998002600)
        """
        # Remover caracteres não numéricos
        clean_phone = ''.join(filter(str.isdigit, phone))
        
        # Adicionar código do Brasil se não tiver
        if not clean_phone.startswith('55'):
            clean_phone = '55' + clean_phone
        
        # Remover 9º dígito extra se houver (alguns casos legados)
        # Formato esperado: 55 + DDD (2) + número (8 ou 9 dígitos)
        if len(clean_phone) == 13 and clean_phone[4] == '9':
            # Número com 9 dígitos (padrão atual)
            return clean_phone
        elif len(clean_phone) == 12:
            # Número com 8 dígitos (fixo ou celular antigo)
            return clean_phone
        
        return clean_phone
    
    def get_instance_info(self) -> Dict:
        """
        Obtém informações da instância
        
        Returns:
            Dict: Dados da instância
        """
        try:
            url = f"{self.base_url}/instance/fetchInstances"
            response = requests.get(url, headers=self.headers, timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                
                # Filtrar pela instância específica
                for instance in data:
                    if instance.get('instance', {}).get('instanceName') == self.instance_name:
                        return instance
                
                logger.warning(f"⚠️  Instância {self.instance_name} não encontrada")
                return {}
            else:
                logger.error(f"❌ Erro ao obter informações: HTTP {response.status_code}")
                return {}
                
        except Exception as e:
            logger.error(f"❌ Erro ao obter informações da instância: {e}")
            return {}
    
    def logout(self) -> bool:
        """
        Desconecta a instância WhatsApp
        
        Returns:
            bool: True se desconectado com sucesso
        """
        try:
            url = f"{self.base_url}/instance/logout/{self.instance_name}"
            response = requests.delete(url, headers=self.headers, timeout=10)
            
            if response.status_code == 200:
                logger.info("✅ Instância desconectada com sucesso")
                return True
            else:
                logger.error(f"❌ Erro ao desconectar: HTTP {response.status_code}")
                return False
                
        except Exception as e:
            logger.error(f"❌ Erro ao desconectar instância: {e}")
            return False