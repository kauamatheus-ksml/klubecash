// lib/features/cashback/presentation/widgets/cashback_statistics_card.dart
// Widget para exibir cartões de estatísticas de cashback

import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/utils/currency_utils.dart';
import '../../../../core/widgets/custom_button.dart';

/// Enum para tipos de estatística de cashback
enum CashbackStatisticType {
  /// Total economizado (valor acumulado)
  totalSaved,
  /// Saldo disponível
  availableBalance,
  /// Saldo pendente
  pendingBalance,
  /// Total de transações
  totalTransactions,
  /// Cashback médio por transação
  averageCashback,
  /// Lojas utilizadas
  storesUsed,
  /// Meta mensal
  monthlyGoal,
  /// Economia este mês
  monthlySavings,
}

/// Widget principal para cartão de estatística
class CashbackStatisticsCard extends StatelessWidget {
  /// Tipo da estatística
  final CashbackStatisticType type;
  
  /// Valor principal da estatística
  final double value;
  
  /// Valor secundário (opcional)
  final double? secondaryValue;
  
  /// Título personalizado (opcional)
  final String? customTitle;
  
  /// Descrição personalizada (opcional)
  final String? customDescription;
  
  /// Ícone personalizado (opcional)
  final IconData? customIcon;
  
  /// Emoji personalizado (opcional)
  final String? customEmoji;
  
  /// Se deve mostrar como moeda
  final bool isMonetary;
  
  /// Se deve exibir em modo compacto
  final bool isCompact;
  
  /// Callback ao tocar no cartão
  final VoidCallback? onTap;
  
  /// Se deve mostrar animação
  final bool showAnimation;
  
  /// Delay da animação (para efeito staggered)
  final int animationDelay;

  const CashbackStatisticsCard({
    super.key,
    required this.type,
    required this.value,
    this.secondaryValue,
    this.customTitle,
    this.customDescription,
    this.customIcon,
    this.customEmoji,
    this.isMonetary = true,
    this.isCompact = false,
    this.onTap,
    this.showAnimation = true,
    this.animationDelay = 0,
  });

  @override
  Widget build(BuildContext context) {
    final config = _getStatisticConfiguration();
    
    Widget card = Container(
      width: double.infinity,
      padding: EdgeInsets.all(
        isCompact ? AppDimensions.paddingMedium : AppDimensions.paddingLarge,
      ),
      decoration: BoxDecoration(
        gradient: config.gradient,
        borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
        boxShadow: [
          BoxShadow(
            color: config.shadowColor.withOpacity(0.15),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildHeader(config),
          SizedBox(height: isCompact ? 8 : AppDimensions.spacingMedium),
          _buildMainValue(config),
          if (!isCompact && secondaryValue != null) ...[
            const SizedBox(height: AppDimensions.spacingSmall),
            _buildSecondaryValue(config),
          ],
          if (!isCompact && _hasBreakdown()) ...[
            const SizedBox(height: AppDimensions.spacingMedium),
            _buildBreakdown(config),
          ],
        ],
      ),
    );

    if (onTap != null) {
      card = Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
          child: card,
        ),
      );
    }

    if (showAnimation) {
      card = card
          .animate(delay: Duration(milliseconds: animationDelay))
          .fadeIn(duration: 400.ms)
          .slideY(begin: 0.3, end: 0, duration: 500.ms);
    }

