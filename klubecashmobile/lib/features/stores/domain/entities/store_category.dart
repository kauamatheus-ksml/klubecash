// lib/features/stores/domain/entities/store_category.dart
// Entidade StoreCategory - Representa uma categoria de loja do sistema Klube Cash

/// Entidade que representa uma categoria de loja
///
/// Esta classe define a estrutura de uma categoria de loja,
/// contendo todas as informações necessárias para classificar
/// e filtrar lojas parceiras no app Klube Cash
class StoreCategory {
  final String id;
  final String name;
  final String slug;
  final String? description;
  final String? iconName;
  final String? colorHex;
  final int sortOrder;
  final bool isActive;
  final int storeCount;

  const StoreCategory({
    required this.id,
    required this.name,
    required this.slug,
    this.description,
    this.iconName,
    this.colorHex,
    this.sortOrder = 0,
    this.isActive = true,
    this.storeCount = 0,
  });

  /// Categorias pré-definidas do sistema
  static const StoreCategory all = StoreCategory(
    id: 'all',
    name: 'Todas',
    slug: 'todas',
    description: 'Todas as categorias de lojas',
    iconName: 'grid_view',
    sortOrder: 0,
  );

  static const StoreCategory food = StoreCategory(
    id: 'food',
    name: 'Alimentação',
    slug: 'alimentacao',
    description: 'Restaurantes, lanchonetes, delivery e mercados',
    iconName: 'restaurant',
    colorHex: '#FF6B35',
    sortOrder: 1,
  );

  static const StoreCategory fashion = StoreCategory(
    id: 'fashion',
    name: 'Moda',
    slug: 'moda',
    description: 'Roupas, calçados, acessórios e vestuário em geral',
    iconName: 'checkroom',
    colorHex: '#E91E63',
    sortOrder: 2,
  );

  static const StoreCategory services = StoreCategory(
    id: 'services',
    name: 'Serviços',
    slug: 'servicos',
    description: 'Prestação de serviços diversos',
    iconName: 'build',
    colorHex: '#2196F3',
    sortOrder: 3,
  );

  static const StoreCategory technology = StoreCategory(
    id: 'technology',
    name: 'Tecnologia',
    slug: 'tecnologia',
    description: 'Eletrônicos, gadgets, informática e inovação',
    iconName: 'devices',
    colorHex: '#9C27B0',
    sortOrder: 4,
  );

  static const StoreCategory health = StoreCategory(
    id: 'health',
    name: 'Saúde e Beleza',
    slug: 'saude',
    description: 'Farmácias, clínicas, salões e produtos de beleza',
    iconName: 'local_hospital',
    colorHex: '#4CAF50',
    sortOrder: 5,
  );

  static const StoreCategory home = StoreCategory(
    id: 'home',
    name: 'Casa e Decoração',
    slug: 'casa',
    description: 'Móveis, decoração, utensílios domésticos e jardim',
    iconName: 'home',
    colorHex: '#FF9800',
    sortOrder: 6,
  );

  static const StoreCategory sports = StoreCategory(
    id: 'sports',
    name: 'Esportes',
    slug: 'esportes',
    description: 'Artigos esportivos, academias e atividades físicas',
    iconName: 'sports_soccer',
    colorHex: '#00BCD4',
    sortOrder: 7,
  );

  static const StoreCategory education = StoreCategory(
    id: 'education',
    name: 'Educação',
    slug: 'educacao',
    description: 'Cursos, livros, material escolar e capacitação',
    iconName: 'school',
    colorHex: '#3F51B5',
    sortOrder: 8,
  );

  static const StoreCategory travel = StoreCategory(
    id: 'travel',
    name: 'Viagem',
    slug: 'viagem',
    description: 'Hotéis, passagens, turismo e hospedagem',
    iconName: 'flight',
    colorHex: '#607D8B',
    sortOrder: 9,
  );

  static const StoreCategory others = StoreCategory(
    id: 'others',
    name: 'Outros',
    slug: 'outros',
    description: 'Outras categorias não especificadas',
    iconName: 'category',
    colorHex: '#795548',
    sortOrder: 99,
  );

