// lib/features/dashboard/domain/entities/transaction_summary.dart
// Entidade TransactionSummary - Representa o resumo de uma transação para o dashboard

/// Enum para status da transação
enum TransactionStatus {
  pending,     // Aguardando
  confirmed,   // Confirmado
  cancelled,   // Cancelado
  expired,     // Expirado
}

/// Enum para tipo de transação
enum TransactionType {
  purchase,    // Compra com cashback
  usage,       // Uso de saldo
  refund,      // Estorno
}

/// Entidade que representa o resumo de uma transação
///
/// Esta classe contém as informações essenciais de uma transação
/// exibidas no dashboard e listas de transações recentes
class TransactionSummary {
  final String id;
  final String storeId;
  final String storeName;
  final String? storeLogo;
  final String? storeCategory;
  final double purchaseAmount;
  final double cashbackAmount;
  final double? usedBalance;
  final double cashbackPercentage;
  final TransactionStatus status;
  final TransactionType type;
  final DateTime transactionDate;
  final DateTime? releaseDate;
  final String transactionCode;
  final String? description;
  final bool isNew;

  const TransactionSummary({
    required this.id,
    required this.storeId,
    required this.storeName,
    this.storeLogo,
    this.storeCategory,
    required this.purchaseAmount,
    required this.cashbackAmount,
    this.usedBalance,
    required this.cashbackPercentage,
    required this.status,
    required this.type,
    required this.transactionDate,
    this.releaseDate,
    required this.transactionCode,
    this.description,
    this.isNew = false,
  });

  /// Retorna o valor final da compra (valor - saldo usado)
  double get finalAmount {
    if (usedBalance != null) {
      return purchaseAmount - usedBalance!;
    }
    return purchaseAmount;
  }

  /// Retorna se a transação está confirmada
  bool get isConfirmed => status == TransactionStatus.confirmed;

  /// Retorna se a transação está pendente
  bool get isPending => status == TransactionStatus.pending;

  /// Retorna se é uma transação de compra
  bool get isPurchase => type == TransactionType.purchase;

  /// Retorna se é uma transação de uso de saldo
  bool get isUsage => type == TransactionType.usage;

  /// Retorna a cor do status para UI
  String get statusColor {
    switch (status) {
      case TransactionStatus.confirmed:
        return 'green';
      case TransactionStatus.pending:
        return 'orange';
      case TransactionStatus.cancelled:
        return 'red';
      case TransactionStatus.expired:
        return 'gray';
    }
  }

  /// Retorna o texto do status em português
  String get statusText {
    switch (status) {
      case TransactionStatus.confirmed:
        return 'Confirmado';
      case TransactionStatus.pending:
        return 'Aguardando';
      case TransactionStatus.cancelled:
        return 'Cancelado';
      case TransactionStatus.expired:
        return 'Expirado';
    }
  }

  /// Cria uma cópia da entidade com campos atualizados
  TransactionSummary copyWith({
    String? id,
    String? storeId,
    String? storeName,
    String? storeLogo,
    String? storeCategory,
    double? purchaseAmount,
    double? cashbackAmount,
    double? usedBalance,
    double? cashbackPercentage,
    TransactionStatus? status,
    TransactionType? type,
    DateTime? transactionDate,
    DateTime? releaseDate,
    String? transactionCode,
    String? description,
    bool? isNew,
  }) {
    return TransactionSummary(
      id: id ?? this.id,
      storeId: storeId ?? this.storeId,
      storeName: storeName ?? this.storeName,
      storeLogo: storeLogo ?? this.storeLogo,
      storeCategory: storeCategory ?? this.storeCategory,
      purchaseAmount: purchaseAmount ?? this.purchaseAmount,
      cashbackAmount: cashbackAmount ?? this.cashbackAmount,
      usedBalance: usedBalance ?? this.usedBalance,
      cashbackPercentage: cashbackPercentage ?? this.cashbackPercentage,
      status: status ?? this.status,
      type: type ?? this.type,
      transactionDate: transactionDate ?? this.transactionDate,
      releaseDate: releaseDate ?? this.releaseDate,
      transactionCode: transactionCode ?? this.transactionCode,
      description: description ?? this.description,
      isNew: isNew ?? this.isNew,
    );
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;

    return other is TransactionSummary &&
        other.id == id &&
        other.storeId == storeId &&
        other.storeName == storeName &&
        other.storeLogo == storeLogo &&
        other.storeCategory == storeCategory &&
        other.purchaseAmount == purchaseAmount &&
        other.cashbackAmount == cashbackAmount &&
        other.usedBalance == usedBalance &&
        other.cashbackPercentage == cashbackPercentage &&
        other.status == status &&
        other.type == type &&
        other.transactionDate == transactionDate &&
        other.releaseDate == releaseDate &&
        other.transactionCode == transactionCode &&
        other.description == description &&
        other.isNew == isNew;
  }

  @override
  int get hashCode {
    return Object.hash(
      id,
      storeId,
      storeName,
      storeLogo,
      storeCategory,
      purchaseAmount,
      cashbackAmount,
      usedBalance,
      cashbackPercentage,
      status,
      type,
      transactionDate,
      releaseDate,
      transactionCode,
      description,
      isNew,
    );
  }

  @override
  String toString() {
    return 'TransactionSummary(id: $id, storeName: $storeName, '
        'purchaseAmount: $purchaseAmount, cashbackAmount: $cashbackAmount, '
        'status: $status, type: $type)';
  }
}