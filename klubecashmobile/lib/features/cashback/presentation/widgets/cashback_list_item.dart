// lib/features/cashback/presentation/widgets/cashback_list_item.dart
// Widget para exibir cada item da lista de transações de cashback

import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:cached_network_image/cached_network_image.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/utils/currency_utils.dart';
import '../../../../core/utils/date_utils.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../domain/entities/cashback_transaction.dart';
import 'cashback_status_chip.dart';

/// Widget para exibir cada item da lista de transações de cashback
class CashbackListItem extends StatelessWidget {
  /// Transação de cashback a ser exibida
  final CashbackTransaction transaction;
  
  /// Callback ao tocar no item
  final VoidCallback? onTap;
  
  /// Callback ao tocar em "Ver detalhes"
  final VoidCallback? onViewDetails;
  
  /// Se deve mostrar animação
  final bool showAnimation;
  
  /// Índice para animação staggered
  final int? animationIndex;

  const CashbackListItem({
    super.key,
    required this.transaction,
    this.onTap,
    this.onViewDetails,
    this.showAnimation = true,
    this.animationIndex,
  });

  @override
  Widget build(BuildContext context) {
    Widget item = Container(
      margin: const EdgeInsets.only(bottom: AppDimensions.spacingMedium),
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
        border: Border.all(
          color: AppColors.gray200,
          width: 1,
        ),
        boxShadow: [
          BoxShadow(
            color: AppColors.black.withOpacity(0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildHeader(),
          const SizedBox(height: AppDimensions.spacingMedium),
          _buildTransactionInfo(),
          const SizedBox(height: AppDimensions.spacingMedium),
          _buildDetailsButton(),
        ],
      ),
    );

    if (onTap != null) {
      item = Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
          child: item,
        ),
      );
    }

    if (showAnimation) {
      final delay = animationIndex != null ? (animationIndex! * 100) : 0;
      return item
          .animate(delay: Duration(milliseconds: delay))
          .fadeIn(duration: 300.ms)
          .slideY(begin: 0.3, end: 0, duration: 400.ms);
    }

    return item;
  }

  /// Constrói o cabeçalho com informações da loja e status
  Widget _buildHeader() {
    return Row(
      children: [
        _buildStoreAvatar(),
        const SizedBox(width: AppDimensions.spacingMedium),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      transaction.storeName ?? 'Loja não informada',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        color: AppColors.textPrimary,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  const SizedBox(width: AppDimensions.spacingSmall),
                  CashbackStatusChip(status: transaction.status),
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
                    AppDateUtils.formatDateTime(transaction.transactionDate),
                    style: const TextStyle(
                      fontSize: 14,
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

  /// Constrói o avatar da loja
  Widget _buildStoreAvatar() {
    return Container(
      width: 48,
      height: 48,
      decoration: BoxDecoration(
        color: AppColors.gray100,
        borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
        border: Border.all(
          color: AppColors.gray200,
          width: 1,
        ),
      ),
      child: transaction.storeLogo != null
          ? ClipRRect(
              borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
              child: CachedNetworkImage(
                imageUrl: transaction.storeLogo!,
                fit: BoxFit.cover,
                placeholder: (context, url) => _buildStoreInitial(),
                errorWidget: (context, url, error) => _buildStoreInitial(),
              ),
            )
          : _buildStoreInitial(),
    );
  }

  /// Constrói o avatar com inicial da loja
  Widget _buildStoreInitial() {
    final storeName = transaction.storeName ?? 'L';
    final initial = storeName.isNotEmpty ? storeName[0].toUpperCase() : 'L';
    
    return Center(
      child: Text(
        initial,
        style: const TextStyle(
          fontSize: 20,
          fontWeight: FontWeight.w600,
          color: AppColors.primary,
        ),
      ),
    );
  }

  /// Constrói as informações da transação
  Widget _buildTransactionInfo() {
    return Column(
      children: [
        _buildTransactionRow(
          'Valor da compra',
          CurrencyUtils.formatCurrency(transaction.totalAmount),
          textColor: AppColors.textPrimary,
        ),
        if (transaction.balanceUsed != null && transaction.balanceUsed! > 0) ...[
          const SizedBox(height: 8),
          _buildTransactionRow(
            'Saldo usado',
            '- ${CurrencyUtils.formatCurrency(transaction.balanceUsed!)}',
            textColor: AppColors.error,
          ),
        ],
        const SizedBox(height: 8),
        _buildTransactionRow(
          'Você pagou',
          CurrencyUtils.formatCurrency(_calculateAmountPaid()),
          textColor: AppColors.textPrimary,
        ),
        const SizedBox(height: 8),
        _buildTransactionRow(
          'Cashback ganho',
          CurrencyUtils.formatCurrency(transaction.cashbackAmount),
          textColor: AppColors.success,
          isCashback: true,
        ),
      ],
    );
  }

  /// Constrói uma linha de informação da transação
  Widget _buildTransactionRow(
    String label,
    String value, {
    Color? textColor,
    bool isCashback = false,
  }) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontSize: 14,
            color: AppColors.textSecondary,
          ),
        ),
        Row(
          children: [
            if (isCashback)
              Container(
                margin: const EdgeInsets.only(right: 4),
                child: Text(
                  '+',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: textColor ?? AppColors.textPrimary,
                  ),
                ),
              ),
            Text(
              value,
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: textColor ?? AppColors.textPrimary,
              ),
            ),
          ],
        ),
      ],
    );
  }

  /// Constrói o botão de detalhes
  Widget _buildDetailsButton() {
    return SizedBox(
      width: double.infinity,
      child: CustomButton(
        text: 'Ver detalhes',
        onPressed: onViewDetails,
        type: ButtonType.outline,
        size: ButtonSize.small,
        icon: Icons.arrow_forward,
        iconPosition: IconPosition.right,
      ),
    );
  }

  /// Calcula o valor pago pelo cliente
  double _calculateAmountPaid() {
    double amountPaid = transaction.totalAmount;
    
    if (transaction.balanceUsed != null && transaction.balanceUsed! > 0) {
      amountPaid -= transaction.balanceUsed!;
    }
    
    return amountPaid > 0 ? amountPaid : 0;
  }
}

