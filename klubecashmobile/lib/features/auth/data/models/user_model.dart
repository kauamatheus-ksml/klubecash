// lib/features/auth/data/models/user_model.dart
// ARQUIVO #34 - UserModel - Modelo de dados do usuário para comunicação com API

import '../../domain/entities/user.dart';

/// Modelo de dados do usuário para comunicação com a API
///
/// Extende a entidade [User] e adiciona funcionalidades de
/// serialização/deserialização para JSON, seguindo o padrão
/// Clean Architecture onde os modelos implementam as entidades
class UserModel extends User {
  const UserModel({
    required super.id,
    required super.name,
    required super.email,
    required super.cpf,
    super.phone,
    super.alternativeEmail,
    super.profilePictureUrl,
    required super.createdAt,
    super.updatedAt,
    super.isActive,
    super.isEmailVerified,
    super.isCpfVerified,
  });

  /// Cria um UserModel a partir de um Map (resposta da API)
  ///
  /// [json] - Map contendo os dados do usuário vindos da API
  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id']?.toString() ?? '',
      name: json['nome'] ?? json['name'] ?? '',
      email: json['email'] ?? '',
      cpf: json['cpf'] ?? '',
      phone: json['telefone'] ?? json['phone'],
      alternativeEmail: json['email_alternativo'] ?? json['alternativeEmail'],
      profilePictureUrl: json['foto_perfil'] ?? json['profilePictureUrl'],
      createdAt: json['data_criacao'] != null
          ? DateTime.parse(json['data_criacao'])
          : json['createdAt'] != null
              ? DateTime.parse(json['createdAt'])
              : DateTime.now(),
      updatedAt: json['data_atualizacao'] != null
          ? DateTime.parse(json['data_atualizacao'])
          : json['updatedAt'] != null
              ? DateTime.parse(json['updatedAt'])
              : null,
      isActive: json['ativo'] ?? json['isActive'] ?? true,
      isEmailVerified: json['email_verificado'] ?? json['isEmailVerified'] ?? false,
      isCpfVerified: json['cpf_verificado'] ?? json['isCpfVerified'] ?? false,
    );
  }

  /// Converte o UserModel para Map (para envio à API)
  ///
  /// [includeId] - Se deve incluir o ID no Map (útil para updates)
  Map<String, dynamic> toJson({bool includeId = false}) {
    final Map<String, dynamic> map = {
      'nome': name,
      'email': email,
      'cpf': cpf,
      'telefone': phone,
      'email_alternativo': alternativeEmail,
      'foto_perfil': profilePictureUrl,
      'ativo': isActive,
      'email_verificado': isEmailVerified,
      'cpf_verificado': isCpfVerified,
    };

    if (includeId) {
      map['id'] = id;
    }

    if (updatedAt != null) {
      map['data_atualizacao'] = updatedAt!.toIso8601String();
    }

    return map;
  }

  /// Cria um UserModel a partir de uma entidade User
  ///
  /// [user] - Entidade User do domínio
  factory UserModel.fromEntity(User user) {
    return UserModel(
      id: user.id,
      name: user.name,
      email: user.email,
      cpf: user.cpf,
      phone: user.phone,
      alternativeEmail: user.alternativeEmail,
      profilePictureUrl: user.profilePictureUrl,
      createdAt: user.createdAt,
      updatedAt: user.updatedAt,
      isActive: user.isActive,
      isEmailVerified: user.isEmailVerified,
      isCpfVerified: user.isCpfVerified,
    );
  }

  /// Converte o UserModel para entidade User
  ///
  /// Usado para passar dados da camada de dados para o domínio
  User toEntity() {
    return User(
      id: id,
      name: name,
      email: email,
      cpf: cpf,
      phone: phone,
      alternativeEmail: alternativeEmail,
      profilePictureUrl: profilePictureUrl,
      createdAt: createdAt,
      updatedAt: updatedAt,
      isActive: isActive,
      isEmailVerified: isEmailVerified,
      isCpfVerified: isCpfVerified,
    );
  }

  /// Cria uma cópia do UserModel com campos atualizados
  ///
  /// Sobrescreve o método copyWith da entidade para retornar UserModel
  @override
  UserModel copyWith({
    String? id,
    String? name,
    String? email,
    String? cpf,
    String? phone,
    String? alternativeEmail,
    String? profilePictureUrl,
    DateTime? createdAt,
    DateTime? updatedAt,
    bool? isActive,
    bool? isEmailVerified,
    bool? isCpfVerified,
  }) {
    return UserModel(
      id: id ?? this.id,
      name: name ?? this.name,
      email: email ?? this.email,
      cpf: cpf ?? this.cpf,
      phone: phone ?? this.phone,
      alternativeEmail: alternativeEmail ?? this.alternativeEmail,
      profilePictureUrl: profilePictureUrl ?? this.profilePictureUrl,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      isActive: isActive ?? this.isActive,
      isEmailVerified: isEmailVerified ?? this.isEmailVerified,
      isCpfVerified: isCpfVerified ?? this.isCpfVerified,
    );
  }

  /// Valida se os dados essenciais do usuário estão presentes
  ///
  /// Retorna [true] se o usuário tem todos os dados obrigatórios
  bool get isValid {
    return id.isNotEmpty &&
        name.isNotEmpty &&
        email.isNotEmpty &&
        cpf.isNotEmpty;
  }

  /// Verifica se o perfil do usuário está completo
  ///
  /// Usado para determinar se o usuário precisa completar dados
  bool get isProfileComplete {
    return isValid &&
        phone != null &&
        phone!.isNotEmpty &&
        isEmailVerified &&
        isCpfVerified;
  }

  /// Retorna o nome de exibição (primeiro nome)
  ///
  /// Útil para saudações e interfaces mais casuais
  String get displayName {
    final nameParts = name.trim().split(' ');
    return nameParts.isNotEmpty ? nameParts.first : 'Usuário';
  }

  /// Retorna as iniciais do nome para avatares
  ///
  /// Máximo de 2 caracteres das iniciais do nome
  String get initials {
    final nameParts = name.trim().split(' ');
    if (nameParts.isEmpty) return 'U';
    
    if (nameParts.length == 1) {
      return nameParts.first.substring(0, 1).toUpperCase();
    }
    
    return (nameParts.first.substring(0, 1) + 
            nameParts.last.substring(0, 1)).toUpperCase();
  }

  @override
  String toString() {
    return 'UserModel(id: $id, name: $name, email: $email, cpf: $cpf, isActive: $isActive)';
  }

  /// Lista de propriedades para comparação de igualdade
  @override
  List<Object?> get props => [
        id,
        name,
        email,
        cpf,
        phone,
        alternativeEmail,
        profilePictureUrl,
        createdAt,
        updatedAt,
        isActive,
        isEmailVerified,
        isCpfVerified,
      ];
}