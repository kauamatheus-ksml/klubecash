// lib/features/dashboard/data/models/transaction_summary_model.dart
// Modelo de dados para resumo de transações - camada de dados

import 'package:equatable/equatable.dart';
import '../../domain/entities/transaction_summary.dart';

/// Modelo de dados para resumo de transações
/// 
/// Implementa serialização/deserialização JSON e estende a entidade
/// do domínio para seguir os princípios da Clean Architecture
class TransactionSummaryModel extends TransactionSummary with EquatableMixin {
  const TransactionSummaryModel({
    required super.id,
    required super.transactionCode,
    required super.totalAmount,
    required super.cashbackAmount,
    required super.clientAmount,
    required super.date,
    required super.status,
    required super.storeName,
    super.description = '',
    super.balanceUsed = 0.0,
    super.storeId,
    super.userId,
    super.adminAmount = 0.0,
    super.storeAmount = 0.0,
  });

  /// Cria instância a partir de JSON da API
  factory TransactionSummaryModel.fromJson(Map<String, dynamic> json) {
    return TransactionSummaryModel(
      id: _parseInt(json['id']),
      transactionCode: json['codigo_transacao'] ?? json['transaction_code'] ?? '',
      totalAmount: _parseDouble(json['valor_total'] ?? json['total_amount']),
      cashbackAmount: _parseDouble(json['valor_cashback'] ?? json['cashback_amount']),
      clientAmount: _parseDouble(json['valor_cliente'] ?? json['client_amount']),
      adminAmount: _parseDouble(json['valor_admin'] ?? json['admin_amount']),
      storeAmount: _parseDouble(json['valor_loja'] ?? json['store_amount']),
      balanceUsed: _parseDouble(json['valor_saldo_usado'] ?? json['balance_used']),
      date: _parseDateTime(json['data_transacao'] ?? json['transaction_date']),
      status: _parseStatus(json['status']),
      storeName: json['loja_nome'] ?? json['store_name'] ?? json['nome_fantasia'] ?? '',
      description: json['descricao'] ?? json['description'] ?? '',
      storeId: _parseInt(json['loja_id'] ?? json['store_id']),
      userId: _parseInt(json['usuario_id'] ?? json['user_id']),
    );
  }

  /// Converte instância para JSON
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'transaction_code': transactionCode,
      'total_amount': totalAmount,
      'cashback_amount': cashbackAmount,
      'client_amount': clientAmount,
      'admin_amount': adminAmount,
      'store_amount': storeAmount,
      'balance_used': balanceUsed,
      'transaction_date': date.toIso8601String(),
      'status': status.name,
      'store_name': storeName,
      'description': description,
      'store_id': storeId,
      'user_id': userId,
    };
  }

  /// Cria cópia com valores atualizados
  TransactionSummaryModel copyWith({
    int? id,
    String? transactionCode,
    double? totalAmount,
    double? cashbackAmount,
    double? clientAmount,
    double? adminAmount,
    double? storeAmount,
    double? balanceUsed,
    DateTime? date,
    TransactionStatus? status,
    String? storeName,
    String? description,
    int? storeId,
    int? userId,
  }) {
    return TransactionSummaryModel(
      id: id ?? this.id,
      transactionCode: transactionCode ?? this.transactionCode,
      totalAmount: totalAmount ?? this.totalAmount,
      cashbackAmount: cashbackAmount ?? this.cashbackAmount,
      clientAmount: clientAmount ?? this.clientAmount,
      adminAmount: adminAmount ?? this.adminAmount,
      storeAmount: storeAmount ?? this.storeAmount,
      balanceUsed: balanceUsed ?? this.balanceUsed,
      date: date ?? this.date,
      status: status ?? this.status,
      storeName: storeName ?? this.storeName,
      description: description ?? this.description,
      storeId: storeId ?? this.storeId,
      userId: userId ?? this.userId,
    );
  }

  /// Instância vazia para estados iniciais
  static final empty = TransactionSummaryModel(
    id: 0,
    transactionCode: '',
    totalAmount: 0.0,
    cashbackAmount: 0.0,
    clientAmount: 0.0,
    date: DateTime.now(),
    status: TransactionStatus.pending,
    storeName: '',
  );

  /// Lista de status válidos
  static const validStatuses = [
    'pendente',
    'aprovado', 
    'cancelado',
    'pagamento_pendente'
  ];

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

  /// Helper para parsing de data
  static DateTime _parseDateTime(dynamic value) {
    if (value == null) return DateTime.now();
    if (value is DateTime) return value;
    if (value is String) {
      return DateTime.tryParse(value) ?? DateTime.now();
    }
    return DateTime.now();
  }

  /// Helper para parsing de status
  static TransactionStatus _parseStatus(dynamic value) {
    if (value == null) return TransactionStatus.pending;
    
    final statusStr = value.toString().toLowerCase();
    switch (statusStr) {
      case 'aprovado':
      case 'approved':
        return TransactionStatus.approved;
      case 'cancelado':
      case 'cancelled':
      case 'canceled':
        return TransactionStatus.cancelled;
      case 'pagamento_pendente':
      case 'payment_pending':
        return TransactionStatus.paymentPending;
      case 'pendente':
      case 'pending':
      default:
        return TransactionStatus.pending;
    }
  }

  /// Verifica se a transação teve uso de saldo
  bool get hasBalanceUsed => balanceUsed > 0;

  /// Verifica se é uma transação aprovada
  bool get isApproved => status == TransactionStatus.approved;

  /// Verifica se é uma transação cancelada
  bool get isCancelled => status == TransactionStatus.cancelled;

  /// Verifica se é uma transação pendente
  bool get isPending => status == TransactionStatus.pending;

  /// Valor efetivamente pago pelo cliente (total - saldo usado)
  double get effectiveAmount => totalAmount - balanceUsed;

  @override
  List<Object?> get props => [
        id,
        transactionCode,
        totalAmount,
        cashbackAmount,
        clientAmount,
        adminAmount,
        storeAmount,
        balanceUsed,
        date,
        status,
        storeName,
        description,
        storeId,
        userId,
      ];

  @override
  bool get stringify => true;
}

/// Enum para status das transações
enum TransactionStatus {
  pending('pendente'),
  approved('aprovado'),
  cancelled('cancelado'),
  paymentPending('pagamento_pendente');

  const TransactionStatus(this.value);
  final String value;

  /// Obtém status a partir do valor string
  static TransactionStatus fromString(String value) {
    return TransactionStatus.values.firstWhere(
      (status) => status.value == value.toLowerCase(),
      orElse: () => TransactionStatus.pending,
    );
  }
}