/// Widget para lista de transações de cashback com animações staggered
class CashbackTransactionsList extends StatelessWidget {
  /// Lista de transações
  final List<CashbackTransaction> transactions;
  
  /// Se está carregando
  final bool isLoading;
  
  /// Callback ao tocar em uma transação
  final ValueChanged<CashbackTransaction>? onTransactionTap;
  
  /// Callback ao tocar em "Ver detalhes"
  final ValueChanged<CashbackTransaction>? onViewDetails;
  
  /// Se deve mostrar animações
  final bool showAnimations;

  const CashbackTransactionsList({
    super.key,
    required this.transactions,
    this.isLoading = false,
    this.onTransactionTap,
    this.onViewDetails,
    this.showAnimations = true,
  });

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return const Center(
        child: CircularProgressIndicator(),
      );
    }

    if (transactions.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.receipt_long_outlined,
              size: 64,
              color: AppColors.gray400,
            ),
            const SizedBox(height: AppDimensions.spacingMedium),
            Text(
              'Nenhuma transação encontrada',
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w500,
                color: AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Faça sua primeira compra em uma loja parceira\npara começar a acumular cashback!',
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 14,
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
      );
    }

    return ListView.builder(
      physics: const NeverScrollableScrollPhysics(),
      shrinkWrap: true,
      itemCount: transactions.length,
      itemBuilder: (context, index) {
        final transaction = transactions[index];
        
        return CashbackListItem(
          transaction: transaction,
          onTap: onTransactionTap != null 
              ? () => onTransactionTap!(transaction)
              : null,
          onViewDetails: onViewDetails != null 
              ? () => onViewDetails!(transaction)
              : null,
          showAnimation: showAnimations,
          animationIndex: showAnimations ? index : null,
        );
      },
    );
  }
}

/// Widget para item de transação simplificado (para uso em listas compactas)
class CashbackListItemCompact extends StatelessWidget {
  /// Transação de cashback
  final CashbackTransaction transaction;
  
  /// Callback ao tocar no item
  final VoidCallback? onTap;

  const CashbackListItemCompact({
    super.key,
    required this.transaction,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
        child: Container(
          padding: const EdgeInsets.all(AppDimensions.paddingMedium),
          margin: const EdgeInsets.only(bottom: AppDimensions.spacingSmall),
          decoration: BoxDecoration(
            color: AppColors.white,
            borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
            border: Border.all(color: AppColors.gray200),
          ),
          child: Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: AppColors.gray100,
                  borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
                ),
                child: Center(
                  child: Text(
                    (transaction.storeName ?? 'L')[0].toUpperCase(),
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                      color: AppColors.primary,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: AppDimensions.spacingMedium),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      transaction.storeName ?? 'Loja não informada',
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: AppColors.textPrimary,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 2),
                    Text(
                      AppDateUtils.formatDate(transaction.transactionDate),
                      style: const TextStyle(
                        fontSize: 12,
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    '+ ${CurrencyUtils.formatCurrency(transaction.cashbackAmount)}',
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: AppColors.success,
                    ),
                  ),
                  const SizedBox(height: 2),
                  CashbackStatusChip(
                    status: transaction.status,
                    isCompact: true,
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}