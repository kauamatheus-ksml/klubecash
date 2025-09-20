// lib/features/profile/presentation/widgets/profile_menu_item.dart
// ARQUIVO #106 - ProfileMenuItem - Item reutilizável para menu do perfil

import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/theme/text_styles.dart';

/// Widget reutilizável para itens do menu de perfil
class ProfileMenuItem extends StatelessWidget {
  /// Ícone do item
  final IconData icon;
  
  /// Título principal
  final String title;
  
  /// Subtítulo opcional
  final String? subtitle;
  
  /// Callback ao tocar o item
  final VoidCallback? onTap;
  
  /// Se o item está habilitado
  final bool enabled;
  
  /// Cor personalizada do ícone
  final Color? iconColor;
  
  /// Tamanho do ícone
  final double? iconSize;
  
  /// Widget personalizado no lado direito
  final Widget? trailing;
  
  /// Se deve mostrar seta de navegação
  final bool showArrow;
  
  /// Badge/contador opcional
  final String? badge;
  
  /// Cor do badge
  final Color? badgeColor;
  
  /// Se deve mostrar divisor inferior
  final bool showDivider;
  
  /// Padding personalizado
  final EdgeInsetsGeometry? padding;
  
  /// Cor de fundo personalizada
  final Color? backgroundColor;
  
  /// Se é um item perigoso (ex: logout, excluir)
  final bool isDangerous;

  const ProfileMenuItem({
    super.key,
    required this.icon,
    required this.title,
    this.subtitle,
    this.onTap,
    this.enabled = true,
    this.iconColor,
    this.iconSize,
    this.trailing,
    this.showArrow = true,
    this.badge,
    this.badgeColor,
    this.showDivider = true,
    this.padding,
    this.backgroundColor,
    this.isDangerous = false,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDisabled = !enabled;
    
    final titleColor = isDangerous 
        ? AppColors.error 
        : isDisabled 
            ? AppColors.textMuted 
            : AppColors.textPrimary;
            
    final subtitleTextColor = isDisabled 
        ? AppColors.textMuted 
        : AppColors.textSecondary;
        
    final itemIconColor = iconColor ?? 
        (isDangerous 
            ? AppColors.error 
            : isDisabled 
                ? AppColors.textMuted 
                : AppColors.primary);

    return Column(
      children: [
        Material(
          color: backgroundColor ?? Colors.transparent,
          child: InkWell(
            onTap: enabled ? onTap : null,
            borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
            child: Container(
              width: double.infinity,
              padding: padding ?? const EdgeInsets.symmetric(
                horizontal: AppDimensions.paddingMedium,
                vertical: AppDimensions.paddingMedium,
              ),
              child: Row(
                children: [
                  // Ícone principal
                  _buildIcon(itemIconColor),
                  
                  const SizedBox(width: AppDimensions.spacingMedium),
                  
                  // Conteúdo principal
                  Expanded(
                    child: _buildContent(titleColor, subtitleTextColor),
                  ),
                  
                  // Badge se disponível
                  if (badge != null) ...[
                    _buildBadge(),
                    const SizedBox(width: AppDimensions.spacingSmall),
                  ],
                  
                  // Trailing widget personalizado ou seta
                  if (trailing != null)
                    trailing!
                  else if (showArrow && enabled)
                    _buildArrow(),
                ],
              ),
            ),
          ),
        ),
        
        // Divisor se habilitado
        if (showDivider)
          Padding(
            padding: const EdgeInsets.only(
              left: AppDimensions.iconSize + AppDimensions.spacingMedium * 2,
            ),
            child: Divider(
              height: 1,
              thickness: 0.5,
              color: AppColors.borderLight,
            ),
          ),
      ],
    );
  }

  /// Constrói o ícone principal
  Widget _buildIcon(Color color) {
    return Container(
      width: AppDimensions.iconSizeLarge,
      height: AppDimensions.iconSizeLarge,
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
      ),
      child: Icon(
        icon,
        size: iconSize ?? AppDimensions.iconSize,
        color: color,
      ),
    );
  }

