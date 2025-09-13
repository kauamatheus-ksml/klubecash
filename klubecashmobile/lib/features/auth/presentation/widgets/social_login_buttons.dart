// lib/features/auth/presentation/widgets/social_login_buttons.dart
// 🔗 Social Login Buttons - Botões para login com redes sociais (Google, Facebook, Apple)

import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_images.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/constants/app_dimensions.dart';

/// Tipos de login social disponíveis
enum SocialLoginType { google, facebook, apple }

/// Widget de botões para login social
class SocialLoginButtons extends StatelessWidget {
  /// Callback para login com Google
  final VoidCallback? onGooglePressed;
  
  /// Callback para login com Facebook
  final VoidCallback? onFacebookPressed;
  
  /// Callback para login com Apple
  final VoidCallback? onApplePressed;
  
  /// Se deve mostrar loading em algum botão
  final SocialLoginType? loadingType;
  
  /// Se os botões estão habilitados
  final bool enabled;
  
  /// Layout dos botões (horizontal ou vertical)
  final SocialButtonLayout layout;
  
  /// Se deve mostrar labels nos botões
  final bool showLabels;
  
  /// Espaçamento entre os botões
  final double spacing;

  const SocialLoginButtons({
    super.key,
    this.onGooglePressed,
    this.onFacebookPressed,
    this.onApplePressed,
    this.loadingType,
    this.enabled = true,
    this.layout = SocialButtonLayout.horizontal,
    this.showLabels = true,
    this.spacing = 8.0,
  });

  @override
  Widget build(BuildContext context) {
    switch (layout) {
      case SocialButtonLayout.horizontal:
        return _buildHorizontalLayout();
      case SocialButtonLayout.vertical:
        return _buildVerticalLayout();
      case SocialButtonLayout.googleOnly:
        return _buildGoogleOnlyLayout();
    }
  }

  /// Layout horizontal (padrão - Google maior, Facebook e Apple menores)
  Widget _buildHorizontalLayout() {
    return Row(
      children: [
        // Botão Google (maior)
        Expanded(
          flex: 2,
          child: _SocialButton(
            type: SocialLoginType.google,
            onPressed: onGooglePressed,
            isLoading: loadingType == SocialLoginType.google,
            enabled: enabled,
            showLabel: showLabels,
            isPrimary: true,
          ),
        ),
        
        SizedBox(width: spacing),
        
        // Botão Facebook
        Expanded(
          flex: 1,
          child: _SocialButton(
            type: SocialLoginType.facebook,
            onPressed: onFacebookPressed,
            isLoading: loadingType == SocialLoginType.facebook,
            enabled: enabled,
            showLabel: false, // Compacto
          ),
        ),
        
        SizedBox(width: spacing),
        
        // Botão Apple
        Expanded(
          flex: 1,
          child: _SocialButton(
            type: SocialLoginType.apple,
            onPressed: onApplePressed,
            isLoading: loadingType == SocialLoginType.apple,
            enabled: enabled,
            showLabel: false, // Compacto
          ),
        ),
      ],
    );
  }

  /// Layout vertical (todos os botões com mesmo tamanho)
  Widget _buildVerticalLayout() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _SocialButton(
          type: SocialLoginType.google,
          onPressed: onGooglePressed,
          isLoading: loadingType == SocialLoginType.google,
          enabled: enabled,
          showLabel: showLabels,
          fullWidth: true,
        ),
        
        SizedBox(height: spacing),
        
        _SocialButton(
          type: SocialLoginType.facebook,
          onPressed: onFacebookPressed,
          isLoading: loadingType == SocialLoginType.facebook,
          enabled: enabled,
          showLabel: showLabels,
          fullWidth: true,
        ),
        
        SizedBox(height: spacing),
        
        _SocialButton(
          type: SocialLoginType.apple,
          onPressed: onApplePressed,
          isLoading: loadingType == SocialLoginType.apple,
          enabled: enabled,
          showLabel: showLabels,
          fullWidth: true,
        ),
      ],
    );
  }

  /// Layout apenas Google (para casos específicos)
  Widget _buildGoogleOnlyLayout() {
    return _SocialButton(
      type: SocialLoginType.google,
      onPressed: onGooglePressed,
      isLoading: loadingType == SocialLoginType.google,
      enabled: enabled,
      showLabel: showLabels,
      fullWidth: true,
      isPrimary: true,
    );
  }
}

/// Widget individual de botão social
class _SocialButton extends StatefulWidget {
  final SocialLoginType type;
  final VoidCallback? onPressed;
  final bool isLoading;
  final bool enabled;
  final bool showLabel;
  final bool fullWidth;
  final bool isPrimary;

  const _SocialButton({
    required this.type,
    this.onPressed,
    this.isLoading = false,
    this.enabled = true,
    this.showLabel = true,
    this.fullWidth = false,
    this.isPrimary = false,
  });

  @override
  State<_SocialButton> createState() => _SocialButtonState();
}

