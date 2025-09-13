// lib/core/constants/app_strings.dart
// Este arquivo contém todos os textos utilizados no app Klube Cash
// Organizado para suportar internacionalização futura

class AppStrings {
  // ==================== APP INFO ====================
  
  static const String appName = 'Klube Cash';
  static const String appVersion = '1.0.0';
  static const String appDescription = 'Sistema de cashback inteligente';

  // ==================== ONBOARDING ====================
  
  static const String onboardingTitle1 = 'Ganhe Cashback';
  static const String onboardingSubtitle1 = 'Receba dinheiro de volta em cada compra nas lojas parceiras';
  
  static const String onboardingTitle2 = 'Lojas Parceiras';
  static const String onboardingSubtitle2 = 'Descubra centenas de estabelecimentos que oferecem cashback';
  
  static const String onboardingTitle3 = 'Saque Fácil';
  static const String onboardingSubtitle3 = 'Transfira seus ganhos via PIX quando quiser';

  // ==================== AUTH SCREEN ====================
  
  // Login
  static const String loginTitle = 'Entrar';
  static const String loginSubtitle = 'Acesse sua conta e continue ganhando cashback';
  static const String emailLabel = 'Email ou CPF';
  static const String passwordLabel = 'Senha';
  static const String rememberMe = 'Lembrar-me';
  static const String forgotPassword = 'Esqueceu sua senha?';
  static const String loginButton = 'Entrar';
  static const String noAccount = 'Não tem conta?';
  static const String signUpLink = 'Cadastre-se';
  static const String orDivider = 'ou';
  static const String loginWithGoogle = 'Continuar com Google';
  static const String loginWithFacebook = 'Continuar com Facebook';
  
  // Register
  static const String registerTitle = 'Criar Conta';
  static const String registerSubtitle = 'Comece a ganhar cashback hoje mesmo';
  static const String fullNameLabel = 'Nome completo';
  static const String cpfLabel = 'CPF';
  static const String phoneLabel = 'Telefone';
  static const String confirmPasswordLabel = 'Confirmar senha';
  static const String agreeTerms = 'Concordo com os termos de uso';
  static const String registerButton = 'Cadastrar';
  static const String hasAccount = 'Já tem conta?';
  static const String signInLink = 'Faça login';
  
  // Recover Password
  static const String recoverTitle = 'Recuperar Senha';
  static const String recoverSubtitle = 'Digite seu email para receber as instruções';
  static const String sendInstructions = 'Enviar Instruções';
  static const String backToLogin = 'Voltar ao login';

  // ==================== NAVIGATION ====================
  
  static const String navHome = 'Início';
  static const String navHistory = 'Histórico';
  static const String navStores = 'Lojas';
  static const String navProfile = 'Perfil';

  // ==================== DASHBOARD ====================
  
  static const String welcomeBack = 'Bem-vindo de volta';
  static const String totalBalance = 'Saldo Total';
  static const String availableBalance = 'Disponível';
  static const String pendingBalance = 'Pendente';
  static const String totalEarned = 'Total Ganho';
  static const String withdrawButton = 'Sacar';
  static const String earnCashback = 'Ganhar Cashback';
  static const String recentTransactions = 'Transações Recentes';
  static const String viewAll = 'Ver todas';
  static const String quickActions = 'Ações Rápidas';
  static const String findStores = 'Encontrar Lojas';
  static const String cashbackHistory = 'Histórico de Cashback';

  // ==================== STORES ====================
  
  static const String partnerStores = 'Lojas Parceiras';
  static const String storesSubtitle = 'Descubra onde ganhar cashback';
  static const String searchStores = 'Buscar lojas...';
  static const String allCategories = 'Todas as categorias';
  static const String nearbyStores = 'Próximas a você';
  static const String popularStores = 'Mais populares';
  static const String cashbackRate = 'Taxa de cashback';
  static const String storeDetails = 'Detalhes da loja';
  static const String openHours = 'Horário de funcionamento';
  static const String storeAddress = 'Endereço';
  static const String contactInfo = 'Contato';

  // ==================== CASHBACK ====================
  
  static const String earnedCashback = 'Cashback Ganho';
  static const String pendingCashback = 'Cashback Pendente';
  static const String availableCashback = 'Cashback Disponível';
  static const String cashbackPercentage = '% de cashback';
  static const String cashbackValue = 'Valor do cashback';
  static const String minimumWithdraw = 'Saque mínimo';
  static const String withdrawalFee = 'Taxa de saque';
  static const String processingTime = 'Tempo de processamento';

