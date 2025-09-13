// lib/features/stores/presentation/widgets/store_card.dart
// 🏪 Store Card - Widget de card para exibir informações das lojas parceiras

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:cached_network_image/cached_network_image.dart';

import '../../domain/entities/store.dart';
import '../providers/stores_provider.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/utils/currency_utils.dart';

/// Widget de card para exibir informações das lojas parceiras
/// 
/// Mostra logo, nome, categoria, cashback e ações disponíveis.
/// Suporta diferentes layouts (grid/list) e estados (favorito, novo).
class StoreCard extends ConsumerWidget {
  /// Dados da loja
  final Store store;
  
  /// Callback ao tocar no card
  final VoidCallback? onTap;
  
  /// Callback para ação de favorito
  final VoidCallback? onFavoriteTap;
  
  /// Callback para ver detalhes
  final VoidCallback? onDetailsTap;
  
  /// Callback para visitar loja
  final VoidCallback? onVisitStoreTap;
  
  /// Estilo do card (compacto para grid, expandido para lista)
  final StoreCardStyle style;
  
  /// Se deve mostrar botões de ação
  final bool showActions;
  
  /// Se deve mostrar informações de saldo
  final bool showBalance;
  
  /// Saldo disponível na loja (opcional)
  final double? availableBalance;
  
  /// Total recebido de cashback da loja (opcional)
  final double? totalReceived;
  
  /// Total usado na loja (opcional)
  final double? totalUsed;

  const StoreCard({
    super.key,
    required this.store,
    this.onTap,
    this.onFavoriteTap,
    this.onDetailsTap,
    this.onVisitStoreTap,
    this.style = StoreCardStyle.compact,
    this.showActions = true,
    this.showBalance = false,
    this.availableBalance,
    this.totalReceived,
    this.totalUsed,
  });

  /// Construtor para card compacto (lista/grid)
  const StoreCard.compact({
    super.key,
    required this.store,
    this.onTap,
    this.onFavoriteTap,
    this.onDetailsTap,
    this.onVisitStoreTap,
  })  : style = StoreCardStyle.compact,
        showActions = false,
        showBalance = false,
        availableBalance = null,
        totalReceived = null,
        totalUsed = null;

