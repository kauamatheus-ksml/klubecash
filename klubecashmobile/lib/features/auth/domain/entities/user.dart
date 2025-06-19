// lib/features/auth/domain/entities/user.dart
// Entidade User - Representa o usuário do sistema Klube Cash

/// Entidade que representa um usuário do sistema
///
/// Esta classe define a estrutura básica de um usuário,
/// contendo todas as informações pessoais necessárias
/// para o funcionamento do app Klube Cash
/// 
/// 
/// 

enum UserType { client, store, admin }

class User {
  final String id;
  final String name;
  final String email;
  final String cpf;
  final UserType type; // Adicione esta linha
  final String? phone;
  final String? alternativeEmail;
  final String? profilePictureUrl;
  final DateTime createdAt;
  final DateTime? updatedAt;
  final bool isActive;
  final bool isEmailVerified;
  final bool isCpfVerified;

  const User({
    required this.id,
    required this.name,
    required this.email,
    required this.cpf,
    this.type = UserType.client, // Adicione esta linha com um valor padrão
    this.phone,
    this.alternativeEmail,
    this.profilePictureUrl,
    required this.createdAt,
    this.updatedAt,
    this.isActive = true,
    this.isEmailVerified = false,
    this.isCpfVerified = false,
  });

  /// Cria uma cópia da entidade com campos atualizados
  User copyWith({
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
    return User(
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

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;

    return other is User &&
        other.id == id &&
        other.name == name &&
        other.email == email &&
        other.cpf == cpf &&
        other.phone == phone &&
        other.alternativeEmail == alternativeEmail &&
        other.profilePictureUrl == profilePictureUrl &&
        other.createdAt == createdAt &&
        other.updatedAt == updatedAt &&
        other.isActive == isActive &&
        other.isEmailVerified == isEmailVerified &&
        other.isCpfVerified == isCpfVerified;
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
      createdAt,
      updatedAt,
      isActive,
      isEmailVerified,
      isCpfVerified,
    );
  }

  @override
  String toString() {
    return 'User(id: $id, name: $name, email: $email, cpf: $cpf)';
  }
}
