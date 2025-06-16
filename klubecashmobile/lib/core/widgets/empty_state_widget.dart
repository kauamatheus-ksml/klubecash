// empty_state_widget.dart - Widget para exibir estados vazios no app Klube Cash
// Arquivo: lib/core/widgets/empty_state_widget.dart

import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_dimensions.dart';

/// Widget para exibir estados vazios de forma consistente na aplicação
/// 
/// Usado quando listas, dados ou conteúdos estão vazios.
/// Suporta customização visual e ações para guiar o usuário.
class EmptyStateWidget extends StatelessWidget {
  /// Ícone a ser exibido
  final IconData icon;
  
  /// Título do estado vazio
  final String title;
  
  /// Descrição/mensagem do estado vazio
  final String description;
  
  /// Callback para ação principal
  final VoidCallback? onAction;
  
  /// Texto do botão de ação
  final String? actionText;
  
  /// Ícone do botão de ação
  final IconData? actionIcon;
  
  /// Cor do ícone principal
  final Color? iconColor;
  
  /// Cor do botão de ação
  final Color? actionColor;
  
  /// Tipo de exibição do widget
  final EmptyStateType type;
  
  /// URL da imagem (opcional, usado no lugar do ícone)
  final String? imageUrl;
  
  /// Asset de imagem local (opcional, usado no lugar do ícone)
  final String? imageAsset;
  
  /// Tamanho do ícone
  final double iconSize;

  const EmptyStateWidget({
    super.key,
    required this.icon,
    required this.title,
    required this.description,
    this.onAction,
    this.actionText,
    this.actionIcon,
    this.iconColor,
    this.actionColor,
    this.type = EmptyStateType.standard,
    this.imageUrl,
    this.imageAsset,
    this.iconSize = 80,
  });

  /// Estado vazio para lista de transações
  const EmptyStateWidget.transactions({
    super.key,
    this.onAction,
    this.actionText = 'Fazer compra',
    this.actionColor,
  })  : icon = Icons.receipt_long_outlined,
        title = 'Nenhuma transação encontrada',
        description = 'Quando você fizer suas primeiras compras, elas aparecerão aqui.',
        actionIcon = Icons.shopping_bag_outlined,
        iconColor = AppColors.info,
        type = EmptyStateType.standard,
        imageUrl = null,
        imageAsset = null,
        iconSize = 80;

  /// Estado vazio para cashback
  const EmptyStateWidget.cashback({
    super.key,
    this.onAction,
    this.actionText = 'Explorar lojas',
    this.actionColor,
  })  : icon = Icons.account_balance_wallet_outlined,
        title = 'Sem cashback disponível',
        description = 'Comece a fazer compras em lojas parceiras para ganhar cashback.',
        actionIcon = Icons.store_outlined,
        iconColor = AppColors.primary,
        type = EmptyStateType.standard,
        imageUrl = null,
        imageAsset = null,
        iconSize = 80;

  /// Estado vazio para lojas parceiras
  const EmptyStateWidget.stores({
    super.key,
    this.onAction,
    this.actionText = 'Buscar lojas',
    this.actionColor,
  })  : icon = Icons.store_outlined,
        title = 'Nenhuma loja encontrada',
        description = 'Não encontramos lojas com os filtros aplicados. Tente ajustar sua busca.',
        actionIcon = Icons.search,
        iconColor = AppColors.warning,
        type = EmptyStateType.standard,
        imageUrl = null,
        imageAsset = null,
        iconSize = 80;

  /// Estado vazio para favoritos
  const EmptyStateWidget.favorites({
    super.key,
    this.onAction,
    this.actionText = 'Explorar lojas',
    this.actionColor,
  })  : icon = Icons.favorite_border,
        title = 'Nenhuma loja favorita',
        description = 'Adicione suas lojas favoritas para acessá-las rapidamente.',
        actionIcon = Icons.explore_outlined,
        iconColor = AppColors.error,
        type = EmptyStateType.standard,
        imageUrl = null,
        imageAsset = null,
        iconSize = 80;

  /// Estado vazio para notificações
  const EmptyStateWidget.notifications({
    super.key,
    this.onAction,
    this.actionColor,
  })  : icon = Icons.notifications_none,
        title = 'Sem notificações',
        description = 'Você está em dia! Não há notificações pendentes.',
        actionText = null,
        actionIcon = null,
        iconColor = AppColors.success,
        type = EmptyStateType.standard,
        imageUrl = null,
        imageAsset = null,
        iconSize = 80;

  /// Estado vazio para busca
  const EmptyStateWidget.search({
    super.key,
    required String query,
    this.onAction,
    this.actionText = 'Limpar busca',
    this.actionColor,
  })  : icon = Icons.search_off,
        title = 'Nenhum resultado encontrado',
        description = 'Não encontramos resultados para "$query". Tente outros termos.',
        actionIcon = Icons.clear,
        iconColor = AppColors.textMuted,
        type = EmptyStateType.search,
        imageUrl = null,
        imageAsset = null,
        iconSize = 80;

