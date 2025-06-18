// lib/features/stores/data/models/store_category_model.dart
// Arquivo: Store Category Model - Modelo de dados da categoria de loja - Camada Data

import 'package:equatable/equatable.dart';
import '../../domain/entities/store_category.dart';

/// Modelo de dados para categoria de loja
/// 
/// Implementa serialização/deserialização JSON e estende a entidade
/// do domínio para seguir os princípios da Clean Architecture
class StoreCategoryModel extends StoreCategory with EquatableMixin {
  const StoreCategoryModel({
    required super.id,
    required super.name,
    required super.slug,
    super.description,
    super.icon,
    super.color,
    super.storeCount = 0,
    super.isActive = true,
    super.sortOrder = 0,
    super.createdAt,
    super.updatedAt,
  });

  /// Cria instância a partir de JSON da API
  factory StoreCategoryModel.fromJson(Map<String, dynamic> json) {
    return StoreCategoryModel(
      id: _parseInt(json['id']),
      name: json['nome'] ?? json['name'] ?? '',
      slug: json['slug'] ?? _generateSlug(json['nome'] ?? json['name'] ?? ''),
      description: json['descricao'] ?? json['description'],
      icon: json['icone'] ?? json['icon'],
      color: json['cor'] ?? json['color'],
      storeCount: _parseInt(json['total_lojas'] ?? json['store_count']),
      isActive: json['ativo'] ?? json['is_active'] ?? true,
      sortOrder: _parseInt(json['ordem'] ?? json['sort_order']),
      createdAt: json['data_criacao'] != null || json['created_at'] != null
          ? _parseDateTime(json['data_criacao'] ?? json['created_at'])
          : null,
      updatedAt: json['data_atualizacao'] != null || json['updated_at'] != null
          ? _parseDateTime(json['data_atualizacao'] ?? json['updated_at'])
          : null,
    );
  }

