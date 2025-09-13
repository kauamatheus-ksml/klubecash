// lib/features/auth/presentation/widgets/login_form.dart
// 📝 Login Form - Formulário de login reutilizável com validação e integração

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../providers/auth_provider.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/widgets/custom_text_field.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/utils/validators.dart';

/// Widget de formulário de login com validação e integração ao auth provider
class LoginForm extends ConsumerStatefulWidget {
  /// Callback executado após login bem-sucedido
  final VoidCallback? onLoginSuccess;
  
  /// Se deve mostrar animações
  final bool animated;
  
  /// Callback para navegação customizada
  final VoidCallback? onForgotPassword;
  final VoidCallback? onRegister;

  const LoginForm({
    super.key,
    this.onLoginSuccess,
    this.animated = true,
    this.onForgotPassword,
    this.onRegister,
  });

  @override
  ConsumerState<LoginForm> createState() => _LoginFormState();
}

class _LoginFormState extends ConsumerState<LoginForm>
    with TickerProviderStateMixin {
  // Form key para validação
  final _formKey = GlobalKey<FormState>();
  
  // Controllers dos campos
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  
  // Focus nodes
  final _emailFocusNode = FocusNode();
  final _passwordFocusNode = FocusNode();
  
  // Estado do checkbox "Lembrar-me"
  bool _rememberMe = false;
  
  // Controladores de animação
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;
  late Animation<Offset> _slideAnimation;

  @override
  void initState() {
    super.initState();
    _setupAnimations();
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    _emailFocusNode.dispose();
    _passwordFocusNode.dispose();
    _animationController.dispose();
    super.dispose();
  }

  void _setupAnimations() {
    _animationController = AnimationController(
      duration: const Duration(milliseconds: 1000),
      vsync: this,
    );

    _fadeAnimation = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _animationController,
      curve: const Interval(0.0, 0.8, curve: Curves.easeOut),
    ));

    _slideAnimation = Tween<Offset>(
      begin: const Offset(0, 0.3),
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: _animationController,
      curve: const Interval(0.2, 1.0, curve: Curves.easeOutCubic),
    ));

    if (widget.animated) {
      _animationController.forward();
    }
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProviderProvider);

    final formContent = Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Campo de Email
          _buildEmailField(),
          
          const SizedBox(height: AppDimensions.marginMedium),
          
          // Campo de Senha
          _buildPasswordField(),
          
          const SizedBox(height: AppDimensions.marginMedium),
          
          // Opções adicionais (Lembrar-me e Esqueci senha)
          _buildOptionsRow(),
          
          const SizedBox(height: AppDimensions.marginLarge),
          
          // Botão de Login
          _buildLoginButton(authState),
          
          // Exibir erro se houver
          if (authState.errorMessage != null) ...[
            const SizedBox(height: AppDimensions.marginMedium),
            _buildErrorMessage(authState.errorMessage!),
          ],
          
          const SizedBox(height: AppDimensions.marginLarge),
          
          // Link para cadastro
          _buildRegisterLink(),
        ],
      ),
    );

    return widget.animated
        ? AnimatedBuilder(
            animation: _animationController,
            builder: (context, child) {
              return FadeTransition(
                opacity: _fadeAnimation,
                child: SlideTransition(
                  position: _slideAnimation,
                  child: formContent,
                ),
              );
            },
          )
        : formContent;
  }

  /// Constrói o campo de email
  Widget _buildEmailField() {
    return CustomTextField.email(
      controller: _emailController,
      focusNode: _emailFocusNode,
      label: AppStrings.email,
      hint: AppStrings.emailHint,
      validator: Validators.email,
      textInputAction: TextInputAction.next,
      onFieldSubmitted: (_) {
        FocusScope.of(context).requestFocus(_passwordFocusNode);
      },
    );
  }

  /// Constrói o campo de senha
  Widget _buildPasswordField() {
    return CustomTextField.password(
      controller: _passwordController,
      focusNode: _passwordFocusNode,
      label: AppStrings.password,
      hint: AppStrings.passwordHint,
      validator: Validators.password,
      textInputAction: TextInputAction.done,
      onFieldSubmitted: (_) => _handleLogin(),
    );
  }

  /// Constrói a linha com checkbox "Lembrar-me" e link "Esqueci senha"
  Widget _buildOptionsRow() {
    return Row(
      children: [
        // Checkbox Lembrar-me
        Expanded(
          child: Row(
            children: [
              SizedBox(
                width: 20,
                height: 20,
                child: Checkbox(
                  value: _rememberMe,
                  onChanged: (value) {
                    setState(() {
                      _rememberMe = value ?? false;
                    });
                  },
                  activeColor: AppColors.primary,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Text(
                AppStrings.rememberMe,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: AppColors.gray600,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
        
        // Link Esqueci minha senha
        TextButton(
          onPressed: _handleForgotPassword,
          style: TextButton.styleFrom(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            minimumSize: Size.zero,
            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
          ),
          child: Text(
            AppStrings.forgotPassword,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: AppColors.primary,
              fontWeight: FontWeight.w600,
              decoration: TextDecoration.underline,
              decorationColor: AppColors.primary,
            ),
          ),
        ),
      ],
    );
  }

  /// Constrói o botão de login
  Widget _buildLoginButton(AuthState authState) {
    return CustomButton(
      text: AppStrings.login,
      onPressed: authState.isLoading ? null : _handleLogin,
      isLoading: authState.isLoading,
      type: ButtonType.primary,
      width: double.infinity,
    );
  }

  /// Constrói a mensagem de erro
  Widget _buildErrorMessage(String errorMessage) {
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
              errorMessage,
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

  /// Constrói o link para cadastro
  Widget _buildRegisterLink() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Text(
          AppStrings.dontHaveAccount,
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
            color: AppColors.gray600,
            fontWeight: FontWeight.w400,
          ),
        ),
        TextButton(
          onPressed: _handleRegister,
          style: TextButton.styleFrom(
            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 4),
            minimumSize: Size.zero,
            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
          ),
          child: Text(
            AppStrings.registerFree,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: AppColors.primary,
              fontWeight: FontWeight.w700,
            ),
          ),
        ),
      ],
    );
  }

  /// Manipula o processo de login
  Future<void> _handleLogin() async {
    // Limpar erro anterior
    ref.read(authProviderProvider.notifier).clearError();
    
    // Validar formulário
    if (!_formKey.currentState!.validate()) {
      return;
    }

    // Esconder teclado
    FocusScope.of(context).unfocus();

    try {
      // Tentar fazer login
      final success = await ref.read(authProviderProvider.notifier).login(
        email: _emailController.text.trim(),
        password: _passwordController.text,
      );

      if (success) {
        // Login bem-sucedido
        if (widget.onLoginSuccess != null) {
          widget.onLoginSuccess!();
        } else {
          // Navegação padrão para dashboard
          if (mounted) {
            context.go('/dashboard');
          }
        }
      }
    } catch (e) {
      // Erro já é tratado pelo provider
      debugPrint('Erro no login: $e');
    }
  }

  /// Manipula a navegação para esqueci senha
  void _handleForgotPassword() {
    if (widget.onForgotPassword != null) {
      widget.onForgotPassword!();
    } else {
      context.push('/recover-password');
    }
  }

  /// Manipula a navegação para cadastro
  void _handleRegister() {
    if (widget.onRegister != null) {
      widget.onRegister!();
    } else {
      context.push('/register');
    }
  }
}

/// Versão compacta do formulário de login (sem animações e opções extras)
class CompactLoginForm extends ConsumerWidget {
  final VoidCallback? onLoginSuccess;

  const CompactLoginForm({
    super.key,
    this.onLoginSuccess,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return LoginForm(
      onLoginSuccess: onLoginSuccess,
      animated: false,
    );
  }
}