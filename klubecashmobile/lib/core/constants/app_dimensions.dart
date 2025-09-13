// lib/core/constants/app_dimensions.dart
// Este arquivo contém todas as dimensões utilizadas no app Klube Cash
// Espaçamentos, bordas, tamanhos e breakpoints responsivos

class AppDimensions {
  // ==================== ESPAÇAMENTOS ====================
  
  /// Espaçamentos extras pequenos
  static const double spacingXxs = 2.0;
  static const double spacingXs = 4.0;
  
  /// Espaçamentos pequenos
  static const double spacingSm = 8.0;
  
  /// Espaçamento padrão
  static const double spacingMd = 16.0;
  
  /// Espaçamentos grandes
  static const double spacingLg = 24.0;
  static const double spacingXl = 32.0;
  static const double spacingXxl = 48.0;
  static const double spacingXxxl = 64.0;

  // ==================== PADDING ====================
  
  /// Paddings para containers
  static const double paddingXs = 4.0;
  static const double paddingSm = 8.0;
  static const double paddingMd = 16.0;
  static const double paddingLg = 24.0;
  static const double paddingXl = 32.0;
  
  /// Paddings específicos
  static const double paddingCard = 20.0;
  static const double paddingScreen = 16.0;
  static const double paddingButton = 16.0;
  static const double paddingInput = 12.0;

  // ==================== MARGIN ====================
  
  /// Margins padrão
  static const double marginXs = 4.0;
  static const double marginSm = 8.0;
  static const double marginMd = 16.0;
  static const double marginLg = 24.0;
  static const double marginXl = 32.0;
  
  /// Margins específicos
  static const double marginSection = 32.0;
  static const double marginCard = 16.0;

  // ==================== BORDER RADIUS ====================
  
  /// Raios de borda pequenos
  static const double radiusXs = 4.0;
  static const double radiusSm = 6.0;
  
  /// Raio padrão
  static const double radiusMd = 8.0;
  
  /// Raios grandes
  static const double radiusLg = 12.0;
  static const double radiusXl = 16.0;
  static const double radiusXxl = 20.0;
  static const double radiusXxxl = 24.0;
  
  /// Raio circular completo
  static const double radiusFull = 999.0;
  
  /// Raios específicos
  static const double radiusCard = 12.0;
  static const double radiusButton = 8.0;
  static const double radiusInput = 8.0;
  static const double radiusDialog = 16.0;

  // ==================== LARGURAS ====================
  
  /// Larguras de container
  static const double containerMaxWidth = 1200.0;
  static const double containerPadding = 16.0;
  
  /// Larguras de componentes
  static const double buttonMinWidth = 120.0;
  static const double inputMinWidth = 200.0;
  static const double cardMinWidth = 300.0;
  
  /// Larguras específicas
  static const double sidebarWidth = 250.0;
  static const double bottomNavHeight = 80.0;
  static const double appBarHeight = 56.0;

  // ==================== ALTURAS ====================
  
  /// Alturas de componentes
  static const double buttonHeight = 48.0;
  static const double buttonHeightSm = 36.0;
  static const double buttonHeightLg = 56.0;
  
  static const double inputHeight = 48.0;
  static const double inputHeightSm = 36.0;
  static const double inputHeightLg = 56.0;
  
  /// Alturas específicas
  static const double logoHeight = 40.0;
  static const double avatarSize = 40.0;
  static const double avatarSizeSm = 32.0;
  static const double avatarSizeLg = 56.0;
  static const double avatarSizeXl = 80.0;
  
  static const double iconSize = 24.0;
  static const double iconSizeSm = 16.0;
  static const double iconSizeLg = 32.0;
  static const double iconSizeXl = 48.0;

  // ==================== DIMENSÕES DE CARD ====================
  
  /// Cards padrão
  static const double cardPadding = 20.0;
  static const double cardMargin = 16.0;
  static const double cardRadius = 12.0;
  static const double cardElevation = 2.0;
  
  /// Cards especiais
  static const double cardBalancePadding = 24.0;
  static const double cardBalanceRadius = 16.0;
  static const double cardStorePadding = 16.0;
  static const double cardStoreRadius = 12.0;

  // ==================== DIMENSÕES DE LISTA ====================
  
  /// Itens de lista
  static const double listItemHeight = 64.0;
  static const double listItemHeightSm = 48.0;
  static const double listItemHeightLg = 80.0;
  static const double listItemPadding = 16.0;
  
  /// Divisores
  static const double dividerHeight = 1.0;
  static const double dividerThickness = 0.5;

  // ==================== DIMENSÕES DE GRID ====================
  
  /// Grids responsivos
  static const double gridSpacing = 16.0;
  static const double gridSpacingSm = 8.0;
  static const double gridSpacingLg = 24.0;
  
  /// Columns
  static const double gridColumnMinWidth = 250.0;
  static const double gridCardMinWidth = 300.0;

