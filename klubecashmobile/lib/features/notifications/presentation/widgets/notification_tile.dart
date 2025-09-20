// lib/features/notifications/presentation/widgets/notification_tile.dart
// 🔔 NotificationTile - Widget para exibir cada notificação individual

import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:timeago/timeago.dart' as timeago;

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../domain/entities/notification.dart';
import '../providers/notifications_provider.dart';

/// Widget para exibir cada item da lista de notificações
class NotificationTile extends ConsumerWidget {
  /// Notificação a ser exibida
  final AppNotification notification;
  
  /// Callback ao tocar na notificação
  final VoidCallback? onTap;
  
  /// Se deve mostrar animação
  final bool showAnimation;
  
  /// Índice para animação staggered
  final int? animationIndex;
  
  /// Se deve mostrar ações inline
  final bool showActions;

  const NotificationTile({
    super.key,
    required this.notification,
    this.onTap,
    this.showAnimation = true,
    this.animationIndex,
    this.showActions = false,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    Widget tile = Container(
      margin: const EdgeInsets.only(bottom: AppDimensions.spacingSmall),
      decoration: BoxDecoration(
        color: notification.isRead ? AppColors.white : AppColors.primaryLight.withOpacity(0.3),
        borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
        border: Border.all(
          color: notification.isRead ? AppColors.gray200 : AppColors.primary.withOpacity(0.2),
          width: 1,
        ),
        boxShadow: [
          BoxShadow(
            color: AppColors.black.withOpacity(0.04),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () => _handleTap(context, ref),
          borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
          child: Padding(
            padding: const EdgeInsets.all(AppDimensions.paddingMedium),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _buildHeader(),
                const SizedBox(height: AppDimensions.spacingSmall),
                _buildContent(),
                if (showActions && !notification.isRead) ...[
                  const SizedBox(height: AppDimensions.spacingMedium),
                  _buildActions(context, ref),
                ],
              ],
            ),
          ),
        ),
      ),
    );

    if (showAnimation) {
      final delay = animationIndex != null ? (animationIndex! * 100) : 0;
      return tile
          .animate(delay: Duration(milliseconds: delay))
          .fadeIn(duration: 300.ms)
          .slideX(begin: 0.2, end: 0, duration: 400.ms);
    }

    return tile;
  }

  /// Constrói o cabeçalho com ícone, título e timestamp
  Widget _buildHeader() {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildNotificationIcon(),
        const SizedBox(width: AppDimensions.spacingMedium),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      notification.title,
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: notification.isRead ? FontWeight.w500 : FontWeight.w600,
                        color: AppColors.textPrimary,
                      ),
                      overflow: TextOverflow.ellipsis,
                      maxLines: 2,
                    ),
                  ),
                  if (!notification.isRead) ...[
                    const SizedBox(width: AppDimensions.spacingSmall),
                    Container(
                      width: 8,
                      height: 8,
                      decoration: const BoxDecoration(
                        color: AppColors.primary,
                        shape: BoxShape.circle,
                      ),
                    ),
                  ],
                ],
              ),
              const SizedBox(height: 4),
              Row(
                children: [
                  Icon(
                    Icons.schedule,
                    size: 14,
                    color: AppColors.textSecondary,
                  ),
                  const SizedBox(width: 4),
                  Text(
                    timeago.format(notification.createdAt, locale: 'pt_BR'),
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ],
    );
  }

  /// Constrói o ícone da notificação baseado no tipo
  Widget _buildNotificationIcon() {
    IconData iconData;
    Color backgroundColor;
    Color iconColor;

    switch (notification.type) {
      case NotificationType.cashback:
        iconData = Icons.account_balance_wallet;
        backgroundColor = AppColors.success.withOpacity(0.1);
        iconColor = AppColors.success;
        break;
      case NotificationType.transaction:
        iconData = Icons.receipt_long;
        backgroundColor = AppColors.info.withOpacity(0.1);
        iconColor = AppColors.info;
        break;
      case NotificationType.promotion:
        iconData = Icons.local_offer;
        backgroundColor = AppColors.warning.withOpacity(0.1);
        iconColor = AppColors.warning;
        break;
      case NotificationType.system:
        iconData = Icons.settings;
        backgroundColor = AppColors.gray400.withOpacity(0.1);
        iconColor = AppColors.gray600;
        break;
      case NotificationType.security:
        iconData = Icons.security;
        backgroundColor = AppColors.error.withOpacity(0.1);
        iconColor = AppColors.error;
        break;
      default:
        iconData = Icons.notifications;
        backgroundColor = AppColors.primary.withOpacity(0.1);
        iconColor = AppColors.primary;
    }

    return Container(
      width: 44,
      height: 44,
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
      ),
      child: Center(
        child: Icon(
          iconData,
          size: 20,
          color: iconColor,
        ),
      ),
    );
  }

  /// Constrói o conteúdo da notificação
  Widget _buildContent() {
    return Text(
      notification.message,
      style: TextStyle(
        fontSize: 14,
        color: AppColors.textSecondary,
        fontWeight: notification.isRead ? FontWeight.w400 : FontWeight.w500,
        height: 1.4,
      ),
      maxLines: 3,
      overflow: TextOverflow.ellipsis,
    );
  }

  /// Constrói as ações da notificação
  Widget _buildActions(BuildContext context, WidgetRef ref) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.end,
      children: [
        if (notification.actionUrl != null)
          TextButton.icon(
            onPressed: () => _handleActionTap(context),
            icon: const Icon(Icons.open_in_new, size: 16),
            label: const Text('Abrir'),
            style: TextButton.styleFrom(
              foregroundColor: AppColors.primary,
              textStyle: const TextStyle(fontSize: 12),
            ),
          ),
        const SizedBox(width: AppDimensions.spacingSmall),
        TextButton(
          onPressed: () => _markAsRead(ref),
          child: const Text(
            'Marcar como lida',
            style: TextStyle(fontSize: 12),
          ),
        ),
      ],
    );
  }

  /// Manipula o toque na notificação
  void _handleTap(BuildContext context, WidgetRef ref) {
    if (!notification.isRead) {
      _markAsRead(ref);
    }
    
    if (onTap != null) {
      onTap!();
    } else if (notification.actionUrl != null) {
      _handleActionTap(context);
    }
  }

  /// Manipula o toque na ação da notificação
  void _handleActionTap(BuildContext context) {
    if (notification.actionUrl == null) return;
    
    // TODO: Implementar navegação baseada na URL
    // Pode ser para uma tela específica do app ou URL externa
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Abrindo: ${notification.actionUrl}'),
        duration: const Duration(seconds: 2),
      ),
    );
  }

  /// Marca a notificação como lida
  void _markAsRead(WidgetRef ref) {
    if (!notification.isRead) {
      ref.read(notificationsNotifierProvider.notifier)
          .markNotificationAsRead(notification.id);
    }
  }
}

