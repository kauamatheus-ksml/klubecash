# automation/message_templates.py
"""
Templates de mensagens para notificações de cashback
"""

from datetime import datetime, timedelta

def format_currency(value):
    """Formata valor para moeda brasileira"""
    return f"R$ {value:,.2f}".replace(',', 'X').replace('.', ',').replace('X', '.')

def format_phone(phone):
    """Formata telefone para padrão WhatsApp internacional"""
    # Remove caracteres não numéricos
    phone = ''.join(filter(str.isdigit, phone))
    
    # Adiciona código do Brasil se não tiver
    if not phone.startswith('55'):
        phone = '55' + phone
    
    return phone

def generate_cashback_message(transaction_data):
    """
    Gera mensagem de notificação de cashback
    
    Args:
        transaction_data: Dicionário com dados da transação
    
    Returns:
        String com a mensagem formatada
    """
    client_name = transaction_data.get('client_name', 'Cliente')
    store_name = transaction_data.get('store_name', 'Loja parceira')
    purchase_value = transaction_data.get('valor_total', 0)
    cashback_value = transaction_data.get('cashback_value', 0)
    transaction_code = transaction_data.get('codigo_transacao', 'N/A')
    
    # Data de liberação (7 dias úteis)
    release_date = datetime.now() + timedelta(days=10)
    release_date_str = release_date.strftime('%d/%m/%Y')
    
    message = f"""⭐ *{client_name}*, sua compra foi registrada!

🏪 {store_name}
💰 Compra: {format_currency(purchase_value)}
🎁 Cashback: {format_currency(cashback_value)}

⏰ Liberação em até 7 dias úteis
📅 Previsão: {release_date_str}

💳 Código: {transaction_code}

Acesse sua conta: https://klubecash.com

📱 *Klube Cash - Dinheiro de volta em suas compras!*"""
    
    return message

def generate_mvp_instant_cashback_message(transaction_data):
    """
    Gera mensagem para cashback instantâneo (lojas MVP)
    """
    client_name = transaction_data.get('client_name', 'Cliente')
    store_name = transaction_data.get('store_name', 'Loja parceira')
    purchase_value = transaction_data.get('valor_total', 0)
    cashback_value = transaction_data.get('cashback_value', 0)
    
    message = f"""🎉 *{client_name}*, cashback creditado!

✨ CASHBACK INSTANTÂNEO ✨

🏪 {store_name}
💰 Compra: {format_currency(purchase_value)}
🎁 Cashback: {format_currency(cashback_value)}

✅ *Já disponível em sua conta!*

Você pode usar seu cashback agora mesmo em {store_name}!

Acesse: https://klubecash.com

📱 *Klube Cash - Seu dinheiro volta na hora!*"""
    
    return message

def generate_error_message():
    """Mensagem genérica de erro"""
    return """Desculpe, houve um problema ao processar sua notificação. 
Entre em contato com o suporte: suporte@klubecash.com"""