  /// Estado vazio personalizado com imagem
  const EmptyStateWidget.withImage({
    super.key,
    required this.title,
    required this.description,
    this.imageAsset,
    this.imageUrl,
    this.onAction,
    this.actionText,
    this.actionIcon,
    this.actionColor,
  })  : icon = Icons.help_outline,
        iconColor = null,
        type = EmptyStateType.standard,
        iconSize = 80;

  /// Estado vazio compacto (para uso em cards pequenos)
  const EmptyStateWidget.compact({
    super.key,
    required this.title,
    this.description = '',
    this.onAction,
    this.actionText,
    this.actionColor,
  })  : icon = Icons.inbox_outlined,
        actionIcon = null,
        iconColor = AppColors.textMuted,
        type = EmptyStateType.compact,
        imageUrl = null,
        imageAsset = null,
        iconSize = 48;

  @override
  Widget build(BuildContext context) {
    switch (type) {
      case EmptyStateType.compact:
        return _buildCompactState(context);
      case EmptyStateType.search:
      case EmptyStateType.standard:
        return _buildStandardState(context);
    }
  }

  Widget _buildCompactState(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            icon,
            size: iconSize,
            color: iconColor ?? AppColors.textMuted,
          ),
          const SizedBox(height: AppDimensions.marginSmall),
          Text(
            title,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              color: AppColors.textSecondary,
              fontWeight: FontWeight.w600,
            ),
            textAlign: TextAlign.center,
          ),
          if (description.isNotEmpty) ...[
            const SizedBox(height: AppDimensions.marginXSmall),
            Text(
              description,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: AppColors.textMuted,
              ),
              textAlign: TextAlign.center,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ],
          if (onAction != null && actionText != null) ...[
            const SizedBox(height: AppDimensions.marginMedium),
            TextButton(
              onPressed: onAction,
              child: Text(actionText!),
              style: TextButton.styleFrom(
                foregroundColor: actionColor ?? AppColors.primary,
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildStandardState(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppDimensions.paddingXLarge),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            _buildIllustration(),
            const SizedBox(height: AppDimensions.marginLarge),
            Text(
              title,
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                color: AppColors.textPrimary,
                fontWeight: FontWeight.w600,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: AppDimensions.marginMedium),
            Text(
              description,
              style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                color: AppColors.textSecondary,
                height: 1.5,
              ),
              textAlign: TextAlign.center,
              maxLines: 3,
              overflow: TextOverflow.ellipsis,
            ),
            if (onAction != null && actionText != null) ...[
              const SizedBox(height: AppDimensions.marginXLarge),
              _buildActionButton(context),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildIllustration() {
    if (imageAsset != null) {
      return Image.asset(
        imageAsset!,
        width: 120,
        height: 120,
        fit: BoxFit.contain,
      );
    }
    
    if (imageUrl != null) {
      return Image.network(
        imageUrl!,
        width: 120,
        height: 120,
        fit: BoxFit.contain,
        errorBuilder: (context, error, stackTrace) => _buildDefaultIcon(),
      );
    }
    
    return _buildDefaultIcon();
  }

  Widget _buildDefaultIcon() {
    return Container(
      width: 120,
      height: 120,
      decoration: BoxDecoration(
        color: (iconColor ?? AppColors.textMuted).withOpacity(0.1),
        shape: BoxShape.circle,
      ),
      child: Icon(
        icon,
        size: iconSize,
        color: iconColor ?? AppColors.textMuted,
      ),
    );
  }

  Widget _buildActionButton(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      child: ElevatedButton.icon(
        onPressed: onAction,
        icon: actionIcon != null ? Icon(actionIcon!) : const SizedBox.shrink(),
        label: Text(actionText!),
        style: ElevatedButton.styleFrom(
          backgroundColor: actionColor ?? AppColors.primary,
          foregroundColor: AppColors.textOnPrimary,
          padding: const EdgeInsets.symmetric(
            vertical: AppDimensions.paddingMedium,
            horizontal: AppDimensions.paddingLarge,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
          ),
        ),
      ),
    );
  }
}

/// Tipos de estado vazio
enum EmptyStateType {
  /// Estado padrão (grande, para telas principais)
  standard,
  
  /// Estado compacto (pequeno, para cards)
  compact,
  
  /// Estado de busca (com contexto de pesquisa)
  search,
}

/// Extensão para facilitar o uso em listas
extension EmptyStateListExtension<T> on List<T> {
  /// Retorna um widget de estado vazio se a lista estiver vazia
  Widget emptyState({
    required Widget emptyWidget,
    required Widget Function(List<T>) builder,
  }) {
    return isEmpty ? emptyWidget : builder(this);
  }
}

/// Extensão para widgets com estado vazio
extension EmptyStateExtension on Widget {
  /// Envolve o widget com tratamento de estado vazio
  Widget withEmptyState({
    required bool isEmpty,
    required EmptyStateWidget emptyState,
  }) {
    return isEmpty ? emptyState : this;
  }
}