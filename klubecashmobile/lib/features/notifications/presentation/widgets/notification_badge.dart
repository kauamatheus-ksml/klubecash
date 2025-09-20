// lib/features/notifications/presentation/widgets/notification_badge.dart
// 🔔 NotificationBadge - Widget para exibir badge com contador de notificações

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../providers/notifications_provider.dart';

/// Widget que exibe um badge com contador de notificações não lidas
class NotificationBadge extends ConsumerWidget {
  /// Widget filho sobre o qual o badge será posicionado
  final Widget child;
  
  /// Tamanho do badge
  final double? size;
  
  /// Cor de fundo do badge
  final Color? backgroundColor;
  
  /// Cor do texto do badge
  final Color? textColor;
  
  /// Posição horizontal do badge (0.0 = esquerda, 1.0 = direita)
  final double horizontalPosition;
  
  /// Posição vertical do badge (0.0 = topo, 1.0 = baixo)
  final double verticalPosition;
  
  /// Offset adicional para ajuste fino da posição
  final Offset? offset;
  
  /// Se deve mostrar animação quando o contador muda
  final bool showAnimation;
  
  /// Limite máximo a ser exibido (ex: 99+)
  final int? maxCount;
  
  /// Se deve mostrar apenas um ponto quando count > 0
  final bool showDot;

  const NotificationBadge({
    super.key,
    required this.child,
    this.size,
    this.backgroundColor,
    this.textColor,
    this.horizontalPosition = 1.0,
    this.verticalPosition = 0.0,
    this.offset,
    this.showAnimation = true,
    this.maxCount = 99,
    this.showDot = false,
  });

  /// Badge padrão para ícone de notificação
  const NotificationBadge.icon({
    super.key,
    required this.child,
    this.showAnimation = true,
    this.maxCount = 99,
  })  : size = null,
        backgroundColor = null,
        textColor = null,
        horizontalPosition = 1.0,
        verticalPosition = 0.0,
        offset = null,
        showDot = false;

  /// Badge pequeno que mostra apenas um ponto
  const NotificationBadge.dot({
    super.key,
    required this.child,
    this.backgroundColor,
    this.horizontalPosition = 1.0,
    this.verticalPosition = 0.0,
    this.offset,
    this.showAnimation = true,
  })  : size = 8,
        textColor = null,
        maxCount = null,
        showDot = true;

  /// Badge para menu item
  const NotificationBadge.menuItem({
    super.key,
    required this.child,
    this.maxCount = 9,
    this.showAnimation = true,
  })  : size = 16,
        backgroundColor = null,
        textColor = null,
        horizontalPosition = 1.0,
        verticalPosition = 0.0,
        offset = const Offset(4, -4),
        showDot = false;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final unreadCount = ref.watch(unreadNotificationsCountProvider);
    
    if (unreadCount == 0) {
      return child;
    }

    Widget badge = _buildBadge(unreadCount);
    
    if (showAnimation) {
      badge = badge
          .animate(key: ValueKey(unreadCount))
          .scale(
            duration: 200.ms,
            begin: const Offset(0.5, 0.5),
            end: const Offset(1.0, 1.0),
            curve: Curves.elasticOut,
          )
          .fadeIn(duration: 150.ms);
    }

