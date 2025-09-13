// lib/features/auth/presentation/screens/recover_password_screen.dart
// 🔑 Recover Password Screen - Tela de recuperação de senha com instruções claras

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../providers/auth_provider.dart';
import '../widgets/auth_header.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/widgets/custom_app_bar.dart';
import '../../../../core/widgets/custom_text_field.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/utils/validators.dart';

/// Tela de recuperação de senha com processo claro e intuitivo
class RecoverPasswordScreen extends ConsumerStatefulWidget {
  const RecoverPasswordScreen({super.key});

  @override
  ConsumerState<RecoverPasswordScreen> createState() => _RecoverPasswordScreenState();
}

class _RecoverPasswordScreenState extends ConsumerState<RecoverPasswordScreen>
    with TickerProviderStateMixin {
  // Controladores
  final ScrollController _scrollController = ScrollController();
  final TextEditingController _emailController = TextEditingController();
  final FocusNode _emailFocusNode = FocusNode();
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  
  // Controladores de animação
  late AnimationController _containerAnimationController;
  late AnimationController _stepsAnimationController;
  late AnimationController _successAnimationController;
  
  // Animações
  late Animation<double> _containerOpacityAnimation;
  late Animation<Offset> _containerSlideAnimation;
  late Animation<double> _stepsOpacityAnimation;
  late Animation<double> _successScaleAnimation;
  
  // Estados
  bool _isLoading = false;
  bool _isSuccess = false;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _setupAnimations();
    _startAnimations();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _emailController.dispose();
    _emailFocusNode.dispose();
    _containerAnimationController.dispose();
    _stepsAnimationController.dispose();
    _successAnimationController.dispose();
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

    // Animação dos passos
    _stepsAnimationController = AnimationController(
      duration: const Duration(milliseconds: 1000),
      vsync: this,
    );

    _stepsOpacityAnimation = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _stepsAnimationController,
      curve: Curves.easeOut,
    ));

    // Animação de sucesso
    _successAnimationController = AnimationController(
      duration: const Duration(milliseconds: 600),
      vsync: this,
    );

    _successScaleAnimation = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _successAnimationController,
      curve: Curves.elasticOut,
    ));
  }

  void _startAnimations() {
    Future.delayed(const Duration(milliseconds: 100), () {
      if (mounted) {
        _containerAnimationController.forward();
      }
    });

    Future.delayed(const Duration(milliseconds: 600), () {
      if (mounted) {
        _stepsAnimationController.forward();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.primary,
      appBar: _buildAppBar(),
      body: GestureDetector(
        onTap: () => FocusScope.of(context).unfocus(),
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
                            
                            // Container principal
                            _buildMainContainer(isSmallScreen),
                            
                            // Espaço flexível
                            if (!isSmallScreen) 
                              const Spacer(flex: 2),
                            
                            // Seção "Como funciona?"
                            if (!_isSuccess)
                              _buildHowItWorksSection(),
                            
                            // Espaço inferior
                            const Spacer(flex: 1),
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

  /// Constrói o container principal
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
              constraints: const BoxConstraints(maxWidth: 450),
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
                child: _isSuccess 
                    ? _buildSuccessContent()
                    : _buildFormContent(),
              ),
            ),
          ),
        );
      },
    );
  }

  /// Constrói o conteúdo do formulário
  Widget _buildFormContent() {
    return Form(
      key: _formKey,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Header
          const RecoverPasswordHeader(),
          
          const SizedBox(height: AppDimensions.marginLarge),
          
          // Campo de email
          CustomTextField.email(
            controller: _emailController,
            focusNode: _emailFocusNode,
            label: AppStrings.emailLabel,
            hint: AppStrings.emailHint,
            validator: Validators.email,
            enabled: !_isLoading,
            textInputAction: TextInputAction.done,
            onFieldSubmitted: (_) => _handleRecoverPassword(),
          ),
          
          const SizedBox(height: AppDimensions.marginLarge),
          
          // Botão enviar
          CustomButton(
            text: AppStrings.sendInstructions,
            onPressed: _isLoading ? null : _handleRecoverPassword,
            isLoading: _isLoading,
            type: ButtonType.primary,
            width: double.infinity,
          ),
          
          // Mensagem de erro
          if (_errorMessage != null) ...[
            const SizedBox(height: AppDimensions.marginMedium),
            _buildErrorMessage(),
          ],
          
          const SizedBox(height: AppDimensions.marginLarge),
          
          // Link para voltar ao login
          _buildBackToLoginLink(),
        ],
      ),
    );
  }

  /// Constrói o conteúdo de sucesso
  Widget _buildSuccessContent() {
    return AnimatedBuilder(
      animation: _successAnimationController,
      builder: (context, child) {
        return Transform.scale(
          scale: _successScaleAnimation.value,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Ícone de sucesso
              Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  color: AppColors.success.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(40),
                ),
                child: Icon(
                  Icons.mark_email_read_outlined,
                  size: 40,
                  color: AppColors.success,
                ),
              ),
              
              const SizedBox(height: AppDimensions.marginLarge),
              
              // Título
              Text(
                AppStrings.instructionsSent,
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  color: AppColors.gray900,
                  fontWeight: FontWeight.bold,
                ),
                textAlign: TextAlign.center,
              ),
              
              const SizedBox(height: AppDimensions.marginMedium),
              
              // Descrição
              Text(
                AppStrings.checkEmailInstructions,
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                  color: AppColors.gray600,
                  height: 1.5,
                ),
                textAlign: TextAlign.center,
              ),
              
              const SizedBox(height: AppDimensions.marginMedium),
              
              // Email enviado para
              Container(
                padding: const EdgeInsets.all(AppDimensions.paddingMedium),
                decoration: BoxDecoration(
                  color: AppColors.gray50,
                  borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
                ),
                child: Row(
                  children: [
                    Icon(
                      Icons.email_outlined,
                      color: AppColors.primary,
                      size: 20,
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        _emailController.text,
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: AppColors.gray800,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              
              const SizedBox(height: AppDimensions.marginLarge),
              
              // Botões de ação
              Column(
                children: [
                  // Botão para abrir email
                  CustomButton(
                    text: AppStrings.openEmail,
                    onPressed: _handleOpenEmail,
                    type: ButtonType.primary,
                    width: double.infinity,
                  ),
                  
                  const SizedBox(height: AppDimensions.marginMedium),
                  
                  // Botão para reenviar
                  CustomButton(
                    text: AppStrings.resendEmail,
                    onPressed: _handleResendEmail,
                    type: ButtonType.secondary,
                    width: double.infinity,
                  ),
                ],
              ),
              
              const SizedBox(height: AppDimensions.marginLarge),
              
              // Link para voltar ao login
              _buildBackToLoginLink(),
            ],
          ),
        );
      },
    );
  }

  /// Constrói a mensagem de erro
  Widget _buildErrorMessage() {
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      decoration: BoxDecoration(
        color: AppColors.error.withOpacity(0.1),
        borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
        border: Border.all(
          color: AppColors.error.withOpacity(0.3),
          width: 1,
        ),
      ),
      child: Row(
        children: [
          Icon(
            Icons.error_outline,
            color: AppColors.error,
            size: 20,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              _errorMessage!,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: AppColors.error,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// Constrói o link para voltar ao login
  Widget _buildBackToLoginLink() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Text(
          AppStrings.rememberedPassword,
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
            color: AppColors.gray600,
          ),
        ),
        TextButton(
          onPressed: () => context.go('/login'),
          style: TextButton.styleFrom(
            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 4),
            minimumSize: Size.zero,
            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
          ),
          child: Text(
            AppStrings.backToLogin,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: AppColors.primary,
              fontWeight: FontWeight.w700,
              decoration: TextDecoration.underline,
            ),
          ),
        ),
      ],
    );
  }

  /// Constrói a seção "Como funciona?"
  Widget _buildHowItWorksSection() {
    return AnimatedBuilder(
      animation: _stepsAnimationController,
      builder: (context, child) {
        return Opacity(
          opacity: _stepsOpacityAnimation.value,
          child: Container(
            width: double.infinity,
            constraints: const BoxConstraints(maxWidth: 450),
            margin: const EdgeInsets.only(top: AppDimensions.marginLarge),
            decoration: BoxDecoration(
              color: AppColors.white.withOpacity(0.9),
              borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
              border: Border.all(
                color: AppColors.white.withOpacity(0.3),
                width: 1,
              ),
            ),
            padding: const EdgeInsets.all(AppDimensions.paddingLarge),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  AppStrings.howItWorks,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    color: AppColors.gray900,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                
                const SizedBox(height: AppDimensions.marginMedium),
                
                // Passos
                ...List.generate(4, (index) {
                  final steps = [
                    AppStrings.step1RecoverPassword,
                    AppStrings.step2RecoverPassword,
                    AppStrings.step3RecoverPassword,
                    AppStrings.step4RecoverPassword,
                  ];
                  
                  return Padding(
                    padding: EdgeInsets.only(
                      bottom: index < 3 ? AppDimensions.marginMedium : 0,
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          width: 24,
                          height: 24,
                          decoration: BoxDecoration(
                            color: AppColors.primary,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Center(
                            child: Text(
                              '${index + 1}',
                              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                color: AppColors.white,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ),
                        
                        const SizedBox(width: 12),
                        
                        Expanded(
                          child: Text(
                            steps[index],
                            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              color: AppColors.gray700,
                              height: 1.4,
                            ),
                          ),
                        ),
                      ],
                    ),
                  );
                }),
                
                const SizedBox(height: AppDimensions.marginMedium),
                
                // Aviso sobre expiração
                Container(
                  padding: const EdgeInsets.all(AppDimensions.paddingMedium),
                  decoration: BoxDecoration(
                    color: AppColors.info.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
                    border: Border.all(
                      color: AppColors.info.withOpacity(0.3),
                      width: 1,
                    ),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        Icons.info_outline,
                        color: AppColors.info,
                        size: 20,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          AppStrings.linkExpirationWarning,
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                            color: AppColors.info,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  /// Manipula a recuperação de senha
  Future<void> _handleRecoverPassword() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    FocusScope.of(context).unfocus();
    HapticFeedback.lightImpact();

    try {
      final success = await ref.read(authProviderProvider.notifier).recoverPassword(
        email: _emailController.text.trim(),
      );

      if (success && mounted) {
        setState(() {
          _isLoading = false;
          _isSuccess = true;
        });
        
        HapticFeedback.notificationFeedback();
        _successAnimationController.forward();
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isLoading = false;
          _errorMessage = 'Erro ao enviar instruções. Tente novamente.';
        });
        
        HapticFeedback.notificationFeedback();
      }
    }
  }

  /// Manipula abertura do app de email
  void _handleOpenEmail() {
    HapticFeedback.lightImpact();
    // TODO: Implementar abertura do app de email
    _showMessage('Abrindo app de email...');
  }

  /// Manipula reenvio do email
  Future<void> _handleResendEmail() async {
    HapticFeedback.lightImpact();
    
    setState(() {
      _isSuccess = false;
      _isLoading = false;
    });
    
    _successAnimationController.reset();
    _showMessage('Você pode enviar um novo email de recuperação.');
  }

  /// Mostra mensagem temporária
  void _showMessage(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
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

/// Modal de recuperação de senha
class RecoverPasswordModal extends StatelessWidget {
  final VoidCallback? onClose;

  const RecoverPasswordModal({
    super.key,
    this.onClose,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      height: MediaQuery.of(context).size.height * 0.75,
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
            
            // Header
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
                      Icons.close,
                      color: AppColors.white,
                    ),
                  ),
                  Text(
                    AppStrings.recoverPassword,
                    style: TextStyle(
                      color: AppColors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(width: 48), // Espaço para balanceamento
                ],
              ),
            ),
            
            // Conteúdo
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
                      const RecoverPasswordHeader(),
                      // TODO: Adicionar formulário compacto aqui
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