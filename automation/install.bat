@echo off
REM Script de instalação para Windows
REM Klube Cash - 2025

echo Instalando WhatsApp Automation para Klube Cash...

REM Verificar Python
python --version >nul 2>&1
if errorlevel 1 (
    echo Python nao encontrado! Instale Python 3.8 ou superior.
    pause
    exit /b 1
)

echo Python encontrado

REM Criar ambiente virtual
echo Criando ambiente virtual...
python -m venv venv

REM Ativar ambiente virtual
call venv\Scripts\activate

REM Instalar dependências
echo Instalando dependencias...
pip install --upgrade pip
pip install -r requirements.txt

REM Criar pasta de logs
if not exist logs mkdir logs

REM Verificar .env
if not exist .env (
    echo Arquivo .env nao encontrado!
    echo Criando .env a partir do template...
    copy .env.example .env
    echo AVISO: Edite o arquivo .env antes de continuar!
    pause
)

echo Instalacao concluida!
echo.
echo Proximos passos:
echo 1. Edite o arquivo .env
echo 2. Execute: start.bat
pause