  /// Construtor para card com informações de saldo
  const StoreCard.withBalance({
    super.key,
    required this.store,
    this.onTap,
    this.onFavoriteTap,
    this.onDetailsTap,
    this.onVisitStoreTap,
    this.availableBalance,
    this.totalReceived,
    this.totalUsed,
  })  : style = StoreCardStyle.expanded,
        showActions = true,
        showBalance = true;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
        child: Container(
          padding: const EdgeInsets.all(AppDimensions.paddingMedium),
          child: style == StoreCardStyle.compact
              ? _buildCompactContent(context, ref)
              : _buildExpandedContent(context, ref),
        ),
      ),
    );
  }

  /// Constrói o conteúdo compacto do card
  Widget _buildCompactContent(BuildContext context, WidgetRef ref) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header com logo e favorito
        Row(
          children: [
            _buildStoreLogo(),
            const Spacer(),
            _buildFavoriteButton(ref),
          ],
        ),
        
        const SizedBox(height: AppDimensions.spacingSmall),
        
        // Nome da loja
        Text(
          store.name,
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
            color: AppColors.textPrimary,
          ),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        
        const SizedBox(height: AppDimensions.spacingXSmall),
        
        // Categoria
        Text(
          store.category.name,
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
            color: AppColors.textSecondary,
          ),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        
        const SizedBox(height: AppDimensions.spacingSmall),
        
        // Cashback destaque
        _buildCashbackHighlight(context),
        
        // Badge "Nova" se aplicável
        if (store.isNew) ...[
          const SizedBox(height: AppDimensions.spacingXSmall),
          _buildNewBadge(context),
        ],
      ],
    );
  }

  /// Constrói o conteúdo expandido do card
  Widget _buildExpandedContent(BuildContext context, WidgetRef ref) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header com logo, info e favorito
        Row(
          children: [
            _buildStoreLogo(),
            const SizedBox(width: AppDimensions.spacingMedium),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Nome e badge "Nova"
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          store.name,
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w600,
                            color: AppColors.textPrimary,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      if (store.isNew) ...[
                        const SizedBox(width: AppDimensions.spacingXSmall),
                        _buildNewBadge(context),
                      ],
                    ],
                  ),
                  
                  const SizedBox(height: AppDimensions.spacingXSmall),
                  
                  // Categoria
                  Text(
                    store.category.name,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
            _buildFavoriteButton(ref),
          ],
        ),
        
        const SizedBox(height: AppDimensions.spacingMedium),
        
        // Cashback destaque
        _buildCashbackHighlight(context),
        
        // Informações de saldo se habilitado
        if (showBalance) ...[
          const SizedBox(height: AppDimensions.spacingMedium),
          _buildBalanceInfo(context),
        ],
        
        // Botões de ação se habilitado
        if (showActions) ...[
          const SizedBox(height: AppDimensions.spacingMedium),
          _buildActionButtons(context),
        ],
      ],
    );
  }

  /// Constrói o logo da loja
  Widget _buildStoreLogo() {
    return Container(
      width: 48,
      height: 48,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
        color: AppColors.surface,
        border: Border.all(
          color: AppColors.border,
          width: 1,
        ),
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
        child: store.logoUrl != null && store.logoUrl!.isNotEmpty
            ? CachedNetworkImage(
                imageUrl: store.logoUrl!,
                width: 48,
                height: 48,
                fit: BoxFit.cover,
                placeholder: (context, url) => _buildLogoPlaceholder(),
                errorWidget: (context, url, error) => _buildLogoPlaceholder(),
              )
            : _buildLogoPlaceholder(),
      ),
    );
  }

  /// Constrói o placeholder do logo
  Widget _buildLogoPlaceholder() {
    return Container(
      width: 48,
      height: 48,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            AppColors.primary.withOpacity(0.1),
            AppColors.primary.withOpacity(0.2),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Center(
        child: Text(
          store.name.isNotEmpty ? store.name[0].toUpperCase() : '?',
          style: TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.bold,
            color: AppColors.primary,
          ),
        ),
      ),
    );
  }

  /// Constrói o botão de favorito
  Widget _buildFavoriteButton(WidgetRef ref) {
    return IconButton(
      onPressed: () {
        ref.read(storesNotifierProvider.notifier).toggleFavorite(store.id);
        onFavoriteTap?.call();
      },
      icon: Icon(
        store.isFavorite ? Icons.favorite : Icons.favorite_border,
        color: store.isFavorite ? AppColors.error : AppColors.textSecondary,
        size: 20,
      ),
      constraints: const BoxConstraints(
        minWidth: 32,
        minHeight: 32,
      ),
      padding: EdgeInsets.zero,
    );
  }

  /// Constrói o destaque do cashback
  Widget _buildCashbackHighlight(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: AppDimensions.paddingSmall,
        vertical: AppDimensions.paddingXSmall,
      ),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            AppColors.success.withOpacity(0.1),
            AppColors.success.withOpacity(0.05),
          ],
          begin: Alignment.centerLeft,
          end: Alignment.centerRight,
        ),
        borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
        border: Border.all(
          color: AppColors.success.withOpacity(0.2),
          width: 1,
        ),
      ),
      child: Row(
        children: [
          Icon(
            Icons.monetization_on,
            color: AppColors.success,
            size: 16,
          ),
          const SizedBox(width: AppDimensions.spacingXSmall),
          Text(
            'Você ganha ${store.cashbackPercentage.toStringAsFixed(1)}% de volta',
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: AppColors.success,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  /// Constrói a badge "Nova"
  Widget _buildNewBadge(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: 6,
        vertical: 2,
      ),
      decoration: BoxDecoration(
        color: AppColors.primary,
        borderRadius: BorderRadius.circular(AppDimensions.radiusXSmall),
      ),
      child: Text(
        'NOVA',
        style: Theme.of(context).textTheme.labelSmall?.copyWith(
          color: AppColors.onPrimary,
          fontWeight: FontWeight.bold,
          fontSize: 10,
        ),
      ),
    );
  }

  /// Constrói as informações de saldo
  Widget _buildBalanceInfo(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingSmall),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
        border: Border.all(
          color: AppColors.border,
          width: 1,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Você pode usar:',
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: AppDimensions.spacingXSmall),
          Text(
            CurrencyUtils.formatToCurrency(availableBalance ?? 0),
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
              color: AppColors.success,
              fontWeight: FontWeight.bold,
            ),
          ),
          if (totalReceived != null || totalUsed != null) ...[
            const SizedBox(height: AppDimensions.spacingXSmall),
            Row(
              children: [
                if (totalReceived != null) ...[
                  Text(
                    'Total recebido',
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const Spacer(),
                  Text(
                    CurrencyUtils.formatToCurrency(totalReceived!),
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: AppColors.textPrimary,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ],
            ),
            if (totalUsed != null) ...[
              const SizedBox(height: AppDimensions.spacingXSmall),
              Row(
                children: [
                  Text(
                    'Já usado',
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const Spacer(),
                  Text(
                    CurrencyUtils.formatToCurrency(totalUsed!),
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: AppColors.textPrimary,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ],
          ],
        ],
      ),
    );
  }

  /// Constrói os botões de ação
  Widget _buildActionButtons(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: CustomButton.outlined(
            text: 'Ver Detalhes',
            onPressed: onDetailsTap,
            size: ButtonSize.small,
          ),
        ),
        const SizedBox(width: AppDimensions.spacingSmall),
        Expanded(
          child: CustomButton.primary(
            text: 'Visitar Loja',
            onPressed: onVisitStoreTap,
            size: ButtonSize.small,
          ),
        ),
      ],
    );
  }
}

/// Estilo do card da loja
enum StoreCardStyle {
  /// Card compacto para grid ou lista simples
  compact,
  
  /// Card expandido com mais informações
  expanded,
}