  /// Converte instância para JSON
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'slug': slug,
      'description': description,
      'icon': icon,
      'color': color,
      'store_count': storeCount,
      'is_active': isActive,
      'sort_order': sortOrder,
      'created_at': createdAt?.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
    };
  }

  /// Cria cópia com valores atualizados
  StoreCategoryModel copyWith({
    int? id,
    String? name,
    String? slug,
    String? description,
    String? icon,
    String? color,
    int? storeCount,
    bool? isActive,
    int? sortOrder,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return StoreCategoryModel(
      id: id ?? this.id,
      name: name ?? this.name,
      slug: slug ?? this.slug,
      description: description ?? this.description,
      icon: icon ?? this.icon,
      color: color ?? this.color,
      storeCount: storeCount ?? this.storeCount,
      isActive: isActive ?? this.isActive,
      sortOrder: sortOrder ?? this.sortOrder,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  /// Cria StoreCategoryModel a partir de entidade StoreCategory
  factory StoreCategoryModel.fromEntity(StoreCategory category) {
    return StoreCategoryModel(
      id: category.id,
      name: category.name,
      slug: category.slug,
      description: category.description,
      icon: category.icon,
      color: category.color,
      storeCount: category.storeCount,
      isActive: category.isActive,
      sortOrder: category.sortOrder,
      createdAt: category.createdAt,
      updatedAt: category.updatedAt,
    );
  }

  /// Converte lista de JSON para lista de StoreCategoryModel
  static List<StoreCategoryModel> fromJsonList(List<dynamic> jsonList) {
    return jsonList
        .map((json) => StoreCategoryModel.fromJson(json as Map<String, dynamic>))
        .toList();
  }

  /// Categorias padrão do sistema Klube Cash
  static List<StoreCategoryModel> getDefaultCategories() {
    return [
      const StoreCategoryModel(
        id: 0,
        name: 'Todas',
        slug: 'todas',
        description: 'Todas as categorias de lojas',
        icon: 'apps',
        color: '#FF7A00',
        isActive: true,
        sortOrder: 0,
      ),
      const StoreCategoryModel(
        id: 1,
        name: 'Alimentação',
        slug: 'alimentacao',
        description: 'Restaurantes, fast food, delivery e alimentação em geral',
        icon: 'restaurant',
        color: '#4CAF50',
        isActive: true,
        sortOrder: 1,
      ),
      const StoreCategoryModel(
        id: 2,
        name: 'Moda',
        slug: 'moda',
        description: 'Roupas, calçados, acessórios e vestuário em geral',
        icon: 'checkroom',
        color: '#E91E63',
        isActive: true,
        sortOrder: 2,
      ),
      const StoreCategoryModel(
        id: 3,
        name: 'Tecnologia',
        slug: 'tecnologia',
        description: 'Eletrônicos, gadgets, informática e tecnologia',
        icon: 'computer',
        color: '#2196F3',
        isActive: true,
        sortOrder: 3,
      ),
      const StoreCategoryModel(
        id: 4,
        name: 'Saúde e Beleza',
        slug: 'saude-beleza',
        description: 'Farmácias, cosméticos, cuidados pessoais',
        icon: 'health_and_safety',
        color: '#9C27B0',
        isActive: true,
        sortOrder: 4,
      ),
      const StoreCategoryModel(
        id: 5,
        name: 'Casa e Decoração',
        slug: 'casa-decoracao',
        description: 'Móveis, decoração, utilidades domésticas',
        icon: 'home',
        color: '#FF9800',
        isActive: true,
        sortOrder: 5,
      ),
      const StoreCategoryModel(
        id: 6,
        name: 'Esportes',
        slug: 'esportes',
        description: 'Artigos esportivos, academia, atividades físicas',
        icon: 'sports_soccer',
        color: '#607D8B',
        isActive: true,
        sortOrder: 6,
      ),
      const StoreCategoryModel(
        id: 7,
        name: 'Educação',
        slug: 'educacao',
        description: 'Cursos, livros, material educativo',
        icon: 'school',
        color: '#795548',
        isActive: true,
        sortOrder: 7,
      ),
      const StoreCategoryModel(
        id: 8,
        name: 'Viagem',
        slug: 'viagem',
        description: 'Hotéis, passagens, agências de turismo',
        icon: 'flight',
        color: '#00BCD4',
        isActive: true,
        sortOrder: 8,
      ),
      const StoreCategoryModel(
        id: 9,
        name: 'Serviços',
        slug: 'servicos',
        description: 'Serviços profissionais, manutenção, consultoria',
        icon: 'build',
        color: '#FFC107',
        isActive: true,
        sortOrder: 9,
      ),
      const StoreCategoryModel(
        id: 10,
        name: 'Outros',
        slug: 'outros',
        description: 'Outras categorias não especificadas',
        icon: 'category',
        color: '#9E9E9E',
        isActive: true,
        sortOrder: 10,
      ),
    ];
  }

  /// Busca categoria por slug
  static StoreCategoryModel? findBySlug(String slug, List<StoreCategoryModel> categories) {
    try {
      return categories.firstWhere(
        (category) => category.slug.toLowerCase() == slug.toLowerCase(),
      );
    } catch (e) {
      return null;
    }
  }

  /// Filtra categorias ativas e ordena por sortOrder
  static List<StoreCategoryModel> getActiveCategories(List<StoreCategoryModel> categories) {
    return categories
        .where((category) => category.isActive)
        .toList()
      ..sort((a, b) => a.sortOrder.compareTo(b.sortOrder));
  }

  @override
  List<Object?> get props => [
        id,
        name,
        slug,
        description,
        icon,
        color,
        storeCount,
        isActive,
        sortOrder,
        createdAt,
        updatedAt,
      ];
}

// Helper methods para parsing seguro de dados
int _parseInt(dynamic value) {
  if (value == null) return 0;
  if (value is int) return value;
  if (value is String) return int.tryParse(value) ?? 0;
  return 0;
}

DateTime? _parseDateTime(dynamic value) {
  if (value == null) return null;
  if (value is DateTime) return value;
  if (value is String) {
    return DateTime.tryParse(value);
  }
  return null;
}

String _generateSlug(String name) {
  return name
      .toLowerCase()
      .replaceAll(RegExp(r'[áâãàä]'), 'a')
      .replaceAll(RegExp(r'[éêë]'), 'e')
      .replaceAll(RegExp(r'[íîï]'), 'i')
      .replaceAll(RegExp(r'[óôõö]'), 'o')
      .replaceAll(RegExp(r'[úûü]'), 'u')
      .replaceAll(RegExp(r'[ç]'), 'c')
      .replaceAll(RegExp(r'[^a-z0-9\s]'), '')
      .replaceAll(RegExp(r'\s+'), '-')
      .trim();
}