    return card;
  }

  Widget _buildHeader(StatisticConfiguration config) {
    return Row(
      children: [
        Container(
          padding: EdgeInsets.all(isCompact ? 8 : 12),
          decoration: BoxDecoration(
            color: Colors.white.withOpacity(0.2),
            borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
          ),
          child: customEmoji != null
              ? Text(
                  customEmoji!,
                  style: TextStyle(fontSize: isCompact ? 20 : 24),
                )
              : Icon(
                  customIcon ?? config.icon,
                  color: Colors.white,
                  size: isCompact ? 20 : 24,
                ),
        ),
        const SizedBox(width: AppDimensions.spacingMedium),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                customTitle ?? config.title,
                style: TextStyle(
                  color: Colors.white,
                  fontSize: isCompact ? 14 : 16,
                  fontWeight: FontWeight.w600,
                ),
              ),
              if (!isCompact)
                Text(
                  customDescription ?? config.description,
                  style: TextStyle(
                    color: Colors.white.withOpacity(0.9),
                    fontSize: 12,
                  ),
                ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildMainValue(StatisticConfiguration config) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.baseline,
      textBaseline: TextBaseline.alphabetic,
      children: [
        if (isMonetary) ...[
          Text(
            'R\$',
            style: TextStyle(
              color: Colors.white,
              fontSize: isCompact ? 14 : 18,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(width: 4),
          Text(
            CurrencyUtils.formatNumber(value),
            style: TextStyle(
              color: Colors.white,
              fontSize: isCompact ? 24 : 32,
              fontWeight: FontWeight.w800,
              height: 1.0,
            ),
          ),
        ] else ...[
          Text(
            _formatNonMonetaryValue(value),
            style: TextStyle(
              color: Colors.white,
              fontSize: isCompact ? 24 : 32,
              fontWeight: FontWeight.w800,
              height: 1.0,
            ),
          ),
          if (config.suffix.isNotEmpty) ...[
            const SizedBox(width: 4),
            Text(
              config.suffix,
              style: TextStyle(
                color: Colors.white.withOpacity(0.8),
                fontSize: isCompact ? 14 : 16,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ],
      ],
    );
  }

  Widget _buildSecondaryValue(StatisticConfiguration config) {
    if (secondaryValue == null) return const SizedBox.shrink();
    
    return Row(
      children: [
        Icon(
          Icons.trending_up,
          color: Colors.white.withOpacity(0.8),
          size: 16,
        ),
        const SizedBox(width: 4),
        Text(
          isMonetary 
              ? CurrencyUtils.formatCurrency(secondaryValue!)
              : _formatNonMonetaryValue(secondaryValue!),
          style: TextStyle(
            color: Colors.white.withOpacity(0.9),
            fontSize: 14,
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(width: 4),
        Text(
          'este mês',
          style: TextStyle(
            color: Colors.white.withOpacity(0.7),
            fontSize: 12,
          ),
        ),
      ],
    );
  }

  Widget _buildBreakdown(StatisticConfiguration config) {
    switch (type) {
      case CashbackStatisticType.totalSaved:
        return _buildSavingsBreakdown();
      case CashbackStatisticType.monthlyGoal:
        return _buildGoalProgress();
      default:
        return const SizedBox.shrink();
    }
  }

  Widget _buildSavingsBreakdown() {
    final used = value * 0.3; // Exemplo: 30% usado
    final available = value - used;
    
    return Column(
      children: [
        Container(
          height: 1,
          margin: const EdgeInsets.symmetric(vertical: 8),
          color: Colors.white.withOpacity(0.3),
        ),
        Row(
          children: [
            Expanded(
              child: _buildBreakdownItem(
                '✓ Já usei',
                CurrencyUtils.formatCurrency(used),
              ),
            ),
            Expanded(
              child: _buildBreakdownItem(
                '📋 Disponível',
                CurrencyUtils.formatCurrency(available),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildGoalProgress() {
    final progress = secondaryValue != null ? secondaryValue! / value : 0.0;
    final progressClamped = progress.clamp(0.0, 1.0);
    
    return Column(
      children: [
        Container(
          height: 1,
          margin: const EdgeInsets.symmetric(vertical: 8),
          color: Colors.white.withOpacity(0.3),
        ),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              'Progresso',
              style: TextStyle(
                color: Colors.white.withOpacity(0.8),
                fontSize: 12,
              ),
            ),
            Text(
              '${(progressClamped * 100).toInt()}%',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 12,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
        const SizedBox(height: 4),
        LinearProgressIndicator(
          value: progressClamped,
          backgroundColor: Colors.white.withOpacity(0.3),
          valueColor: const AlwaysStoppedAnimation<Color>(Colors.white),
        ),
      ],
    );
  }

  Widget _buildBreakdownItem(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            color: Colors.white.withOpacity(0.8),
            fontSize: 11,
          ),
        ),
        Text(
          value,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 14,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }

  StatisticConfiguration _getStatisticConfiguration() {
    switch (type) {
      case CashbackStatisticType.totalSaved:
        return StatisticConfiguration(
          title: 'Total Economizado',
          description: 'Quanto você já economizou com cashback',
          icon: Icons.savings,
          gradient: const LinearGradient(
            colors: [AppColors.info, Color(0xFF2563EB)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          shadowColor: AppColors.info,
        );
        
      case CashbackStatisticType.availableBalance:
        return StatisticConfiguration(
          title: 'Saldo Disponível',
          description: 'Pronto para usar em suas compras',
          icon: Icons.account_balance_wallet,
          gradient: const LinearGradient(
            colors: [AppColors.success, Color(0xFF059669)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          shadowColor: AppColors.success,
        );
        
      case CashbackStatisticType.pendingBalance:
        return StatisticConfiguration(
          title: 'Chegando em Breve',
          description: 'Cashback que ainda vai ser liberado',
          icon: Icons.schedule,
          gradient: const LinearGradient(
            colors: [AppColors.warning, Color(0xFFD97706)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          shadowColor: AppColors.warning,
        );
        
      case CashbackStatisticType.totalTransactions:
        return StatisticConfiguration(
          title: 'Transações',
          description: 'Vezes que você ganhou cashback',
          icon: Icons.receipt_long,
          suffix: 'compras',
          gradient: const LinearGradient(
            colors: [AppColors.primary, AppColors.primaryDark],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          shadowColor: AppColors.primary,
        );
        
      case CashbackStatisticType.averageCashback:
        return StatisticConfiguration(
          title: 'Cashback Médio',
          description: 'Valor médio por transação',
          icon: Icons.trending_up,
          gradient: const LinearGradient(
            colors: [Color(0xFF8B5CF6), Color(0xFF7C3AED)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          shadowColor: const Color(0xFF8B5CF6),
        );
        
      case CashbackStatisticType.storesUsed:
        return StatisticConfiguration(
          title: 'Lojas Parceiras',
          description: 'Lojas onde você já fez compras',
          icon: Icons.store,
          suffix: 'lojas',
          gradient: const LinearGradient(
            colors: [Color(0xFFEC4899), Color(0xFFDB2777)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          shadowColor: const Color(0xFFEC4899),
        );
        
      case CashbackStatisticType.monthlyGoal:
        return StatisticConfiguration(
          title: 'Meta do Mês',
          description: 'Seu objetivo de economia',
          icon: Icons.flag,
          gradient: const LinearGradient(
            colors: [Color(0xFF10B981), Color(0xFF059669)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          shadowColor: const Color(0xFF10B981),
        );
        
      case CashbackStatisticType.monthlySavings:
        return StatisticConfiguration(
          title: 'Este Mês',
          description: 'Economia do mês atual',
          icon: Icons.calendar_today,
          gradient: const LinearGradient(
            colors: [Color(0xFFF59E0B), Color(0xFFD97706)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          shadowColor: const Color(0xFFF59E0B),
        );
    }
  }

  bool _hasBreakdown() {
    return type == CashbackStatisticType.totalSaved || 
           type == CashbackStatisticType.monthlyGoal;
  }

  String _formatNonMonetaryValue(double value) {
    if (value >= 1000) {
      return '${(value / 1000).toStringAsFixed(1)}k';
    }
    return value.toInt().toString();
  }
}

/// Widget para grid de estatísticas
class CashbackStatisticsGrid extends StatelessWidget {
  /// Lista de estatísticas para exibir
  final List<CashbackStatisticItem> statistics;
  
  /// Número de colunas
  final int crossAxisCount;
  
  /// Se deve mostrar animações staggered
  final bool showAnimations;
  
  /// Callback ao tocar em uma estatística
  final Function(CashbackStatisticType)? onStatisticTap;

  const CashbackStatisticsGrid({
    super.key,
    required this.statistics,
    this.crossAxisCount = 2,
    this.showAnimations = true,
    this.onStatisticTap,
  });

  @override
  Widget build(BuildContext context) {
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: crossAxisCount,
        mainAxisSpacing: AppDimensions.spacingMedium,
        crossAxisSpacing: AppDimensions.spacingMedium,
        childAspectRatio: 1.3,
      ),
      itemCount: statistics.length,
      itemBuilder: (context, index) {
        final statistic = statistics[index];
        return CashbackStatisticsCard(
          type: statistic.type,
          value: statistic.value,
          secondaryValue: statistic.secondaryValue,
          customTitle: statistic.customTitle,
          customDescription: statistic.customDescription,
          isMonetary: statistic.isMonetary,
          isCompact: true,
          showAnimation: showAnimations,
          animationDelay: index * 100,
          onTap: onStatisticTap != null 
              ? () => onStatisticTap!(statistic.type)
              : null,
        );
      },
    );
  }
}

/// Widget para lista horizontal de estatísticas
class CashbackStatisticsHorizontalList extends StatelessWidget {
  /// Lista de estatísticas
  final List<CashbackStatisticItem> statistics;
  
  /// Se deve mostrar animações
  final bool showAnimations;
  
  /// Callback ao tocar em uma estatística
  final Function(CashbackStatisticType)? onStatisticTap;

  const CashbackStatisticsHorizontalList({
    super.key,
    required this.statistics,
    this.showAnimations = true,
    this.onStatisticTap,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 160,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: AppDimensions.paddingMedium),
        itemCount: statistics.length,
        separatorBuilder: (_, __) => const SizedBox(width: AppDimensions.spacingMedium),
        itemBuilder: (context, index) {
          final statistic = statistics[index];
          return SizedBox(
            width: 200,
            child: CashbackStatisticsCard(
              type: statistic.type,
              value: statistic.value,
              secondaryValue: statistic.secondaryValue,
              customTitle: statistic.customTitle,
              customDescription: statistic.customDescription,
              isMonetary: statistic.isMonetary,
              isCompact: true,
              showAnimation: showAnimations,
              animationDelay: index * 100,
              onTap: onStatisticTap != null 
                  ? () => onStatisticTap!(statistic.type)
                  : null,
            ),
          );
        },
      ),
    );
  }
}

/// Classe para configuração de estatística
class StatisticConfiguration {
  final String title;
  final String description;
  final IconData icon;
  final String suffix;
  final LinearGradient gradient;
  final Color shadowColor;

  const StatisticConfiguration({
    required this.title,
    required this.description,
    required this.icon,
    this.suffix = '',
    required this.gradient,
    required this.shadowColor,
  });
}

/// Classe de dados para item de estatística
class CashbackStatisticItem {
  final CashbackStatisticType type;
  final double value;
  final double? secondaryValue;
  final String? customTitle;
  final String? customDescription;
  final bool isMonetary;

  const CashbackStatisticItem({
    required this.type,
    required this.value,
    this.secondaryValue,
    this.customTitle,
    this.customDescription,
    this.isMonetary = true,
  });
}

/// Função de conveniência para criar cartão de saldo disponível
Widget createAvailableBalanceCard(double value, {VoidCallback? onTap}) {
  return CashbackStatisticsCard(
    type: CashbackStatisticType.availableBalance,
    value: value,
    onTap: onTap,
  );
}

/// Função de conveniência para criar cartão de total economizado
Widget createTotalSavedCard(double value, double used, {VoidCallback? onTap}) {
  return CashbackStatisticsCard(
    type: CashbackStatisticType.totalSaved,
    value: value,
    secondaryValue: used,
    onTap: onTap,
  );
}

/// Função de conveniência para criar grid de estatísticas padrão
Widget createDefaultStatisticsGrid({
  required double totalSaved,
  required double availableBalance,
  required double pendingBalance,
  required int totalTransactions,
  Function(CashbackStatisticType)? onTap,
}) {
  return CashbackStatisticsGrid(
    statistics: [
      CashbackStatisticItem(
        type: CashbackStatisticType.totalSaved,
        value: totalSaved,
      ),
      CashbackStatisticItem(
        type: CashbackStatisticType.availableBalance,
        value: availableBalance,
      ),
      CashbackStatisticItem(
        type: CashbackStatisticType.pendingBalance,
        value: pendingBalance,
      ),
      CashbackStatisticItem(
        type: CashbackStatisticType.totalTransactions,
        value: totalTransactions.toDouble(),
        isMonetary: false,
      ),
    ],
    onStatisticTap: onTap,
  );
}