  /// Lista de todas as categorias disponíveis
  static const List<StoreCategory> defaultCategories = [
    all,
    food,
    fashion,
    services,
    technology,
    health,
    home,
    sports,
    education,
    travel,
    others,
  ];

  /// Lista de categorias para filtros (sem "Todas")
  static const List<StoreCategory> filterCategories = [
    food,
    fashion,
    services,
    technology,
    health,
    home,
    sports,
    education,
    travel,
    others,
  ];

  /// Cria uma cópia da entidade com campos atualizados
  StoreCategory copyWith({
    String? id,
    String? name,
    String? slug,
    String? description,
    String? iconName,
    String? colorHex,
    int? sortOrder,
    bool? isActive,
    int? storeCount,
  }) {
    return StoreCategory(
      id: id ?? this.id,
      name: name ?? this.name,
      slug: slug ?? this.slug,
      description: description ?? this.description,
      iconName: iconName ?? this.iconName,
      colorHex: colorHex ?? this.colorHex,
      sortOrder: sortOrder ?? this.sortOrder,
      isActive: isActive ?? this.isActive,
      storeCount: storeCount ?? this.storeCount,
    );
  }

  /// Busca uma categoria pelo slug
  static StoreCategory? findBySlug(String slug) {
    try {
      return defaultCategories.firstWhere(
        (category) => category.slug.toLowerCase() == slug.toLowerCase(),
      );
    } catch (e) {
      return null;
    }
  }

  /// Busca uma categoria pelo nome
  static StoreCategory? findByName(String name) {
    try {
      return defaultCategories.firstWhere(
        (category) => category.name.toLowerCase() == name.toLowerCase(),
      );
    } catch (e) {
      return null;
    }
  }

  /// Busca uma categoria pelo ID
  static StoreCategory? findById(String id) {
    try {
      return defaultCategories.firstWhere(
        (category) => category.id == id,
      );
    } catch (e) {
      return null;
    }
  }

  /// Converte uma string de categoria do backend para StoreCategory
  static StoreCategory fromBackendString(String categoryString) {
    final normalizedString = categoryString.toLowerCase().trim();
    
    // Mapeamento de strings do backend para categorias
    switch (normalizedString) {
      case 'alimentação':
      case 'alimentacao':
      case 'food':
        return food;
      case 'moda':
      case 'moda e vestuário':
      case 'fashion':
        return fashion;
      case 'serviços':
      case 'servicos':
      case 'services':
        return services;
      case 'tecnologia':
      case 'tech':
      case 'technology':
        return technology;
      case 'saúde':
      case 'saude':
      case 'saúde e beleza':
      case 'health':
        return health;
      case 'casa':
      case 'casa e decoração':
      case 'casa e decoracao':
      case 'home':
        return home;
      case 'esportes':
      case 'sports':
        return sports;
      case 'educação':
      case 'educacao':
      case 'education':
        return education;
      case 'viagem':
      case 'travel':
        return travel;
      default:
        return others;
    }
  }

  /// Verifica se a categoria tem lojas disponíveis
  bool get hasStores => storeCount > 0;

  /// Retorna as categorias ativas ordenadas
  static List<StoreCategory> getActiveCategories() {
    return defaultCategories
        .where((category) => category.isActive)
        .toList()
        ..sort((a, b) => a.sortOrder.compareTo(b.sortOrder));
  }

  /// Retorna a cor padrão se não houver cor definida
  String get displayColor => colorHex ?? '#757575';

  /// Retorna o ícone padrão se não houver ícone definido
  String get displayIcon => iconName ?? 'category';

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;

    return other is StoreCategory &&
        other.id == id &&
        other.name == name &&
        other.slug == slug &&
        other.description == description &&
        other.iconName == iconName &&
        other.colorHex == colorHex &&
        other.sortOrder == sortOrder &&
        other.isActive == isActive &&
        other.storeCount == storeCount;
  }

  @override
  int get hashCode {
    return Object.hash(
      id,
      name,
      slug,
      description,
      iconName,
      colorHex,
      sortOrder,
      isActive,
      storeCount,
    );
  }

  @override
  String toString() {
    return 'StoreCategory(id: $id, name: $name, storeCount: $storeCount)';
  }
}