class _SocialButtonState extends State<_SocialButton>
    with SingleTickerProviderStateMixin {
  late AnimationController _animationController;
  late Animation<double> _scaleAnimation;

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
      duration: const Duration(milliseconds: 100),
      vsync: this,
    );
    _scaleAnimation = Tween<double>(
      begin: 1.0,
      end: 0.95,
    ).animate(CurvedAnimation(
      parent: _animationController,
      curve: Curves.easeInOut,
    ));
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final config = _getSocialConfig(widget.type);
    final isEnabled = widget.enabled && widget.onPressed != null;

    return AnimatedBuilder(
      animation: _scaleAnimation,
      builder: (context, child) {
        return Transform.scale(
          scale: _scaleAnimation.value,
          child: GestureDetector(
            onTapDown: isEnabled ? (_) => _animationController.forward() : null,
            onTapUp: isEnabled ? (_) => _animationController.reverse() : null,
            onTapCancel: isEnabled ? () => _animationController.reverse() : null,
            onTap: isEnabled ? widget.onPressed : null,
            child: Container(
              height: 48,
              padding: const EdgeInsets.symmetric(
                horizontal: AppDimensions.paddingMedium,
                vertical: AppDimensions.paddingSmall,
              ),
              decoration: BoxDecoration(
                color: _getBackgroundColor(config),
                borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
                border: Border.all(
                  color: _getBorderColor(config),
                  width: 1,
                ),
                boxShadow: [
                  if (isEnabled)
                    BoxShadow(
                      color: Colors.black.withOpacity(0.05),
                      blurRadius: 4,
                      offset: const Offset(0, 2),
                    ),
                ],
              ),
              child: _buildButtonContent(config),
            ),
          ),
        );
      },
    );
  }

  /// Constrói o conteúdo do botão
  Widget _buildButtonContent(_SocialConfig config) {
    if (widget.isLoading) {
      return Center(
        child: SizedBox(
          width: 20,
          height: 20,
          child: CircularProgressIndicator(
            strokeWidth: 2,
            valueColor: AlwaysStoppedAnimation<Color>(config.textColor),
          ),
        ),
      );
    }

    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        // Ícone
        SvgPicture.asset(
          config.iconPath,
          width: 20,
          height: 20,
        ),
        
        // Label (se deve mostrar)
        if (widget.showLabel) ...[
          const SizedBox(width: 8),
          Flexible(
            child: Text(
              config.label,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: config.textColor,
                fontWeight: FontWeight.w600,
                fontSize: 14,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ],
    );
  }

  /// Retorna a cor de fundo do botão
  Color _getBackgroundColor(_SocialConfig config) {
    if (!widget.enabled) {
      return AppColors.gray100;
    }
    
    if (widget.isPrimary && widget.type == SocialLoginType.google) {
      return AppColors.primaryUltraLight; // #FFF1E6
    }
    
    return config.backgroundColor;
  }

  /// Retorna a cor da borda do botão
  Color _getBorderColor(_SocialConfig config) {
    if (!widget.enabled) {
      return AppColors.gray200;
    }
    
    return config.borderColor;
  }

  /// Retorna a configuração do botão social
  _SocialConfig _getSocialConfig(SocialLoginType type) {
    switch (type) {
      case SocialLoginType.google:
        return _SocialConfig(
          iconPath: AppImages.googleIcon,
          label: AppStrings.continueWithGoogle,
          backgroundColor: AppColors.white,
          textColor: widget.isPrimary ? AppColors.primary : AppColors.gray700,
          borderColor: AppColors.gray200,
        );
        
      case SocialLoginType.facebook:
        return _SocialConfig(
          iconPath: AppImages.facebookIcon,
          label: AppStrings.continueWithFacebook,
          backgroundColor: const Color(0xFF1877F2),
          textColor: AppColors.white,
          borderColor: const Color(0xFF1877F2),
        );
        
      case SocialLoginType.apple:
        return _SocialConfig(
          iconPath: AppImages.appleIcon,
          label: AppStrings.continueWithApple,
          backgroundColor: AppColors.black,
          textColor: AppColors.white,
          borderColor: AppColors.black,
        );
    }
  }
}

/// Configuração de um botão social
class _SocialConfig {
  final String iconPath;
  final String label;
  final Color backgroundColor;
  final Color textColor;
  final Color borderColor;

  const _SocialConfig({
    required this.iconPath,
    required this.label,
    required this.backgroundColor,
    required this.textColor,
    required this.borderColor,
  });
}

/// Layout dos botões sociais
enum SocialButtonLayout {
  /// Layout horizontal - Google maior, outros menores
  horizontal,
  /// Layout vertical - todos com mesmo tamanho
  vertical,
  /// Apenas botão do Google
  googleOnly,
}

/// Widget de divisor "ou" entre login normal e social
class SocialLoginDivider extends StatelessWidget {
  final String text;

  const SocialLoginDivider({
    super.key,
    this.text = 'ou',
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: AppDimensions.marginMedium),
      child: Row(
        children: [
          Expanded(
            child: Container(
              height: 1,
              color: AppColors.gray300,
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Text(
              text,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: AppColors.gray500,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          Expanded(
            child: Container(
              height: 1,
              color: AppColors.gray300,
            ),
          ),
        ],
      ),
    );
  }
}

/// Widget completo com divisor e botões sociais
class SocialLoginSection extends StatelessWidget {
  final VoidCallback? onGooglePressed;
  final VoidCallback? onFacebookPressed;
  final VoidCallback? onApplePressed;
  final SocialLoginType? loadingType;
  final bool enabled;
  final SocialButtonLayout layout;
  final String dividerText;

  const SocialLoginSection({
    super.key,
    this.onGooglePressed,
    this.onFacebookPressed,
    this.onApplePressed,
    this.loadingType,
    this.enabled = true,
    this.layout = SocialButtonLayout.horizontal,
    this.dividerText = 'ou',
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        SocialLoginDivider(text: dividerText),
        SocialLoginButtons(
          onGooglePressed: onGooglePressed,
          onFacebookPressed: onFacebookPressed,
          onApplePressed: onApplePressed,
          loadingType: loadingType,
          enabled: enabled,
          layout: layout,
        ),
      ],
    );
  }
}