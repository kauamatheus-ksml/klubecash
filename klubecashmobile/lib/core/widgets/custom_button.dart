// custom_button.dart - Widget de botão customizado reutilizável para o app Klube Cash
// Arquivo: lib/core/widgets/custom_button.dart

import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_dimensions.dart';

/// Widget de botão customizado para uso consistente na aplicação
/// 
/// Oferece diferentes tipos, tamanhos e estados para diversos contextos.
/// Suporta ícones, carregamento e customização visual completa.
class CustomButton extends StatelessWidget {
  /// Texto do botão
  final String text;
  
  /// Callback executado ao pressionar o botão
  final VoidCallback? onPressed;
  
  /// Tipo visual do botão
  final ButtonType type;
  
  /// Tamanho do botão
  final ButtonSize size;
  
  /// Ícone a ser exibido (opcional)
  final IconData? icon;
  
  /// Posição do ícone
  final IconPosition iconPosition;
  
  /// Se o botão está em estado de carregamento
  final bool isLoading;
  
  /// Se o botão deve ocupar toda a largura disponível
  final bool isFullWidth;
  
  /// Cor customizada (substitui a cor do tipo)
  final Color? backgroundColor;
  
  /// Cor do texto customizada
  final Color? textColor;
  
  /// Borda customizada
  final BorderSide? border;
  
  /// BorderRadius customizado
  final BorderRadius? borderRadius;
  
  /// Padding customizado
  final EdgeInsetsGeometry? padding;
  
  /// Elevação do botão
  final double? elevation;

  const CustomButton({
    super.key,
    required this.text,
    this.onPressed,
    this.type = ButtonType.primary,
    this.size = ButtonSize.medium,
    this.icon,
    this.iconPosition = IconPosition.leading,
    this.isLoading = false,
    this.isFullWidth = false,
    this.backgroundColor,
    this.textColor,
    this.border,
    this.borderRadius,
    this.padding,
    this.elevation,
  });

  /// Botão primário (padrão do app)
  const CustomButton.primary({
    super.key,
    required this.text,
    this.onPressed,
    this.icon,
    this.iconPosition = IconPosition.leading,
    this.isLoading = false,
    this.isFullWidth = false,
    this.size = ButtonSize.medium,
  })  : type = ButtonType.primary,
        backgroundColor = null,
        textColor = null,
        border = null,
        borderRadius = null,
        padding = null,
        elevation = null;

  /// Botão secundário
  const CustomButton.secondary({
    super.key,
    required this.text,
    this.onPressed,
    this.icon,
    this.iconPosition = IconPosition.leading,
    this.isLoading = false,
    this.isFullWidth = false,
    this.size = ButtonSize.medium,
  })  : type = ButtonType.secondary,
        backgroundColor = null,
        textColor = null,
        border = null,
        borderRadius = null,
        padding = null,
        elevation = null;

  /// Botão outlined
  const CustomButton.outlined({
    super.key,
    required this.text,
    this.onPressed,
    this.icon,
    this.iconPosition = IconPosition.leading,
    this.isLoading = false,
    this.isFullWidth = false,
    this.size = ButtonSize.medium,
  })  : type = ButtonType.outlined,
        backgroundColor = null,
        textColor = null,
        border = null,
        borderRadius = null,
        padding = null,
        elevation = null;

  /// Botão de texto
  const CustomButton.text({
    super.key,
    required this.text,
    this.onPressed,
    this.icon,
    this.iconPosition = IconPosition.leading,
    this.isLoading = false,
    this.isFullWidth = false,
    this.size = ButtonSize.medium,
  })  : type = ButtonType.text,
        backgroundColor = null,
        textColor = null,
        border = null,
        borderRadius = null,
        padding = null,
        elevation = null;

  /// Botão de perigo/delete
  const CustomButton.danger({
    super.key,
    required this.text,
    this.onPressed,
    this.icon,
    this.iconPosition = IconPosition.leading,
    this.isLoading = false,
    this.isFullWidth = false,
    this.size = ButtonSize.medium,
  })  : type = ButtonType.danger,
        backgroundColor = null,
        textColor = null,
        border = null,
        borderRadius = null,
        padding = null,
        elevation = null;

  /// Botão de sucesso
  const CustomButton.success({
    super.key,
    required this.text,
    this.onPressed,
    this.icon,
    this.iconPosition = IconPosition.leading,
    this.isLoading = false,
    this.isFullWidth = false,
    this.size = ButtonSize.medium,
  })  : type = ButtonType.success,
        backgroundColor = null,
        textColor = null,
        border = null,
        borderRadius = null,
        padding = null,
        elevation = null;

