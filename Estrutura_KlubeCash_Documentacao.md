# Documentação da Estrutura do Projeto KlubeCash

## Índice
1. [Visão Geral](#visão-geral)
2. [Arquitetura do Sistema](#arquitetura-do-sistema)
3. [Estrutura de Diretórios](#estrutura-de-diretórios)
4. [Padrões Arquiteturais](#padrões-arquiteturais)
5. [Componentes Principais](#componentes-principais)
6. [Banco de Dados](#banco-de-dados)
7. [APIs e Integrações](#apis-e-integrações)
8. [Segurança](#segurança)
9. [Frontend e UI](#frontend-e-ui)
10. [Mobile (Flutter)](#mobile-flutter)
11. [Como Replicar em Outros Projetos](#como-replicar-em-outros-projetos)

---

## Visão Geral

O **KlubeCash** é um sistema completo de cashback desenvolvido em PHP com arquitetura MVC (Model-View-Controller). O sistema permite que clientes ganhem dinheiro de volta em compras, lojas gerenciem transações e administradores controlem todo o ecossistema.

### Características Principais:
- Sistema multi-usuário (Clientes, Lojas, Funcionários, Administradores)
- Integração com Mercado Pago e OpenPix
- PWA (Progressive Web App) com funcionalidades offline
- Aplicativo mobile em Flutter
- Sistema de notificações via WhatsApp
- Dashboard administrativo completo

---

## Arquitetura do Sistema

### Padrão MVC Híbrido
O sistema utiliza uma abordagem MVC adaptada:

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│    Views    │◄──►│ Controllers │◄──►│   Models    │
│ (Frontend)  │    │  (Lógica)   │    │ (Dados)     │
└─────────────┘    └─────────────┘    └─────────────┘
       │                   │                   │
       └───────────────────┼───────────────────┘
                           │
                    ┌─────────────┐
                    │  Database   │
                    │   (MySQL)   │
                    └─────────────┘
```

### Fluxo de Dados:
1. **Entrada**: Usuário interage com Views (PHP/HTML/CSS/JS)
2. **Processamento**: Controllers processam requisições
3. **Dados**: Models interagem com o banco de dados
4. **Saída**: Resposta retorna via Views ou APIs JSON

---

## Estrutura de Diretórios

```
klubecash/
├── 📁 api/                          # APIs principais do sistema
│   ├── balance.php                  # API de saldo
│   ├── mercadopago.php             # Integração Mercado Pago
│   ├── openpix.php                 # Integração OpenPix
│   ├── transactions.php            # API de transações
│   └── users.php                   # API de usuários
│
├── 📁 api2/                         # APIs secundárias/auxiliares
│   ├── login.php                   # API de autenticação
│   ├── profile/                    # APIs de perfil
│   └── notifications.php           # API de notificações
│
├── 📁 assets/                       # Recursos estáticos
│   ├── css/                        # Folhas de estilo
│   │   ├── components/             # CSS de componentes
│   │   └── views/                  # CSS específico por view
│   ├── js/                         # JavaScript
│   │   ├── components/             # JS de componentes
│   │   └── admin/                  # JS administrativo
│   ├── images/                     # Imagens do sistema
│   └── icons/                      # Ícones PWA
│
├── 📁 auth/                         # Sistema de autenticação
│   └── google/                     # Autenticação Google OAuth
│
├── 📁 classes/                      # Classes utilitárias
│   ├── CashbackNotifier.php        # Notificações de cashback
│   ├── ImageGenerator.php          # Geração de imagens
│   └── SaldoConsulta.php          # Consulta de saldos
│
├── 📁 config/                       # Configurações do sistema
│   ├── constants.php               # Constantes globais
│   ├── database.php                # Configuração do BD
│   └── email.php                   # Configuração de email
│
├── 📁 controllers/                  # Controladores MVC
│   ├── AdminController.php         # Controle administrativo
│   ├── AuthController.php          # Controle de autenticação
│   ├── ClientController.php        # Controle de clientes
│   ├── StoreController.php         # Controle de lojas
│   └── TransactionController.php   # Controle de transações
│
├── 📁 core/                         # Núcleo do sistema
│   └── bootstrap.php               # Inicialização do sistema
│
├── 📁 cron/                         # Tarefas agendadas
│   ├── send-scheduled-emails.php   # Envio de emails
│   └── send-weekly-emails.php      # Relatórios semanais
│
├── 📁 libs/                         # Bibliotecas externas
│   └── PHPMailer/                  # Biblioteca de email
│
├── 📁 models/                       # Modelos de dados
│   ├── User.php                    # Modelo de usuário
│   ├── Store.php                   # Modelo de loja
│   ├── Transaction.php             # Modelo de transação
│   └── Payment.php                 # Modelo de pagamento
│
├── 📁 pwa/                          # Progressive Web App
│   ├── manifest.json               # Manifesto PWA
│   ├── sw.js                       # Service Worker
│   └── offline.html                # Página offline
│
├── 📁 utils/                        # Utilitários do sistema
│   ├── Email.php                   # Utilitários de email
│   ├── Security.php                # Segurança
│   ├── Validator.php               # Validações
│   ├── PaymentProcessor.php        # Processamento de pagamentos
│   └── WhatsAppBot.php             # Bot do WhatsApp
│
├── 📁 views/                        # Camada de apresentação
│   ├── admin/                      # Painel administrativo
│   ├── client/                     # Área do cliente
│   ├── stores/                     # Área das lojas
│   ├── auth/                       # Páginas de autenticação
│   └── components/                 # Componentes reutilizáveis
│
├── 📁 klubecashmobile/              # Aplicativo Flutter
│   ├── lib/                        # Código fonte Dart
│   │   ├── features/               # Features por domínio
│   │   ├── core/                   # Núcleo da aplicação
│   │   └── main.dart               # Ponto de entrada
│   ├── android/                    # Configurações Android
│   └── ios/                        # Configurações iOS
│
├── 📁 whatsapp/                     # Bot WhatsApp (Node.js)
│   ├── bot.js                      # Lógica do bot
│   ├── package.json                # Dependências Node.js
│   └── start.sh                    # Script de inicialização
│
└── index.php                       # Página principal
```

---

## Padrões Arquiteturais

### 1. **MVC (Model-View-Controller)**
```php
// Exemplo de Controller
class AuthController {
    public static function login($email, $senha) {
        // Validação de entrada
        $validator = new Validator();
        
        // Interação com Model
        $user = new User();
        $result = $user->authenticate($email, $senha);
        
        // Retorno para View
        return $result;
    }
}
```

### 2. **Singleton Pattern** (Conexão com BD)
```php
class Database {
    private static $connection = null;
    
    public static function getConnection() {
        if (self::$connection === null) {
            // Criar nova conexão
            self::$connection = new PDO($dsn, $user, $pass);
        }
        return self::$connection;
    }
}
```

### 3. **Factory Pattern** (Para criação de objetos)
```php
class UserFactory {
    public static function create($type) {
        switch($type) {
            case 'cliente': return new Cliente();
            case 'loja': return new Loja();
            case 'admin': return new Administrador();
        }
    }
}
```

### 4. **Repository Pattern** (Acesso a dados)
```php
class UserRepository {
    public function findById($id) {
        // Lógica de busca no BD
    }
    
    public function save($user) {
        // Lógica de salvamento
    }
}
```

---

## Componentes Principais

### 1. **Sistema de Autenticação**
- **Localização**: `controllers/AuthController.php`
- **Responsabilidades**:
  - Login/logout de usuários
  - Registro de novos usuários
  - Recuperação de senha
  - Gestão de sessões
  - Autenticação Google OAuth

```php
// Exemplo de uso
$result = AuthController::login($email, $senha);
if ($result['status']) {
    // Usuário autenticado
    header('Location: /dashboard');
}
```

### 2. **Sistema de Cashback**
- **Localização**: `classes/CashbackNotifier.php`
- **Responsabilidades**:
  - Cálculo de cashback
  - Notificações automáticas
  - Processamento de transações

### 3. **Sistema de Pagamentos**
- **Localização**: `utils/PaymentProcessor.php`
- **Integrações**:
  - Mercado Pago (PIX, cartão)
  - OpenPix (PIX)
  - Webhooks para confirmação

### 4. **Sistema de Notificações**
- **WhatsApp**: `whatsapp/bot.js`
- **Push Notifications**: PWA
- **Email**: `utils/Email.php`

---

## Banco de Dados

### Estrutura Principal:

```sql
-- Usuários do sistema
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    tipo ENUM('cliente', 'loja', 'admin', 'funcionario'),
    status ENUM('ativo', 'inativo', 'bloqueado'),
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lojas parceiras
CREATE TABLE lojas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    nome_fantasia VARCHAR(255) NOT NULL,
    categoria VARCHAR(100),
    porcentagem_cashback DECIMAL(5,2),
    status ENUM('pendente', 'aprovado', 'rejeitado'),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Transações de cashback
CREATE TABLE transacoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT,
    loja_id INT,
    valor_compra DECIMAL(10,2),
    valor_cashback DECIMAL(10,2),
    status ENUM('pendente', 'aprovado', 'cancelado'),
    data_transacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (loja_id) REFERENCES lojas(id)
);
```

### Padrões de Uso:
1. **Prepared Statements** para segurança
2. **Transações** para operações complexas
3. **Índices** para performance
4. **Foreign Keys** para integridade

---

## APIs e Integrações

### 1. **APIs REST Internas**

#### Estrutura Padrão:
```php
// api/exemplo.php
header('Content-Type: application/json');

try {
    // Validação de entrada
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Processamento
    $result = processarRequisicao($input);
    
    // Resposta
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

### 2. **Integração Mercado Pago**
```php
// utils/MercadoPagoClient.php
class MercadoPagoClient {
    private $accessToken = MP_ACCESS_TOKEN;
    
    public function createPayment($data) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => MP_BASE_URL . '/v1/payments',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json'
            ]
        ]);
        
        return json_decode(curl_exec($curl), true);
    }
}
```

### 3. **Sistema de Webhooks**
```php
// webhook/openpix.php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

if (Security::validateWebhook($payload, $signature)) {
    $data = json_decode($payload, true);
    PaymentProcessor::processWebhook($data);
}
```

---

## Segurança

### 1. **Proteção CSRF**
```php
// utils/Security.php
class Security {
    public static function generateCSRFToken() {
        return bin2hex(random_bytes(32));
    }
    
    public static function validateCSRFToken($token) {
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
```

### 2. **Sanitização de Dados**
```php
public static function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
```

### 3. **Validação de Permissões**
```php
public static function requireAdminAccess() {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        header('Location: /acesso-negado');
        exit;
    }
}
```

### 4. **Configurações de Sessão Segura**
```php
// config/constants.php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
```

---

## Frontend e UI

### 1. **Arquitetura CSS**
```
assets/css/
├── mobile-first.css        # Base responsiva
├── components/            # Componentes UI
│   ├── toast.css         # Notificações
│   └── sidebar.css       # Navegação
└── views/                # Estilos por página
    ├── admin/
    ├── client/
    └── stores/
```

### 2. **JavaScript Modular**
```javascript
// assets/js/components/toast.js
class Toast {
    static show(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
    }
}
```

### 3. **PWA (Progressive Web App)**
```javascript
// pwa/sw.js - Service Worker
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open('klube-cash-v1').then(cache => {
            return cache.addAll([
                '/',
                '/cliente/dashboard',
                '/assets/css/main.css',
                '/assets/js/main.js'
            ]);
        })
    );
});
```

### 4. **Componentes Reutilizáveis**
```php
// views/components/sidebar.php
function renderSidebar($activeMenu, $userType) {
    $menuItems = getMenuItemsByUserType($userType);
    include 'sidebar-template.php';
}
```

---

## Mobile (Flutter)

### Arquitetura Clean Architecture

```
lib/
├── core/                  # Núcleo da aplicação
│   ├── constants/        # Constantes
│   ├── network/          # Cliente HTTP
│   ├── utils/            # Utilitários
│   └── widgets/          # Widgets base
│
├── features/             # Features por domínio
│   ├── auth/
│   │   ├── data/         # Fontes de dados
│   │   ├── domain/       # Regras de negócio
│   │   └── presentation/ # UI e Estado
│   ├── dashboard/
│   ├── cashback/
│   └── stores/
│
└── main.dart             # Ponto de entrada
```

### Exemplo de Feature:
```dart
// features/auth/presentation/providers/auth_provider.dart
class AuthProvider extends ChangeNotifier {
  final LoginUseCase _loginUseCase;
  
  Future<void> login(String email, String password) async {
    final result = await _loginUseCase(
      LoginParams(email: email, password: password)
    );
    
    result.fold(
      (failure) => _handleError(failure),
      (user) => _handleSuccess(user),
    );
  }
}
```

---

## Como Replicar em Outros Projetos

### 1. **Estrutura Base Recomendada**

```
projeto/
├── config/              # Configurações
│   ├── constants.php    # Constantes globais
│   └── database.php     # Conexão BD
├── core/                # Núcleo
│   └── bootstrap.php    # Inicialização
├── controllers/         # Controladores
├── models/             # Modelos
├── views/              # Views
│   └── components/     # Componentes
├── utils/              # Utilitários
├── assets/             # Recursos estáticos
└── api/                # APIs
```

### 2. **Padrões para Adotar**

#### A. **Configuração Centralizada**
```php
// config/constants.php
define('DB_HOST', 'localhost');
define('SITE_URL', 'https://seusite.com');
define('EMAIL_FROM', 'noreply@seusite.com');
```

#### B. **Bootstrap de Inicialização**
```php
// core/bootstrap.php
require_once 'config/constants.php';
require_once 'config/database.php';

// Autoload de classes
spl_autoload_register(function($class) {
    $paths = ['controllers/', 'models/', 'utils/'];
    foreach($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            break;
        }
    }
});
```

#### C. **Controller Base**
```php
// controllers/BaseController.php
abstract class BaseController {
    protected function validateInput($data, $rules) {
        return Validator::validate($data, $rules);
    }
    
    protected function jsonResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}
```

#### D. **Model Base**
```php
// models/BaseModel.php
abstract class BaseModel {
    protected $db;
    protected $table;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
```

### 3. **Implementação de Segurança**

```php
// utils/Security.php - Adaptar para seu projeto
class Security {
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(strip_tags(trim($input)));
    }
    
    public static function validateSession() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }
}
```

### 4. **Sistema de Componentes**

```php
// views/components/layout.php
function renderLayout($title, $content, $sidebar = true) {
    include 'header.php';
    if ($sidebar) include 'sidebar.php';
    echo $content;
    include 'footer.php';
}
```

### 5. **API Padronizada**

```php
// api/base_api.php
abstract class BaseAPI {
    protected function handleRequest() {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch($method) {
                case 'GET': return $this->get();
                case 'POST': return $this->post($input);
                case 'PUT': return $this->put($input);
                case 'DELETE': return $this->delete();
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }
    
    protected function successResponse($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    }
    
    protected function errorResponse($message) {
        echo json_encode(['success' => false, 'error' => $message]);
    }
}
```

### 6. **Checklist para Implementação**

#### Essencial:
- [ ] Configuração de constantes centralizadas
- [ ] Sistema de autoload ou bootstrap
- [ ] Conexão segura com banco de dados
- [ ] Validação e sanitização de entrada
- [ ] Sistema de sessões seguro
- [ ] Estrutura MVC básica

#### Recomendado:
- [ ] Sistema de logs
- [ ] Cache de consultas
- [ ] Compressão de assets
- [ ] Proteção CSRF
- [ ] Rate limiting para APIs
- [ ] Backup automático

#### Avançado:
- [ ] PWA com Service Workers
- [ ] Sistema de notificações
- [ ] Integração com APIs de pagamento
- [ ] Dashboard administrativo
- [ ] Sistema de relatórios

---

## Conclusão

A estrutura do KlubeCash demonstra um sistema robusto e escalável que pode servir como base para diversos tipos de projetos web. Os padrões adotados garantem:

- **Maintibilidade**: Código organizado e bem estruturado
- **Segurança**: Práticas de segurança em todas as camadas
- **Escalabilidade**: Arquitetura que suporta crescimento
- **Reutilização**: Componentes modulares e reutilizáveis
- **Performance**: Otimizações de cache e consultas

Para implementar em outros projetos, recomenda-se adaptar os componentes conforme a necessidade específica, mantendo sempre os princípios de segurança e boas práticas apresentados nesta documentação.

---

**Desenvolvido por**: Equipe KlubeCash  
**Versão**: 2.1.0  
**Data**: 2025  
**Licença**: Proprietária