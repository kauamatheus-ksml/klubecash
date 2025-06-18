// lib/features/cashback/data/models/cashback_filter_model.dart
// Arquivo: Modelo de Filtro de Cashback - Camada Data

import '../../domain/entities/cashback_filter.dart';

/// Modelo de dados para filtros de transações de cashback
/// 
/// Este modelo representa os filtros aplicáveis às consultas de cashback,
/// incluindo métodos de serialização para comunicação com a API.
class CashbackFilterModel extends CashbackFilter {
  const CashbackFilterModel({
    super.status,
    super.storeId,
    super.storeName,
    super.startDate,
    super.endDate,
    super.minAmount,
    super.maxAmount,
    super.searchTerm,
    super.userId,
    super.sortBy,
    super.sortOrder,
    super.page,
    super.perPage,
    super.transactionCode,
    super.includeBalance,
    super.onlyWithCashback,
  });

  /// Cria um modelo a partir de um JSON
  factory CashbackFilterModel.fromJson(Map<String, dynamic> json) {
    return CashbackFilterModel(
      status: _parseTransactionStatusList(json['status']),
      storeId: json['store_id']?.toString() ?? json['loja_id']?.toString(),
      storeName: json['store_name']?.toString() ?? json['nome_loja']?.toString(),
      startDate: _parseDateTime(json['start_date'] ?? json['data_inicio']),
      endDate: _parseDateTime(json['end_date'] ?? json['data_fim']),
      minAmount: _parseDouble(json['min_amount'] ?? json['valor_minimo']),
      maxAmount: _parseDouble(json['max_amount'] ?? json['valor_maximo']),
      searchTerm: json['search_term']?.toString() ?? json['cliente']?.toString() ?? json['search']?.toString(),
      userId: json['user_id']?.toString() ?? json['usuario_id']?.toString(),
      sortBy: _parseSortBy(json['sort_by'] ?? json['ordenar_por']),
      sortOrder: _parseSortOrder(json['sort_order'] ?? json['ordem']),
      page: _parseInt(json['page'] ?? json['pagina']),
      perPage: _parseInt(json['per_page'] ?? json['por_pagina']),
      transactionCode: json['transaction_code']?.toString() ?? json['codigo_transacao']?.toString(),
      includeBalance: _parseBool(json['include_balance'] ?? json['incluir_saldo']),
      onlyWithCashback: _parseBool(json['only_with_cashback'] ?? json['apenas_com_cashback']),
    );
  }

  /// Converte o modelo para JSON
  Map<String, dynamic> toJson() {
    final json = <String, dynamic>{};

    if (status != null && status!.isNotEmpty) {
      json['status'] = status!.map((s) => s.value).toList();
    }
    if (storeId != null) json['store_id'] = storeId;
    if (storeName != null) json['store_name'] = storeName;
    if (startDate != null) json['start_date'] = _formatDate(startDate!);
    if (endDate != null) json['end_date'] = _formatDate(endDate!);
    if (minAmount != null) json['min_amount'] = minAmount;
    if (maxAmount != null) json['max_amount'] = maxAmount;
    if (searchTerm != null && searchTerm!.isNotEmpty) json['search_term'] = searchTerm;
    if (userId != null) json['user_id'] = userId;
    if (sortBy != null) json['sort_by'] = sortBy!.value;
    if (sortOrder != null) json['sort_order'] = sortOrder!.value;
    if (page != null) json['page'] = page;
    if (perPage != null) json['per_page'] = perPage;
    if (transactionCode != null) json['transaction_code'] = transactionCode;
    if (includeBalance != null) json['include_balance'] = includeBalance;
    if (onlyWithCashback != null) json['only_with_cashback'] = onlyWithCashback;

    return json;
  }

  /// Converte para o formato esperado pela API (nomes em português)
  Map<String, dynamic> toApiJson() {
    final json = <String, dynamic>{};

    if (status != null && status!.isNotEmpty) {
      if (status!.length == 1) {
        json['status'] = status!.first.value;
      } else {
        json['status'] = status!.map((s) => s.value).toList();
      }
    }
    if (storeId != null) json['loja_id'] = storeId;
    if (startDate != null) json['data_inicio'] = _formatDate(startDate!);
    if (endDate != null) json['data_fim'] = _formatDate(endDate!);
    if (minAmount != null) json['valor_minimo'] = minAmount;
    if (maxAmount != null) json['valor_maximo'] = maxAmount;
    if (searchTerm != null && searchTerm!.isNotEmpty) json['cliente'] = searchTerm;
    if (userId != null) json['usuario_id'] = userId;
    if (page != null) json['pagina'] = page;
    if (perPage != null) json['por_pagina'] = perPage;
    if (transactionCode != null) json['codigo_transacao'] = transactionCode;

    return json;
  }

