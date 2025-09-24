# Klube Cash WhatsApp Bot - Configuração de Execução Contínua

## Serviço Systemd Configurado

O bot foi configurado para executar continuamente através de um serviço systemd com as seguintes características:

### Comandos Úteis:
```bash
# Verificar status do serviço
systemctl status klube-whatsapp-bot.service

# Iniciar/parar/reiniciar o serviço
systemctl start klube-whatsapp-bot.service
systemctl stop klube-whatsapp-bot.service
systemctl restart klube-whatsapp-bot.service

# Ver logs em tempo real
journalctl -f -u klube-whatsapp-bot.service

# Ver logs históricos
journalctl -u klube-whatsapp-bot.service

# Habilitar/desabilitar inicialização automática
systemctl enable klube-whatsapp-bot.service
systemctl disable klube-whatsapp-bot.service
```

### Recursos de Resiliência:

1. **Auto-restart automático**: Se o bot falhar, o systemd irá reiniciá-lo automaticamente em 5 segundos
2. **Reconexão automática**: O código foi melhorado para reconectar automaticamente quando a conexão com o WhatsApp for perdida
3. **Tratamento de erros**: Logs detalhados e tratamento de diferentes estados de conexão
4. **Preservação de sessão**: Tokens de sessão são mantidos para evitar necessidade de QR Code frequente
5. **Limites de recursos**: Configurados limites de memória para evitar consumo excessivo

### Configurações de Segurança:
- Limite de 5 tentativas de restart em 60 segundos
- Limites de memória: High=512MB, Max=1GB
- Limite de arquivos abertos: 65536

### Logs:
Os logs são automaticamente gerenciados pelo systemd e podem ser visualizados com `journalctl`.

### Status Atual:
✅ Serviço ativo e funcionando
✅ Auto-restart configurado
✅ Inicialização automática no boot habilitada
✅ Sessão WhatsApp autenticada

O bot agora está configurado para executar 24/7 com resiliência total!