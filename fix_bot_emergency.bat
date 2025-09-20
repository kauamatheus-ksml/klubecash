@echo off
echo ========================================
echo 🚨 CORREÇÃO EMERGENCIAL - Bot WhatsApp
echo ========================================

echo.
echo 1️⃣ Matando todos os processos Node.js...
taskkill /IM node.exe /F 2>nul
timeout /t 2 /nobreak >nul

echo 2️⃣ Verificando se porta 3002 está livre...
netstat -ano | findstr :3002

echo.
echo 3️⃣ Limpando cache npm...
cd whatsapp
npm cache clean --force 2>nul

echo.
echo 4️⃣ Aguardando 5 segundos para garantir limpeza...
timeout /t 5 /nobreak >nul

echo.
echo 5️⃣ Iniciando bot WhatsApp limpo...
start "Klube Cash WhatsApp Bot" cmd /k "cd /d C:\Users\Kaua\Documents\Projetos\klubecash\whatsapp && npm start"

echo.
echo 6️⃣ Aguardando inicialização...
timeout /t 10 /nobreak >nul

echo.
echo 7️⃣ Testando conectividade...
cd ..
curl -s http://148.230.73.190:3002/status

echo.
echo ========================================
echo ✅ CORREÇÃO CONCLUÍDA!
echo ========================================
echo.
echo 📱 O bot deve estar rodando em uma nova janela
echo 📋 Escaneie o QR Code que aparecerá
echo 🧪 Execute: php send_test_message_now.php
echo.
pause