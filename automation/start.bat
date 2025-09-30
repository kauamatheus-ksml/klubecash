@echo off
REM Script para iniciar o serviço no Windows
REM Klube Cash - 2025

echo Iniciando WhatsApp Automation Service...

REM Ativar ambiente virtual
call venv\Scripts\activate

REM Verificar .env
if not exist .env (
    echo Arquivo .env nao encontrado!
    echo Execute install.bat primeiro
    pause
    exit /b 1
)

REM Executar script
echo Executando whatsapp_sender.py...
python whatsapp_sender.py

REM Se parar, mostrar mensagem
echo Servico parado
pause