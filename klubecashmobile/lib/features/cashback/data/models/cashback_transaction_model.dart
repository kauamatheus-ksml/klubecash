// lib/features/cashback/data/models/cashback_transaction_model.dart
// Arquivo: Modelo de Transação de Cashback - Camada Data

import '../../domain/entities/cashback_transaction.dart';

/// Modelo de dados para transações de cashback
/// 
/// Este modelo representa uma transação de cashback na camada de dados,
/// incluindo métodos de serialização para comunicação com a API.
class CashbackTransactionModel extends CashbackTransaction {
  const CashbackTransactionModel({
    required super.id,
    required super.storeId,
    required super.storeName,
    super.storeLogo,
    required super.userId,
    super.userName,
    required super.totalAmount,
    required super.cashbackAmount,
    required super.cashbackPercentage,
    required super.clientAmount,
    required super.adminAmount,
    required super.storeAmount,
    required super.transactionDate,
    required super.status,
    super.transactionCode,
    super.approvalDate,
    super.description,
    super.balanceUsed,
    super.refundReason,
    super.createdAt,
    super.updatedAt,
  });

  /// Cria um modelo a partir de um JSON
  factory CashbackTransactionModel.fromJson(Map<String, dynamic> json) {
    return CashbackTransactionModel(
      id: json['id']?.toString() ?? '',
      storeId: json['store_id']?.toString() ?? json['loja_id']?.toString() ?? '',
      storeName: json['store_name']?.toString() ?? json['nome_loja']?.toString() ?? json['loja_nome']?.toString() ?? '',
      storeLogo: json['store_logo']?.toString() ?? json['loja_logo']?.toString(),
      userId: json['user_id']?.toString() ?? json['usuario_id']?.toString() ?? '',
      userName: json['user_name']?.toString() ?? json['nome_usuario']?.toString(),
      totalAmount: _parseDouble(json['total_amount'] ?? json['valor_total']),
      cashbackAmount: _parseDouble(json['cashback_amount'] ?? json['valor_cashback']),
      cashbackPercentage: _parseDouble(json['cashback_percentage'] ?? json['percentual_cashback']),
      clientAmount: _parseDouble(json['client_amount'] ?? json['valor_cliente']),
      adminAmount: _parseDouble(json['admin_amount'] ?? json['valor_admin']),
      storeAmount: _parseDouble(json['store_amount'] ?? json['valor_loja']),
      transactionDate: _parseDateTime(json['transaction_date'] ?? json['data_transacao']),
      status: _parseTransactionStatus(json['status']),
      transactionCode: json['transaction_code']?.toString() ?? json['codigo_transacao']?.toString(),
      approvalDate: _parseDateTime(json['approval_date'] ?? json['data_aprovacao']),
      description: json['description']?.toString() ?? json['descricao']?.toString(),
      balanceUsed: _parseDouble(json['balance_used'] ?? json['saldo_usado']),
      refundReason: json['refund_reason']?.toString() ?? json['motivo_estorno']?.toString(),
      createdAt: _parseDateTime(json['created_at'] ?? json['data_criacao']),
      updatedAt: _parseDateTime(json['updated_at'] ?? json['data_atualizacao']),
    );
  }

