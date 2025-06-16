// custom_text_field.dart - Widget de campo de texto customizado para o app Klube Cash
// Arquivo: lib/core/widgets/custom_text_field.dart

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_dimensions.dart';

/// Widget de campo de texto customizado para uso consistente na aplicação
/// 
/// Oferece validação, formatação, diferentes tipos e estados visuais.
/// Suporta máscaras, ícones, toggle de senha e indicador de força.
class CustomTextField extends StatefulWidget {
  /// Controller do campo de texto
  final TextEditingController? controller;
  
  /// Label do campo
  final String? label;
  
  /// Hint/placeholder do campo
  final String? hint;
  
  /// Texto de ajuda
  final String? helperText;
  
  /// Função de validação
  final String? Function(String?)? validator;
  
  /// Callback executado quando o valor muda
  final void Function(String)? onChanged;
  
  /// Callback executado quando o campo é submetido
  final void Function(String)? onFieldSubmitted;
  
  /// Tipo do campo de texto
  final TextFieldType type;
  
  /// Tipo do teclado
  final TextInputType? keyboardType;
  
  /// Ação do teclado
  final TextInputAction? textInputAction;
  
  /// Formatadores de entrada
  final List<TextInputFormatter>? inputFormatters;
  
  /// Ícone prefix
  final IconData? prefixIcon;
  
  /// Widget suffix
  final Widget? suffix;
  
  /// Ícone suffix
  final IconData? suffixIcon;
  
  /// Callback do ícone suffix
  final VoidCallback? onSuffixTap;
  
  /// Se o campo está habilitado
  final bool enabled;
  
  /// Se o campo é obrigatório
  final bool isRequired;
  
  /// Se deve mostrar contador de caracteres
  final bool showCounter;
  
  /// Tamanho máximo do texto
  final int? maxLength;
  
  /// Número máximo de linhas
  final int? maxLines;
  
  /// Número mínimo de linhas
  final int? minLines;
  
  /// Se deve auto-validar
  final AutovalidateMode autovalidateMode;
  
  /// Focus node
  final FocusNode? focusNode;
  
  /// Valor inicial
  final String? initialValue;
  
  /// Estilo do texto
  final TextStyle? textStyle;
  
  /// Cor da borda
  final Color? borderColor;
  
  /// Cor de fundo
  final Color? fillColor;

  const CustomTextField({
    super.key,
    this.controller,
    this.label,
    this.hint,
    this.helperText,
    this.validator,
    this.onChanged,
    this.onFieldSubmitted,
    this.type = TextFieldType.text,
    this.keyboardType,
    this.textInputAction,
    this.inputFormatters,
    this.prefixIcon,
    this.suffix,
    this.suffixIcon,
    this.onSuffixTap,
    this.enabled = true,
    this.isRequired = false,
    this.showCounter = false,
    this.maxLength,
    this.maxLines = 1,
    this.minLines,
    this.autovalidateMode = AutovalidateMode.onUserInteraction,
    this.focusNode,
    this.initialValue,
    this.textStyle,
    this.borderColor,
    this.fillColor,
  });

  /// Campo de email
  const CustomTextField.email({
    super.key,
    this.controller,
    this.label = 'Email',
    this.hint = 'Digite seu email',
    this.validator,
    this.onChanged,
    this.onFieldSubmitted,
    this.enabled = true,
    this.isRequired = true,
    this.focusNode,
    this.initialValue,
  })  : type = TextFieldType.email,
        keyboardType = TextInputType.emailAddress,
        textInputAction = TextInputAction.next,
        inputFormatters = null,
        prefixIcon = Icons.email_outlined,
        suffix = null,
        suffixIcon = null,
        onSuffixTap = null,
        showCounter = false,
        maxLength = null,
        maxLines = 1,
        minLines = null,
        autovalidateMode = AutovalidateMode.onUserInteraction,
        helperText = null,
        textStyle = null,
        borderColor = null,
        fillColor = null;

