// lib/features/auth/presentation/screens/splash_screen.dart
// 🚀 Splash Screen - Tela inicial com logo animado e verificação de autenticação

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../providers/auth_provider.dart';
import '../../domain/entities/user.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_images.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/constants/app_dimensions.dart';

/// Tela de splash com logo animado e verificação de autenticação
class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen>
    with TickerProviderStateMixin {
  // Controladores de animação
  late AnimationController _logoAnimationController;
  late AnimationController _textAnimationController;
  late AnimationController _loadingAnimationController;
  
  // Animações
  late Animation<double> _logoScaleAnimation;
  late Animation<double> _logoOpacityAnimation;
  late Animation<double> _textOpacityAnimation;
  late Animation<Offset> _textSlideAnimation;
  late Animation<double> _loadingOpacityAnimation;
  
  // Estado da verificação
  bool _authCheckCompleted = false;
  bool _minimumTimeElapsed = false;
  
  // Tempo mínimo de exibição da splash (para evitar flicker)
  static const Duration _minimumDisplayTime = Duration(milliseconds: 2000);
  static const Duration _logoAnimationDuration = Duration(milliseconds: 1200);
  static const Duration _textAnimationDuration = Duration(milliseconds: 800);

  @override
  void initState() {
    super.initState();
    _setupAnimations();
    _startSplashSequence();
  }

  @override
  void dispose() {
    _logoAnimationController.dispose();
    _textAnimationController.dispose();
    _loadingAnimationController.dispose();
    super.dispose();
  }

  void _setupAnimations() {
    // Animação do logo
    _logoAnimationController = AnimationController(
      duration: _logoAnimationDuration,
      vsync: this,
    );

    _logoScaleAnimation = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _logoAnimationController,
      curve: const Interval(0.0, 0.7, curve: Curves.elasticOut),
    ));

    _logoOpacityAnimation = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _logoAnimationController,
      curve: const Interval(0.0, 0.5, curve: Curves.easeOut),
    ));

    // Animação do texto
    _textAnimationController = AnimationController(
      duration: _textAnimationDuration,
      vsync: this,
    );

    _textOpacityAnimation = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _textAnimationController,
      curve: Curves.easeOut,
    ));

    _textSlideAnimation = Tween<Offset>(
      begin: const Offset(0, 0.3),
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: _textAnimationController,
      curve: Curves.easeOutCubic,
    ));

    // Animação do loading
    _loadingAnimationController = AnimationController(
      duration: const Duration(milliseconds: 1500),
      vsync: this,
    );

    _loadingOpacityAnimation = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _loadingAnimationController,
      curve: Curves.easeInOut,
    ));
  }

  void _startSplashSequence() async {
    // Configurar status bar para modo imersivo
    SystemChrome.setSystemUIOverlayStyle(
      const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
        systemNavigationBarColor: AppColors.primary,
        systemNavigationBarIconBrightness: Brightness.light,
      ),
    );

    // Iniciar animações
    _logoAnimationController.forward();
    
    // Aguardar um pouco antes do texto
    await Future.delayed(const Duration(milliseconds: 600));
    _textAnimationController.forward();
    
    // Aguardar mais um pouco antes do loading
    await Future.delayed(const Duration(milliseconds: 400));
    _loadingAnimationController.forward();
    
    // Iniciar verificação de autenticação em paralelo
    _checkAuthenticationStatus();
    
    // Garantir tempo mínimo de exibição
    Future.delayed(_minimumDisplayTime, () {
      setState(() {
        _minimumTimeElapsed = true;
      });
      _navigateToNextScreen();
    });
  }

  void _checkAuthenticationStatus() async {
    try {
      // O auth provider já verifica o estado automaticamente quando inicia
      // Aguardamos um pouco para dar tempo da verificação
      await Future.delayed(const Duration(milliseconds: 1000));
      
      setState(() {
        _authCheckCompleted = true;
      });
      
      _navigateToNextScreen();
    } catch (e) {
      // Em caso de erro, considera como não autenticado
      setState(() {
        _authCheckCompleted = true;
      });
      
      _navigateToNextScreen();
    }
  }

  void _navigateToNextScreen() {
    // Só navega se ambas condições foram atendidas
    if (!_authCheckCompleted || !_minimumTimeElapsed || !mounted) {
      return;
    }

    final authState = ref.read(authProviderProvider);
    
    // Aguardar um pouco para a transição ficar mais suave
    Future.delayed(const Duration(milliseconds: 300), () {
      if (!mounted) return;
      
      if (authState.isAuthenticated && authState.user != null) {
        // Usuário autenticado - redirecionar para dashboard apropriado
        _navigateToDashboard(authState.user!.type);
      } else {
        // Usuário não autenticado - ir para login
        context.go('/login');
      }
    });
  }

  void _navigateToDashboard(UserType userType) {
    switch (userType) {
      case UserType.admin:
        context.go('/admin/dashboard');
        break;
      case UserType.store:
        context.go('/store/dashboard');
        break;
      case UserType.client:
      default:
        context.go('/dashboard');
        break;
    }
  }

  @override
  Widget build(BuildContext context) {
    // Escutar mudanças no auth provider
    ref.listen<AuthState>(authProviderProvider, (previous, next) {
      // Se mudou de loading para não loading, marcar como completo
      if (previous?.isLoading == true && !next.isLoading) {
        setState(() {
          _authCheckCompleted = true;
        });
        _navigateToNextScreen();
      }
    });

    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(
          gradient: AppColors.primaryGradient,
        ),
        child: SafeArea(
          child: Column(
            children: [
              // Espaço superior
              const Spacer(flex: 2),
              
              // Logo principal
              _buildAnimatedLogo(),
              
              const SizedBox(height: AppDimensions.marginLarge),
              
              // Nome da marca
              _buildAnimatedBrandName(),
              
              // Espaço central
              const Spacer(flex: 2),
              
              // Indicador de loading
              _buildLoadingIndicator(),
              
              // Espaço inferior
              const Spacer(flex: 1),
              
              // Versão do app (opcional)
              _buildVersionInfo(),
              
              const SizedBox(height: AppDimensions.marginMedium),
            ],
          ),
        ),
      ),
    );
  }

  /// Constrói o logo animado
  Widget _buildAnimatedLogo() {
    return AnimatedBuilder(
      animation: _logoAnimationController,
      builder: (context, child) {
        return Transform.scale(
          scale: _logoScaleAnimation.value,
          child: Opacity(
            opacity: _logoOpacityAnimation.value,
            child: Container(
              width: 120,
              height: 120,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: AppColors.white,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.1),
                    blurRadius: 20,
                    offset: const Offset(0, 10),
                  ),
                ],
              ),
              child: Center(
                child: Text(
                  'C',
                  style: TextStyle(
                    fontSize: 48,
                    fontWeight: FontWeight.bold,
                    color: AppColors.primary,
                    fontFamily: 'Poppins',
                  ),
                ),
              ),
            ),
          ),
        );
      },
    );
  }

  /// Constrói o nome da marca animado
  Widget _buildAnimatedBrandName() {
    return AnimatedBuilder(
      animation: _textAnimationController,
      builder: (context, child) {
        return SlideTransition(
          position: _textSlideAnimation,
          child: Opacity(
            opacity: _textOpacityAnimation.value,
            child: Text(
              AppStrings.appName,
              style: TextStyle(
                fontSize: 32,
                fontWeight: FontWeight.bold,
                color: AppColors.white,
                fontFamily: 'Poppins',
                letterSpacing: 1.2,
                shadows: [
                  Shadow(
                    color: Colors.black.withOpacity(0.1),
                    offset: const Offset(0, 2),
                    blurRadius: 4,
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  /// Constrói o indicador de loading
  Widget _buildLoadingIndicator() {
    return AnimatedBuilder(
      animation: _loadingAnimationController,
      builder: (context, child) {
        return Opacity(
          opacity: _loadingOpacityAnimation.value,
          child: Column(
            children: [
              // Indicador circular
              SizedBox(
                width: 24,
                height: 24,
                child: CircularProgressIndicator(
                  strokeWidth: 2.5,
                  valueColor: AlwaysStoppedAnimation<Color>(
                    AppColors.white.withOpacity(0.8),
                  ),
                ),
              ),
              
              const SizedBox(height: AppDimensions.marginMedium),
              
              // Texto de loading
              Text(
                AppStrings.loading,
                style: TextStyle(
                  fontSize: 14,
                  color: AppColors.white.withOpacity(0.8),
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  /// Constrói as informações de versão
  Widget _buildVersionInfo() {
    return Text(
      AppStrings.appVersion,
      style: TextStyle(
        fontSize: 12,
        color: AppColors.white.withOpacity(0.6),
        fontWeight: FontWeight.w400,
      ),
    );
  }
}

/// Widget de erro para splash screen (caso necessário)
class SplashErrorScreen extends StatelessWidget {
  final String? errorMessage;
  final VoidCallback? onRetry;

  const SplashErrorScreen({
    super.key,
    this.errorMessage,
    this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(
          gradient: AppColors.primaryGradient,
        ),
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(AppDimensions.paddingLarge),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  Icons.error_outline,
                  size: 64,
                  color: AppColors.white,
                ),
                
                const SizedBox(height: AppDimensions.marginLarge),
                
                Text(
                  errorMessage ?? AppStrings.generalError,
                  style: TextStyle(
                    fontSize: 16,
                    color: AppColors.white,
                    fontWeight: FontWeight.w500,
                  ),
                  textAlign: TextAlign.center,
                ),
                
                const SizedBox(height: AppDimensions.marginLarge),
                
                if (onRetry != null)
                  ElevatedButton(
                    onPressed: onRetry,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.white,
                      foregroundColor: AppColors.primary,
                    ),
                    child: Text(AppStrings.tryAgain),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}