// lib/features/cashback/presentation/widgets/cashback_status_chip.dart
// Widget para exibir chips de status das transações de cashback

import 'package:flutter/material.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../domain/entities/cashback_transaction.dart';

/// Widget para exibir o status de uma transação de cashback como chip colorido
class CashbackStatusChip extends StatelessWidget {
  /// Status da transação
  final CashbackTransactionStatus status;
  
  /// Se deve exibir em modo compacto (menor)
  final bool isCompact;
  
  /// Se deve mostrar ícone
  final bool showIcon;
  
  /// Cor personalizada de fundo (sobrescreve a cor padrão do status)
  final Color? backgroundColor;
  
  /// Cor personalizada do texto (sobrescreve a cor padrão do status)
  final Color? textColor;

  const CashbackStatusChip({
    super.key,
    required this.status,
    this.isCompact = false,
    this.showIcon = true,
    this.backgroundColor,
    this.textColor,
  });

  @override
  Widget build(BuildContext context) {
    final statusConfig = _getStatusConfiguration();
    
    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: isCompact ? 8 : 12,
        vertical: isCompact ? 4 : 6,
      ),
      decoration: BoxDecoration(
        color: backgroundColor ?? statusConfig.backgroundColor,
        borderRadius: BorderRadius.circular(
          isCompact ? AppDimensions.radiusSmall : AppDimensions.radiusMedium,
        ),
        border: Border.all(
          color: statusConfig.borderColor,
          width: 1,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (showIcon) ...[
            Icon(
              statusConfig.icon,
              size: isCompact ? 12 : 14,
              color: textColor ?? statusConfig.textColor,
            ),
            SizedBox(width: isCompact ? 4 : 6),
          ],
          Text(
            statusConfig.label,
            style: TextStyle(
              fontSize: isCompact ? 11 : 12,
              fontWeight: FontWeight.w600,
              color: textColor ?? statusConfig.textColor,
              height: 1.2,
            ),
          ),
        ],
      ),
    );
  }

  /// Retorna a configuração visual para cada status
  StatusConfiguration _getStatusConfiguration() {
    switch (status) {
      case CashbackTransactionStatus.pendente:
        return StatusConfiguration(
          label: 'Aguardando',
          icon: Icons.schedule,
          backgroundColor: AppColors.warningLight,
          textColor: AppColors.warningDark,
          borderColor: AppColors.warning.withOpacity(0.3),
        );
        
      case CashbackTransactionStatus.aprovado:
        return StatusConfiguration(
          label: 'Confirmado',
          icon: Icons.check_circle,
          backgroundColor: AppColors.successLight,
          textColor: AppColors.successDark,
          borderColor: AppColors.success.withOpacity(0.3),
        );
        
      case CashbackTransactionStatus.cancelado:
        return StatusConfiguration(
          label: 'Cancelado',
          icon: Icons.cancel,
          backgroundColor: AppColors.errorLight,
          textColor: AppColors.errorDark,
          borderColor: AppColors.error.withOpacity(0.3),
        );
        
      case CashbackTransactionStatus.pagamentoPendente:
        return StatusConfiguration(
          label: 'Pag. Pendente',
          icon: Icons.payment,
          backgroundColor: AppColors.infoLight,
          textColor: AppColors.infoDark,
          borderColor: AppColors.info.withOpacity(0.3),
        );
    }
  }
}

/// Widget simplificado para exibir apenas o status como texto colorido
class CashbackStatusText extends StatelessWidget {
  /// Status da transação
  final CashbackTransactionStatus status;
  
  /// Tamanho da fonte
  final double? fontSize;
  
  /// Peso da fonte
  final FontWeight? fontWeight;

  const CashbackStatusText({
    super.key,
    required this.status,
    this.fontSize,
    this.fontWeight,
  });

  @override
  Widget build(BuildContext context) {
    final statusConfig = CashbackStatusChip(status: status)._getStatusConfiguration();
    
    return Text(
      statusConfig.label,
      style: TextStyle(
        fontSize: fontSize ?? 14,
        fontWeight: fontWeight ?? FontWeight.w500,
        color: statusConfig.textColor,
      ),
    );
  }
}

/// Widget para exibir status como badge circular (ícone apenas)
class CashbackStatusBadge extends StatelessWidget {
  /// Status da transação
  final CashbackTransactionStatus status;
  
  /// Tamanho do badge
  final double size;

  const CashbackStatusBadge({
    super.key,
    required this.status,
    this.size = 24,
  });

  @override
  Widget build(BuildContext context) {
    final statusConfig = CashbackStatusChip(status: status)._getStatusConfiguration();
    
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: statusConfig.backgroundColor,
        shape: BoxShape.circle,
        border: Border.all(
          color: statusConfig.borderColor,
          width: 1,
        ),
      ),
      child: Icon(
        statusConfig.icon,
        size: size * 0.6,
        color: statusConfig.textColor,
      ),
    );
  }
}

/// Widget para lista de status com legenda
class CashbackStatusLegend extends StatelessWidget {
  /// Lista de status para exibir na legenda
  final List<CashbackTransactionStatus>? statusList;
  
  /// Se deve exibir em layout horizontal
  final bool isHorizontal;