/// Versão compacta do tile de notificação para uso em listas menores
class NotificationTileCompact extends ConsumerWidget {
  final AppNotification notification;
  final VoidCallback? onTap;

  const NotificationTileCompact({
    super.key,
    required this.notification,
    this.onTap,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Container(
      margin: const EdgeInsets.only(bottom: AppDimensions.spacingXSmall),
      decoration: BoxDecoration(
        color: notification.isRead ? AppColors.white : AppColors.primaryLight.withOpacity(0.3),
        borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
        border: Border.all(
          color: notification.isRead ? AppColors.gray200 : AppColors.primary.withOpacity(0.2),
        ),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () {
            if (!notification.isRead) {
              ref.read(notificationsNotifierProvider.notifier)
                  .markNotificationAsRead(notification.id);
            }
            onTap?.call();
          },
          borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
          child: Padding(
            padding: const EdgeInsets.all(AppDimensions.paddingSmall),
            child: Row(
              children: [
                _buildCompactIcon(),
                const SizedBox(width: AppDimensions.spacingSmall),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        notification.title,
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: notification.isRead ? FontWeight.w500 : FontWeight.w600,
                          color: AppColors.textPrimary,
                        ),
                        overflow: TextOverflow.ellipsis,
                        maxLines: 1,
                      ),
                      const SizedBox(height: 2),
                      Text(
                        notification.message,
                        style: const TextStyle(
                          fontSize: 12,
                          color: AppColors.textSecondary,
                        ),
                        overflow: TextOverflow.ellipsis,
                        maxLines: 1,
                      ),
                    ],
                  ),
                ),
                if (!notification.isRead)
                  Container(
                    width: 6,
                    height: 6,
                    decoration: const BoxDecoration(
                      color: AppColors.primary,
                      shape: BoxShape.circle,
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildCompactIcon() {
    IconData iconData;
    Color iconColor;

    switch (notification.type) {
      case NotificationType.cashback:
        iconData = Icons.account_balance_wallet;
        iconColor = AppColors.success;
        break;
      case NotificationType.transaction:
        iconData = Icons.receipt_long;
        iconColor = AppColors.info;
        break;
      case NotificationType.promotion:
        iconData = Icons.local_offer;
        iconColor = AppColors.warning;
        break;
      case NotificationType.system:
        iconData = Icons.settings;
        iconColor = AppColors.gray600;
        break;
      case NotificationType.security:
        iconData = Icons.security;
        iconColor = AppColors.error;
        break;
      default:
        iconData = Icons.notifications;
        iconColor = AppColors.primary;
    }
    
    return Container(
      width: 32,
      height: 32,
      decoration: BoxDecoration(
        color: iconColor.withOpacity(0.1),
        borderRadius: BorderRadius.circular(4),
      ),
      child: Center(
        child: Icon(
          iconData,
          size: 16,
          color: iconColor,
        ),
      ),
    );
  }
}