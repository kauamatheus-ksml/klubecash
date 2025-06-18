// lib/features/stores/data/models/store_model.dart
// Arquivo: Store Model - Modelo de dados da loja parceira - Camada Data

import 'package:equatable/equatable.dart';
import '../../domain/entities/store.dart';

/// Modelo de dados para loja parceira
/// 
/// Implementa serialização/deserialização JSON e estende a entidade
/// do domínio para seguir os princípios da Clean Architecture
class StoreModel extends Store with EquatableMixin {
  const StoreModel({
    required super.id,
    required super.nomeFantasia,
    required super.razaoSocial,
    required super.cnpj,
    required super.email,
    required super.telefone,
    required super.porcentagemCashback,
    required super.status,
    required super.dataCadastro,
    super.categoria,
    super.descricao,
    super.website,
    super.logo,
    super.dataAprovacao,
    super.observacao,
    super.endereco,
    super.isActive = true,
  });

  /// Cria instância a partir de JSON da API
  factory StoreModel.fromJson(Map<String, dynamic> json) {
    return StoreModel(
      id: _parseInt(json['id']),
      nomeFantasia: json['nome_fantasia'] ?? json['fantasy_name'] ?? '',
      razaoSocial: json['razao_social'] ?? json['corporate_name'] ?? '',
      cnpj: json['cnpj'] ?? '',
      email: json['email'] ?? '',
      telefone: json['telefone'] ?? json['phone'] ?? '',
      porcentagemCashback: _parseDouble(json['porcentagem_cashback'] ?? json['cashback_percentage']),
      categoria: json['categoria'] ?? json['category'],
      descricao: json['descricao'] ?? json['description'],
      website: json['website'],
      logo: json['logo'],
      status: _parseStoreStatus(json['status']),
      dataCadastro: _parseDateTime(json['data_cadastro'] ?? json['created_at']),
      dataAprovacao: json['data_aprovacao'] != null || json['approved_at'] != null
          ? _parseDateTime(json['data_aprovacao'] ?? json['approved_at'])
          : null,
      observacao: json['observacao'] ?? json['observation'],
      endereco: json['endereco'] != null ? StoreAddress.fromJson(json['endereco']) : null,
      isActive: json['ativo'] ?? json['is_active'] ?? true,
    );
  }

  /// Converte instância para JSON
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'nome_fantasia': nomeFantasia,
      'razao_social': razaoSocial,
      'cnpj': cnpj,
      'email': email,
      'telefone': telefone,
      'porcentagem_cashback': porcentagemCashback,
      'categoria': categoria,
      'descricao': descricao,
      'website': website,
      'logo': logo,
      'status': status.name,
      'data_cadastro': dataCadastro.toIso8601String(),
      'data_aprovacao': dataAprovacao?.toIso8601String(),
      'observacao': observacao,
      'endereco': endereco?.toJson(),
      'is_active': isActive,
    };
  }

  /// Cria cópia com valores atualizados
  StoreModel copyWith({
    int? id,
    String? nomeFantasia,
    String? razaoSocial,
    String? cnpj,
    String? email,
    String? telefone,
    double? porcentagemCashback,
    String? categoria,
    String? descricao,
    String? website,
    String? logo,
    StoreStatus? status,
    DateTime? dataCadastro,
    DateTime? dataAprovacao,
    String? observacao,
    StoreAddress? endereco,
    bool? isActive,
  }) {
    return StoreModel(
      id: id ?? this.id,
      nomeFantasia: nomeFantasia ?? this.nomeFantasia,
      razaoSocial: razaoSocial ?? this.razaoSocial,
      cnpj: cnpj ?? this.cnpj,
      email: email ?? this.email,
      telefone: telefone ?? this.telefone,
      porcentagemCashback: porcentagemCashback ?? this.porcentagemCashback,
      categoria: categoria ?? this.categoria,
      descricao: descricao ?? this.descricao,
      website: website ?? this.website,
      logo: logo ?? this.logo,
      status: status ?? this.status,
      dataCadastro: dataCadastro ?? this.dataCadastro,
      dataAprovacao: dataAprovacao ?? this.dataAprovacao,
      observacao: observacao ?? this.observacao,
      endereco: endereco ?? this.endereco,
      isActive: isActive ?? this.isActive,
    );
  }

  /// Cria StoreModel a partir de entidade Store
  factory StoreModel.fromEntity(Store store) {
    return StoreModel(
      id: store.id,
      nomeFantasia: store.nomeFantasia,
      razaoSocial: store.razaoSocial,
      cnpj: store.cnpj,
      email: store.email,
      telefone: store.telefone,
      porcentagemCashback: store.porcentagemCashback,
      categoria: store.categoria,
      descricao: store.descricao,
      website: store.website,
      logo: store.logo,
      status: store.status,
      dataCadastro: store.dataCadastro,
      dataAprovacao: store.dataAprovacao,
      observacao: store.observacao,
      endereco: store.endereco,
      isActive: store.isActive,
    );
  }

  /// Converte lista de JSON para lista de StoreModel
  static List<StoreModel> fromJsonList(List<dynamic> jsonList) {
    return jsonList
        .map((json) => StoreModel.fromJson(json as Map<String, dynamic>))
        .toList();
  }

  @override
  List<Object?> get props => [
        id,
        nomeFantasia,
        razaoSocial,
        cnpj,
        email,
        telefone,
        porcentagemCashback,
        categoria,
        descricao,
        website,
        logo,
        status,
        dataCadastro,
        dataAprovacao,
        observacao,
        endereco,
        isActive,
      ];
}

