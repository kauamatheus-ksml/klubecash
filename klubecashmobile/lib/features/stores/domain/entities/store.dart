// lib/features/stores/domain/entities/store.dart
// Entidade Store - Representa uma loja parceira do sistema Klube Cash

/// Entidade que representa uma loja parceira
///
/// Esta classe define a estrutura de uma loja parceira,
/// contendo todas as informações necessárias para o
/// funcionamento das funcionalidades de lojas no app Klube Cash
class Store {
  final String id;
  final String fantasyName;
  final String companyName;
  final String cnpj;
  final String email;
  final String phone;
  final String category;
  final double cashbackPercentage;
  final String? description;
  final String? website;
  final String? logoUrl;
  final String? userId;
  final String status;
  final DateTime createdAt;
  final DateTime? approvedAt;
  final String? observation;
  final StoreAddress? address;
  final bool isFavorite;
  final bool isNew;
  final double? rating;
  final int? reviewsCount;

  const Store({
    required this.id,
    required this.fantasyName,
    required this.companyName,
    required this.cnpj,
    required this.email,
    required this.phone,
    required this.category,
    required this.cashbackPercentage,
    this.description,
    this.website,
    this.logoUrl,
    this.userId,
    required this.status,
    required this.createdAt,
    this.approvedAt,
    this.observation,
    this.address,
    this.isFavorite = false,
    this.isNew = false,
    this.rating,
    this.reviewsCount,
  });

  /// Cria uma cópia da entidade com campos atualizados
  Store copyWith({
    String? id,
    String? fantasyName,
    String? companyName,
    String? cnpj,
    String? email,
    String? phone,
    String? category,
    double? cashbackPercentage,
    String? description,
    String? website,
    String? logoUrl,
    String? userId,
    String? status,
    DateTime? createdAt,
    DateTime? approvedAt,
    String? observation,
    StoreAddress? address,
    bool? isFavorite,
    bool? isNew,
    double? rating,
    int? reviewsCount,
  }) {
    return Store(
      id: id ?? this.id,
      fantasyName: fantasyName ?? this.fantasyName,
      companyName: companyName ?? this.companyName,
      cnpj: cnpj ?? this.cnpj,
      email: email ?? this.email,
      phone: phone ?? this.phone,
      category: category ?? this.category,
      cashbackPercentage: cashbackPercentage ?? this.cashbackPercentage,
      description: description ?? this.description,
      website: website ?? this.website,
      logoUrl: logoUrl ?? this.logoUrl,
      userId: userId ?? this.userId,
      status: status ?? this.status,
      createdAt: createdAt ?? this.createdAt,
      approvedAt: approvedAt ?? this.approvedAt,
      observation: observation ?? this.observation,
      address: address ?? this.address,
      isFavorite: isFavorite ?? this.isFavorite,
      isNew: isNew ?? this.isNew,
      rating: rating ?? this.rating,
      reviewsCount: reviewsCount ?? this.reviewsCount,
    );
  }

  /// Verifica se a loja está aprovada
  bool get isApproved => status == 'aprovado';

  /// Verifica se a loja está pendente
  bool get isPending => status == 'pendente';

  /// Verifica se a loja está rejeitada
  bool get isRejected => status == 'rejeitado';

  /// Retorna o percentual de cashback formatado
  String get formattedCashbackPercentage => '${cashbackPercentage.toStringAsFixed(1)}%';

  /// Retorna a avaliação formatada
  String get formattedRating => rating?.toStringAsFixed(1) ?? '0.0';

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;

    return other is Store &&
        other.id == id &&
        other.fantasyName == fantasyName &&
        other.companyName == companyName &&
        other.cnpj == cnpj &&
        other.email == email &&
        other.phone == phone &&
        other.category == category &&
        other.cashbackPercentage == cashbackPercentage &&
        other.description == description &&
        other.website == website &&
        other.logoUrl == logoUrl &&
        other.userId == userId &&
        other.status == status &&
        other.createdAt == createdAt &&
        other.approvedAt == approvedAt &&
        other.observation == observation &&
        other.address == address &&
        other.isFavorite == isFavorite &&
        other.isNew == isNew &&
        other.rating == rating &&
        other.reviewsCount == reviewsCount;
  }

  @override
  int get hashCode {
    return Object.hashAll([
      id,
      fantasyName,
      companyName,
      cnpj,
      email,
      phone,
      category,
      cashbackPercentage,
      description,
      website,
      logoUrl,
      userId,
      status,
      createdAt,
      approvedAt,
      observation,
      address,
      isFavorite,
      isNew,
      rating,
      reviewsCount,
    ]);
  }

  @override
  String toString() {
    return 'Store(id: $id, fantasyName: $fantasyName, category: $category, cashback: $formattedCashbackPercentage)';
  }
}

/// Entidade que representa o endereço de uma loja
class StoreAddress {
  final String? zipCode;
  final String? street;
  final String? number;
  final String? complement;
  final String? neighborhood;
  final String? city;
  final String? state;

  const StoreAddress({
    this.zipCode,
    this.street,
    this.number,
    this.complement,
    this.neighborhood,
    this.city,
    this.state,
  });

  /// Retorna o endereço completo formatado
  String get fullAddress {
    final parts = <String>[];
    
    if (street != null && street!.isNotEmpty) {
      String streetWithNumber = street!;
      if (number != null && number!.isNotEmpty) {
        streetWithNumber += ', $number';
      }
      parts.add(streetWithNumber);
    }
    
    if (neighborhood != null && neighborhood!.isNotEmpty) {
      parts.add(neighborhood!);
    }
    
    if (city != null && city!.isNotEmpty && state != null && state!.isNotEmpty) {
      parts.add('$city - $state');
    } else if (city != null && city!.isNotEmpty) {
      parts.add(city!);
    }
    
    if (zipCode != null && zipCode!.isNotEmpty) {
      parts.add('CEP: $zipCode');
    }
    
    return parts.join(', ');
  }

  /// Cria uma cópia do endereço com campos atualizados
  StoreAddress copyWith({
    String? zipCode,
    String? street,
    String? number,
    String? complement,
    String? neighborhood,
    String? city,
    String? state,
  }) {
    return StoreAddress(
      zipCode: zipCode ?? this.zipCode,
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

    return other is StoreAddress &&
        other.zipCode == zipCode &&
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
      zipCode,
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
    return 'StoreAddress($fullAddress)';
  }
}