  const CashbackStatusLegend({
    super.key,
    this.statusList,
    this.isHorizontal = false,
  });

  @override
  Widget build(BuildContext context) {
    final statusToShow = statusList ?? CashbackTransactionStatus.values;
    
    return Wrap(
      direction: isHorizontal ? Axis.horizontal : Axis.vertical,
      spacing: isHorizontal ? 16 : 8,
      runSpacing: 8,
      children: statusToShow.map((status) {
        return Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            CashbackStatusBadge(
              status: status,
              size: 16,
            ),
            const SizedBox(width: 8),
            CashbackStatusText(
              status: status,
              fontSize: 12,
            ),
          ],
        );
      }).toList(),
    );
  }
}

/// Widget interativo para seleção de status (usado em filtros)
class CashbackStatusSelector extends StatelessWidget {
  /// Status atualmente selecionados
  final List<CashbackTransactionStatus> selectedStatus;
  
  /// Callback quando status é selecionado/desselecionado
  final Function(CashbackTransactionStatus, bool) onStatusChanged;
  
  /// Se permite seleção múltipla
  final bool allowMultiple;

  const CashbackStatusSelector({
    super.key,
    required this.selectedStatus,
    required this.onStatusChanged,
    this.allowMultiple = true,
  });

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: CashbackTransactionStatus.values.map((status) {
        final isSelected = selectedStatus.contains(status);
        final statusConfig = CashbackStatusChip(status: status)._getStatusConfiguration();
        
        return FilterChip(
          label: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                statusConfig.icon,
                size: 14,
                color: isSelected 
                    ? statusConfig.textColor 
                    : AppColors.textSecondary,
              ),
              const SizedBox(width: 6),
              Text(statusConfig.label),
            ],
          ),
          selected: isSelected,
          onSelected: (selected) {
            onStatusChanged(status, selected);
          },
          backgroundColor: AppColors.gray100,
          selectedColor: statusConfig.backgroundColor,
          checkmarkColor: statusConfig.textColor,
          labelStyle: TextStyle(
            color: isSelected 
                ? statusConfig.textColor 
                : AppColors.textSecondary,
            fontWeight: isSelected ? FontWeight.w600 : FontWeight.w400,
          ),
          side: BorderSide(
            color: isSelected 
                ? statusConfig.borderColor 
                : AppColors.gray300,
            width: 1,
          ),
        );
      }).toList(),
    );
  }
}

/// Extensão para facilitar uso das cores de status
extension CashbackTransactionStatusExtension on CashbackTransactionStatus {
  /// Retorna a cor primária do status
  Color get primaryColor {
    switch (this) {
      case CashbackTransactionStatus.pendente:
        return AppColors.warning;
      case CashbackTransactionStatus.aprovado:
        return AppColors.success;
      case CashbackTransactionStatus.cancelado:
        return AppColors.error;
      case CashbackTransactionStatus.pagamentoPendente:
        return AppColors.info;
    }
  }
  
  /// Retorna a cor de fundo do status
  Color get backgroundColor {
    switch (this) {
      case CashbackTransactionStatus.pendente:
        return AppColors.warningLight;
      case CashbackTransactionStatus.aprovado:
        return AppColors.successLight;
      case CashbackTransactionStatus.cancelado:
        return AppColors.errorLight;
      case CashbackTransactionStatus.pagamentoPendente:
        return AppColors.infoLight;
    }
  }
  
  /// Retorna o ícone do status
  IconData get icon {
    switch (this) {
      case CashbackTransactionStatus.pendente:
        return Icons.schedule;
      case CashbackTransactionStatus.aprovado:
        return Icons.check_circle;
      case CashbackTransactionStatus.cancelado:
        return Icons.cancel;
      case CashbackTransactionStatus.pagamentoPendente:
        return Icons.payment;
    }
  }
  
  /// Retorna o label do status
  String get label {
    switch (this) {
      case CashbackTransactionStatus.pendente:
        return 'Aguardando';
      case CashbackTransactionStatus.aprovado:
        return 'Confirmado';
      case CashbackTransactionStatus.cancelado:
        return 'Cancelado';
      case CashbackTransactionStatus.pagamentoPendente:
        return 'Pag. Pendente';
    }
  }
}

/// Classe para configuração visual de status
class StatusConfiguration {
  final String label;
  final IconData icon;
  final Color backgroundColor;
  final Color textColor;
  final Color borderColor;

  const StatusConfiguration({
    required this.label,
    required this.icon,
    required this.backgroundColor,
    required this.textColor,
    required this.borderColor,
  });
}

/// Função utilitária para criar chip de status
Widget createStatusChip(
  CashbackTransactionStatus status, {
  bool isCompact = false,
  bool showIcon = true,
}) {
  return CashbackStatusChip(
    status: status,
    isCompact: isCompact,
    showIcon: showIcon,
  );
}

/// Função utilitária para obter cor por status
Color getStatusColor(CashbackTransactionStatus status) {
  return status.primaryColor;
}

/// Função utilitária para obter cor de fundo por status
Color getStatusBackgroundColor(CashbackTransactionStatus status) {
  return status.backgroundColor;
}