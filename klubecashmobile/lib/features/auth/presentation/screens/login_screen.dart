// lib/features/auth/presentation/screens/login_screen.dart
// 🔐 Login Screen - Tela de login com design moderno e responsivo

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../widgets/auth_header.dart';
import '../widgets/login_form.dart';
import '../widgets/social_login_buttons.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_images.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/constants/app_dimensions.dart';

/// Tela de login com layout responsivo e design moderno
class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen>
    with TickerProviderStateMixin {
  // Controlador de scroll
  final ScrollController _scrollController = ScrollController();
  
  // Controladores de animação
  late AnimationController _containerAnimationController;
  late AnimationController _illustrationAnimationController;
  
  // Animações
  late Animation<double> _containerScaleAnimation;
  late Animation<Offset> _containerSlideAnimation;
  late Animation<double> _illustrationScaleAnimation;
  
  // FocusNode para gerenciar foco
  final FocusNode _formFocusNode = FocusNode();

  @override
  void initState() {
    super.initState();
    _setupAnimations();
    _setupKeyboardListener();
    _startAnimations();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _containerAnimationController.dispose();
    _illustrationAnimationController.dispose();
    _formFocusNode.dispose();
    super.dispose();
  }

  void _setupAnimations() {
    // Animação do container principal
    _containerAnimationController = AnimationController(
      duration: const Duration(milliseconds: 1000),
      vsync: this,
    );

    _containerScaleAnimation = Tween<double>(
      begin: 0.8,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _containerAnimationController,
      curve: const Interval(0.0, 0.7, curve: Curves.elasticOut),
    ));

    _containerSlideAnimation = Tween<Offset>(
      begin: const Offset(0, 0.3),
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: _containerAnimationController,
      curve: const Interval(0.0, 0.8, curve: Curves.easeOutCubic),
    ));

    // Animação da ilustração
    _illustrationAnimationController = AnimationController(
      duration: const Duration(milliseconds: 1200),
      vsync: this,
    );

    _illustrationScaleAnimation = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _illustrationAnimationController,
      curve: const Interval(0.3, 1.0, curve: Curves.elasticOut),
    ));
  }

  void _setupKeyboardListener() {
    // Listener para mudanças no teclado
    WidgetsBinding.instance.addPostFrameCallback((_) {
      MediaQuery.of(context).viewInsets.bottom > 0
          ? _onKeyboardShown()
          : _onKeyboardHidden();
    });
  }

  void _startAnimations() {
    // Começar animações com delay
    Future.delayed(const Duration(milliseconds: 200), () {
      if (mounted) {
        _containerAnimationController.forward();
      }
    });

    Future.delayed(const Duration(milliseconds: 600), () {
      if (mounted) {
        _illustrationAnimationController.forward();
      }
    });
  }

  void _onKeyboardShown() {
    // Scroll para mostrar o formulário quando teclado aparecer
    Future.delayed(const Duration(milliseconds: 300), () {
      if (mounted && _scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent * 0.3,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOutCubic,
        );
      }
    });
  }

  void _onKeyboardHidden() {
    // Opcional: scroll de volta quando teclado esconder
    if (mounted && _scrollController.hasClients) {
      _scrollController.animateTo(
        0,
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeOutCubic,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.primary,
      body: GestureDetector(
        onTap: () {
          // Esconder teclado ao tocar fora
          FocusScope.of(context).unfocus();
        },
        child: Container(
          decoration: const BoxDecoration(
            gradient: AppColors.primaryGradient,
          ),
          child: SafeArea(
            child: LayoutBuilder(
              builder: (context, constraints) {
                final isSmallScreen = constraints.maxHeight < 700;
                
                return SingleChildScrollView(
                  controller: _scrollController,
                  physics: const ClampingScrollPhysics(),
                  child: ConstrainedBox(
                    constraints: BoxConstraints(
                      minHeight: constraints.maxHeight,
                    ),
                    child: IntrinsicHeight(
                      child: Padding(
                        padding: EdgeInsets.symmetric(
                          horizontal: AppDimensions.paddingLarge,
                          vertical: isSmallScreen 
                              ? AppDimensions.paddingMedium 
                              : AppDimensions.paddingLarge,
                        ),
                        child: Column(
                          children: [
                            // Espaço superior flexível
                            if (!isSmallScreen) 
                              const Spacer(flex: 1),
                            
                            // Container principal com formulário
                            _buildMainContainer(isSmallScreen),
                            
                            // Espaço inferior flexível
                            if (!isSmallScreen) 
                              const Spacer(flex: 1),
                            
                            // Ilustração (apenas em telas maiores)
                            if (!isSmallScreen)
                              _buildIllustration(),
                            
                            // Espaçamento final
                            SizedBox(
                              height: isSmallScreen 
                                  ? AppDimensions.marginMedium 
                                  : AppDimensions.marginLarge,
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                );
              },
            ),
          ),
        ),
      ),
    );
  }

  /// Constrói o container principal com o formulário
  Widget _buildMainContainer(bool isSmallScreen) {
    return AnimatedBuilder(
      animation: _containerAnimationController,
      builder: (context, child) {
        return Transform.scale(
          scale: _containerScaleAnimation.value,
          child: SlideTransition(
            position: _containerSlideAnimation,
            child: Container(
              width: double.infinity,
              constraints: const BoxConstraints(maxWidth: 450),
              decoration: BoxDecoration(
                color: AppColors.white,
                borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.1),
                    blurRadius: 20,
                    offset: const Offset(0, 10),
                  ),
                ],
              ),
              child: Padding(
                padding: EdgeInsets.all(
                  isSmallScreen 
                      ? AppDimensions.paddingMedium 
                      : AppDimensions.paddingLarge,
                ),
                child: Focus(
                  focusNode: _formFocusNode,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Header com logo e título
                      const LoginHeader(),
                      
                      const SizedBox(height: AppDimensions.marginMedium),
                      
                      // Botões de login social
                      SocialLoginButtons(
                        onGooglePressed: _handleGoogleLogin,
                        onFacebookPressed: _handleFacebookLogin,
                        onApplePressed: _handleAppleLogin,
                        layout: SocialButtonLayout.horizontal,
                      ),
                      
                      // Divisor "ou"
                      const SocialLoginDivider(),
                      
                      // Formulário de login
                      const LoginForm(),
                    ],
                  ),
                ),
              ),
            ),
          ),
        );
      },
    );
  }

  /// Constrói a ilustração da parte inferior
  Widget _buildIllustration() {
    return AnimatedBuilder(
      animation: _illustrationAnimationController,
      builder: (context, child) {
        return Transform.scale(
          scale: _illustrationScaleAnimation.value,
          child: Container(
            height: 120,
            margin: const EdgeInsets.only(top: AppDimensions.marginMedium),
            child: Stack(
              alignment: Alignment.center,
              children: [
                // Personagem animado
                _buildAnimatedCharacter(),
                
                // Elementos flutuantes
                _buildFloatingElements(),
              ],
            ),
          ),
        );
      },
    );
  }

  /// Constrói o personagem animado
  Widget _buildAnimatedCharacter() {
    return TweenAnimationBuilder<double>(
      duration: const Duration(seconds: 2),
      tween: Tween(begin: 0.0, end: 1.0),
      builder: (context, value, child) {
        return Transform.translate(
          offset: Offset(0, -10 * (0.5 - (value - 0.5).abs())),
          child: Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: AppColors.primary,
              borderRadius: BorderRadius.circular(40),
              boxShadow: [
                BoxShadow(
                  color: AppColors.primary.withOpacity(0.3),
                  blurRadius: 15,
                  offset: const Offset(0, 5),
                ),
              ],
            ),
            child: Center(
              child: Icon(
                Icons.person,
                size: 40,
                color: AppColors.white,
              ),
            ),
          ),
        );
      },
    );
  }

  /// Constrói elementos flutuantes decorativos
  Widget _buildFloatingElements() {
    return Stack(
      children: [
        // Elemento 1 - Esquerda
        Positioned(
          left: 20,
          top: 10,
          child: _buildFloatingIcon(
            Icons.attach_money,
            const Duration(seconds: 3),
          ),
        ),
        
        // Elemento 2 - Direita
        Positioned(
          right: 20,
          top: 20,
          child: _buildFloatingIcon(
            Icons.shopping_bag,
            const Duration(seconds: 4),
          ),
        ),
        
        // Elemento 3 - Centro Superior
        Positioned(
          left: 0,
          right: 0,
          top: -10,
          child: Center(
            child: _buildFloatingIcon(
              Icons.star,
              const Duration(seconds: 2),
            ),
          ),
        ),
      ],
    );
  }

  /// Constrói um ícone flutuante
  Widget _buildFloatingIcon(IconData icon, Duration duration) {
    return TweenAnimationBuilder<double>(
      duration: duration,
      tween: Tween(begin: 0.0, end: 1.0),
      builder: (context, value, child) {
        final offset = 15 * (0.5 - (value - 0.5).abs());
        return Transform.translate(
          offset: Offset(0, offset),
          child: Container(
            width: 30,
            height: 30,
            decoration: BoxDecoration(
              color: AppColors.white.withOpacity(0.9),
              borderRadius: BorderRadius.circular(15),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.1),
                  blurRadius: 8,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Icon(
              icon,
              size: 16,
              color: AppColors.primary,
            ),
          ),
        );
      },
    );
  }

  /// Manipula login com Google
  void _handleGoogleLogin() {
    HapticFeedback.lightImpact();
    // TODO: Implementar login com Google
    _showComingSoonMessage('Google');
  }

  /// Manipula login com Facebook
  void _handleFacebookLogin() {
    HapticFeedback.lightImpact();
    // TODO: Implementar login com Facebook
    _showComingSoonMessage('Facebook');
  }

  /// Manipula login com Apple
  void _handleAppleLogin() {
    HapticFeedback.lightImpact();
    // TODO: Implementar login com Apple
    _showComingSoonMessage('Apple');
  }

  /// Mostra mensagem de "em breve"
  void _showComingSoonMessage(String provider) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Login com $provider em breve!'),
        backgroundColor: AppColors.info,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
        ),
        duration: const Duration(seconds: 2),
      ),
    );
  }
}