  /// Converte para query parameters da URL
  Map<String, String> toQueryParams() {
    final params = <String, String>{};

    if (status != null && status!.isNotEmpty) {
      if (status!.length == 1) {
        params['status'] = status!.first.value;
      } else {
        for (int i = 0; i < status!.length; i++) {
          params['status[$i]'] = status![i].value;
        }
      }
    }
    if (storeId != null) params['loja_id'] = storeId!;
    if (startDate != null) params['data_inicio'] = _formatDate(startDate!);
    if (endDate != null) params['data_fim'] = _formatDate(endDate!);
    if (minAmount != null) params['valor_minimo'] = minAmount.toString();
    if (maxAmount != null) params['valor_maximo'] = maxAmount.toString();
    if (searchTerm != null && searchTerm!.isNotEmpty) params['cliente'] = searchTerm!;
    if (userId != null) params['usuario_id'] = userId!;
    if (page != null) params['pagina'] = page.toString();
    if (perPage != null) params['por_pagina'] = perPage.toString();
    if (transactionCode != null) params['codigo_transacao'] = transactionCode!;

    return params;
  }

  /// Cria uma cópia do modelo com campos atualizados
  CashbackFilterModel copyWith({
    List<CashbackTransactionStatus>? status,
    String? storeId,
    String? storeName,
    DateTime? startDate,
    DateTime? endDate,
    double? minAmount,
    double? maxAmount,
    String? searchTerm,
    String? userId,
    CashbackSortBy? sortBy,
    SortOrder? sortOrder,
    int? page,
    int? perPage,
    String? transactionCode,
    bool? includeBalance,
    bool? onlyWithCashback,
  }) {
    return CashbackFilterModel(
      status: status ?? this.status,
      storeId: storeId ?? this.storeId,
      storeName: storeName ?? this.storeName,
      startDate: startDate ?? this.startDate,
      endDate: endDate ?? this.endDate,
      minAmount: minAmount ?? this.minAmount,
      maxAmount: maxAmount ?? this.maxAmount,
      searchTerm: searchTerm ?? this.searchTerm,
      userId: userId ?? this.userId,
      sortBy: sortBy ?? this.sortBy,
      sortOrder: sortOrder ?? this.sortOrder,
      page: page ?? this.page,
      perPage: perPage ?? this.perPage,
      transactionCode: transactionCode ?? this.transactionCode,
      includeBalance: includeBalance ?? this.includeBalance,
      onlyWithCashback: onlyWithCashback ?? this.onlyWithCashback,
    );
  }

  /// Reseta os filtros mantendo apenas paginação
  CashbackFilterModel reset() {
    return CashbackFilterModel(
      page: page,
      perPage: perPage,
      sortBy: sortBy,
      sortOrder: sortOrder,
    );
  }

  /// Verifica se há filtros ativos (exceto paginação)
  bool get hasActiveFilters {
    return status != null && status!.isNotEmpty ||
           storeId != null ||
           startDate != null ||
           endDate != null ||
           minAmount != null ||
           maxAmount != null ||
           searchTerm != null && searchTerm!.isNotEmpty ||
           transactionCode != null && transactionCode!.isNotEmpty ||
           onlyWithCashback == true;
  }

  /// Retorna uma descrição legível dos filtros ativos
  String get activeFiltersDescription {
    final filters = <String>[];

    if (status != null && status!.isNotEmpty) {
      if (status!.length == 1) {
        filters.add('Status: ${status!.first.displayName}');
      } else {
        filters.add('Status: ${status!.map((s) => s.displayName).join(', ')}');
      }
    }
    if (storeName != null) filters.add('Loja: $storeName');
    if (startDate != null || endDate != null) {
      if (startDate != null && endDate != null) {
        filters.add('Período: ${_formatDate(startDate!)} - ${_formatDate(endDate!)}');
      } else if (startDate != null) {
        filters.add('A partir de: ${_formatDate(startDate!)}');
      } else if (endDate != null) {
        filters.add('Até: ${_formatDate(endDate!)}');
      }
    }
    if (minAmount != null || maxAmount != null) {
      if (minAmount != null && maxAmount != null) {
        filters.add('Valor: R\$ ${minAmount!.toStringAsFixed(2)} - R\$ ${maxAmount!.toStringAsFixed(2)}');
      } else if (minAmount != null) {
        filters.add('Valor mín.: R\$ ${minAmount!.toStringAsFixed(2)}');
      } else if (maxAmount != null) {
        filters.add('Valor máx.: R\$ ${maxAmount!.toStringAsFixed(2)}');
      }
    }
    if (searchTerm != null && searchTerm!.isNotEmpty) {
      filters.add('Busca: "$searchTerm"');
    }
    if (onlyWithCashback == true) {
      filters.add('Apenas com cashback');
    }

    return filters.join(' • ');
  }