/// Modelo para endereço da loja
class StoreAddress with EquatableMixin {
  final int? id;
  final String cep;
  final String logradouro;
  final String numero;
  final String? complemento;
  final String bairro;
  final String cidade;
  final String estado;

  const StoreAddress({
    this.id,
    required this.cep,
    required this.logradouro,
    required this.numero,
    this.complemento,
    required this.bairro,
    required this.cidade,
    required this.estado,
  });

  factory StoreAddress.fromJson(Map<String, dynamic> json) {
    return StoreAddress(
      id: _parseInt(json['id']),
      cep: json['cep'] ?? '',
      logradouro: json['logradouro'] ?? json['street'] ?? '',
      numero: json['numero'] ?? json['number'] ?? '',
      complemento: json['complemento'] ?? json['complement'],
      bairro: json['bairro'] ?? json['neighborhood'] ?? '',
      cidade: json['cidade'] ?? json['city'] ?? '',
      estado: json['estado'] ?? json['state'] ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'cep': cep,
      'logradouro': logradouro,
      'numero': numero,
      'complemento': complemento,
      'bairro': bairro,
      'cidade': cidade,
      'estado': estado,
    };
  }

  @override
  List<Object?> get props => [
        id,
        cep,
        logradouro,
        numero,
        complemento,
        bairro,
        cidade,
        estado,
      ];
}

// Helper methods para parsing seguro de dados
int _parseInt(dynamic value) {
  if (value == null) return 0;
  if (value is int) return value;
  if (value is String) return int.tryParse(value) ?? 0;
  return 0;
}

double _parseDouble(dynamic value) {
  if (value == null) return 0.0;
  if (value is double) return value;
  if (value is int) return value.toDouble();
  if (value is String) return double.tryParse(value) ?? 0.0;
  return 0.0;
}

DateTime _parseDateTime(dynamic value) {
  if (value == null) return DateTime.now();
  if (value is DateTime) return value;
  if (value is String) {
    return DateTime.tryParse(value) ?? DateTime.now();
  }
  return DateTime.now();
}

StoreStatus _parseStoreStatus(dynamic value) {
  if (value == null) return StoreStatus.pending;
  
  final statusString = value.toString().toLowerCase();
  switch (statusString) {
    case 'aprovado':
    case 'approved':
    case 'ativo':
    case 'active':
      return StoreStatus.approved;
    case 'rejeitado':
    case 'rejected':
    case 'inativo':
    case 'inactive':
      return StoreStatus.rejected;
    case 'pendente':
    case 'pending':
    default:
      return StoreStatus.pending;
  }
}