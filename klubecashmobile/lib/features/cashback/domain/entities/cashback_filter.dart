// Arquivo: lib/features/cashback/domain/entities/cashback_filter.dart
// Entidade que representa os filtros disponíveis para consultas de cashback

import 'package:klube_cash/features/cashback/domain/entities/cashback_transaction.dart';

/// Enum que representa as opções de ordenação disponíveis
enum CashbackSortOption {
  /// Ordenar por data da transação
  data,
  /// Ordenar por valor da transação
  valorTransacao,
  /// Ordenar por valor do cashback
  valorCashback,
  /// Ordenar por nome da loja
  nomeLoja,
  /// Ordenar por categoria da loja
  categoria,
}

/// Enum que representa a direção da ordenação
enum SortDirection {
  /// Ordem crescente (ASC)
  asc,
  /// Ordem decrescente (DESC)
  desc,
}

/// Entidade que representa os filtros para consulta de transações de cashback
class CashbackFilter {
  /// Data de início do período de consulta
  final DateTime? dataInicio;
  
  /// Data de fim do período de consulta
  final DateTime? dataFim;
  
  /// Lista de status de transação para filtrar
  final List<CashbackTransactionStatus>? status;
  
  /// Lista de IDs das lojas para filtrar
  final List<int>? lojaIds;
  
  /// Texto para busca no nome da loja
  final String? textoBusca;
  
  /// Categoria da loja para filtrar
  final String? categoria;
  
  /// Valor mínimo do cashback para filtrar
  final double? cashbackMinimo;
  
  /// Valor máximo do cashback para filtrar
  final double? cashbackMaximo;
  
  /// Valor mínimo da transação para filtrar
  final double? valorMinimoTransacao;
  
  /// Valor máximo da transação para filtrar
  final double? valorMaximoTransacao;
  
  /// Campo para ordenação
  final CashbackSortOption orderBy;
  
  /// Direção da ordenação
  final SortDirection sortDirection;
  
  /// Número da página para paginação
  final int? pagina;
  
  /// Número de itens por página
  final int? itensPorPagina;

  const CashbackFilter({
    this.dataInicio,
    this.dataFim,
    this.status,
    this.lojaIds,
    this.textoBusca,
    this.categoria,
    this.cashbackMinimo,
    this.cashbackMaximo,
    this.valorMinimoTransacao,
    this.valorMaximoTransacao,
    this.orderBy = CashbackSortOption.data,
    this.sortDirection = SortDirection.desc,
    this.pagina,
    this.itensPorPagina,
  });

  /// Cria uma cópia do filtro com os valores modificados
  CashbackFilter copyWith({
    DateTime? dataInicio,
    DateTime? dataFim,
    List<CashbackTransactionStatus>? status,
    List<int>? lojaIds,
    String? textoBusca,
    String? categoria,
    double? cashbackMinimo,
    double? cashbackMaximo,
    double? valorMinimoTransacao,
    double? valorMaximoTransacao,
    CashbackSortOption? orderBy,
    SortDirection? sortDirection,
    int? pagina,
    int? itensPorPagina,
  }) {
    return CashbackFilter(
      dataInicio: dataInicio ?? this.dataInicio,
      dataFim: dataFim ?? this.dataFim,
      status: status ?? this.status,
      lojaIds: lojaIds ?? this.lojaIds,
      textoBusca: textoBusca ?? this.textoBusca,
      categoria: categoria ?? this.categoria,
      cashbackMinimo: cashbackMinimo ?? this.cashbackMinimo,
      cashbackMaximo: cashbackMaximo ?? this.cashbackMaximo,
      valorMinimoTransacao: valorMinimoTransacao ?? this.valorMinimoTransacao,
      valorMaximoTransacao: valorMaximoTransacao ?? this.valorMaximoTransacao,
      orderBy: orderBy ?? this.orderBy,
      sortDirection: sortDirection ?? this.sortDirection,
      pagina: pagina ?? this.pagina,
      itensPorPagina: itensPorPagina ?? this.itensPorPagina,
    );
  }

  /// Limpa todos os filtros, mantendo apenas a ordenação padrão
  CashbackFilter clear() {
    return const CashbackFilter(
      orderBy: CashbackSortOption.data,
      sortDirection: SortDirection.desc,
    );
  }

  /// Verifica se algum filtro está ativo
  bool get hasActiveFilters {
    return dataInicio != null ||
        dataFim != null ||
        (status != null && status!.isNotEmpty) ||
        (lojaIds != null && lojaIds!.isNotEmpty) ||
        (textoBusca != null && textoBusca!.isNotEmpty) ||
        (categoria != null && categoria!.isNotEmpty) ||
        cashbackMinimo != null ||
        cashbackMaximo != null ||
        valorMinimoTransacao != null ||
        valorMaximoTransacao != null;
  }

  /// Retorna o número de filtros ativos
  int get activeFiltersCount {
    int count = 0;
    
    if (dataInicio != null || dataFim != null) count++;
    if (status != null && status!.isNotEmpty) count++;
    if (lojaIds != null && lojaIds!.isNotEmpty) count++;
    if (textoBusca != null && textoBusca!.isNotEmpty) count++;
    if (categoria != null && categoria!.isNotEmpty) count++;
    if (cashbackMinimo != null) count++;
    if (cashbackMaximo != null) count++;
    if (valorMinimoTransacao != null) count++;
    if (valorMaximoTransacao != null) count++;
    
    return count;
  }