  /// Campo de senha
  const CustomTextField.password({
    super.key,
    this.controller,
    this.label = 'Senha',
    this.hint = 'Digite sua senha',
    this.validator,
    this.onChanged,
    this.onFieldSubmitted,
    this.enabled = true,
    this.isRequired = true,
    this.focusNode,
    this.initialValue,
  })  : type = TextFieldType.password,
        keyboardType = TextInputType.visiblePassword,
        textInputAction = TextInputAction.done,
        inputFormatters = null,
        prefixIcon = Icons.lock_outlined,
        suffix = null,
        suffixIcon = null,
        onSuffixTap = null,
        showCounter = false,
        maxLength = null,
        maxLines = 1,
        minLines = null,
        autovalidateMode = AutovalidateMode.onUserInteraction,
        helperText = null,
        textStyle = null,
        borderColor = null,
        fillColor = null;

  /// Campo de CPF
  const CustomTextField.cpf({
    super.key,
    this.controller,
    this.label = 'CPF',
    this.hint = '000.000.000-00',
    this.validator,
    this.onChanged,
    this.enabled = true,
    this.isRequired = true,
    this.focusNode,
    this.initialValue,
  })  : type = TextFieldType.cpf,
        keyboardType = TextInputType.number,
        textInputAction = TextInputAction.next,
        inputFormatters = null, // Será definido no build
        prefixIcon = Icons.person_outlined,
        suffix = null,
        suffixIcon = null,
        onSuffixTap = null,
        showCounter = false,
        maxLength = 14,
        maxLines = 1,
        minLines = null,
        autovalidateMode = AutovalidateMode.onUserInteraction,
        onFieldSubmitted = null,
        helperText = null,
        textStyle = null,
        borderColor = null,
        fillColor = null;

  /// Campo de telefone
  const CustomTextField.phone({
    super.key,
    this.controller,
    this.label = 'Telefone',
    this.hint = '(00) 00000-0000',
    this.validator,
    this.onChanged,
    this.enabled = true,
    this.isRequired = false,
    this.focusNode,
    this.initialValue,
  })  : type = TextFieldType.phone,
        keyboardType = TextInputType.phone,
        textInputAction = TextInputAction.next,
        inputFormatters = null, // Será definido no build
        prefixIcon = Icons.phone_outlined,
        suffix = null,
        suffixIcon = null,
        onSuffixTap = null,
        showCounter = false,
        maxLength = 15,
        maxLines = 1,
        minLines = null,
        autovalidateMode = AutovalidateMode.onUserInteraction,
        onFieldSubmitted = null,
        helperText = null,
        textStyle = null,
        borderColor = null,
        fillColor = null;

  /// Campo de busca
  const CustomTextField.search({
    super.key,
    this.controller,
    this.label,
    this.hint = 'Buscar...',
    this.onChanged,
    this.onFieldSubmitted,
    this.enabled = true,
    this.focusNode,
  })  : type = TextFieldType.search,
        keyboardType = TextInputType.text,
        textInputAction = TextInputAction.search,
        inputFormatters = null,
        prefixIcon = Icons.search,
        suffix = null,
        suffixIcon = null,
        onSuffixTap = null,
        showCounter = false,
        maxLength = null,
        maxLines = 1,
        minLines = null,
        autovalidateMode = AutovalidateMode.disabled,
        validator = null,
        isRequired = false,
        initialValue = null,
        helperText = null,
        textStyle = null,
        borderColor = null,
        fillColor = null;

  @override
  State<CustomTextField> createState() => _CustomTextFieldState();
}

class _CustomTextFieldState extends State<CustomTextField> {
  late bool _obscureText;
  late FocusNode _focusNode;
  String? _errorText;
  PasswordStrength _passwordStrength = PasswordStrength.none;

  @override
  void initState() {
    super.initState();
    _obscureText = widget.type == TextFieldType.password;
    _focusNode = widget.focusNode ?? FocusNode();
    _focusNode.addListener(_onFocusChange);
  }

  @override
  void dispose() {
    if (widget.focusNode == null) {
      _focusNode.dispose();
    }
    super.dispose();
  }

  void _onFocusChange() {
    setState(() {});
  }

  void _togglePasswordVisibility() {
    setState(() {
      _obscureText = !_obscureText;
    });
  }

  void _onChanged(String value) {
    if (widget.type == TextFieldType.password) {
      _updatePasswordStrength(value);
    }
    widget.onChanged?.call(value);
  }

