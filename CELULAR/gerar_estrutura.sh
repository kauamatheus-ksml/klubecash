#!/bin/bash

# --- Script para Gerar Estrutura de Projeto Flutter ---

# Cores para o terminal
GREEN='\033[0;32m'
NC='\033[0m' # No Color

echo -e "${GREEN}Iniciando a criação do projeto Flutter 'klubecash_app'...${NC}"
# 1. Cria o projeto Flutter
flutter create klubecash_app

# Se a criação falhar, interrompe o script
if [ $? -ne 0 ]; then
    echo "Falha ao criar o projeto Flutter. Verifique se o Flutter está instalado e no seu PATH."
    exit 1
fi

# 2. Entra na pasta do projeto
cd klubecash_app

echo -e "${GREEN}Estrutura base criada. Agora, criando as pastas e arquivos...${NC}"

# 3. Cria as pastas principais (common e features)
mkdir -p lib/common/{api,utils,widgets,theme}
mkdir -p lib/features/{auth,dashboard,stores,qr_code,statement,withdrawal,profile}

# 4. Cria a estrutura interna de cada feature
# Usando um loop para criar a estrutura 'presentation/{pages,widgets}' para cada feature
for feature in auth dashboard stores statement withdrawal profile
do
    mkdir -p lib/features/$feature/presentation/{pages,widgets}
done

# Cria a estrutura para a feature 'qr_code' que não tem widgets
mkdir -p lib/features/qr_code/presentation/pages

# 5. Cria os arquivos .dart vazios
touch lib/common/theme/app_colors.dart
touch lib/common/theme/app_theme.dart

# Arquivos da feature 'auth'
touch lib/features/auth/presentation/pages/login_page.dart
touch lib/features/auth/presentation/widgets/login_form.dart

# Arquivos da feature 'dashboard'
touch lib/features/dashboard/presentation/pages/dashboard_page.dart
touch lib/features/dashboard/presentation/widgets/balance_card.dart
touch lib/features/dashboard/presentation/widgets/bottom_nav_bar.dart

# Arquivos da feature 'stores'
touch lib/features/stores/presentation/pages/partner_stores_page.dart
touch lib/features/stores/presentation/widgets/store_list_item.dart
touch lib/features/stores/presentation/widgets/search_bar.dart

# Arquivo da feature 'qr_code'
touch lib/features/qr_code/presentation/pages/qr_code_scanner_page.dart

# Arquivos da feature 'statement'
touch lib/features/statement/presentation/pages/statement_page.dart
touch lib/features/statement/presentation/widgets/transaction_tile.dart

# Arquivo da feature 'withdrawal'
touch lib/features/withdrawal/presentation/pages/withdrawal_page.dart

# Arquivos da feature 'profile'
touch lib/features/profile/presentation/pages/profile_page.dart
touch lib/features/profile/presentation/pages/edit_profile_page.dart
touch lib/features/profile/presentation/widgets/profile_option_tile.dart

# 6. Sobrescreve o main.dart com um conteúdo inicial
# ATENÇÃO: A linha 'import' pode dar erro no início, pois os arquivos ainda estão vazios.
# Isso é normal. O erro desaparecerá conforme você desenvolve as telas.
echo "import 'package:flutter/material.dart';
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
" > lib/main.dart

echo -e "${GREEN}Estrutura de pastas e arquivos criada com sucesso dentro de 'klubecash_app/'!${NC}"
echo "Próximo passo: Abra a pasta 'klubecash_app' no seu editor de código preferido (ex: VS Code)."