  // ==================== TRANSACTIONS ====================
  
  static const String transactionHistory = 'Histórico de Transações';
  static const String transactionDetails = 'Detalhes da Transação';
  static const String transactionDate = 'Data';
  static const String transactionAmount = 'Valor';
  static const String transactionStore = 'Loja';
  static const String transactionStatus = 'Status';
  static const String transactionId = 'ID da Transação';
  
  // Status
  static const String statusApproved = 'Aprovado';
  static const String statusPending = 'Pendente';
  static const String statusCanceled = 'Cancelado';
  static const String statusProcessing = 'Processando';
  static const String statusRefunded = 'Estornado';

  // ==================== PROFILE ====================
  
  static const String myProfile = 'Meu Perfil';
  static const String personalInfo = 'Informações Pessoais';
  static const String editProfile = 'Editar Perfil';
  static const String changePassword = 'Alterar Senha';
  static const String notifications = 'Notificações';
  static const String security = 'Segurança';
  static const String privacy = 'Privacidade';
  static const String help = 'Ajuda';
  static const String about = 'Sobre';
  static const String logout = 'Sair';
  
  static const String currentPassword = 'Senha atual';
  static const String newPassword = 'Nova senha';
  static const String confirmNewPassword = 'Confirmar nova senha';

  // ==================== BUTTONS ====================
  
  static const String save = 'Salvar';
  static const String cancel = 'Cancelar';
  static const String confirm = 'Confirmar';
  static const String close = 'Fechar';
  static const String next = 'Próximo';
  static const String previous = 'Anterior';
  static const String finish = 'Finalizar';
  static const String retry = 'Tentar novamente';
  static const String refresh = 'Atualizar';
  static const String filter = 'Filtrar';
  static const String clear = 'Limpar';
  static const String apply = 'Aplicar';
  static const String edit = 'Editar';
  static const String delete = 'Excluir';
  static const String share = 'Compartilhar';

  // ==================== VALIDATION MESSAGES ====================
  
  static const String fieldRequired = 'Este campo é obrigatório';
  static const String invalidEmail = 'Email inválido';
  static const String invalidCpf = 'CPF inválido';
  static const String invalidPhone = 'Telefone inválido';
  static const String passwordTooShort = 'Senha deve ter pelo menos 8 caracteres';
  static const String passwordsNotMatch = 'Senhas não coincidem';
  static const String nameMinLength = 'Nome deve ter pelo menos 3 caracteres';
  static const String phoneRequired = 'Telefone é obrigatório';
  static const String cpfRequired = 'CPF é obrigatório';
  static const String emailRequired = 'Email é obrigatório';

  // ==================== SUCCESS MESSAGES ====================
  
  static const String loginSuccess = 'Login realizado com sucesso!';
  static const String registerSuccess = 'Conta criada com sucesso!';
  static const String profileUpdated = 'Perfil atualizado com sucesso!';
  static const String passwordChanged = 'Senha alterada com sucesso!';
  static const String withdrawalSuccess = 'Saque realizado com sucesso!';
  static const String cashbackEarned = 'Cashback ganho com sucesso!';

  // ==================== ERROR MESSAGES ====================
  
  static const String loginError = 'Erro ao fazer login';
  static const String registerError = 'Erro ao criar conta';
  static const String networkError = 'Erro de conexão. Verifique sua internet';
  static const String serverError = 'Erro interno do servidor';
  static const String invalidCredentials = 'Email ou senha incorretos';
  static const String userNotFound = 'Usuário não encontrado';
  static const String emailAlreadyExists = 'Este email já está cadastrado';
  static const String cpfAlreadyExists = 'Este CPF já está cadastrado';
  static const String weakPassword = 'Senha muito fraca';
  static const String sessionExpired = 'Sessão expirada. Faça login novamente';
  static const String insufficientBalance = 'Saldo insuficiente';
  static const String minimumWithdrawNotMet = 'Valor mínimo para saque não atingido';
  static const String transactionNotFound = 'Transação não encontrada';
  static const String storeNotFound = 'Loja não encontrada';

  // ==================== LOADING STATES ====================
  
