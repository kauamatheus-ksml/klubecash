// lib/features/profile/data/models/profile_model.dart
// ARQUIVO #101 - ProfileModel - Modelo de dados do perfil para comunicação com API

import '../../domain/entities/profile.dart';

/// Modelo de dados do perfil para comunicação com a API
///
/// Extende a entidade [Profile] e adiciona funcionalidades de
/// serialização/deserialização para JSON, seguindo o padrão
/// Clean Architecture onde os modelos implementam as entidades
class ProfileModel extends Profile {
  const ProfileModel({
    required super.id,
    required super.name,
    required super.email,
    required super.cpf,
    super.phone,
    super.alternativeEmail,
    super.profilePictureUrl,
    super.cep,
    super.street,
    super.streetNumber,
    super.complement,
    super.neighborhood,
    super.city,
    super.state,
    required super.createdAt,
    super.updatedAt,
    super.isActive,
    super.isEmailVerified,
    super.isCpfVerified,
    super.completionPercentage,
  });

  /// Cria um ProfileModel a partir de um Map (resposta da API)
  ///
  /// [json] - Map contendo os dados do perfil vindos da API
  factory ProfileModel.fromJson(Map<String, dynamic> json) {
    return ProfileModel(
      id: json['id']?.toString() ?? '',
      name: json['nome'] ?? json['name'] ?? '',
      email: json['email'] ?? '',
      cpf: json['cpf'] ?? '',
      phone: json['telefone'] ?? json['phone'],
      alternativeEmail: json['email_alternativo'] ?? json['alternativeEmail'],
      profilePictureUrl: json['foto_perfil'] ?? json['profilePictureUrl'],
      cep: json['cep'],
      street: json['logradouro'] ?? json['street'],
      streetNumber: json['numero'] ?? json['streetNumber'],
      complement: json['complemento'] ?? json['complement'],
      neighborhood: json['bairro'] ?? json['neighborhood'],
      city: json['cidade'] ?? json['city'],
      state: json['estado'] ?? json['state'],
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
      completionPercentage: json['percentual_conclusao']?.toDouble() ?? 
                           json['completionPercentage']?.toDouble() ?? 0.0,
    );
  }