  // ==================== BREAKPOINTS RESPONSIVOS ====================
  
  /// Breakpoints para design responsivo
  static const double breakpointXs = 480.0;
  static const double breakpointSm = 640.0;
  static const double breakpointMd = 768.0;
  static const double breakpointLg = 1024.0;
  static const double breakpointXl = 1280.0;
  static const double breakpointXxl = 1536.0;

  // ==================== TAMANHOS DE FONTE ====================
  
  /// Tamanhos de texto (para referência)
  static const double fontSizeXs = 12.0;
  static const double fontSizeSm = 14.0;
  static const double fontSizeMd = 16.0;
  static const double fontSizeLg = 18.0;
  static const double fontSizeXl = 20.0;
  static const double fontSizeXxl = 24.0;
  static const double fontSizeXxxl = 32.0;

  // ==================== ELEVAÇÕES E SOMBRAS ====================
  
  /// Elevações Material Design
  static const double elevationXs = 1.0;
  static const double elevationSm = 2.0;
  static const double elevationMd = 4.0;
  static const double elevationLg = 8.0;
  static const double elevationXl = 12.0;
  static const double elevationXxl = 16.0;

  // ==================== DIMENSÕES DE FORMULÁRIO ====================
  
  /// Campos de formulário
  static const double formFieldSpacing = 16.0;
  static const double formFieldPadding = 12.0;
  static const double formFieldRadius = 8.0;
  static const double formLabelSpacing = 8.0;
  
  /// Botões de formulário
  static const double formButtonSpacing = 24.0;
  static const double formButtonPadding = 16.0;

  // ==================== DIMENSÕES DE NAVEGAÇÃO ====================
  
  /// Bottom Navigation
  static const double bottomNavItemSize = 24.0;
  static const double bottomNavLabelSpacing = 4.0;
  static const double bottomNavPadding = 8.0;
  
  /// Drawer/Sidebar
  static const double drawerWidth = 280.0;
  static const double drawerItemHeight = 48.0;
  static const double drawerItemPadding = 16.0;

  // ==================== DIMENSÕES DE TRANSAÇÃO ====================
  
  /// Cards de transação
  static const double transactionCardHeight = 80.0;
  static const double transactionCardPadding = 16.0;
  static const double transactionCardRadius = 12.0;
  static const double transactionIconSize = 32.0;

  // ==================== DIMENSÕES DE LOJA ====================
  
  /// Cards de loja
  static const double storeCardMinWidth = 280.0;
  static const double storeCardPadding = 16.0;
  static const double storeCardRadius = 12.0;
  static const double storeLogoSize = 60.0;
  static const double storeLogoRadius = 8.0;

  // ==================== DIMENSÕES DE CASHBACK ====================
  
  /// Componentes de cashback
  static const double cashbackCardPadding = 24.0;
  static const double cashbackCardRadius = 16.0;
  static const double cashbackIconSize = 48.0;
  static const double cashbackBadgeSize = 24.0;

  // ==================== ANIMAÇÕES E TRANSIÇÕES ====================
  
  /// Durações (em milissegundos)
  static const int animationDurationFast = 150;
  static const int animationDurationNormal = 300;
  static const int animationDurationSlow = 500;
  
  /// Distâncias para gestos
  static const double swipeThreshold = 100.0;
  static const double dragThreshold = 16.0;

  // ==================== MÉTODOS AUXILIARES ====================
  
  /// Retorna padding responsivo baseado na largura da tela
  static double getResponsivePadding(double screenWidth) {
    if (screenWidth < breakpointSm) {
      return paddingSm;
    } else if (screenWidth < breakpointMd) {
      return paddingMd;
    } else {
      return paddingLg;
    }
  }
  
  /// Retorna espaçamento de grid responsivo
  static double getResponsiveGridSpacing(double screenWidth) {
    if (screenWidth < breakpointSm) {
      return gridSpacingSm;
    } else if (screenWidth < breakpointLg) {
      return gridSpacing;
    } else {
      return gridSpacingLg;
    }
  }
  
  /// Retorna número de colunas para grid responsivo
  static int getGridColumns(double screenWidth) {
    if (screenWidth < breakpointSm) {
      return 1;
    } else if (screenWidth < breakpointMd) {
      return 2;
    } else if (screenWidth < breakpointLg) {
      return 3;
    } else {
      return 4;
    }
  }
  
  /// Verifica se é tela pequena (mobile)
  static bool isSmallScreen(double screenWidth) {
    return screenWidth < breakpointMd;
  }
  
  /// Verifica se é tablet
  static bool isTablet(double screenWidth) {
    return screenWidth >= breakpointMd && screenWidth < breakpointLg;
  }
  
  /// Verifica se é desktop
  static bool isDesktop(double screenWidth) {
    return screenWidth >= breakpointLg;
  }
}