  void _updatePasswordStrength(String password) {
    setState(() {
      _passwordStrength = _calculatePasswordStrength(password);
    });
  }

  PasswordStrength _calculatePasswordStrength(String password) {
    if (password.isEmpty) return PasswordStrength.none;
    if (password.length < 6) return PasswordStrength.weak;
    
    int score = 0;
    if (password.length >= 8) score++;
    if (RegExp(r'[A-Z]').hasMatch(password)) score++;
    if (RegExp(r'[a-z]').hasMatch(password)) score++;
    if (RegExp(r'[0-9]').hasMatch(password)) score++;
    if (RegExp(r'[!@#$%^&*(),.?":{}|<>]').hasMatch(password)) score++;
    
    if (score <= 2) return PasswordStrength.weak;
    if (score <= 3) return PasswordStrength.fair;
    if (score <= 4) return PasswordStrength.good;
    return PasswordStrength.strong;
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (widget.label != null) _buildLabel(),
        _buildTextField(),
        if (widget.type == TextFieldType.password) _buildPasswordStrength(),
        if (widget.helperText != null) _buildHelperText(),
      ],
    );
  }

  Widget _buildLabel() {
    return Padding(
      padding: const EdgeInsets.only(bottom: AppDimensions.marginSmall),
      child: RichText(
        text: TextSpan(
          text: widget.label!,
          style: Theme.of(context).textTheme.labelMedium?.copyWith(
            color: AppColors.textSecondary,
            fontWeight: FontWeight.w600,
          ),
          children: [
            if (widget.isRequired)
              TextSpan(
                text: ' *',
                style: TextStyle(
                  color: AppColors.error,
                  fontWeight: FontWeight.bold,
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildTextField() {
    return TextFormField(
      controller: widget.controller,
      initialValue: widget.initialValue,
      focusNode: _focusNode,
      enabled: widget.enabled,
      obscureText: _obscureText,
      keyboardType: widget.keyboardType ?? _getDefaultKeyboardType(),
      textInputAction: widget.textInputAction,
      inputFormatters: widget.inputFormatters ?? _getDefaultFormatters(),
      maxLength: widget.maxLength,
      maxLines: widget.maxLines,
      minLines: widget.minLines,
      autovalidateMode: widget.autovalidateMode,
      validator: widget.validator,
      onChanged: _onChanged,
      onFieldSubmitted: widget.onFieldSubmitted,
      style: widget.textStyle ?? Theme.of(context).textTheme.bodyLarge,
      decoration: _buildInputDecoration(),
    );
  }

  InputDecoration _buildInputDecoration() {
    final hasError = _errorText != null;
    final isFocused = _focusNode.hasFocus;
    
    return InputDecoration(
      hintText: widget.hint,
      hintStyle: TextStyle(
        color: AppColors.textMuted,
        fontWeight: FontWeight.normal,
      ),
      prefixIcon: widget.prefixIcon != null
          ? Icon(
              widget.prefixIcon,
              color: _getPrefixIconColor(hasError, isFocused),
              size: 20,
            )
          : null,
      suffixIcon: _buildSuffixIcon(),
      suffix: widget.suffix,
      filled: true,
      fillColor: widget.fillColor ?? _getFillColor(hasError),
      contentPadding: const EdgeInsets.symmetric(
        horizontal: AppDimensions.paddingMedium,
        vertical: AppDimensions.paddingMedium,
      ),
      border: _buildBorder(),
      enabledBorder: _buildBorder(),
      focusedBorder: _buildBorder(isFocused: true),
      errorBorder: _buildBorder(hasError: true),
      focusedErrorBorder: _buildBorder(isFocused: true, hasError: true),
      disabledBorder: _buildBorder(isDisabled: true),
      counterText: widget.showCounter ? null : '',
      errorText: _errorText,
      errorStyle: TextStyle(
        color: AppColors.error,
        fontSize: 12,
        fontWeight: FontWeight.w500,
      ),
    );
  }

  Widget? _buildSuffixIcon() {
    if (widget.type == TextFieldType.password) {
      return IconButton(
        icon: Icon(
          _obscureText ? Icons.visibility : Icons.visibility_off,
          color: AppColors.textMuted,
          size: 20,
        ),
        onPressed: _togglePasswordVisibility,
      );
    }
    
    if (widget.suffixIcon != null) {
      return IconButton(
        icon: Icon(
          widget.suffixIcon,
          color: AppColors.textMuted,
          size: 20,
        ),
        onPressed: widget.onSuffixTap,
      );
    }
    
    return null;
  }

  Widget _buildPasswordStrength() {
    if (_passwordStrength == PasswordStrength.none) {
      return const SizedBox.shrink();
    }
    
    return Padding(
      padding: const EdgeInsets.only(top: AppDimensions.marginSmall),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          LinearProgressIndicator(
            value: _getPasswordStrengthValue(),
            backgroundColor: AppColors.gray200,
            valueColor: AlwaysStoppedAnimation<Color>(
              _getPasswordStrengthColor(),
            ),
          ),
          const SizedBox(height: AppDimensions.marginXSmall),
          Text(
            _getPasswordStrengthText(),
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: _getPasswordStrengthColor(),
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHelperText() {
    return Padding(
      padding: const EdgeInsets.only(top: AppDimensions.marginSmall),
      child: Text(
        widget.helperText!,
        style: Theme.of(context).textTheme.bodySmall?.copyWith(
          color: AppColors.textMuted,
        ),
      ),
    );
  }

  OutlineInputBorder _buildBorder({
    bool isFocused = false,
    bool hasError = false,
    bool isDisabled = false,
  }) {
    Color borderColor;
    double borderWidth = 1.5;
    
    if (hasError) {
      borderColor = AppColors.error;
      borderWidth = 2.0;
    } else if (isFocused) {
      borderColor = AppColors.primary;
      borderWidth = 2.0;
    } else if (isDisabled) {
      borderColor = AppColors.gray300;
    } else {
      borderColor = widget.borderColor ?? AppColors.borderLight;
    }
    
    return OutlineInputBorder(
      borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
      borderSide: BorderSide(
        color: borderColor,
        width: borderWidth,
      ),
    );
  }

  Color _getFillColor(bool hasError) {
    if (!widget.enabled) return AppColors.gray100;
    if (hasError) return AppColors.errorLight.withOpacity(0.1);
    return AppColors.background;
  }

  Color _getPrefixIconColor(bool hasError, bool isFocused) {
    if (hasError) return AppColors.error;
    if (isFocused) return AppColors.primary;
    return AppColors.textMuted;
  }

  TextInputType _getDefaultKeyboardType() {
    switch (widget.type) {
      case TextFieldType.email:
        return TextInputType.emailAddress;
      case TextFieldType.phone:
      case TextFieldType.cpf:
        return TextInputType.number;
      case TextFieldType.password:
        return TextInputType.visiblePassword;
      default:
        return TextInputType.text;
    }
  }

  List<TextInputFormatter>? _getDefaultFormatters() {
    switch (widget.type) {
      case TextFieldType.cpf:
        return [
          FilteringTextInputFormatter.digitsOnly,
          // CPF Formatter seria implementado em formatters.dart
        ];
      case TextFieldType.phone:
        return [
          FilteringTextInputFormatter.digitsOnly,
          // Phone Formatter seria implementado em formatters.dart
        ];
      default:
        return null;
    }
  }

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

  Color _getPasswordStrengthColor() {
    switch (_passwordStrength) {
      case PasswordStrength.weak:
        return AppColors.error;
      case PasswordStrength.fair:
        return AppColors.warning;
      case PasswordStrength.good:
        return AppColors.info;
      case PasswordStrength.strong:
        return AppColors.success;
      default:
        return AppColors.gray300;
    }
  }

  String _getPasswordStrengthText() {
    switch (_passwordStrength) {
      case PasswordStrength.weak:
        return 'Senha fraca';
      case PasswordStrength.fair:
        return 'Senha razoável';
      case PasswordStrength.good:
        return 'Senha boa';
      case PasswordStrength.strong:
        return 'Senha forte';
      default:
        return '';
    }
  }
}

/// Tipos de campo de texto
enum TextFieldType {
  text,
  email,
  password,
  cpf,
  phone,
  search,
}

/// Níveis de força da senha
enum PasswordStrength {
  none,
  weak,
  fair,
  good,
  strong,
}