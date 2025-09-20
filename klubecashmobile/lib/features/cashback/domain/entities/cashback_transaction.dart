// Arquivo: lib/features/cashback/domain/entities/cashback_transaction.dart
// Entidade que representa uma transação de cashback no sistema Klube Cash

/// Enum que representa os possíveis status de uma transação de cashback
enum CashbackTransactionStatus {
  /// Transação ainda não processada
  pendente,
  /// Transação aprovada e cashback liberado
  aprovado,
  /// Transação cancelada
  cancelado,
  /// Transação aprovada mas pagamento ainda pendente
  pagamentoPendente,
}

/// Entidade que representa uma transação de cashback
class CashbackTransaction {
  /// Identificador único da transação
  final int id;
  
  /// ID do usuário que realizou a transação
  final int usuarioId;
  
  /// ID da loja onde a transação foi realizada
  final int lojaId;
  
  /// Valor total da compra
  final double valorTotal;
  
  /// Valor total do cashback gerado
  final double valorCashback;
  
  /// Valor do cashback destinado ao cliente
  final double valorCliente;
  
  /// Valor da comissão destinada ao admin
  final double valorAdmin;
  
  /// Valor da comissão destinada à loja
  final double valorLoja;
  
  /// Código único da transação
  final String? codigoTransacao;
  
  /// Descrição adicional da transação
  final String? descricao;
  
  /// Data e hora da transação
  final DateTime dataTransacao;
  
  /// Status atual da transação
  final CashbackTransactionStatus status;
  
  /// Nome da loja (para exibição)
  final String? nomeLoja;
  
  /// Logo da loja (URL para exibição)
  final String? logoLoja;

  const CashbackTransaction({
    required this.id,
    required this.usuarioId,
    required this.lojaId,
    required this.valorTotal,
    required this.valorCashback,
    required this.valorCliente,
    required this.valorAdmin,
    required this.valorLoja,
    this.codigoTransacao,
    this.descricao,
    required this.dataTransacao,
    required this.status,
    this.nomeLoja,
    this.logoLoja,
  });

  /// Cria uma cópia da transação com os valores modificados
  CashbackTransaction copyWith({
    int? id,
    int? usuarioId,
    int? lojaId,
    double? valorTotal,
    double? valorCashback,
    double? valorCliente,
    double? valorAdmin,
    double? valorLoja,
    String? codigoTransacao,
    String? descricao,
    DateTime? dataTransacao,
    CashbackTransactionStatus? status,
    String? nomeLoja,
    String? logoLoja,
  }) {
    return CashbackTransaction(
      id: id ?? this.id,
      usuarioId: usuarioId ?? this.usuarioId,
      lojaId: lojaId ?? this.lojaId,
      valorTotal: valorTotal ?? this.valorTotal,
      valorCashback: valorCashback ?? this.valorCashback,
      valorCliente: valorCliente ?? this.valorCliente,
      valorAdmin: valorAdmin ?? this.valorAdmin,
      valorLoja: valorLoja ?? this.valorLoja,
      codigoTransacao: codigoTransacao ?? this.codigoTransacao,
      descricao: descricao ?? this.descricao,
      dataTransacao: dataTransacao ?? this.dataTransacao,
      status: status ?? this.status,
      nomeLoja: nomeLoja ?? this.nomeLoja,
      logoLoja: logoLoja ?? this.logoLoja,
    );
  }

  /// Verifica se a transação está aprovada
  bool get isAprovada => status == CashbackTransactionStatus.aprovado;
  
  /// Verifica se a transação está pendente
  bool get isPendente => status == CashbackTransactionStatus.pendente;
  
  /// Verifica se a transação foi cancelada
  bool get isCancelada => status == CashbackTransactionStatus.cancelado;
  
  /// Verifica se o pagamento está pendente
  bool get isPagamentoPendente => status == CashbackTransactionStatus.pagamentoPendente;

  /// Retorna o percentual de cashback da transação
  double get percentualCashback {
    if (valorTotal <= 0) return 0.0;
    return (valorCashback / valorTotal) * 100;
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    
    return other is CashbackTransaction &&
      other.id == id &&
      other.usuarioId == usuarioId &&
      other.lojaId == lojaId &&
      other.valorTotal == valorTotal &&
      other.valorCashback == valorCashback &&
      other.valorCliente == valorCliente &&
      other.valorAdmin == valorAdmin &&
      other.valorLoja == valorLoja &&
      other.codigoTransacao == codigoTransacao &&
      other.descricao == descricao &&
      other.dataTransacao == dataTransacao &&
      other.status == status &&
      other.nomeLoja == nomeLoja &&
      other.logoLoja == logoLoja;
  }

  @override
  int get hashCode {
    return id.hashCode ^
      usuarioId.hashCode ^
      lojaId.hashCode ^
      valorTotal.hashCode ^
      valorCashback.hashCode ^
      valorCliente.hashCode ^
      valorAdmin.hashCode ^
      valorLoja.hashCode ^
      codigoTransacao.hashCode ^
      descricao.hashCode ^
      dataTransacao.hashCode ^
      status.hashCode ^
      nomeLoja.hashCode ^
      logoLoja.hashCode;
  }

  @override
  String toString() {
    return 'CashbackTransaction(id: $id, usuarioId: $usuarioId, lojaId: $lojaId, '
        'valorTotal: $valorTotal, valorCashback: $valorCashback, status: $status, '
        'dataTransacao: $dataTransacao, codigoTransacao: $codigoTransacao)';
  }
}