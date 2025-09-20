// lib/features/dashboard/presentation/widgets/recent_transactions_list.dart
// 📋 Recent Transactions List - Lista de transações recentes do cashback

import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:cached_network_image/cached_network_image.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/utils/currency_utils.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../domain/entities/transaction_summary.dart';

/// Enum para status das transações
enum TransactionStatus {
  confirmed('Confirmado', AppColors.success, '✅'),
  pending('Aguardando', AppColors.warning, '⏳'),
  cancelled('Cancelado', AppColors.error, '❌'),
  released('Liberado', AppColors.success, '💰');

  const TransactionStatus(this.label, this.color, this.icon);
  final String label;
  final Color color;
  final String icon;
}

/// Widget para lista de transações recentes
class RecentTransactionsList extends StatelessWidget {
  /// Lista de transações
  final List<TransactionSummary> transactions;
  
  /// Se está carregando
  final bool isLoading;
  
  /// Callback ao tocar em uma transação
  final ValueChanged<TransactionSummary>? onTransactionTap;
  
  /// Callback para ver todas as transações
  final VoidCallback? onViewAll;
  
  /// Se deve mostrar animação
  final bool showAnimation;
  
  /// Número máximo de itens a exibir
  final int maxItems;

  const RecentTransactionsList({
    super.key,
    required this.transactions,
    this.isLoading = false,
    this.onTransactionTap,
    this.onViewAll,
    this.showAnimation = true,
    this.maxItems = 5,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildHeader(context),
          if (isLoading)
            _buildLoadingState()
          else if (transactions.isEmpty)
            _buildEmptyState()
          else
            _buildTransactionsList(),
        ],
      ),
    );
  }

  Widget _buildHeader(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            'Suas Últimas Compras',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: AppColors.textPrimary,
            ),
          ),
          if (onViewAll != null && transactions.isNotEmpty)
            GestureDetector(
              onTap: onViewAll,
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    'Ver Todas',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: AppColors.primary,
                    ),
                  ),
                  const SizedBox(width: 4),
                  Icon(
                    Icons.arrow_forward_ios,
                    size: 12,
                    color: AppColors.primary,
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildLoadingState() {
    return Column(
      children: List.generate(3, (index) => _buildShimmerItem(index)),
    );
  }

  Widget _buildShimmerItem(int index) {
    return Container(
      margin: const EdgeInsets.symmetric(
        horizontal: AppDimensions.paddingMedium,
        vertical: 8,
      ),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: AppColors.gray200,
              borderRadius: BorderRadius.circular(24),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  height: 16,
                  width: double.infinity,
                  decoration: BoxDecoration(
                    color: AppColors.gray200,
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
                const SizedBox(height: 8),
                Container(
                  height: 12,
                  width: 100,
                  decoration: BoxDecoration(
                    color: AppColors.gray200,
                    borderRadius: BorderRadius.circular(6),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    ).animate(onPlay: (controller) => controller.repeat()).shimmer(
      duration: 1000.ms,
      color: Colors.white.withOpacity(0.5),
    );
  }

  Widget _buildEmptyState() {
    return Padding(
      padding: const EdgeInsets.all(AppDimensions.paddingLarge),
      child: Center(
        child: Column(
          children: [
            const Text('🛍️', style: TextStyle(fontSize: 48)),
            const SizedBox(height: 16),
            Text(
              'Nenhuma transação ainda',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Suas compras nas lojas parceiras aparecerão aqui',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 14,
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTransactionsList() {
    final displayTransactions = transactions.take(maxItems).toList();
    
    return Column(
      children: [
        ...displayTransactions.asMap().entries.map((entry) {
          final index = entry.key;
          final transaction = entry.value;
          
          Widget item = _TransactionListItem(
            transaction: transaction,
            onTap: onTransactionTap != null 
                ? () => onTransactionTap!(transaction)
                : null,
          );
          
          if (showAnimation) {
            item = item
                .animate(delay: (index * 100).ms)
                .fadeIn(duration: 300.ms)
                .slideX(begin: 0.2, end: 0, duration: 400.ms);
          }
          
          return item;
        }),
        if (transactions.length > maxItems)
          Padding(
            padding: const EdgeInsets.all(AppDimensions.paddingMedium),
            child: CustomButton(
              text: 'Carregar mais',
              onPressed: onViewAll,
              type: ButtonType.outlined,
              size: ButtonSize.small,
            ),
          ),
        const SizedBox(height: AppDimensions.spacingSmall),
      ],
    );
  }
}

/// Widget para item individual da transação
class _TransactionListItem extends StatelessWidget {
  final TransactionSummary transaction;
  final VoidCallback? onTap;

  const _TransactionListItem({
    required this.transaction,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final status = _getTransactionStatus(transaction.status);
    
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(
            horizontal: AppDimensions.paddingMedium,
            vertical: 12,
          ),
          child: Row(
            children: [
              _buildStoreAvatar(),
              const SizedBox(width: 12),
              Expanded(child: _buildTransactionInfo(status)),
              _buildTransactionAmount(status),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStoreAvatar() {
    return Container(
      width: 48,
      height: 48,
      decoration: BoxDecoration(
        color: AppColors.primary,
        borderRadius: BorderRadius.circular(24),
      ),
      child: transaction.storeLogo?.isNotEmpty == true
          ? ClipRRect(
              borderRadius: BorderRadius.circular(24),
              child: CachedNetworkImage(
                imageUrl: transaction.storeLogo!,
                fit: BoxFit.cover,
                placeholder: (context, url) => _buildDefaultAvatar(),
                errorWidget: (context, url, error) => _buildDefaultAvatar(),
              ),
            )
          : _buildDefaultAvatar(),
    );
  }

  Widget _buildDefaultAvatar() {
    return Center(
      child: Text(
        transaction.storeName.isNotEmpty 
            ? transaction.storeName[0].toUpperCase()
            : 'L',
        style: const TextStyle(
          color: Colors.white,
          fontSize: 18,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }

  Widget _buildTransactionInfo(TransactionStatus status) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                transaction.storeName,
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: AppColors.textPrimary,
                ),
                overflow: TextOverflow.ellipsis,
              ),
            ),
            const SizedBox(width: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
              decoration: BoxDecoration(
                color: status.color.withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                status.label,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: status.color,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 4),
        Row(
          children: [
            Icon(
              Icons.calendar_today,
              size: 12,
              color: AppColors.textSecondary,
            ),
            const SizedBox(width: 4),
            Text(
              AppFormatters.formatDate(transaction.date),
              style: TextStyle(
                fontSize: 12,
                color: AppColors.textSecondary,
              ),
            ),
            const SizedBox(width: 12),
            Text(
              'Você pagou: ${CurrencyUtils.format(transaction.purchaseAmount)}',
              style: TextStyle(
                fontSize: 12,
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
        const SizedBox(height: 2),
        Text(
          'Cashback ganho: ${CurrencyUtils.format(transaction.cashbackAmount)}',
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: AppColors.success,
          ),
        ),
      ],
    );
  }

  Widget _buildTransactionAmount(TransactionStatus status) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(status.icon, style: const TextStyle(fontSize: 12)),
            const SizedBox(width: 4),
            if (onTap != null)
              Icon(
                Icons.arrow_forward_ios,
                size: 12,
                color: AppColors.textSecondary,
              ),
          ],
        ),
      ],
    );
  }

  TransactionStatus _getTransactionStatus(String status) {
    switch (status.toLowerCase()) {
      case 'confirmado':
      case 'aprovado':
        return TransactionStatus.confirmed;
      case 'aguardando':
      case 'pendente':
        return TransactionStatus.pending;
      case 'cancelado':
        return TransactionStatus.cancelled;
      case 'liberado':
        return TransactionStatus.released;
      default:
        return TransactionStatus.pending;
    }
  }
}

/// Factory methods para diferentes usos
extension RecentTransactionsListFactory on RecentTransactionsList {
  /// Cria lista com dados de exemplo para demonstração
  static RecentTransactionsList demo({
    ValueChanged<TransactionSummary>? onTransactionTap,
    VoidCallback? onViewAll,
  }) {
    final now = DateTime.now();
    final transactions = [
      TransactionSummary(
        id: '1',
        storeName: 'Kaua Matheus da Silva Lopes',
        storeLogo: null,
        date: now.subtract(const Duration(days: 1)),
        purchaseAmount: 100.0,
        cashbackAmount: 5.0,
        status: 'confirmado',
      ),
      TransactionSummary(
        id: '2',
        storeName: 'Loja Exemplo Dois',
        storeLogo: null,
        date: now.subtract(const Duration(days: 3)),
        purchaseAmount: 50.0,
        cashbackAmount: 2.5,
        status: 'aguardando',
      ),
    ];

    return RecentTransactionsList(
      transactions: transactions,
      onTransactionTap: onTransactionTap,
      onViewAll: onViewAll,
    );
  }
}