  /// Converte o modelo para JSON
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'store_id': storeId,
      'store_name': storeName,
      'store_logo': storeLogo,
      'user_id': userId,
      'user_name': userName,
      'total_amount': totalAmount,
      'cashback_amount': cashbackAmount,
      'cashback_percentage': cashbackPercentage,
      'client_amount': clientAmount,
      'admin_amount': adminAmount,
      'store_amount': storeAmount,
      'transaction_date': transactionDate.toIso8601String(),
      'status': status.name,
      'transaction_code': transactionCode,
      'approval_date': approvalDate?.toIso8601String(),
      'description': description,
      'balance_used': balanceUsed,
      'refund_reason': refundReason,
      'created_at': createdAt?.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
    };
  }

  /// Cria uma cópia do modelo com campos atualizados
  CashbackTransactionModel copyWith({
    String? id,
    String? storeId,
    String? storeName,
    String? storeLogo,
    String? userId,
    String? userName,
    double? totalAmount,
    double? cashbackAmount,
    double? cashbackPercentage,
    double? clientAmount,
    double? adminAmount,
    double? storeAmount,
    DateTime? transactionDate,
    CashbackTransactionStatus? status,
    String? transactionCode,
    DateTime? approvalDate,
    String? description,
    double? balanceUsed,
    String? refundReason,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return CashbackTransactionModel(
      id: id ?? this.id,
      storeId: storeId ?? this.storeId,
      storeName: storeName ?? this.storeName,
      storeLogo: storeLogo ?? this.storeLogo,
      userId: userId ?? this.userId,
      userName: userName ?? this.userName,
      totalAmount: totalAmount ?? this.totalAmount,
      cashbackAmount: cashbackAmount ?? this.cashbackAmount,
      cashbackPercentage: cashbackPercentage ?? this.cashbackPercentage,
      clientAmount: clientAmount ?? this.clientAmount,
      adminAmount: adminAmount ?? this.adminAmount,
      storeAmount: storeAmount ?? this.storeAmount,
      transactionDate: transactionDate ?? this.transactionDate,
      status: status ?? this.status,
      transactionCode: transactionCode ?? this.transactionCode,
      approvalDate: approvalDate ?? this.approvalDate,
      description: description ?? this.description,
      balanceUsed: balanceUsed ?? this.balanceUsed,
      refundReason: refundReason ?? this.refundReason,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  /// Converte uma lista de JSON para uma lista de modelos
  static List<CashbackTransactionModel> fromJsonList(List<dynamic> jsonList) {
    return jsonList
        .where((json) => json != null && json is Map<String, dynamic>)
        .map((json) => CashbackTransactionModel.fromJson(json as Map<String, dynamic>))
        .toList();
  }

  /// Helpers para parsing seguro dos dados

  static double _parseDouble(dynamic value) {
    if (value == null) return 0.0;
    if (value is double) return value;
    if (value is int) return value.toDouble();
    if (value is String) {
      // Remove formatação brasileira se presente
      final cleanValue = value.replaceAll(RegExp(r'[R$\s]'), '').replaceAll(',', '.');
      return double.tryParse(cleanValue) ?? 0.0;
    }
    return 0.0;
  }

  static DateTime _parseDateTime(dynamic value) {
    if (value == null) return DateTime.now();
    if (value is DateTime) return value;
    if (value is String) {
      try {
        // Tenta vários formatos de data
        if (value.contains('/')) {
          // Formato brasileiro: dd/mm/yyyy hh:mm:ss
          final parts = value.split(' ');
          final dateParts = parts[0].split('/');
          if (dateParts.length == 3) {
            final timeParts = parts.length > 1 ? parts[1].split(':') : ['0', '0', '0'];
            return DateTime(
              int.parse(dateParts[2]), // ano
              int.parse(dateParts[1]), // mês
              int.parse(dateParts[0]), // dia
              timeParts.isNotEmpty ? int.parse(timeParts[0]) : 0, // hora
              timeParts.length > 1 ? int.parse(timeParts[1]) : 0, // minuto
              timeParts.length > 2 ? int.parse(timeParts[2]) : 0, // segundo
            );
          }
        }
        return DateTime.parse(value);
      } catch (e) {
        return DateTime.now();
      }
    }
    return DateTime.now();
  }

  static CashbackTransactionStatus _parseTransactionStatus(dynamic value) {
    if (value == null) return CashbackTransactionStatus.pending;
    
    final stringValue = value.toString().toLowerCase();
    
    switch (stringValue) {
      case 'approved':
      case 'aprovado':
      case 'confirmado':
        return CashbackTransactionStatus.approved;
      case 'pending':
      case 'pendente':
      case 'aguardando':
        return CashbackTransactionStatus.pending;
      case 'canceled':
      case 'cancelled':
      case 'cancelado':
        return CashbackTransactionStatus.canceled;
      case 'processing':
      case 'processando':
        return CashbackTransactionStatus.processing;
      case 'refunded':
      case 'estornado':
        return CashbackTransactionStatus.refunded;
      case 'expired':
      case 'expirado':
        return CashbackTransactionStatus.expired;
      case 'payment_pending':
      case 'pagamento_pendente':
        return CashbackTransactionStatus.paymentPending;
      default:
        return CashbackTransactionStatus.pending;
    }
  }

  @override
  String toString() {
    return 'CashbackTransactionModel('
        'id: $id, '
        'storeName: $storeName, '
        'totalAmount: $totalAmount, '
        'cashbackAmount: $cashbackAmount, '
        'status: $status, '
        'transactionDate: $transactionDate'
        ')';
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is CashbackTransactionModel && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;
}

/// Enum para status da transação de cashback
enum CashbackTransactionStatus {
  pending('pending'),
  approved('approved'),
  canceled('canceled'),
  processing('processing'),
  refunded('refunded'),
  expired('expired'),
  paymentPending('payment_pending');

  const CashbackTransactionStatus(this.value);
  final String value;

  @override
  String toString() => value;

  /// Retorna a descrição legível do status
  String get displayName {
    switch (this) {
      case CashbackTransactionStatus.pending:
        return 'Pendente';
      case CashbackTransactionStatus.approved:
        return 'Aprovado';
      case CashbackTransactionStatus.canceled:
        return 'Cancelado';
      case CashbackTransactionStatus.processing:
        return 'Processando';
      case CashbackTransactionStatus.refunded:
        return 'Estornado';
      case CashbackTransactionStatus.expired:
        return 'Expirado';
      case CashbackTransactionStatus.paymentPending:
        return 'Pagamento Pendente';
    }
  }

  /// Retorna a cor associada ao status
  String get colorKey {
    switch (this) {
      case CashbackTransactionStatus.approved:
        return 'success';
      case CashbackTransactionStatus.pending:
      case CashbackTransactionStatus.processing:
      case CashbackTransactionStatus.paymentPending:
        return 'warning';
      case CashbackTransactionStatus.canceled:
      case CashbackTransactionStatus.refunded:
      case CashbackTransactionStatus.expired:
        return 'error';
    }
  }
}