  /// Constrói o conteúdo de texto
  Widget _buildContent(Color titleColor, Color subtitleColor) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        // Título
        Text(
          title,
          style: AppTextStyles.bodyMedium.copyWith(
            color: titleColor,
            fontWeight: FontWeight.w500,
          ),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        
        // Subtítulo se disponível
        if (subtitle != null) ...[
          const SizedBox(height: AppDimensions.spacingXSmall),
          Text(
            subtitle!,
            style: AppTextStyles.bodySmall.copyWith(
              color: subtitleColor,
            ),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ],
    );
  }

  /// Constrói o badge
  Widget _buildBadge() {
    return Container(
      constraints: const BoxConstraints(
        minWidth: 20,
        minHeight: 20,
      ),
      padding: const EdgeInsets.symmetric(
        horizontal: AppDimensions.spacingSmall,
        vertical: 2,
      ),
      decoration: BoxDecoration(
        color: badgeColor ?? AppColors.error,
        borderRadius: BorderRadius.circular(10),
      ),
      child: Text(
        badge!,
        style: AppTextStyles.caption.copyWith(
          color: AppColors.textOnPrimary,
          fontWeight: FontWeight.w600,
        ),
        textAlign: TextAlign.center,
      ),
    );
  }

  /// Constrói a seta de navegação
  Widget _buildArrow() {
    return Icon(
      Icons.chevron_right,
      size: AppDimensions.iconSize,
      color: AppColors.textMuted,
    );
  }
}

/// Variações pré-definidas do ProfileMenuItem

class ProfileMenuSection extends StatelessWidget {
  /// Título da seção
  final String title;
  
  /// Itens da seção
  final List<Widget> children;
  
  /// Padding da seção
  final EdgeInsetsGeometry? padding;

  const ProfileMenuSection({
    super.key,
    required this.title,
    required this.children,
    this.padding,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: padding ?? const EdgeInsets.only(
        top: AppDimensions.spacingLarge,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Título da seção
          Padding(
            padding: const EdgeInsets.symmetric(
              horizontal: AppDimensions.paddingMedium,
              vertical: AppDimensions.spacingSmall,
            ),
            child: Text(
              title,
              style: AppTextStyles.h6.copyWith(
                color: AppColors.textSecondary,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          
          // Container com fundo para os itens
          Container(
            decoration: BoxDecoration(
              color: AppColors.backgroundSecondary,
              borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
            ),
            child: Column(
              children: children,
            ),
          ),
        ],
      ),
    );
  }
}

/// Item de menu com switch
class ProfileMenuSwitchItem extends StatelessWidget {
  /// Ícone do item
  final IconData icon;
  
  /// Título do item
  final String title;
  
  /// Subtítulo opcional
  final String? subtitle;
  
  /// Valor atual do switch
  final bool value;
  
  /// Callback quando o valor muda
  final ValueChanged<bool>? onChanged;
  
  /// Se o item está habilitado
  final bool enabled;

  const ProfileMenuSwitchItem({
    super.key,
    required this.icon,
    required this.title,
    this.subtitle,
    required this.value,
    this.onChanged,
    this.enabled = true,
  });

  @override
  Widget build(BuildContext context) {
    return ProfileMenuItem(
      icon: icon,
      title: title,
      subtitle: subtitle,
      enabled: enabled,
      showArrow: false,
      showDivider: false,
      trailing: Switch.adaptive(
        value: value,
        onChanged: enabled ? onChanged : null,
        activeColor: AppColors.primary,
      ),
      onTap: enabled 
          ? () => onChanged?.call(!value)
          : null,
    );
  }
}

/// Item de menu com informação adicional
class ProfileMenuInfoItem extends StatelessWidget {
  /// Ícone do item
  final IconData icon;
  
  /// Título do item
  final String title;
  
  /// Informação/valor a ser exibido
  final String info;
  
  /// Callback ao tocar
  final VoidCallback? onTap;
  
  /// Se deve mostrar seta
  final bool showArrow;

  const ProfileMenuInfoItem({
    super.key,
    required this.icon,
    required this.title,
    required this.info,
    this.onTap,
    this.showArrow = false,
  });

  @override
  Widget build(BuildContext context) {
    return ProfileMenuItem(
      icon: icon,
      title: title,
      onTap: onTap,
      showArrow: showArrow,
      showDivider: false,
      trailing: Text(
        info,
        style: AppTextStyles.bodySmall.copyWith(
          color: AppColors.textSecondary,
        ),
      ),
    );
  }
}