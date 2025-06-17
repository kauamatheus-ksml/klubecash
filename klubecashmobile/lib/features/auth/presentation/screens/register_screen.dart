// lib/features/auth/presentation/screens/register_screen.dart
// 📝 Register Screen - Tela de registro com formulário em seções e design responsivo

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../widgets/auth_header.dart';
import '../widgets/register_form.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/widgets/custom_app_bar.dart';

/// Tela de registro com formulário organizado e design moderno
class RegisterScreen extends ConsumerStatefulWidget {
  const RegisterScreen({super.key});

  @override
  ConsumerState<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends ConsumerState<RegisterScreen>
    with TickerProviderStateMixin {
  // Controlador de scroll
  final ScrollController _scrollController = ScrollController();
  
  // Controladores de animação
  late AnimationController _containerAnimationController;
  late AnimationController _progressAnimationController;
  
  // Animações
  late Animation<double> _containerOpacityAnimation;
  late Animation<Offset> _containerSlideAnimation;
  late Animation<double> _progressAnimation;
  
  // Estado do progresso
  double _currentProgress = 0.0;
  
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
    _progressAnimationController.dispose();
    _formFocusNode.dispose();
    super.dispose();
  }

  void _setupAnimations() {
    // Animação do container principal
    _containerAnimationController = AnimationController(
      duration: const Duration(milliseconds: 800),
      vsync: this,
    );

    _containerOpacityAnimation = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _containerAnimationController,
      curve: const Interval(0.0, 0.6, curve: Curves.easeOut),
    ));

    _containerSlideAnimation = Tween<Offset>(
      begin: const Offset(0, 0.2),
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: _containerAnimationController,
      curve: const Interval(0.2, 1.0, curve: Curves.easeOutCubic),
    ));

    // Animação do progresso
    _progressAnimationController = AnimationController(
      duration: const Duration(milliseconds: 600),
      vsync: this,
    );

    _progressAnimation = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _progressAnimationController,
      curve: Curves.easeInOut,
    ));
  }

  void _setupKeyboardListener() {
    // Listener para mudanças no teclado
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final viewInsets = MediaQuery.of(context).viewInsets;
      if (viewInsets.bottom > 0) {
        _onKeyboardShown();
      }
    });
  }

  void _startAnimations() {
    // Começar animações com delay
    Future.delayed(const Duration(milliseconds: 100), () {
      if (mounted) {
        _containerAnimationController.forward();
      }
    });

    Future.delayed(const Duration(milliseconds: 400), () {
      if (mounted) {
        _progressAnimationController.forward();
      }
    });
  }

  void _onKeyboardShown() {
    // Scroll para mostrar o campo atual quando teclado aparecer
    Future.delayed(const Duration(milliseconds: 300), () {
      if (mounted && _scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent * 0.5,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOutCubic,
        );
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.primary,
      appBar: _buildAppBar(),
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
                
                return Column(
                  children: [
                    // Indicador de progresso
                    _buildProgressIndicator(),
                    
                    // Conteúdo principal
                    Expanded(
                      child: SingleChildScrollView(
                        controller: _scrollController,
                        physics: const ClampingScrollPhysics(),
                        child: ConstrainedBox(
                          constraints: BoxConstraints(
                            minHeight: constraints.maxHeight - 120,
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
                                  // Espaço superior flexível (menor em telas pequenas)
                                  if (!isSmallScreen) 
                                    const Spacer(flex: 1),
                                  
                                  // Container principal com formulário
                                  _buildMainContainer(isSmallScreen),
                                  
                                  // Espaço inferior flexível
                                  if (!isSmallScreen) 
                                    const Spacer(flex: 1),
                                  
                                  // Ilustração motivacional (apenas em telas maiores)
                                  if (!isSmallScreen)
                                    _buildMotivationalIllustration(),
                                  
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
                      ),
                    ),
                  ],
                );
              },
            ),
          ),
        ),
      ),
    );
  }

  /// Constrói a AppBar customizada
  PreferredSizeWidget _buildAppBar() {
    return CustomAppBar(
      backgroundColor: Colors.transparent,
      foregroundColor: AppColors.white,
      showBackButton: true,
      showShadow: false,
      onBackPressed: () {
        HapticFeedback.lightImpact();
        context.pop();
      },
    );
  }

  /// Constrói o indicador de progresso
  Widget _buildProgressIndicator() {
    return AnimatedBuilder(
      animation: _progressAnimationController,
      builder: (context, child) {
        return Container(
          height: 4,
          margin: const EdgeInsets.symmetric(
            horizontal: AppDimensions.marginLarge,
            vertical: AppDimensions.marginSmall,
          ),
          decoration: BoxDecoration(
            color: AppColors.white.withOpacity(0.2),
            borderRadius: BorderRadius.circular(2),
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(2),
            child: LinearProgressIndicator(
              value: _progressAnimation.value * 0.3, // Começa em 30%
              backgroundColor: Colors.transparent,
              valueColor: AlwaysStoppedAnimation<Color>(
                AppColors.white.withOpacity(0.8),
              ),
            ),
          ),
        );
      },
    );
  }

  /// Constrói o container principal com o formulário
  Widget _buildMainContainer(bool isSmallScreen) {
    return AnimatedBuilder(
      animation: _containerAnimationController,
      builder: (context, child) {
        return Opacity(
          opacity: _containerOpacityAnimation.value,
          child: SlideTransition(
            position: _containerSlideAnimation,
            child: Container(
              width: double.infinity,
              constraints: const BoxConstraints(maxWidth: 500),
              decoration: BoxDecoration(
                color: AppColors.white,
                borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.1),
                    blurRadius: 25,
                    offset: const Offset(0, 15),
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
                      const RegisterHeader(),
                      
                      const SizedBox(height: AppDimensions.marginMedium),
                      
                      // Formulário de registro
                      RegisterForm(
                        onRegisterSuccess: _handleRegisterSuccess,
                        onLogin: _handleNavigateToLogin,
                      ),
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

  /// Constrói a ilustração motivacional
  Widget _buildMotivationalIllustration() {
    return TweenAnimationBuilder<double>(
      duration: const Duration(seconds: 3),
      tween: Tween(begin: 0.0, end: 1.0),
      builder: (context, value, child) {
        return Container(
          height: 100,
          margin: const EdgeInsets.only(top: AppDimensions.marginMedium),
          child: Stack(
            alignment: Alignment.center,
            children: [
              // Personagem comemorando
              Transform.translate(
                offset: Offset(0, -8 * (0.5 - (value - 0.5).abs())),
                child: Container(
                  width: 60,
                  height: 60,
                  decoration: BoxDecoration(
                    color: AppColors.white,
                    borderRadius: BorderRadius.circular(30),
                    boxShadow: [
                      BoxShadow(
                        color: AppColors.white.withOpacity(0.3),
                        blurRadius: 15,
                        offset: const Offset(0, 5),
                      ),
                    ],
                  ),
                  child: Center(
                    child: Text(
                      '🎉',
                      style: TextStyle(
                        fontSize: 24,
                      ),
                    ),
                  ),
                ),
              ),
              
              // Moedas flutuando
              _buildFloatingCoins(value),
            ],
          ),
        );
      },
    );
  }

  /// Constrói moedas flutuantes
  Widget _buildFloatingCoins(double animationValue) {
    return Stack(
      children: [
        // Moeda 1
        Positioned(
          left: 30,
          top: 20,
          child: Transform.translate(
            offset: Offset(
              10 * animationValue,
              -15 * (0.5 - (animationValue - 0.5).abs()),
            ),
            child: _buildCoin(),
          ),
        ),
        
        // Moeda 2
        Positioned(
          right: 30,
          top: 10,
          child: Transform.translate(
            offset: Offset(
              -10 * animationValue,
              -20 * (0.5 - (animationValue - 0.5).abs()),
            ),
            child: _buildCoin(),
          ),
        ),
        
        // Moeda 3
        Positioned(
          left: 0,
          right: 0,
          top: 5,
          child: Center(
            child: Transform.translate(
              offset: Offset(
                0,
                -25 * (0.5 - (animationValue - 0.5).abs()),
              ),
              child: _buildCoin(),
            ),
          ),
        ),
      ],
    );
  }

  /// Constrói uma moeda decorativa
  Widget _buildCoin() {
    return Container(
      width: 20,
      height: 20,
      decoration: BoxDecoration(
        color: const Color(0xFFFFD700), // Dourado
        borderRadius: BorderRadius.circular(10),
        border: Border.all(
          color: const Color(0xFFFFE55C),
          width: 1,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Center(
        child: Text(
          'R\$',
          style: TextStyle(
            fontSize: 8,
            fontWeight: FontWeight.bold,
            color: AppColors.primary,
          ),
        ),
      ),
    );
  }

  /// Manipula sucesso no registro
  void _handleRegisterSuccess() {
    HapticFeedback.notificationFeedback();
    
    // Animar progresso para 100%
    _progressAnimationController.animateTo(1.0);
    
    // Mostrar feedback de sucesso
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            Icon(
              Icons.check_circle,
              color: AppColors.white,
            ),
            const SizedBox(width: 8),
            const Text('Conta criada com sucesso!'),
          ],
        ),
        backgroundColor: AppColors.success,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
        ),
        duration: const Duration(seconds: 2),
      ),
    );
    
    // Navegar para dashboard após sucesso
    Future.delayed(const Duration(milliseconds: 1500), () {
      if (mounted) {
        context.go('/dashboard');
      }
    });
  }

  /// Manipula navegação para login
  void _handleNavigateToLogin() {
    HapticFeedback.lightImpact();
    context.go('/login');
  }
}

/// Versão modal da tela de registro
class RegisterModal extends StatelessWidget {
  final VoidCallback? onRegisterSuccess;
  final VoidCallback? onClose;

  const RegisterModal({
    super.key,
    this.onRegisterSuccess,
    this.onClose,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      height: MediaQuery.of(context).size.height * 0.9,
      decoration: const BoxDecoration(
        gradient: AppColors.primaryGradient,
        borderRadius: BorderRadius.vertical(
          top: Radius.circular(AppDimensions.radiusLarge),
        ),
      ),
      child: SafeArea(
        child: Column(
          children: [
            // Handle para arrastar
            Container(
              width: 40,
              height: 4,
              margin: const EdgeInsets.symmetric(vertical: 12),
              decoration: BoxDecoration(
                color: AppColors.white.withOpacity(0.3),
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            
            // AppBar customizada
            Padding(
              padding: const EdgeInsets.symmetric(
                horizontal: AppDimensions.paddingMedium,
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  IconButton(
                    onPressed: onClose,
                    icon: Icon(
                      Icons.arrow_back,
                      color: AppColors.white,
                    ),
                  ),
                  Text(
                    AppStrings.createAccount,
                    style: TextStyle(
                      color: AppColors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  IconButton(
                    onPressed: onClose,
                    icon: Icon(
                      Icons.close,
                      color: AppColors.white,
                    ),
                  ),
                ],
              ),
            ),
            
            // Conteúdo scrollável
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(AppDimensions.paddingLarge),
                child: Container(
                  width: double.infinity,
                  decoration: BoxDecoration(
                    color: AppColors.white,
                    borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
                  ),
                  padding: const EdgeInsets.all(AppDimensions.paddingLarge),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Header compacto
                      const CompactAuthHeader(
                        title: AppStrings.createAccount,
                        subtitle: AppStrings.registerSubtitle,
                      ),
                      
                      // Formulário compacto
                      CompactRegisterForm(
                        onRegisterSuccess: onRegisterSuccess,
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Widget para mostrar progresso do registro
class RegistrationProgress extends StatelessWidget {
  final int currentStep;
  final int totalSteps;

  const RegistrationProgress({
    super.key,
    required this.currentStep,
    this.totalSteps = 3,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: AppDimensions.paddingMedium,
        vertical: AppDimensions.paddingSmall,
      ),
      child: Row(
        children: List.generate(totalSteps, (index) {
          final isCompleted = index < currentStep;
          final isCurrent = index == currentStep;
          
          return Expanded(
            child: Container(
              height: 4,
              margin: EdgeInsets.only(
                right: index < totalSteps - 1 ? 8 : 0,
              ),
              decoration: BoxDecoration(
                color: isCompleted || isCurrent
                    ? AppColors.primary
                    : AppColors.gray300,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          );
        }),
      ),
    );
  }
}