  /// Converte o ProfileModel para Map (para envio à API)
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
      'cep': cep,
      'logradouro': street,
      'numero': streetNumber,
      'complemento': complement,
      'bairro': neighborhood,
      'cidade': city,
      'estado': state,
      'ativo': isActive,
      'email_verificado': isEmailVerified,
      'cpf_verificado': isCpfVerified,
      'percentual_conclusao': completionPercentage,
    };

    if (includeId) {
      map['id'] = id;
    }

    if (updatedAt != null) {
      map['data_atualizacao'] = updatedAt!.toIso8601String();
    }

    return map;
  }

  /// Cria um ProfileModel a partir de uma entidade Profile
  ///
  /// [profile] - Entidade Profile do domínio
  factory ProfileModel.fromEntity(Profile profile) {
    return ProfileModel(
      id: profile.id,
      name: profile.name,
      email: profile.email,
      cpf: profile.cpf,
      phone: profile.phone,
      alternativeEmail: profile.alternativeEmail,
      profilePictureUrl: profile.profilePictureUrl,
      cep: profile.cep,
      street: profile.street,
      streetNumber: profile.streetNumber,
      complement: profile.complement,
      neighborhood: profile.neighborhood,
      city: profile.city,
      state: profile.state,
      createdAt: profile.createdAt,
      updatedAt: profile.updatedAt,
      isActive: profile.isActive,
      isEmailVerified: profile.isEmailVerified,
      isCpfVerified: profile.isCpfVerified,
      completionPercentage: profile.completionPercentage,
    );
  }

  /// Converte o ProfileModel para entidade Profile
  ///
  /// Usado para passar dados da camada de dados para o domínio
  Profile toEntity() {
    return Profile(
      id: id,
      name: name,
      email: email,
      cpf: cpf,
      phone: phone,
      alternativeEmail: alternativeEmail,
      profilePictureUrl: profilePictureUrl,
      cep: cep,
      street: street,
      streetNumber: streetNumber,
      complement: complement,
      neighborhood: neighborhood,
      city: city,
      state: state,
      createdAt: createdAt,
      updatedAt: updatedAt,
      isActive: isActive,
      isEmailVerified: isEmailVerified,
      isCpfVerified: isCpfVerified,
      completionPercentage: completionPercentage,
    );
  }

  /// Cria uma cópia do ProfileModel com campos atualizados
  ///
  /// Sobrescreve o método copyWith da entidade para retornar ProfileModel
  @override
  ProfileModel copyWith({
    String? id,
    String? name,
    String? email,
    String? cpf,
    String? phone,
    String? alternativeEmail,
    String? profilePictureUrl,
    String? cep,
    String? street,
    String? streetNumber,
    String? complement,
    String? neighborhood,
    String? city,
    String? state,
    DateTime? createdAt,
    DateTime? updatedAt,
    bool? isActive,
    bool? isEmailVerified,
    bool? isCpfVerified,
    double? completionPercentage,
  }) {
    return ProfileModel(
      id: id ?? this.id,
      name: name ?? this.name,
      email: email ?? this.email,
      cpf: cpf ?? this.cpf,
      phone: phone ?? this.phone,
      alternativeEmail: alternativeEmail ?? this.alternativeEmail,
      profilePictureUrl: profilePictureUrl ?? this.profilePictureUrl,
      cep: cep ?? this.cep,
      street: street ?? this.street,
      streetNumber: streetNumber ?? this.streetNumber,
      complement: complement ?? this.complement,
      neighborhood: neighborhood ?? this.neighborhood,
      city: city ?? this.city,
      state: state ?? this.state,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      isActive: isActive ?? this.isActive,
      isEmailVerified: isEmailVerified ?? this.isEmailVerified,
      isCpfVerified: isCpfVerified ?? this.isCpfVerified,
      completionPercentage: completionPercentage ?? this.completionPercentage,
    );
  }

  /// Valida se os dados essenciais do perfil estão presentes
  ///
  /// Retorna [true] se o perfil tem todos os dados obrigatórios
  bool get isValid {
    return id.isNotEmpty &&
        name.isNotEmpty &&
        email.isNotEmpty &&
        cpf.isNotEmpty;
  }

  /// Verifica se o perfil está completo
  ///
  /// Usado para determinar se o usuário precisa completar dados
  bool get isProfileComplete {
    return isValid &&
        phone != null &&
        phone!.isNotEmpty &&
        isEmailVerified &&
        isCpfVerified &&
        hasCompleteAddress;
  }

  /// Verifica se o endereço está completo
  ///
  /// Retorna [true] se todos os campos de endereço estão preenchidos
  bool get hasCompleteAddress {
    return cep != null &&
        cep!.isNotEmpty &&
        street != null &&
        street!.isNotEmpty &&
        streetNumber != null &&
        streetNumber!.isNotEmpty &&
        neighborhood != null &&
        neighborhood!.isNotEmpty &&
        city != null &&
        city!.isNotEmpty &&
        state != null &&
        state!.isNotEmpty;
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

  /// Retorna endereço formatado para exibição
  ///
  /// Formato: "Rua Nome, 123 - Bairro, Cidade/UF"
  String? get formattedAddress {
    if (!hasCompleteAddress) return null;
    
    final addressParts = <String>[];
    
    if (street!.isNotEmpty) {
      addressParts.add('$street, $streetNumber');
    }
    
    if (complement != null && complement!.isNotEmpty) {
      addressParts.add(complement!);
    }
    
    if (neighborhood!.isNotEmpty) {
      addressParts.add(neighborhood!);
    }
    
    if (city!.isNotEmpty && state!.isNotEmpty) {
      addressParts.add('$city/$state');
    }
    
    return addressParts.join(' - ');
  }

  /// Calcula o percentual de conclusão do perfil
  ///
  /// Baseado nos campos obrigatórios e opcionais preenchidos
  double calculateCompletionPercentage() {
    int totalFields = 12; // Campos considerados para completude
    int filledFields = 0;
    
    // Campos obrigatórios (peso maior)
    if (id.isNotEmpty) filledFields++;
    if (name.isNotEmpty) filledFields++;
    if (email.isNotEmpty) filledFields++;
    if (cpf.isNotEmpty) filledFields++;
    
    // Campos importantes
    if (phone != null && phone!.isNotEmpty) filledFields++;
    if (isEmailVerified) filledFields++;
    if (isCpfVerified) filledFields++;
    
    // Campos de endereço
    if (cep != null && cep!.isNotEmpty) filledFields++;
    if (street != null && street!.isNotEmpty) filledFields++;
    if (neighborhood != null && neighborhood!.isNotEmpty) filledFields++;
    if (city != null && city!.isNotEmpty) filledFields++;
    if (state != null && state!.isNotEmpty) filledFields++;
    
    return (filledFields / totalFields * 100).clamp(0.0, 100.0);
  }

  @override
  String toString() {
    return 'ProfileModel(id: $id, name: $name, email: $email, cpf: $cpf, completion: ${completionPercentage}%)';
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
        cep,
        street,
        streetNumber,
        complement,
        neighborhood,
        city,
        state,
        createdAt,
        updatedAt,
        isActive,
        isEmailVerified,
        isCpfVerified,
        completionPercentage,
      ];
}