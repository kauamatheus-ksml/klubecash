// lib/features/dashboard/domain/entities/cashback_summary.dart
// Entidade CashbackSummary - Representa o resumo de cashback do usuário no dashboard

/// Entidade que representa o resumo de cashback de um usuário
///
/// Esta classe contém todas as informações financeiras relacionadas
/// ao cashback do usuário, exibidas no dashboard principal do Klube Cash
class CashbackSummary {
  final double availableBalance;
  final double pendingBalance;
  final double totalSaved;
  final double usedBalance;
  final DateTime lastUpdated;
  final int totalTransactions;
  final int pendingTransactions;
  final String currency;
  final bool hasRecentActivity;
  final double monthlyGoal;
  final double monthlyProgress;

  const CashbackSummary({
    required this.availableBalance,
    required this.pendingBalance,
    required this.totalSaved,
    required this.usedBalance,
    required this.lastUpdated,
    required this.totalTransactions,
    this.pendingTransactions = 0,
    this.currency = 'BRL',
    this.hasRecentActivity = false,
    this.monthlyGoal = 0.0,
    this.monthlyProgress = 0.0,
  });

  /// Retorna o saldo total (disponível + pendente)
  double get totalBalance => availableBalance + pendingBalance;

  /// Retorna se há saldo disponível para usar
  bool get hasAvailableBalance => availableBalance > 0;

  /// Retorna se há cashback pendente de liberação
  bool get hasPendingBalance => pendingBalance > 0;

  /// Retorna a porcentagem de progresso da meta mensal
  double get monthlyProgressPercentage {
    if (monthlyGoal <= 0) return 0.0;
    return (monthlyProgress / monthlyGoal).clamp(0.0, 1.0);
  }

  /// Cria uma cópia da entidade com campos atualizados
  CashbackSummary copyWith({
    double? availableBalance,
    double? pendingBalance,
    double? totalSaved,
    double? usedBalance,
    DateTime? lastUpdated,
    int? totalTransactions,
    int? pendingTransactions,
    String? currency,
    bool? hasRecentActivity,
    double? monthlyGoal,
    double? monthlyProgress,
  }) {
    return CashbackSummary(
      availableBalance: availableBalance ?? this.availableBalance,
      pendingBalance: pendingBalance ?? this.pendingBalance,
      totalSaved: totalSaved ?? this.totalSaved,
      usedBalance: usedBalance ?? this.usedBalance,
      lastUpdated: lastUpdated ?? this.lastUpdated,
      totalTransactions: totalTransactions ?? this.totalTransactions,
      pendingTransactions: pendingTransactions ?? this.pendingTransactions,
      currency: currency ?? this.currency,
      hasRecentActivity: hasRecentActivity ?? this.hasRecentActivity,
      monthlyGoal: monthlyGoal ?? this.monthlyGoal,
      monthlyProgress: monthlyProgress ?? this.monthlyProgress,
    );
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;

    return other is CashbackSummary &&
        other.availableBalance == availableBalance &&
        other.pendingBalance == pendingBalance &&
        other.totalSaved == totalSaved &&
        other.usedBalance == usedBalance &&
        other.lastUpdated == lastUpdated &&
        other.totalTransactions == totalTransactions &&
        other.pendingTransactions == pendingTransactions &&
        other.currency == currency &&
        other.hasRecentActivity == hasRecentActivity &&
        other.monthlyGoal == monthlyGoal &&
        other.monthlyProgress == monthlyProgress;
  }

  @override
  int get hashCode {
    return Object.hash(
      availableBalance,
      pendingBalance,
      totalSaved,
      usedBalance,
      lastUpdated,
      totalTransactions,
      pendingTransactions,
      currency,
      hasRecentActivity,
      monthlyGoal,
      monthlyProgress,
    );
  }

  @override
  String toString() {
    return 'CashbackSummary(availableBalance: $availableBalance, '
        'pendingBalance: $pendingBalance, totalSaved: $totalSaved, '
        'usedBalance: $usedBalance, totalTransactions: $totalTransactions)';
  }
}