  /// Helpers para parsing seguro dos dados

  static List<CashbackTransactionStatus>? _parseTransactionStatusList(dynamic value) {
    if (value == null) return null;

    if (value is String) {
      final status = _parseTransactionStatus(value);
      return status != null ? [status] : null;
    }

    if (value is List) {
      final statusList = value
          .map((item) => _parseTransactionStatus(item))
          .where((status) => status != null)
          .cast<CashbackTransactionStatus>()
          .toList();
      return statusList.isNotEmpty ? statusList : null;
    }

    return null;
  }

  static CashbackTransactionStatus? _parseTransactionStatus(dynamic value) {
    if (value == null) return null;

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
        return null;
    }
  }

  static DateTime? _parseDateTime(dynamic value) {
    if (value == null) return null;
    if (value is DateTime) return value;
    if (value is String && value.isNotEmpty) {
      try {
        // Tenta vários formatos de data
        if (value.contains('/')) {
          // Formato brasileiro: dd/mm/yyyy
          final parts = value.split('/');
          if (parts.length == 3) {
            return DateTime(
              int.parse(parts[2]), // ano
              int.parse(parts[1]), // mês
              int.parse(parts[0]), // dia
            );
          }
        }
        return DateTime.parse(value);
      } catch (e) {
        return null;
      }
    }
    return null;
  }

  static double? _parseDouble(dynamic value) {
    if (value == null) return null;
    if (value is double) return value;
    if (value is int) return value.toDouble();
    if (value is String && value.isNotEmpty) {
      // Remove formatação brasileira se presente
      final cleanValue = value.replaceAll(RegExp(r'[R$\s]'), '').replaceAll(',', '.');
      return double.tryParse(cleanValue);
    }
    return null;
  }

  static int? _parseInt(dynamic value) {
    if (value == null) return null;
    if (value is int) return value;
    if (value is String && value.isNotEmpty) {
      return int.tryParse(value);
    }
    return null;
  }

  static bool? _parseBool(dynamic value) {
    if (value == null) return null;
    if (value is bool) return value;
    if (value is String) {
      return value.toLowerCase() == 'true' || value == '1';
    }
    if (value is int) {
      return value == 1;
    }
    return null;
  }

  static CashbackSortBy? _parseSortBy(dynamic value) {
    if (value == null) return null;
    
    final stringValue = value.toString().toLowerCase();
    
    switch (stringValue) {
      case 'date':
      case 'data':
      case 'data_transacao':
        return CashbackSortBy.date;
      case 'amount':
      case 'valor':
      case 'valor_total':
        return CashbackSortBy.amount;
      case 'cashback':
      case 'valor_cashback':
        return CashbackSortBy.cashback;
      case 'store':
      case 'loja':
      case 'nome_loja':
        return CashbackSortBy.store;
      case 'status':
        return CashbackSortBy.status;
      default:
        return null;
    }
  }

  static SortOrder? _parseSortOrder(dynamic value) {
    if (value == null) return null;
    
    final stringValue = value.toString().toLowerCase();
    
    switch (stringValue) {
      case 'asc':
      case 'ascending':
      case 'crescente':
        return SortOrder.ascending;
      case 'desc':
      case 'descending':
      case 'decrescente':
        return SortOrder.descending;
      default:
        return null;
    }
  }

  static String _formatDate(DateTime date) {
    return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
  }

  @override
  String toString() {
    return 'CashbackFilterModel(${hasActiveFilters ? activeFiltersDescription : 'Sem filtros'})';
  }
}

/// Enum para ordenação das transações de cashback
enum CashbackSortBy {
  date('date'),
  amount('amount'),
  cashback('cashback'),
  store('store'),
  status('status');

  const CashbackSortBy(this.value);
  final String value;

  @override
  String toString() => value;

  String get displayName {
    switch (this) {
      case CashbackSortBy.date:
        return 'Data';
      case CashbackSortBy.amount:
        return 'Valor';
      case CashbackSortBy.cashback:
        return 'Cashback';
      case CashbackSortBy.store:
        return 'Loja';
      case CashbackSortBy.status:
        return 'Status';
    }
  }
}

/// Enum para ordem de classificação
enum SortOrder {
  ascending('asc'),
  descending('desc');

  const SortOrder(this.value);
  final String value;

  @override
  String toString() => value;

  String get displayName {
    switch (this) {
      case SortOrder.ascending:
        return 'Crescente';
      case SortOrder.descending:
        return 'Decrescente';
    }
  }
}

/// Enum reutilizado do transaction model
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
}