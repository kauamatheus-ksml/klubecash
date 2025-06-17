// lib/features/auth/presentation/widgets/register_form.dart
// 📝 Register Form - Formulário de registro com seções organizadas e validação completa

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../providers/auth_provider.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/widgets/custom_text_field.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/utils/formatters.dart';

/// Enum para força da senha
enum PasswordStrength { none, weak, fair, good, strong }

/// Widget de formulário de registro organizado em seções
class RegisterForm extends ConsumerStatefulWidget {
  /// Callback executado após registro bem-sucedido
  final VoidCallback? onRegisterSuccess;
  
  /// Se deve mostrar animações
  final bool animated;
  
  /// Callback para navegação customizada para login
  final VoidCallback? onLogin;

  const RegisterForm({
    super.key,
    this.onRegisterSuccess,
    this.animated = true,
    this.onLogin,
  });

  @override
  ConsumerState<RegisterForm> createState() => _RegisterFormState();
}

class _RegisterFormState extends ConsumerState<RegisterForm>
    with TickerProviderStateMixin {
  // Form key para validação
  final _formKey = GlobalKey<FormState>();
  
  // Controllers dos campos
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  
  // Focus nodes
  final _nameFocusNode = FocusNode();
  final _emailFocusNode = FocusNode();
  final _phoneFocusNode = FocusNode();
  final _passwordFocusNode = FocusNode();
  final _confirmPasswordFocusNode = FocusNode();
  
  // Estado do formulário
  bool _acceptTerms = false;
  PasswordStrength _passwordStrength = PasswordStrength.none;
  
  // Controladores de animação
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;
  late Animation<Offset> _slideAnimation;

  @override
  void initState() {
    super.initState();
    _setupAnimations();
    _setupPasswordListener();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    _nameFocusNode.dispose();
    _emailFocusNode.dispose();
    _phoneFocusNode.dispose();
    _passwordFocusNode.dispose();
    _confirmPasswordFocusNode.dispose();
    _animationController.dispose();
    super.dispose();
  }

  void _setupAnimations() {
    _animationController = AnimationController(
      duration: const Duration(milliseconds: 1200),
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

  void _setupPasswordListener() {
    _passwordController.addListener(() {
      final strength = _calculatePasswordStrength(_passwordController.text);
      if (strength != _passwordStrength) {
        setState(() {
          _passwordStrength = strength;
        });
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProviderProvider);

    final formContent = Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Link para login no topo
          _buildLoginPrompt(),
          
          const SizedBox(height: AppDimensions.marginLarge),
          
          // Seção 1: Suas Informações
          _buildPersonalInfoSection(),
          
          const SizedBox(height: AppDimensions.marginLarge),
          
          // Seção 2: Crie sua Senha
          _buildPasswordSection(),
          
          const SizedBox(height: AppDimensions.marginLarge),
          
          // Checkbox de termos
          _buildTermsCheckbox(),
          
          const SizedBox(height: AppDimensions.marginLarge),
          
          // Botão de registro
          _buildRegisterButton(authState),
          
          // Exibir erro se houver
          if (authState.errorMessage != null) ...[
            const SizedBox(height: AppDimensions.marginMedium),
            _buildErrorMessage(authState.errorMessage!),
          ],
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

  /// Constrói o prompt para login
  Widget _buildLoginPrompt() {
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      decoration: BoxDecoration(
        color: AppColors.gray50,
        borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
        border: Border.all(color: AppColors.gray200),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Text(
            AppStrings.alreadyHaveAccount,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: AppColors.gray600,
              fontWeight: FontWeight.w400,
            ),
          ),
          TextButton(
            onPressed: _handleLogin,
            style: TextButton.styleFrom(
              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 4),
              minimumSize: Size.zero,
              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
            ),
            child: Text(
              AppStrings.doLogin,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: AppColors.primary,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// Constrói a seção de informações pessoais
  Widget _buildPersonalInfoSection() {
    return _FormSection(
      title: AppStrings.yourInformation,
      number: 1,
      children: [
        // Nome completo
        CustomTextField(
          controller: _nameController,
          focusNode: _nameFocusNode,
          label: AppStrings.fullName,
          hint: AppStrings.fullNameHint,
          prefixIcon: Icons.person_outline,
          validator: Validators.name,
          textInputAction: TextInputAction.next,
          onFieldSubmitted: (_) {
            FocusScope.of(context).requestFocus(_emailFocusNode);
          },
        ),
        
        const SizedBox(height: AppDimensions.marginMedium),
        
        // Email
        CustomTextField.email(
          controller: _emailController,
          focusNode: _emailFocusNode,
          textInputAction: TextInputAction.next,
          onFieldSubmitted: (_) {
            FocusScope.of(context).requestFocus(_phoneFocusNode);
          },
        ),
        
        const SizedBox(height: AppDimensions.marginMedium),
        
        // Telefone
        CustomTextField(
          controller: _phoneController,
          focusNode: _phoneFocusNode,
          label: AppStrings.phone,
          hint: AppStrings.phoneHint,
          prefixIcon: Icons.phone_outlined,
          keyboardType: TextInputType.phone,
          inputFormatters: [AppFormatters.dynamicPhoneFormatter],
          validator: Validators.phone,
          textInputAction: TextInputAction.next,
          onFieldSubmitted: (_) {
            FocusScope.of(context).requestFocus(_passwordFocusNode);
          },
        ),
      ],
    );
  }

  /// Constrói a seção de senha
  Widget _buildPasswordSection() {
    return _FormSection(
      title: AppStrings.createPassword,
      number: 2,
      children: [
        // Senha
        CustomTextField.password(
          controller: _passwordController,
          focusNode: _passwordFocusNode,
          helperText: AppStrings.passwordHint,
          textInputAction: TextInputAction.next,
          onFieldSubmitted: (_) {
            FocusScope.of(context).requestFocus(_confirmPasswordFocusNode);
          },
        ),
        
        const SizedBox(height: AppDimensions.marginSmall),
        
        // Indicador de força da senha
        _buildPasswordStrengthIndicator(),
        
        const SizedBox(height: AppDimensions.marginMedium),
        
        // Confirmar senha (opcional mas recomendado)
        CustomTextField.password(
          controller: _confirmPasswordController,
          focusNode: _confirmPasswordFocusNode,
          label: AppStrings.confirmPassword,
          hint: AppStrings.confirmPasswordHint,
          validator: (value) => _validateConfirmPassword(value),
          textInputAction: TextInputAction.done,
          onFieldSubmitted: (_) => _handleRegister(),
        ),
      ],
    );
  }

  /// Constrói o indicador de força da senha
  Widget _buildPasswordStrengthIndicator() {
    if (_passwordController.text.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Barra de progresso
        Container(
          height: 4,
          decoration: BoxDecoration(
            color: AppColors.gray200,
            borderRadius: BorderRadius.circular(2),
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(2),
            child: LinearProgressIndicator(
              value: _getPasswordStrengthValue(),
              backgroundColor: Colors.transparent,
              valueColor: AlwaysStoppedAnimation<Color>(
                _getPasswordStrengthColor(),
              ),
            ),
          ),
        ),
        
        const SizedBox(height: 4),
        
        // Texto indicativo
        Text(
          _getPasswordStrengthText(),
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
            color: _getPasswordStrengthColor(),
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }

  /// Constrói o checkbox de termos de uso
  Widget _buildTermsCheckbox() {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 20,
          height: 20,
          child: Checkbox(
            value: _acceptTerms,
            onChanged: (value) {
              setState(() {
                _acceptTerms = value ?? false;
              });
            },
            activeColor: AppColors.primary,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(4),
            ),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: RichText(
            text: TextSpan(
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: AppColors.gray600,
                height: 1.4,
              ),
              children: [
                TextSpan(text: AppStrings.acceptTermsPrefix),
                TextSpan(
                  text: AppStrings.termsOfUse,
                  style: TextStyle(
                    color: AppColors.primary,
                    fontWeight: FontWeight.w600,
                    decoration: TextDecoration.underline,
                  ),
                ),
                TextSpan(text: AppStrings.and),
                TextSpan(
                  text: AppStrings.privacyPolicy,
                  style: TextStyle(
                    color: AppColors.primary,
                    fontWeight: FontWeight.w600,
                    decoration: TextDecoration.underline,
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  /// Constrói o botão de registro
  Widget _buildRegisterButton(AuthState authState) {
    return CustomButton(
      text: AppStrings.createAccountFree,
      onPressed: authState.isLoading || !_acceptTerms ? null : _handleRegister,
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

  /// Calcula a força da senha
  PasswordStrength _calculatePasswordStrength(String password) {
    if (password.isEmpty) return PasswordStrength.none;
    
    int score = 0;
    
    // Critérios de força
    if (password.length >= 8) score++;
    if (RegExp(r'[a-z]').hasMatch(password)) score++;
    if (RegExp(r'[A-Z]').hasMatch(password)) score++;
    if (RegExp(r'[0-9]').hasMatch(password)) score++;
    if (RegExp(r'[!@#$%^&*(),.?":{}|<>]').hasMatch(password)) score++;
    
    switch (score) {
      case 0:
      case 1:
        return PasswordStrength.weak;
      case 2:
        return PasswordStrength.fair;
      case 3:
      case 4:
        return PasswordStrength.good;
      case 5:
        return PasswordStrength.strong;
      default:
        return PasswordStrength.none;
    }
  }

  /// Retorna o valor numérico da força da senha
  double _getPasswordStrengthValue() {
    switch (_passwordStrength) {
      case PasswordStrength.weak:
        return 0.25;
      case PasswordStrength.fair:
        return 0.5;
      case PasswordStrength.good:
        return 0.75;
      case PasswordStrength.strong:
        return 1.0;
      default:
        return 0.0;
    }
  }

  /// Retorna a cor da força da senha
  Color _getPasswordStrengthColor() {
    switch (_passwordStrength) {
      case PasswordStrength.weak:
        return AppColors.error;
      case PasswordStrength.fair:
        return AppColors.warning;
      case PasswordStrength.good:
        return AppColors.primary;
      case PasswordStrength.strong:
        return AppColors.success;
      default:
        return AppColors.gray300;
    }
  }

  /// Retorna o texto da força da senha
  String _getPasswordStrengthText() {
    switch (_passwordStrength) {
      case PasswordStrength.weak:
        return AppStrings.weakPassword;
      case PasswordStrength.fair:
        return AppStrings.fairPassword;
      case PasswordStrength.good:
        return AppStrings.goodPassword;
      case PasswordStrength.strong:
        return AppStrings.strongPassword;
      default:
        return '';
    }
  }

  /// Valida confirmação de senha
  String? _validateConfirmPassword(String? value) {
    if (value == null || value.isEmpty) {
      return null; // Campo opcional
    }
    
    if (value != _passwordController.text) {
      return AppStrings.passwordsDoNotMatch;
    }
    
    return null;
  }

  /// Manipula o processo de registro
  Future<void> _handleRegister() async {
    // Limpar erro anterior
    ref.read(authProviderProvider.notifier).clearError();
    
    // Validar formulário
    if (!_formKey.currentState!.validate()) {
      return;
    }

    // Verificar se aceitou os termos
    if (!_acceptTerms) {
      _showMessage(AppStrings.mustAcceptTerms);
      return;
    }

    // Esconder teclado
    FocusScope.of(context).unfocus();

    try {
      // Tentar fazer registro
      final success = await ref.read(authProviderProvider.notifier).register(
        name: _nameController.text.trim(),
        email: _emailController.text.trim(),
        password: _passwordController.text,
        phone: _phoneController.text.trim(),
      );

      if (success) {
        // Registro bem-sucedido
        if (widget.onRegisterSuccess != null) {
          widget.onRegisterSuccess!();
        } else {
          // Navegação padrão para dashboard
          if (mounted) {
            context.go('/dashboard');
          }
        }
      }
    } catch (e) {
      // Erro já é tratado pelo provider
      debugPrint('Erro no registro: $e');
    }
  }

  /// Manipula a navegação para login
  void _handleLogin() {
    if (widget.onLogin != null) {
      widget.onLogin!();
    } else {
      context.push('/login');
    }
  }

  /// Mostra mensagem temporária
  void _showMessage(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: AppColors.error,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }
}

/// Widget para seções do formulário
class _FormSection extends StatelessWidget {
  final String title;
  final int number;
  final List<Widget> children;

  const _FormSection({
    required this.title,
    required this.number,
    required this.children,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Título da seção
        Row(
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
                  number.toString(),
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: AppColors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 12,
                  ),
                ),
              ),
            ),
            const SizedBox(width: 12),
            Text(
              title,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                color: AppColors.gray700,
                fontWeight: FontWeight.w600,
                fontSize: 16,
              ),
            ),
          ],
        ),
        
        const SizedBox(height: AppDimensions.marginMedium),
        
        // Conteúdo da seção
        ...children,
      ],
    );
  }
}

/// Versão compacta do formulário de registro
class CompactRegisterForm extends ConsumerWidget {
  final VoidCallback? onRegisterSuccess;

  const CompactRegisterForm({
    super.key,
    this.onRegisterSuccess,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return RegisterForm(
      onRegisterSuccess: onRegisterSuccess,
      animated: false,
    );
  }
}