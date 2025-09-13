// lib/features/profile/domain/entities/profile.dart
// Entidade Profile - Representa o perfil do usuário no sistema Klube Cash

/// Entidade que representa o perfil completo de um usuário
///
/// Esta classe define a estrutura de dados do perfil do usuário,
/// incluindo informações pessoais, de contato e endereço
/// para o funcionamento completo do app Klube Cash
class Profile {
  final String id;
  final String name;
  final String email;
  final String cpf;
  final String? phone;
  final String? alternativeEmail;
  final String? profilePictureUrl;
  final ProfileAddress? address;
  final DateTime createdAt;
  final DateTime? updatedAt;
  final bool isActive;
  final bool isEmailVerified;
  final bool isCpfVerified;
  final double profileCompleteness;

  const Profile({
    required this.id,
    required this.name,
    required this.email,
    required this.cpf,
    this.phone,
    this.alternativeEmail,
    this.profilePictureUrl,
    this.address,
    required this.createdAt,
    this.updatedAt,
    this.isActive = true,
    this.isEmailVerified = false,
    this.isCpfVerified = false,
    this.profileCompleteness = 0.0,
  });

  /// Cria uma cópia da entidade com campos atualizados
  Profile copyWith({
    String? id,
    String? name,
    String? email,
    String? cpf,
    String? phone,
    String? alternativeEmail,
    String? profilePictureUrl,
    ProfileAddress? address,
    DateTime? createdAt,
    DateTime? updatedAt,
    bool? isActive,
    bool? isEmailVerified,
    bool? isCpfVerified,
    double? profileCompleteness,
  }) {
    return Profile(
      id: id ?? this.id,
      name: name ?? this.name,
      email: email ?? this.email,
      cpf: cpf ?? this.cpf,
      phone: phone ?? this.phone,
      alternativeEmail: alternativeEmail ?? this.alternativeEmail,
      profilePictureUrl: profilePictureUrl ?? this.profilePictureUrl,
      address: address ?? this.address,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      isActive: isActive ?? this.isActive,
      isEmailVerified: isEmailVerified ?? this.isEmailVerified,
      isCpfVerified: isCpfVerified ?? this.isCpfVerified,
      profileCompleteness: profileCompleteness ?? this.profileCompleteness,
    );
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;

    return other is Profile &&
        other.id == id &&
        other.name == name &&
        other.email == email &&
        other.cpf == cpf &&
        other.phone == phone &&
        other.alternativeEmail == alternativeEmail &&
        other.profilePictureUrl == profilePictureUrl &&
        other.address == address &&
        other.createdAt == createdAt &&
        other.updatedAt == updatedAt &&
        other.isActive == isActive &&
        other.isEmailVerified == isEmailVerified &&
        other.isCpfVerified == isCpfVerified &&
        other.profileCompleteness == profileCompleteness;
  }

  @override
  int get hashCode {
    return Object.hash(
      id,
      name,
      email,
      cpf,
      phone,
      alternativeEmail,
      profilePictureUrl,
      address,
      createdAt,
      updatedAt,
      isActive,
      isEmailVerified,
      isCpfVerified,
      profileCompleteness,
    );
  }

  @override
  String toString() {
    return 'Profile(id: $id, name: $name, email: $email, cpf: $cpf, profileCompleteness: $profileCompleteness%)';
  }
}

/// Entidade que representa o endereço do usuário
class ProfileAddress {
  final String cep;
  final String street;
  final String number;
  final String? complement;
  final String neighborhood;
  final String city;
  final String state;

  const ProfileAddress({
    required this.cep,
    required this.street,
    required this.number,
    this.complement,
    required this.neighborhood,
    required this.city,
    required this.state,
  });

  /// Cria uma cópia da entidade com campos atualizados
  ProfileAddress copyWith({
    String? cep,
    String? street,
    String? number,
    String? complement,
    String? neighborhood,
    String? city,
    String? state,
  }) {
    return ProfileAddress(
      cep: cep ?? this.cep,
      street: street ?? this.street,
      number: number ?? this.number,
      complement: complement ?? this.complement,
      neighborhood: neighborhood ?? this.neighborhood,
      city: city ?? this.city,
      state: state ?? this.state,
    );
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;

    return other is ProfileAddress &&
        other.cep == cep &&
        other.street == street &&
        other.number == number &&
        other.complement == complement &&
        other.neighborhood == neighborhood &&
        other.city == city &&
        other.state == state;
  }

  @override
  int get hashCode {
    return Object.hash(
      cep,
      street,
      number,
      complement,
      neighborhood,
      city,
      state,
    );
  }

  @override
  String toString() {
    return 'ProfileAddress(street: $street, $number, $neighborhood, $city - $state, CEP: $cep)';
  }
}