  /// Verifica se há filtro de período ativo
  bool get hasPeriodFilter => dataInicio != null || dataFim != null;

  /// Verifica se há filtro de status ativo
  bool get hasStatusFilter => status != null && status!.isNotEmpty;

  /// Verifica se há filtro de lojas ativo
  bool get hasStoreFilter => lojaIds != null && lojaIds!.isNotEmpty;

  /// Verifica se há filtro de valores ativo
  bool get hasValueFilter => 
      cashbackMinimo != null || 
      cashbackMaximo != null || 
      valorMinimoTransacao != null || 
      valorMaximoTransacao != null;

  /// Converte para Map para facilitar uso em APIs
  Map<String, dynamic> toMap() {
    final map = <String, dynamic>{};
    
    if (dataInicio != null) {
      map['data_inicio'] = dataInicio!.toIso8601String();
    }
    
    if (dataFim != null) {
      map['data_fim'] = dataFim!.toIso8601String();
    }
    
    if (status != null && status!.isNotEmpty) {
      map['status'] = status!.map((s) => s.name).toList();
    }
    
    if (lojaIds != null && lojaIds!.isNotEmpty) {
      map['loja_ids'] = lojaIds;
    }
    
    if (textoBusca != null && textoBusca!.isNotEmpty) {
      map['texto_busca'] = textoBusca;
    }
    
    if (categoria != null && categoria!.isNotEmpty) {
      map['categoria'] = categoria;
    }
    
    if (cashbackMinimo != null) {
      map['cashback_minimo'] = cashbackMinimo;
    }
    
    if (cashbackMaximo != null) {
      map['cashback_maximo'] = cashbackMaximo;
    }
    
    if (valorMinimoTransacao != null) {
      map['valor_minimo_transacao'] = valorMinimoTransacao;
    }
    
    if (valorMaximoTransacao != null) {
      map['valor_maximo_transacao'] = valorMaximoTransacao;
    }
    
    map['order_by'] = orderBy.name;
    map['sort_direction'] = sortDirection.name;
    
    if (pagina != null) {
      map['pagina'] = pagina;
    }
    
    if (itensPorPagina != null) {
      map['itens_por_pagina'] = itensPorPagina;
    }
    
    return map;
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    
    return other is CashbackFilter &&
      other.dataInicio == dataInicio &&
      other.dataFim == dataFim &&
      _listEquals(other.status, status) &&
      _listEquals(other.lojaIds, lojaIds) &&
      other.textoBusca == textoBusca &&
      other.categoria == categoria &&
      other.cashbackMinimo == cashbackMinimo &&
      other.cashbackMaximo == cashbackMaximo &&
      other.valorMinimoTransacao == valorMinimoTransacao &&
      other.valorMaximoTransacao == valorMaximoTransacao &&
      other.orderBy == orderBy &&
      other.sortDirection == sortDirection &&
      other.pagina == pagina &&
      other.itensPorPagina == itensPorPagina;
  }

  @override
  int get hashCode {
    return dataInicio.hashCode ^
      dataFim.hashCode ^
      status.hashCode ^
      lojaIds.hashCode ^
      textoBusca.hashCode ^
      categoria.hashCode ^
      cashbackMinimo.hashCode ^
      cashbackMaximo.hashCode ^
      valorMinimoTransacao.hashCode ^
      valorMaximoTransacao.hashCode ^
      orderBy.hashCode ^
      sortDirection.hashCode ^
      pagina.hashCode ^
      itensPorPagina.hashCode;
  }

  @override
  String toString() {
    return 'CashbackFilter('
        'dataInicio: $dataInicio, '
        'dataFim: $dataFim, '
        'status: $status, '
        'lojaIds: $lojaIds, '
        'textoBusca: $textoBusca, '
        'categoria: $categoria, '
        'orderBy: $orderBy, '
        'sortDirection: $sortDirection, '
        'activeFilters: $activeFiltersCount'
        ')';
  }

  /// Função auxiliar para comparar listas
  bool _listEquals<T>(List<T>? a, List<T>? b) {
    if (a == null) return b == null;
    if (b == null || a.length != b.length) return false;
    for (int index = 0; index < a.length; index += 1) {
      if (a[index] != b[index]) return false;
    }
    return true;
  }
}

/// Extensões para facilitar o uso dos enums
extension CashbackSortOptionExtension on CashbackSortOption {
  /// Retorna o nome amigável da opção de ordenação
  String get displayName {
    switch (this) {
      case CashbackSortOption.data:
        return 'Data';
      case CashbackSortOption.valorTransacao:
        return 'Valor da Transação';
      case CashbackSortOption.valorCashback:
        return 'Valor do Cashback';
      case CashbackSortOption.nomeLoja:
        return 'Nome da Loja';
      case CashbackSortOption.categoria:
        return 'Categoria';
    }
  }
}

extension SortDirectionExtension on SortDirection {
  /// Retorna o nome amigável da direção de ordenação
  String get displayName {
    switch (this) {
      case SortDirection.asc:
        return 'Crescente';
      case SortDirection.desc:
        return 'Decrescente';
    }
  }
}