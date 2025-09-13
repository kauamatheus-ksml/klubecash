// lib/features/notifications/presentation/screens/notifications_screen.dart
// 🔔 NotificationsScreen - Tela principal de notificações do usuário

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/widgets/custom_app_bar.dart';
import '../../../../core/widgets/loading_indicator.dart';
import '../../../../core/widgets/error_widget.dart';
import '../../../../core/widgets/empty_state_widget.dart';
import '../providers/notifications_provider.dart';
import '../widgets/notification_tile.dart';
import '../widgets/notification_badge.dart';

/// Tela principal de notificações
class NotificationsScreen extends ConsumerStatefulWidget {
  const NotificationsScreen({super.key});

  @override
  ConsumerState<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends ConsumerState<NotificationsScreen> {
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    // Carrega as notificações ao inicializar a tela
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(notificationsNotifierProvider.notifier).loadNotifications();
    });
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final notificationsState = ref.watch(notificationsNotifierProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: _buildAppBar(notificationsState),
      body: RefreshIndicator(
        onRefresh: () => ref.read(notificationsNotifierProvider.notifier).refreshNotifications(),
        color: AppColors.primary,
        child: _buildBody(notificationsState),
      ),
    );
  }

  /// Constrói a AppBar com ações
  PreferredSizeWidget _buildAppBar(NotificationsState state) {
    return CustomAppBar(
      title: 'Notificações',
      type: AppBarType.secondary,
      actions: [
        // Botão de marcar todas como lidas
        if (state.hasUnreadNotifications)
          TextButton(
            onPressed: state.isMarkingAsRead 
                ? null 
                : () => _markAllAsRead(),
            child: Text(
              'Marcar todas',
              style: TextStyle(
                color: state.isMarkingAsRead 
                    ? AppColors.textMuted 
                    : AppColors.primary,
                fontWeight: FontWeight.w600,
                fontSize: 14,
              ),
            ),
          ),
        
        // Menu de opções
        PopupMenuButton<String>(
          icon: const Icon(Icons.more_vert),
          onSelected: (value) => _handleMenuAction(value),
          itemBuilder: (context) => [
            const PopupMenuItem(
              value: 'refresh',
              child: Row(
                children: [
                  Icon(Icons.refresh, size: 20),
                  SizedBox(width: 8),
                  Text('Atualizar'),
                ],
              ),
            ),
            if (state.hasNotifications)
              const PopupMenuItem(
                value: 'clear_read',
                child: Row(
                  children: [
                    Icon(Icons.clear_all, size: 20),
                    SizedBox(width: 8),
                    Text('Limpar lidas'),
                  ],
                ),
              ),
          ],
        ),
      ],
    );
  }

  /// Constrói o corpo da tela
  Widget _buildBody(NotificationsState state) {
    if (state.isLoading && state.notifications.isEmpty) {
      return const Center(child: LoadingIndicator());
    }

    if (state.hasError && state.notifications.isEmpty) {
      return Center(
        child: CustomErrorWidget(
          message: state.errorMessage!,
          onRetry: () => ref.read(notificationsNotifierProvider.notifier).loadNotifications(),
        ),
      );
    }

    if (state.notifications.isEmpty && !state.isLoading) {
      return _buildEmptyState();
    }

    return CustomScrollView(
      controller: _scrollController,
      slivers: [
        // Header com estatísticas
        if (state.hasNotifications)
          SliverToBoxAdapter(
            child: _buildNotificationsHeader(state),
          ),
        
        // Lista de notificações
        SliverList(
          delegate: SliverChildBuilderDelegate(
            (context, index) {
              final notification = state.notifications[index];
              return Padding(
                padding: EdgeInsets.symmetric(
                  horizontal: AppDimensions.paddingMedium,
                  vertical: AppDimensions.spacingXSmall,
                ),
                child: NotificationTile(
                  notification: notification,
                  onTap: () => _handleNotificationTap(notification),
                  showAnimation: true,
                  animationIndex: index,
                  showActions: true,
                ),
              );
            },
            childCount: state.notifications.length,
          ),
        ),
        
        // Espaçamento inferior
        const SliverToBoxAdapter(
          child: SizedBox(height: AppDimensions.spacingLarge),
        ),
      ],
    );
  }

  /// Constrói o header com estatísticas
  Widget _buildNotificationsHeader(NotificationsState state) {
    return Container(
      margin: const EdgeInsets.all(AppDimensions.paddingMedium),
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            AppColors.primary.withOpacity(0.1),
            AppColors.primaryLight.withOpacity(0.05),
          ],
        ),
        borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
        border: Border.all(
          color: AppColors.primary.withOpacity(0.2),
          width: 1,
        ),
      ),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: AppColors.primary.withOpacity(0.1),
              borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
            ),
            child: Icon(
              Icons.notifications_active,
              color: AppColors.primary,
              size: 24,
            ),
          ),
          const SizedBox(width: AppDimensions.spacingMedium),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Suas Notificações',
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  '${state.notifications.length} ${state.notifications.length == 1 ? 'notificação' : 'notificações'} • ${state.unreadCount} não ${state.unreadCount == 1 ? 'lida' : 'lidas'}',
                  style: const TextStyle(
                    fontSize: 14,
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
          if (state.unreadCount > 0)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: AppColors.error,
                borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
              ),
              child: Text(
                '${state.unreadCount}',
                style: const TextStyle(
                  color: AppColors.white,
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
        ],
      ),
    ).animate().fadeIn(duration: 400.ms).slideY(begin: -0.2, end: 0);
  }

  /// Constrói o estado vazio
  Widget _buildEmptyState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppDimensions.paddingLarge),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 120,
              height: 120,
              decoration: BoxDecoration(
                color: AppColors.primary.withOpacity(0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(
                Icons.notifications_none,
                size: 60,
                color: AppColors.primary.withOpacity(0.6),
              ),
            ).animate().scale(duration: 600.ms, curve: Curves.elasticOut),
            
            const SizedBox(height: AppDimensions.spacingLarge),
            
            Text(
              'Nenhuma notificação',
              style: const TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.w600,
                color: AppColors.textPrimary,
              ),
            ).animate(delay: 200.ms).fadeIn(duration: 400.ms),
            
            const SizedBox(height: AppDimensions.spacingSmall),
            
            Text(
              'Quando você receber notificações importantes, elas aparecerão aqui.',
              style: const TextStyle(
                fontSize: 16,
                color: AppColors.textSecondary,
                height: 1.5,
              ),
              textAlign: TextAlign.center,
            ).animate(delay: 400.ms).fadeIn(duration: 400.ms),
            
            const SizedBox(height: AppDimensions.spacingLarge),
            
            ElevatedButton.icon(
              onPressed: () => context.go('/dashboard'),
              icon: const Icon(Icons.home_outlined),
              label: const Text('Voltar ao início'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: AppColors.white,
                padding: const EdgeInsets.symmetric(
                  horizontal: AppDimensions.paddingLarge,
                  vertical: AppDimensions.paddingMedium,
                ),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
                ),
              ),
            ).animate(delay: 600.ms).fadeIn(duration: 400.ms).slideY(begin: 0.2, end: 0),
          ],
        ),
      ),
    );
  }

  /// Manipula o toque em uma notificação
  void _handleNotificationTap(notification) {
    // Navegar baseado no tipo ou URL da notificação
    if (notification.actionUrl != null) {
      _navigateToNotificationAction(notification.actionUrl!);
    }
  }

  /// Navega para a ação da notificação
  void _navigateToNotificationAction(String actionUrl) {
    // Implementar roteamento baseado na URL
    // Exemplos:
    // /cashback/details/{id}
    // /profile/settings
    // /stores/{id}
    
    if (actionUrl.startsWith('/')) {
      context.go(actionUrl);
    } else {
      // URL externa - mostrar dialog ou abrir browser
      _showExternalLinkDialog(actionUrl);
    }
  }

  /// Mostra dialog para links externos
  void _showExternalLinkDialog(String url) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Abrir link externo'),
        content: Text('Deseja abrir este link em seu navegador?\n\n$url'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Cancelar'),
          ),
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              // TODO: Abrir URL externa
            },
            child: const Text('Abrir'),
          ),
        ],
      ),
    );
  }

  /// Marca todas as notificações como lidas
  Future<void> _markAllAsRead() async {
    final success = await ref.read(notificationsNotifierProvider.notifier).markAllAsRead();
    
    if (mounted && success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Todas as notificações foram marcadas como lidas'),
          backgroundColor: AppColors.success,
          duration: Duration(seconds: 2),
        ),
      );
    }
  }

  /// Manipula ações do menu
  void _handleMenuAction(String action) {
    switch (action) {
      case 'refresh':
        ref.read(notificationsNotifierProvider.notifier).refreshNotifications();
        break;
      case 'clear_read':
        _showClearReadDialog();
        break;
    }
  }

  /// Mostra dialog para limpar notificações lidas
  void _showClearReadDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Limpar notificações lidas'),
        content: const Text(
          'Deseja remover todas as notificações que já foram lidas? Esta ação não pode ser desfeita.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Cancelar'),
          ),
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              // TODO: Implementar remoção de notificações lidas
              _clearReadNotifications();
            },
            style: TextButton.styleFrom(
              foregroundColor: AppColors.error,
            ),
            child: const Text('Limpar'),
          ),
        ],
      ),
    );
  }

  /// Remove notificações lidas
  Future<void> _clearReadNotifications() async {
    // TODO: Implementar no provider
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Notificações lidas foram removidas'),
        backgroundColor: AppColors.success,
      ),
    );
  }
}