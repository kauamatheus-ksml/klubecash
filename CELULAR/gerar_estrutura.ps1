# --- Script para Gerar Estrutura de Projeto Flutter ---

# Cores para o terminal
$GREEN = "\e[32m"
$NC = "\e[0m"

Write-Host "${GREEN}Iniciando a criação do projeto Flutter 'klubecash_app'...${NC}"
# 1. Cria o projeto Flutter
flutter create klubecash_app

# Se a criação falhar, interrompe o script
if ($LASTEXITCODE -ne 0) {
    Write-Host "Falha ao criar o projeto Flutter. Verifique se o Flutter está instalado e no seu PATH."
    exit
}

# 2. Entra na pasta do projeto
Set-Location -Path .\klubecash_app

Write-Host "${GREEN}Estrutura base criada. Agora, criando as pastas e arquivos...${NC}"

# 3. Cria as pastas de 'common'
New-Item -ItemType Directory -Force -Path "lib/common/api"
New-Item -ItemType Directory -Force -Path "lib/common/utils"
New-Item -ItemType Directory -Force -Path "lib/common/widgets"
New-Item -ItemType Directory -Force -Path "lib/common/theme"

# 4. Cria a estrutura interna de cada feature
$features = "auth", "dashboard", "stores", "statement", "withdrawal", "profile"
foreach ($feature in $features) {
    New-Item -ItemType Directory -Force -Path "lib/features/$feature/presentation/pages"
    New-Item -ItemType Directory -Force -Path "lib/features/$feature/presentation/widgets"
}

# Cria a estrutura para a feature 'qr_code'
New-Item -ItemType Directory -Force -Path "lib/features/qr_code/presentation/pages"

# 5. Cria os arquivos .dart vazios
New-Item -ItemType File -Force -Path "lib/common/theme/app_colors.dart"
New-Item -ItemType File -Force -Path "lib/common/theme/app_theme.dart"

# Arquivos da feature 'auth'
New-Item -ItemType File -Force -Path "lib/features/auth/presentation/pages/login_page.dart"
New-Item -ItemType File -Force -Path "lib/features/auth/presentation/widgets/login_form.dart"

# Arquivos da feature 'dashboard'
New-Item -ItemType File -Force -Path "lib/features/dashboard/presentation/pages/dashboard_page.dart"
New-Item -ItemType File -Force -Path "lib/features/dashboard/presentation/widgets/balance_card.dart"
New-Item -ItemType File -Force -Path "lib/features/dashboard/presentation/widgets/bottom_nav_bar.dart"

# Arquivos da feature 'stores'
New-Item -ItemType File -Force -Path "lib/features/stores/presentation/pages/partner_stores_page.dart"
New-Item -ItemType File -Force -Path "lib/features/stores/presentation/widgets/store_list_item.dart"
New-Item -ItemType File -Force -Path "lib/features/stores/presentation/widgets/search_bar.dart"

# Arquivo da feature 'qr_code'
New-Item -ItemType File -Force -Path "lib/features/qr_code/presentation/pages/qr_code_scanner_page.dart"

# Arquivos da feature 'statement'
New-Item -ItemType File -Force -Path "lib/features/statement/presentation/pages/statement_page.dart"
New-Item -ItemType File -Force -Path "lib/features/statement/presentation/widgets/transaction_tile.dart"

# Arquivo da feature 'withdrawal'
New-Item -ItemType File -Force -Path "lib/features/withdrawal/presentation/pages/withdrawal_page.dart"

# Arquivos da feature 'profile'
New-Item -ItemType File -Force -Path "lib/features/profile/presentation/pages/profile_page.dart"
New-Item -ItemType File -Force -Path "lib/features/profile/presentation/pages/edit_profile_page.dart"
New-Item -ItemType File -Force -Path "lib/features/profile/presentation/widgets/profile_option_tile.dart"


# 6. Sobrescreve o main.dart com um conteúdo inicial
$mainDartContent = @"
import 'package:flutter/material.dart';
import 'package:klubecash_app/features/auth/presentation/pages/login_page.dart';
// import 'package:klubecash_app/common/theme/app_theme.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'KlubeCash',
      // theme: AppTheme.lightTheme,
      debugShowCheckedModeBanner: false,
      home: LoginPage(),
    );
  }
}
"@
Set-Content -Path "lib/main.dart" -Value $mainDartContent

Write-Host "${GREEN}Estrutura de pastas e arquivos criada com sucesso dentro de 'klubecash_app/'!${NC}"
Write-Host "Próximo passo: Abra a pasta 'klubecash_app' no seu editor de código preferido (ex: VS Code)."