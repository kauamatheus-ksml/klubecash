// lib/features/dashboard/data/models/cashback_summary_model.dart
// Modelo de dados para resumo de cashback - camada de dados

import 'package:equatable/equatable.dart';
import '../../domain/entities/cashback_summary.dart';

/// Modelo de dados para resumo de cashback
/// 
/// Implementa serialização/deserialização JSON e estende a entidade
/// do domínio para seguir os princípios da Clean Architecture
class CashbackSummaryModel extends CashbackSummary with EquatableMixin {
  const CashbackSummaryModel({
    required super.totalBalance,
    required super.availableBalance,
    required super.pendingBalance,
    required super.totalEarned,
    required super.totalUsed,
    required super.totalSaved,
    required super.lastUpdate,
    super.monthlyEarned = 0.0,
    super.transactionCount = 0,
    super.averageEarning = 0.0,
    super.growthPercentage = 0.0,
  });

  /// Cria instância a partir de JSON da API
  factory CashbackSummaryModel.fromJson(Map<String, dynamic> json) {
    return CashbackSummaryModel(
      totalBalance: _parseDouble(json['total_balance'] ?? json['saldo_total']),
      availableBalance: _parseDouble(json['available_balance'] ?? json['saldo_disponivel']),
      pendingBalance: _parseDouble(json['pending_balance'] ?? json['saldo_pendente']),
      totalEarned: _parseDouble(json['total_earned'] ?? json['total_ganho']),
      totalUsed: _parseDouble(json['total_used'] ?? json['total_usado']),
      totalSaved: _parseDouble(json['total_saved'] ?? json['total_economizado']),
      monthlyEarned: _parseDouble(json['monthly_earned'] ?? json['ganho_mensal']),
      transactionCount: _parseInt(json['transaction_count'] ?? json['total_transacoes']),
      averageEarning: _parseDouble(json['average_earning'] ?? json['media_ganho']),
      growthPercentage: _parseDouble(json['growth_percentage'] ?? json['percentual_crescimento']),
      lastUpdate: DateTime.tryParse(json['last_update'] ?? json['ultima_atualizacao'] ?? '') ?? DateTime.now(),
    );
  }

  /// Converte instância para JSON
  Map<String, dynamic> toJson() {
    return {
      'total_balance': totalBalance,
      'available_balance': availableBalance,
      'pending_balance': pendingBalance,
      'total_earned': totalEarned,
      'total_used': totalUsed,
      'total_saved': totalSaved,
      'monthly_earned': monthlyEarned,
      'transaction_count': transactionCount,
      'average_earning': averageEarning,
      'growth_percentage': growthPercentage,
      'last_update': lastUpdate.toIso8601String(),
    };
  }

  /// Cria cópia com valores atualizados
  CashbackSummaryModel copyWith({
    double? totalBalance,
    double? availableBalance,
    double? pendingBalance,
    double? totalEarned,
    double? totalUsed,
    double? totalSaved,
    double? monthlyEarned,
    int? transactionCount,
    double? averageEarning,
    double? growthPercentage,
    DateTime? lastUpdate,
  }) {
    return CashbackSummaryModel(
      totalBalance: totalBalance ?? this.totalBalance,
      availableBalance: availableBalance ?? this.availableBalance,
      pendingBalance: pendingBalance ?? this.pendingBalance,
      totalEarned: totalEarned ?? this.totalEarned,
      totalUsed: totalUsed ?? this.totalUsed,
      totalSaved: totalSaved ?? this.totalSaved,
      monthlyEarned: monthlyEarned ?? this.monthlyEarned,
      transactionCount: transactionCount ?? this.transactionCount,
      averageEarning: averageEarning ?? this.averageEarning,
      growthPercentage: growthPercentage ?? this.growthPercentage,
      lastUpdate: lastUpdate ?? this.lastUpdate,
    );
  }

  /// Instância vazia para estados iniciais
  static const empty = CashbackSummaryModel(
    totalBalance: 0.0,
    availableBalance: 0.0,
    pendingBalance: 0.0,
    totalEarned: 0.0,
    totalUsed: 0.0,
    totalSaved: 0.0,
    lastUpdate: null,
  );

  /// Helper para parsing seguro de double
  static double _parseDouble(dynamic value) {
    if (value == null) return 0.0;
    if (value is double) return value;
    if (value is int) return value.toDouble();
    if (value is String) {
      // Remove formatação brasileira (R$, pontos, etc)
      final cleanValue = value
          .replaceAll(RegExp(r'[R\$\s]'), '')
          .replaceAll('.', '')
          .replaceAll(',', '.');
      return double.tryParse(cleanValue) ?? 0.0;
    }
    return 0.0;
  }

  /// Helper para parsing seguro de int
  static int _parseInt(dynamic value) {
    if (value == null) return 0;
    if (value is int) return value;
    if (value is double) return value.round();
    if (value is String) return int.tryParse(value) ?? 0;
    return 0;
  }

  @override
  List<Object?> get props => [
        totalBalance,
        availableBalance,
        pendingBalance,
        totalEarned,
        totalUsed,
        totalSaved,
        monthlyEarned,
        transactionCount,
        averageEarning,
        growthPercentage,
        lastUpdate,
      ];

  @override
  bool get stringify => true;
}