    return Stack(
      clipBehavior: Clip.none,
      children: [
        child,
        Positioned(
          top: _getVerticalPosition(),
          right: _getHorizontalPosition(),
          child: badge,
        ),
      ],
    );
  }

  /// Constrói o badge baseado no tipo
  Widget _buildBadge(int count) {
    if (showDot) {
      return _buildDotBadge();
    }
    return _buildCountBadge(count);
  }

  /// Constrói o badge com contador
  Widget _buildCountBadge(int count) {
    final badgeSize = size ?? _getDefaultSize(count);
    final displayCount = _getDisplayCount(count);
    
    return Container(
      width: badgeSize,
      height: badgeSize,
      decoration: BoxDecoration(
        color: backgroundColor ?? AppColors.error,
        borderRadius: BorderRadius.circular(badgeSize / 2),
        border: Border.all(
          color: AppColors.white,
          width: 1.5,
        ),
        boxShadow: [
          BoxShadow(
            color: AppColors.black.withOpacity(0.2),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Center(
        child: Text(
          displayCount,
          style: TextStyle(
            color: textColor ?? AppColors.white,
            fontSize: _getFontSize(badgeSize),
            fontWeight: FontWeight.w600,
            height: 1.0,
          ),
          textAlign: TextAlign.center,
        ),
      ),
    );
  }

  /// Constrói o badge tipo ponto
  Widget _buildDotBadge() {
    final dotSize = size ?? 8.0;
    
    return Container(
      width: dotSize,
      height: dotSize,
      decoration: BoxDecoration(
        color: backgroundColor ?? AppColors.error,
        shape: BoxShape.circle,
        border: Border.all(
          color: AppColors.white,
          width: 1,
        ),
        boxShadow: [
          BoxShadow(
            color: AppColors.black.withOpacity(0.2),
            blurRadius: 2,
            offset: const Offset(0, 1),
          ),
        ],
      ),
    );
  }

  /// Calcula o tamanho padrão baseado no contador
  double _getDefaultSize(int count) {
    if (count < 10) return 18;
    if (count < 100) return 20;
    return 22;
  }

  /// Calcula o tamanho da fonte baseado no tamanho do badge
  double _getFontSize(double badgeSize) {
    if (badgeSize <= 16) return 9;
    if (badgeSize <= 20) return 10;
    return 11;
  }

  /// Formata o contador para exibição
  String _getDisplayCount(int count) {
    if (maxCount != null && count > maxCount!) {
      return '${maxCount!}+';
    }
    return count.toString();
  }

  /// Calcula a posição horizontal
  double? _getHorizontalPosition() {
    if (horizontalPosition >= 1.0) {
      final baseOffset = horizontalPosition == 1.0 ? -2.0 : 0.0;
      return baseOffset + (offset?.dx ?? 0);
    }
    return null; // Será posicionado via left
  }

  /// Calcula a posição vertical
  double? _getVerticalPosition() {
    if (verticalPosition <= 0.0) {
      final baseOffset = verticalPosition == 0.0 ? -2.0 : 0.0;
      return baseOffset + (offset?.dy ?? 0);
    }
    return null; // Será posicionado via bottom
  }
}

/// Widget de ícone de notificação com badge integrado
class NotificationIcon extends ConsumerWidget {
  /// Callback ao tocar no ícone
  final VoidCallback? onTap;
  
  /// Cor do ícone
  final Color? iconColor;
  
  /// Tamanho do ícone
  final double? iconSize;
  
  /// Se deve mostrar tooltip
  final bool showTooltip;
  
  /// Texto do tooltip customizado
  final String? tooltipText;

  const NotificationIcon({
    super.key,
    this.onTap,
    this.iconColor,
    this.iconSize,
    this.showTooltip = true,
    this.tooltipText,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final hasUnread = ref.watch(hasUnreadNotificationsProvider);
    final unreadCount = ref.watch(unreadNotificationsCountProvider);
    
    Widget icon = Icon(
      hasUnread ? Icons.notifications_active : Icons.notifications_outlined,
      color: iconColor ?? AppColors.textPrimary,
      size: iconSize ?? 24,
    );

    if (showTooltip) {
      final tooltip = tooltipText ?? _getTooltipText(unreadCount);
      icon = Tooltip(
        message: tooltip,
        child: icon,
      );
    }

    final iconWithBadge = NotificationBadge.icon(
      child: icon,
    );

    if (onTap != null) {
      return IconButton(
        onPressed: onTap,
        icon: iconWithBadge,
        splashRadius: 20,
      );
    }

    return iconWithBadge;
  }

  String _getTooltipText(int count) {
    if (count == 0) return 'Notificações';
    if (count == 1) return '1 notificação não lida';
    return '$count notificações não lidas';
  }
}

/// Widget simples para contagem de notificações em texto
class NotificationCount extends ConsumerWidget {
  /// Estilo do texto
  final TextStyle? style;
  
  /// Prefixo para o texto
  final String prefix;
  
  /// Se deve esconder quando zero
  final bool hideWhenZero;

  const NotificationCount({
    super.key,
    this.style,
    this.prefix = '',
    this.hideWhenZero = true,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final count = ref.watch(unreadNotificationsCountProvider);
    
    if (hideWhenZero && count == 0) {
      return const SizedBox.shrink();
    }

    return Text(
      '$prefix$count',
      style: style ?? Theme.of(context).textTheme.bodyMedium?.copyWith(
        color: AppColors.textSecondary,
        fontWeight: FontWeight.w500,
      ),
    );
  }
}