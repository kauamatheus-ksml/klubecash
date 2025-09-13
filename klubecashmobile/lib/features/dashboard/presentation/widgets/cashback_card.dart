// lib/features/dashboard/presentation/widgets/cashback_card.dart
// 💳 Cashback Card - Widget para exibir informações de cashback no dashboard

import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/utils/currency_utils.dart';
import '../../../../core/widgets/custom_button.dart';

/// Enum para tipos de card de cashback
enum CashbackCardType {
  /// Saldo disponível para usar (verde)
  available,
  /// Saldo pendente/chegando em breve (verde)
  pending,
  /// Total economizado (azul)
  totalSaved,
  /// Card genérico
  generic,
}

/// Widget de card para exibir informações de cashback
class CashbackCard extends StatelessWidget {
  /// Tipo do card
  final CashbackCardType type;
  
  /// Título principal do card
  final String title;
  
  /// Valor principal em destaque
  final double value;
  
  /// Descrição/subtítulo
  final String? description;
  
  /// Ícone/emoji no canto superior direito
  final String? icon;
  
  /// Informações extras no rodapé
  final Widget? extraInfo;
  
  /// Callback ao tocar no card
  final VoidCallback? onTap;
  
  /// Texto do botão de ação
  final String? actionText;
  
  /// Callback do botão de ação
  final VoidCallback? onActionPressed;
  
  /// Se deve mostrar animação
  final bool showAnimation;
  
  /// Cores customizadas (opcional)
  final Color? backgroundColor;
  final Color? textColor;

  const CashbackCard({
    super.key,
    required this.type,
    required this.title,
    required this.value,
    this.description,
    this.icon,
    this.extraInfo,
    this.onTap,
    this.actionText,
    this.onActionPressed,
    this.showAnimation = true,
    this.backgroundColor,
    this.textColor,
  });

  @override
  Widget build(BuildContext context) {
    final colors = _getCardColors();
    
    Widget card = Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      decoration: BoxDecoration(
        color: backgroundColor ?? colors.background,
        borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.08),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildHeader(colors),
          const SizedBox(height: AppDimensions.spacingSmall),
          _buildValue(colors),
          if (description != null) ...[
            const SizedBox(height: AppDimensions.spacingXSmall),
            _buildDescription(colors),
          ],
          if (extraInfo != null) ...[
            const SizedBox(height: AppDimensions.spacingMedium),
            extraInfo!,
          ],
          if (actionText != null && onActionPressed != null) ...[
            const SizedBox(height: AppDimensions.spacingMedium),
            _buildActionButton(colors),
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
      return card
          .animate()
          .fadeIn(duration: 300.ms, delay: 100.ms)
          .slideY(begin: 0.2, end: 0, duration: 400.ms);
    }

    return card;
  }

  Widget _buildHeader(_CardColors colors) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Expanded(
          child: Text(
            title,
            style: TextStyle(
              color: textColor ?? colors.titleColor,
              fontSize: 16,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
        if (icon != null)
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.2),
              borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
            ),
            child: Text(
              icon!,
              style: const TextStyle(fontSize: 20),
            ),
          ),
      ],
    );
  }

  Widget _buildValue(_CardColors colors) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.baseline,
      textBaseline: TextBaseline.alphabetic,
      children: [
        Text(
          'R\$',
          style: TextStyle(
            color: textColor ?? colors.valueColor,
            fontSize: 18,
            fontWeight: FontWeight.w700,
          ),
        ),
        const SizedBox(width: 4),
        Text(
          CurrencyUtils.formatNumber(value),
          style: TextStyle(
            color: textColor ?? colors.valueColor,
            fontSize: 32,
            fontWeight: FontWeight.w800,
            height: 1.0,
          ),
        ),
      ],
    );
  }

  Widget _buildDescription(_CardColors colors) {
    return Text(
      description!,
      style: TextStyle(
        color: textColor ?? colors.descriptionColor,
        fontSize: 14,
        fontWeight: FontWeight.w400,
      ),
    );
  }

  Widget _buildActionButton(_CardColors colors) {
    return CustomButton(
      text: actionText!,
      onPressed: onActionPressed,
      type: ButtonType.secondary,
      size: ButtonSize.small,
      backgroundColor: Colors.white.withOpacity(0.9),
      textColor: colors.background,
      borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
    );
  }

  _CardColors _getCardColors() {
    switch (type) {
      case CashbackCardType.available:
        return _CardColors(
          background: AppColors.success,
          titleColor: AppColors.white,
          valueColor: AppColors.white,
          descriptionColor: AppColors.white.withOpacity(0.9),
        );
        
      case CashbackCardType.pending:
        return _CardColors(
          background: AppColors.success,
          titleColor: AppColors.white,
          valueColor: AppColors.white,
          descriptionColor: AppColors.white.withOpacity(0.9),
        );
        
      case CashbackCardType.totalSaved:
        return _CardColors(
          background: AppColors.info,
          titleColor: AppColors.white,
          valueColor: AppColors.white,
          descriptionColor: AppColors.white.withOpacity(0.9),
        );
        
      case CashbackCardType.generic:
        return _CardColors(
          background: AppColors.primary,
          titleColor: AppColors.white,
          valueColor: AppColors.white,
          descriptionColor: AppColors.white.withOpacity(0.9),
        );
    }
  }
}