/// Versão compacta da tela de login (para modais ou casos específicos)
class CompactLoginScreen extends StatelessWidget {
  final VoidCallback? onLoginSuccess;
  final VoidCallback? onClose;

  const CompactLoginScreen({
    super.key,
    this.onLoginSuccess,
    this.onClose,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        gradient: AppColors.primaryGradient,
        borderRadius: BorderRadius.vertical(
          top: Radius.circular(AppDimensions.radiusLarge),
        ),
      ),
      child: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(AppDimensions.paddingLarge),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Handle para arrastar
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: AppColors.white.withOpacity(0.3),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              
              const SizedBox(height: AppDimensions.marginLarge),
              
              // Container branco com formulário
              Container(
                width: double.infinity,
                decoration: BoxDecoration(
                  color: AppColors.white,
                  borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
                ),
                padding: const EdgeInsets.all(AppDimensions.paddingLarge),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    // Botão fechar
                    if (onClose != null)
                      Align(
                        alignment: Alignment.topRight,
                        child: IconButton(
                          onPressed: onClose,
                          icon: Icon(
                            Icons.close,
                            color: AppColors.gray500,
                          ),
                        ),
                      ),
                    
                    // Header compacto
                    const CompactAuthHeader(
                      title: AppStrings.welcomeBack,
                      subtitle: AppStrings.loginSubtitle,
                    ),
                    
                    // Formulário compacto
                    CompactLoginForm(
                      onLoginSuccess: onLoginSuccess,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}