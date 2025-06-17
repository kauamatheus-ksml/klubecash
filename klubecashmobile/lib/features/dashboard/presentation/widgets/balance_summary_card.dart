// lib/features/dashboard/presentation/widgets/balance_summary_card.dart
// 💰 Balance Summary Card - Card de resumo dos saldos de cashback

import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/utils/currency_utils.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../domain/entities/cashback_summary.dart';

/// Widget card de resumo de saldos
class BalanceSummaryCard extends StatelessWidget {
  /// Dados do resumo de cashback
  final CashbackSummary? summary;
  
  /// Se está carregando
  final bool isLoading;
  
  /// Callback para ver detalhes do saldo
  final VoidCallback? onViewBalance;
  
  /// Callback para usar saldo
  final VoidCallback? onUseBalance;
  
  /// Se deve mostrar animação
  final bool showAnimation;

  const BalanceSummaryCard({
    super.key,
    this.summary,
    this.isLoading = false,
    this.onViewBalance,
    this.onUseBalance,
    this.showAnimation = true,
  });

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return _buildLoadingState();
    }

    Widget content = Column(
      children: [
        _buildAvailableBalanceCard(),
        const SizedBox(height: AppDimensions.spacingMedium),
        _buildPendingBalanceCard(),
        const SizedBox(height: AppDimensions.spacingMedium),
        _buildTotalSavedCard(),
      ],
    );

    if (showAnimation) {
      content = content
          .animate()
          .fadeIn(duration: 400.ms)
          .slideY(begin: 0.2, end: 0, duration: 500.ms);
    }

    return content;
  }

  Widget _buildAvailableBalanceCard() {
    final availableBalance = summary?.availableBalance ?? 0.0;
    
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppDimensions.paddingLarge),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppColors.success, AppColors.successDark],
        ),
        borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
        boxShadow: [
          BoxShadow(
            color: AppColors.success.withOpacity(0.3),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Text(
                  'Seu Saldo para Usar',
                  style: TextStyle(
                    color: AppColors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.2),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Text('💳', style: TextStyle(fontSize: 20)),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'Saldo liberado para usar em compras.',
            style: TextStyle(
              color: AppColors.white.withOpacity(0.9),
              fontSize: 14,
            ),
          ),
          const SizedBox(height: AppDimensions.spacingMedium),
          Row(
            crossAxisAlignment: CrossAxisAlignment.baseline,
            textBaseline: TextBaseline.alphabetic,
            children: [
              Text(
                'R\$',
                style: TextStyle(
                  color: AppColors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(width: 4),
              Text(
                CurrencyUtils.formatNumber(availableBalance),
                style: TextStyle(
                  color: AppColors.white,
                  fontSize: 32,
                  fontWeight: FontWeight.w800,
                  height: 1.0,
                ),
              ),
            ],
          ),
          if (onUseBalance != null) ...[
            const SizedBox(height: AppDimensions.spacingMedium),
            CustomButton(
              text: 'Ver Como Usar',
              onPressed: onUseBalance,
              type: ButtonType.secondary,
              size: ButtonSize.small,
              backgroundColor: Colors.white.withOpacity(0.9),
              textColor: AppColors.success,
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildPendingBalanceCard() {
    final pendingBalance = summary?.pendingBalance ?? 0.0;
    
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
        border: Border.all(color: AppColors.warning.withOpacity(0.3)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: AppColors.warning.withOpacity(0.1),
              borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
            ),
            child: const Text('⏳', style: TextStyle(fontSize: 24)),
          ),
          const SizedBox(width: AppDimensions.spacingMedium),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Aguardando Liberação',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: AppColors.textPrimary,
                  ),
                ),
                Text(
                  'Cashback que ainda vai ser liberado.',
                  style: TextStyle(
                    fontSize: 12,
                    color: AppColors.textSecondary,
                  ),
                ),
                if (pendingBalance > 0)
                  Text(
                    '✨ Em breve na sua conta!',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w500,
                      color: AppColors.warning,
                    ),
                  ),
              ],
            ),
          ),
          Text(
            CurrencyUtils.format(pendingBalance),
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: AppColors.warning,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTotalSavedCard() {
    final totalBalance = summary?.totalBalance ?? 0.0;
    final availableBalance = summary?.availableBalance ?? 0.0;
    final usedBalance = totalBalance - availableBalance;
    
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppDimensions.paddingLarge),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppColors.info, AppColors.infoDark],
        ),
        borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
        boxShadow: [
          BoxShadow(
            color: AppColors.info.withOpacity(0.3),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Text(
                  'Total Economizado',
                  style: TextStyle(
                    color: AppColors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.2),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Text('📈', style: TextStyle(fontSize: 20)),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'Quanto você já economizou com cashback.',
            style: TextStyle(
              color: AppColors.white.withOpacity(0.9),
              fontSize: 14,
            ),
          ),
          const SizedBox(height: AppDimensions.spacingMedium),
          Row(
            crossAxisAlignment: CrossAxisAlignment.baseline,
            textBaseline: TextBaseline.alphabetic,
            children: [
              Text(
                'R\$',
                style: TextStyle(
                  color: AppColors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(width: 4),
              Text(
                CurrencyUtils.formatNumber(totalBalance),
                style: TextStyle(
                  color: AppColors.white,
                  fontSize: 32,
                  fontWeight: FontWeight.w800,
                  height: 1.0,
                ),
              ),
            ],
          ),
          const SizedBox(height: AppDimensions.spacingMedium),
          Row(
            children: [
              Expanded(
                child: _buildBreakdownItem(
                  '✓ Já usei:',
                  CurrencyUtils.format(usedBalance),
                ),
              ),
              Expanded(
                child: _buildBreakdownItem(
                  '📋 Disponível:',
                  CurrencyUtils.format(availableBalance),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildBreakdownItem(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            color: AppColors.white.withOpacity(0.8),
            fontSize: 12,
            fontWeight: FontWeight.w500,
          ),
        ),
        Text(
          value,
          style: TextStyle(
            color: AppColors.white,
            fontSize: 14,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }

  Widget _buildLoadingState() {
    return Column(
      children: [
        _buildShimmerCard(height: 150),
        const SizedBox(height: AppDimensions.spacingMedium),
        _buildShimmerCard(height: 80),
        const SizedBox(height: AppDimensions.spacingMedium),
        _buildShimmerCard(height: 150),
      ],
    );
  }

  Widget _buildShimmerCard({required double height}) {
    return Container(
      width: double.infinity,
      height: height,
      decoration: BoxDecoration(
        color: AppColors.gray200,
        borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
      ),
    ).animate(onPlay: (controller) => controller.repeat()).shimmer(
      duration: 1000.ms,
      color: Colors.white.withOpacity(0.5),
    );
  }
}