  @override
  Widget build(BuildContext context) {
    final buttonStyle = _getButtonStyle(context);
    final isDisabled = onPressed == null && !isLoading;
    
    Widget buttonChild = _buildButtonContent();
    
    if (isFullWidth) {
      buttonChild = SizedBox(
        width: double.infinity,
        child: buttonChild,
      );
    }

    switch (type) {
      case ButtonType.text:
        return TextButton(
          onPressed: isLoading ? null : onPressed,
          style: buttonStyle,
          child: buttonChild,
        );
      case ButtonType.outlined:
        return OutlinedButton(
          onPressed: isLoading ? null : onPressed,
          style: buttonStyle,
          child: buttonChild,
        );
      default:
        return ElevatedButton(
          onPressed: isLoading ? null : onPressed,
          style: buttonStyle,
          child: buttonChild,
        );
    }
  }

  Widget _buildButtonContent() {
    if (isLoading) {
      return _buildLoadingContent();
    }

    if (icon == null) {
      return Text(text);
    }

    return _buildContentWithIcon();
  }

  Widget _buildLoadingContent() {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        SizedBox(
          width: _getIconSize(),
          height: _getIconSize(),
          child: CircularProgressIndicator(
            strokeWidth: 2,
            valueColor: AlwaysStoppedAnimation<Color>(
              _getLoadingColor(),
            ),
          ),
        ),
        const SizedBox(width: AppDimensions.marginSmall),
        Text('Carregando...'),
      ],
    );
  }

  Widget _buildContentWithIcon() {
    final iconWidget = Icon(
      icon,
      size: _getIconSize(),
    );

    if (iconPosition == IconPosition.leading) {
      return Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          iconWidget,
          const SizedBox(width: AppDimensions.marginSmall),
          Text(text),
        ],
      );
    } else {
      return Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(text),
          const SizedBox(width: AppDimensions.marginSmall),
          iconWidget,
        ],
      );
    }
  }

  ButtonStyle _getButtonStyle(BuildContext context) {
    final colors = _getButtonColors();
    final textStyle = _getTextStyle(context);
    final buttonPadding = padding ?? _getDefaultPadding();
    final buttonBorderRadius = borderRadius ?? _getDefaultBorderRadius();
    final buttonElevation = elevation ?? _getDefaultElevation();

    return ButtonStyle(
      backgroundColor: WidgetStateProperty.resolveWith<Color?>((states) {
        if (states.contains(WidgetState.disabled)) {
          return colors.disabledBackground;
        }
        if (states.contains(WidgetState.pressed)) {
          return colors.pressedBackground;
        }
        if (states.contains(WidgetState.hovered)) {
          return colors.hoveredBackground;
        }
        return colors.background;
      }),
      foregroundColor: WidgetStateProperty.resolveWith<Color?>((states) {
        if (states.contains(WidgetState.disabled)) {
          return colors.disabledForeground;
        }
        return colors.foreground;
      }),
      elevation: WidgetStateProperty.all(buttonElevation),
      padding: WidgetStateProperty.all(buttonPadding),
      shape: WidgetStateProperty.all(
        RoundedRectangleBorder(
          borderRadius: buttonBorderRadius,
          side: border ?? _getDefaultBorder(),
        ),
      ),
      textStyle: WidgetStateProperty.all(textStyle),
      minimumSize: WidgetStateProperty.all(_getMinimumSize()),
    );
  }

  _ButtonColors _getButtonColors() {
    switch (type) {
      case ButtonType.primary:
        return _ButtonColors(
          background: backgroundColor ?? AppColors.primary,
          foreground: textColor ?? AppColors.textOnPrimary,
          pressedBackground: AppColors.primaryDark,
          hoveredBackground: AppColors.primaryLight,
          disabledBackground: AppColors.gray300,
          disabledForeground: AppColors.gray500,
        );
      
      case ButtonType.secondary:
        return _ButtonColors(
          background: backgroundColor ?? AppColors.backgroundSecondary,
          foreground: textColor ?? AppColors.textPrimary,
          pressedBackground: AppColors.gray300,
          hoveredBackground: AppColors.gray200,
          disabledBackground: AppColors.gray100,
          disabledForeground: AppColors.gray400,
        );
      
      case ButtonType.outlined:
        return _ButtonColors(
          background: backgroundColor ?? Colors.transparent,
          foreground: textColor ?? AppColors.primary,
          pressedBackground: AppColors.primaryLight.withOpacity(0.1),
          hoveredBackground: AppColors.primaryLight.withOpacity(0.05),
          disabledBackground: Colors.transparent,
          disabledForeground: AppColors.gray400,
        );
      
      case ButtonType.text:
        return _ButtonColors(
          background: backgroundColor ?? Colors.transparent,
          foreground: textColor ?? AppColors.primary,
          pressedBackground: AppColors.primaryLight.withOpacity(0.1),
          hoveredBackground: AppColors.primaryLight.withOpacity(0.05),
          disabledBackground: Colors.transparent,
          disabledForeground: AppColors.gray400,
        );
      
      case ButtonType.danger:
        return _ButtonColors(
          background: backgroundColor ?? AppColors.error,
          foreground: textColor ?? AppColors.textOnDark,
          pressedBackground: AppColors.errorDark,
          hoveredBackground: AppColors.errorLight,
          disabledBackground: AppColors.gray300,
          disabledForeground: AppColors.gray500,
        );
      
      case ButtonType.success:
        return _ButtonColors(
          background: backgroundColor ?? AppColors.success,
          foreground: textColor ?? AppColors.textOnDark,
          pressedBackground: AppColors.successDark,
          hoveredBackground: AppColors.successLight,
          disabledBackground: AppColors.gray300,
          disabledForeground: AppColors.gray500,
        );
    }
  }

  TextStyle _getTextStyle(BuildContext context) {
    final baseStyle = Theme.of(context).textTheme.labelLarge;
    
    switch (size) {
      case ButtonSize.small:
        return baseStyle?.copyWith(
          fontSize: 14,
          fontWeight: FontWeight.w500,
        ) ?? const TextStyle(fontSize: 14, fontWeight: FontWeight.w500);
      
      case ButtonSize.medium:
        return baseStyle?.copyWith(
          fontSize: 16,
          fontWeight: FontWeight.w600,
        ) ?? const TextStyle(fontSize: 16, fontWeight: FontWeight.w600);
      
      case ButtonSize.large:
        return baseStyle?.copyWith(
          fontSize: 18,
          fontWeight: FontWeight.w600,
        ) ?? const TextStyle(fontSize: 18, fontWeight: FontWeight.w600);
    }
  }

  EdgeInsetsGeometry _getDefaultPadding() {
    switch (size) {
      case ButtonSize.small:
        return const EdgeInsets.symmetric(
          horizontal: AppDimensions.paddingMedium,
          vertical: AppDimensions.paddingSmall,
        );
      case ButtonSize.medium:
        return const EdgeInsets.symmetric(
          horizontal: AppDimensions.paddingLarge,
          vertical: AppDimensions.paddingMedium,
        );
      case ButtonSize.large:
        return const EdgeInsets.symmetric(
          horizontal: AppDimensions.paddingXLarge,
          vertical: AppDimensions.paddingLarge,
        );
    }
  }

  BorderRadius _getDefaultBorderRadius() {
    switch (size) {
      case ButtonSize.small:
        return BorderRadius.circular(AppDimensions.radiusMedium);
      case ButtonSize.medium:
        return BorderRadius.circular(AppDimensions.radiusLarge);
      case ButtonSize.large:
        return BorderRadius.circular(AppDimensions.radiusLarge);
    }
  }

  double _getDefaultElevation() {
    return type == ButtonType.outlined || type == ButtonType.text ? 0 : 2;
  }

  BorderSide _getDefaultBorder() {
    if (type == ButtonType.outlined) {
      return BorderSide(
        color: backgroundColor ?? AppColors.primary,
        width: 1.5,
      );
    }
    return BorderSide.none;
  }

  Size _getMinimumSize() {
    switch (size) {
      case ButtonSize.small:
        return const Size(80, 36);
      case ButtonSize.medium:
        return const Size(100, 48);
      case ButtonSize.large:
        return const Size(120, 56);
    }
  }

  double _getIconSize() {
    switch (size) {
      case ButtonSize.small:
        return 16;
      case ButtonSize.medium:
        return 20;
      case ButtonSize.large:
        return 24;
    }
  }

  Color _getLoadingColor() {
    switch (type) {
      case ButtonType.primary:
      case ButtonType.danger:
      case ButtonType.success:
        return AppColors.textOnPrimary;
      default:
        return AppColors.primary;
    }
  }
}

/// Tipos de botão disponíveis
enum ButtonType {
  /// Botão primário (destaque principal)
  primary,
  
  /// Botão secundário (ação secundária)
  secondary,
  
  /// Botão com borda (outline)
  outlined,
  
  /// Botão de texto (sem fundo)
  text,
  
  /// Botão de perigo (ações destrutivas)
  danger,
  
  /// Botão de sucesso (confirmações)
  success,
}

/// Tamanhos de botão disponíveis
enum ButtonSize {
  /// Pequeno (para espaços limitados)
  small,
  
  /// Médio (padrão)
  medium,
  
  /// Grande (para destaque)
  large,
}

/// Posição do ícone no botão
enum IconPosition {
  /// Ícone antes do texto
  leading,
  
  /// Ícone depois do texto
  trailing,
}

/// Classe auxiliar para cores do botão
class _ButtonColors {
  final Color background;
  final Color foreground;
  final Color pressedBackground;
  final Color hoveredBackground;
  final Color disabledBackground;
  final Color disabledForeground;

  _ButtonColors({
    required this.background,
    required this.foreground,
    required this.pressedBackground,
    required this.hoveredBackground,
    required this.disabledBackground,
    required this.disabledForeground,
  });
}