/// Widget de informações extras para o rodapé do card
class CashbackCardExtraInfo extends StatelessWidget {
  final List<CashbackCardInfoItem> items;

  const CashbackCardExtraInfo({
    super.key,
    required this.items,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: items
          .map((item) => Padding(
                padding: const EdgeInsets.only(bottom: 4),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        if (item.icon != null) ...[
                          Text(item.icon!, style: const TextStyle(fontSize: 12)),
                          const SizedBox(width: 4),
                        ],
                        Text(
                          item.label,
                          style: TextStyle(
                            color: Colors.white.withOpacity(0.9),
                            fontSize: 13,
                            fontWeight: FontWeight.w400,
                          ),
                        ),
                      ],
                    ),
                    Text(
                      item.value,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ))
          .toList(),
    );
  }
}

/// Item de informação extra para o card
class CashbackCardInfoItem {
  final String label;
  final String value;
  final String? icon;

  const CashbackCardInfoItem({
    required this.label,
    required this.value,
    this.icon,
  });
}

/// Cores utilizadas no card
class _CardColors {
  final Color background;
  final Color titleColor;
  final Color valueColor;
  final Color descriptionColor;

  const _CardColors({
    required this.background,
    required this.titleColor,
    required this.valueColor,
    required this.descriptionColor,
  });
}

/// Factory methods para criar cards específicos
extension CashbackCardFactory on CashbackCard {
  /// Cria card de saldo disponível
  static CashbackCard available({
    required double value,
    VoidCallback? onTap,
    VoidCallback? onActionPressed,
  }) {
    return CashbackCard(
      type: CashbackCardType.available,
      title: 'Seu Saldo para Usar',
      value: value,
      description: 'Saldo liberado para usar em compras.',
      icon: '💳',
      actionText: 'Ver Como Usar',
      onTap: onTap,
      onActionPressed: onActionPressed,
    );
  }

  /// Cria card de saldo pendente
  static CashbackCard pending({
    required double value,
    String? storeName,
    double? storeValue,
    VoidCallback? onTap,
  }) {
    return CashbackCard(
      type: CashbackCardType.pending,
      title: 'Chegando em Breve',
      value: value,
      description: 'Cashback que ainda vai ser liberado pelas lojas.',
      icon: '⏳',
      extraInfo: storeName != null && storeValue != null
          ? CashbackCardExtraInfo(
              items: [
                CashbackCardInfoItem(
                  label: storeName,
                  value: CurrencyUtils.format(storeValue),
                ),
              ],
            )
          : null,
      onTap: onTap,
    );
  }

  /// Cria card de total economizado
  static CashbackCard totalSaved({
    required double totalValue,
    double? usedValue,
    double? availableValue,
    VoidCallback? onTap,
  }) {
    return CashbackCard(
      type: CashbackCardType.totalSaved,
      title: 'Total Economizado',
      value: totalValue,
      description: 'Quanto você já economizou com cashback.',
      icon: '📈',
      extraInfo: usedValue != null && availableValue != null
          ? CashbackCardExtraInfo(
              items: [
                CashbackCardInfoItem(
                  icon: '✓',
                  label: 'Já usei:',
                  value: CurrencyUtils.format(usedValue),
                ),
                CashbackCardInfoItem(
                  icon: '📋',
                  label: 'Disponível:',
                  value: CurrencyUtils.format(availableValue),
                ),
              ],
            )
          : null,
      onTap: onTap,
    );
  }
}