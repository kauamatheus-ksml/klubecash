# 💰 Klube Cash - App Cliente

<div align="center">
  <img src="assets/images/logo.png" alt="Klube Cash Logo" width="200"/>
  
  <p><em>Sistema de cashback inteligente para maximizar suas economias</em></p>
  
  [![Flutter Version](https://img.shields.io/badge/Flutter-3.1.0+-blue.svg)](https://flutter.dev/)
  [![Dart Version](https://img.shields.io/badge/Dart-3.1.0+-blue.svg)](https://dart.dev/)
  [![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
  [![Platform](https://img.shields.io/badge/Platform-iOS%20%7C%20Android-lightgrey.svg)](https://flutter.dev/)
</div>

---

## 📱 Sobre o Projeto

O **Klube Cash** é um aplicativo inovador de cashback que permite aos usuários ganhar dinheiro de volta em suas compras em lojas parceiras. Com uma interface intuitiva e recursos avançados, o app oferece uma experiência completa de gerenciamento de cashback.

### ✨ Principais Funcionalidades

- 🔐 **Autenticação segura** com login social (Google/Apple)
- 📊 **Dashboard inteligente** com gráficos e resumos
- 💳 **Histórico detalhado** de transações de cashback
- 🏪 **Catálogo de lojas parceiras** com filtros avançados
- 👤 **Perfil personalizável** do usuário
- 🔔 **Notificações push** em tempo real
- 📱 **Interface responsiva** e acessível

---

## 🏗️ Arquitetura

O projeto segue os princípios da **Clean Architecture** com separação clara de responsabilidades:

📁 lib/
├── 🔧 core/           # Funcionalidades base
├── 🎯 features/       # Módulos por funcionalidade
├── 🎨 shared/         # Componentes compartilhados
├── 📄 main.dart       # Ponto de entrada
└── 🚀 app.dart        # Configuração do app

### 🛠️ Stack Tecnológica

- **Framework:** Flutter 3.1.0+
- **Estado:** Riverpod 2.4.9
- **Navegação:** Go Router 13.2.1
- **Network:** Dio 5.4.1 + Retrofit
- **Storage:** Secure Storage + Hive
- **UI:** Material Design 3
- **Charts:** FL Chart + Syncfusion
- **Notificações:** Firebase Cloud Messaging

---

## 🚀 Começando

### 📋 Pré-requisitos

- [Flutter](https://docs.flutter.dev/get-started/install) 3.1.0+
- [Dart](https://dart.dev/get-dart) 3.1.0+
- [Git](https://git-scm.com/)

### ⚙️ Instalação

1. **Clone o repositório**
   ```bash
   git clone https://github.com/seu-usuario/klube-cash-client.git
   cd klube-cash-client