  static const String loading = 'Carregando...';
  static const String loadingTransactions = 'Carregando transações...';
  static const String loadingStores = 'Carregando lojas...';
  static const String loadingProfile = 'Carregando perfil...';
  static const String processing = 'Processando...';
  static const String savingChanges = 'Salvando alterações...';
  static const String authenticating = 'Autenticando...';

  // ==================== EMPTY STATES ====================
  
  static const String noTransactions = 'Nenhuma transação encontrada';
  static const String noStores = 'Nenhuma loja encontrada';
  static const String noCashback = 'Nenhum cashback disponível';
  static const String noNotifications = 'Nenhuma notificação';
  static const String noResults = 'Nenhum resultado encontrado';
  static const String emptyHistory = 'Histórico vazio';

  // ==================== DIALOGS ====================
  
  static const String confirmLogout = 'Tem certeza que deseja sair?';
  static const String confirmDelete = 'Tem certeza que deseja excluir?';
  static const String confirmWithdraw = 'Confirmar saque de';
  static const String withdrawalInfo = 'O valor será transferido via PIX';
  static const String unsavedChanges = 'Você tem alterações não salvas';
  static const String discardChanges = 'Descartar alterações?';

  // ==================== FORMATS ====================
  
  static const String currencyFormat = 'R\$ %value%';
  static const String percentageFormat = '%value%%';
  static const String dateFormat = 'dd/MM/yyyy';
  static const String timeFormat = 'HH:mm';
  static const String dateTimeFormat = 'dd/MM/yyyy HH:mm';

  // ==================== ACCESSIBILITY ====================
  
  static const String closeButton = 'Fechar';
  static const String menuButton = 'Menu';
  static const String backButton = 'Voltar';
  static const String searchButton = 'Buscar';
  static const String passwordVisibility = 'Mostrar/ocultar senha';
  static const String profileImage = 'Imagem do perfil';
  static const String storeImage = 'Imagem da loja';

  // ==================== TERMS AND CONDITIONS ====================
  
  static const String termsOfUse = 'Termos de Uso';
  static const String privacyPolicy = 'Política de Privacidade';
  static const String cookiePolicy = 'Política de Cookies';
  static const String agreeToTerms = 'Ao continuar, você concorda com nossos';
  static const String and = 'e';

  // ==================== CONTACT AND SUPPORT ====================
  
  static const String contactSupport = 'Entrar em contato';
  static const String supportEmail = 'contato@klubecash.com';
  static const String faq = 'Perguntas Frequentes';
  static const String reportProblem = 'Reportar problema';
  static const String feedback = 'Deixar feedback';

  // ==================== NOTIFICATIONS ====================
  
  static const String notificationTitle = 'Notificações';
  static const String markAllRead = 'Marcar todas como lidas';
  static const String newCashback = 'Novo cashback recebido!';
  static const String withdrawalApproved = 'Saque aprovado!';
  static const String promotionAvailable = 'Nova promoção disponível!';

  // ==================== CATEGORIES ====================
  
  static const String categoryFood = 'Alimentação';
  static const String categoryFashion = 'Moda';
  static const String categoryTech = 'Tecnologia';
  static const String categoryHealth = 'Saúde';
  static const String categoryBeauty = 'Beleza';
  static const String categorySports = 'Esportes';
  static const String categoryEducation = 'Educação';
  static const String categoryTravel = 'Viagem';
  static const String categoryHome = 'Casa e Decoração';
  static const String categoryServices = 'Serviços';

  // ==================== WITHDRAWAL ====================
  
  static const String withdrawTitle = 'Sacar Dinheiro';
  static const String withdrawSubtitle = 'Transfira seu cashback via PIX';
  static const String pixKey = 'Chave PIX';
  static const String withdrawAmount = 'Valor do saque';
  static const String withdrawalFees = 'Taxas aplicáveis';
  static const String netAmount = 'Valor líquido';
  static const String pixKeyTypes = 'CPF, Email, Telefone ou Chave Aleatória';
  
  // ==================== SEARCH AND FILTERS ====================
  
  static const String search = 'Buscar';
  static const String searchHint = 'Digite para buscar...';
  static const String filterBy = 'Filtrar por';
  static const String sortBy = 'Ordenar por';
  static const String dateRange = 'Período';
  static const String category = 'Categoria';
  static const String status = 'Status';
  static const String amount = 'Valor';
  static const String newest = 'Mais recente';
  static const String oldest = 'Mais antigo';
  static const String highestAmount = 'Maior valor';
  static const String lowestAmount = 'Menor valor';
}