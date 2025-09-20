// lib/features/stores/presentation/widgets/store_categories_chips.dart
// 🏷️ Store Categories Chips - Widget de chips horizontais para categorias de lojas

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../domain/entities/store_category.dart';
import '../providers/stores_provider.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';

/// Widget de chips horizontais para filtrar lojas por categoria
/// 
/// Exibe categorias disponíveis em formato de chips clicáveis.
/// Integra-se com o StoresProvider para aplicar filtros em tempo real.
class StoreCategoriesChips extends ConsumerWidget {
  /// Callback executado quando uma categoria é selecionada
  final Function(StoreCategory?)? onCategorySelected;
  
  /// Se deve mostrar contador de lojas por categoria
  final bool showStoreCount;
  
  /// Se deve permitir scroll horizontal
  final bool scrollable;
  
  /// Padding interno do widget
  final EdgeInsetsGeometry? padding;
  
  /// Altura dos chips
  final double chipHeight;

  const StoreCategoriesChips({
    super.key,
    this.onCategorySelected,
    this.showStoreCount = false,
    this.scrollable = true,
    this.padding,
    this.chipHeight = 40,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final storesState = ref.watch(storesNotifierProvider);
    final categories = storesState.categories;
    final selectedCategory = storesState.selectedCategory;
    
    if (categories.isEmpty) {
      return const SizedBox.shrink();
    }

    // Lista com "Todas" + categorias
    final allOptions = <CategoryOption>[
      CategoryOption(
        category: null,
        label: 'Todas',
        count: showStoreCount ? storesState.stores.length : null,
      ),
      ...categories.map((category) => CategoryOption(
        category: category,
        label: category.name,
        count: showStoreCount ? _getStoreCountForCategory(storesState.stores, category) : null,
      )),
    ];

    Widget chipsRow = Row(
      children: allOptions.map((option) => 
        _buildCategoryChip(
          context,
          ref,
          option,
          selectedCategory,
        )
      ).toList(),
    );

    if (scrollable) {
      chipsRow = SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        padding: padding ?? const EdgeInsets.symmetric(
          horizontal: AppDimensions.paddingMedium,
        ),
        child: chipsRow,
      );
    } else {
      chipsRow = Padding(
        padding: padding ?? const EdgeInsets.symmetric(
          horizontal: AppDimensions.paddingMedium,
        ),
        child: chipsRow,
      );
    }

    return SizedBox(
      height: chipHeight + 16, // Altura + padding vertical
      child: chipsRow,
    );
  }

  /// Constrói um chip de categoria
  Widget _buildCategoryChip(
    BuildContext context,
    WidgetRef ref,
    CategoryOption option,
    StoreCategory? selectedCategory,
  ) {
    final isSelected = (option.category == null && selectedCategory == null) ||
                      (option.category != null && selectedCategory?.id == option.category!.id);
    
    return Padding(
      padding: const EdgeInsets.only(right: AppDimensions.spacingSmall),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        curve: Curves.easeInOut,
        child: Material(
          color: Colors.transparent,
          child: InkWell(
            onTap: () => _onChipTapped(ref, option.category),
            borderRadius: BorderRadius.circular(chipHeight / 2),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              height: chipHeight,
              padding: const EdgeInsets.symmetric(
                horizontal: AppDimensions.paddingMedium,
                vertical: AppDimensions.paddingXSmall,
              ),
              decoration: BoxDecoration(
                color: _getChipBackgroundColor(isSelected),
                borderRadius: BorderRadius.circular(chipHeight / 2),
                border: Border.all(
                  color: _getChipBorderColor(isSelected),
                  width: isSelected ? 2 : 1,
                ),
                boxShadow: isSelected ? [
                  BoxShadow(
                    color: AppColors.primary.withOpacity(0.2),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ] : null,
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // Ícone da categoria (se houver)
                  if (option.category?.icon != null) ...[
                    _buildCategoryIcon(option.category!.icon!, isSelected),
                    const SizedBox(width: AppDimensions.spacingXSmall),
                  ],
                  
                  // Nome da categoria
                  Text(
                    option.label,
                    style: _getChipTextStyle(context, isSelected),
                  ),
                  
                  // Contador de lojas (se habilitado)
                  if (option.count != null && option.count! > 0) ...[
                    const SizedBox(width: AppDimensions.spacingXSmall),
                    _buildStoreCounter(option.count!, isSelected),
                  ],
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  /// Constrói o ícone da categoria
  Widget _buildCategoryIcon(String iconName, bool isSelected) {
    return Icon(
      _getCategoryIconData(iconName),
      size: 16,
      color: isSelected ? AppColors.onPrimary : AppColors.textSecondary,
    );
  }

  /// Constrói o contador de lojas
  Widget _buildStoreCounter(int count, bool isSelected) {
    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: 6,
        vertical: 2,
      ),
      decoration: BoxDecoration(
        color: isSelected 
            ? AppColors.onPrimary.withOpacity(0.2)
            : AppColors.primary.withOpacity(0.1),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Text(
        count.toString(),
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: isSelected ? AppColors.onPrimary : AppColors.primary,
        ),
      ),
    );
  }

  /// Obtém a cor de fundo do chip
  Color _getChipBackgroundColor(bool isSelected) {
    if (isSelected) {
      return AppColors.primary;
    }
    return AppColors.surface;
  }

  /// Obtém a cor da borda do chip
  Color _getChipBorderColor(bool isSelected) {
    if (isSelected) {
      return AppColors.primary;
    }
    return AppColors.border;
  }

  /// Obtém o estilo de texto do chip
  TextStyle _getChipTextStyle(BuildContext context, bool isSelected) {
    return Theme.of(context).textTheme.bodyMedium?.copyWith(
      color: isSelected ? AppColors.onPrimary : AppColors.textSecondary,
      fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
      fontSize: 14,
    ) ?? TextStyle(
      color: isSelected ? AppColors.onPrimary : AppColors.textSecondary,
      fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
      fontSize: 14,
    );
  }

  /// Obtém o IconData para um ícone de categoria
  IconData _getCategoryIconData(String iconName) {
    switch (iconName.toLowerCase()) {
      case 'food':
      case 'alimentacao':
        return Icons.restaurant;
      case 'fashion':
      case 'moda':
        return Icons.shopping_bag;
      case 'services':
      case 'servicos':
        return Icons.build;
      case 'electronics':
      case 'eletronicos':
        return Icons.devices;
      case 'health':
      case 'saude':
        return Icons.health_and_safety;
      case 'beauty':
      case 'beleza':
        return Icons.face;
      case 'home':
      case 'casa':
        return Icons.home;
      case 'sports':
      case 'esportes':
        return Icons.sports;
      case 'travel':
      case 'viagem':
        return Icons.flight;
      case 'education':
      case 'educacao':
        return Icons.school;
      default:
        return Icons.category;
    }
  }

  /// Obtém a contagem de lojas para uma categoria específica
  int _getStoreCountForCategory(List stores, StoreCategory category) {
    // Por simplicidade, retorna um número mock
    // Em implementação real, filtrar stores por categoria
    return stores.where((store) => store.category.id == category.id).length;
  }

  /// Callback quando um chip é tocado
  void _onChipTapped(WidgetRef ref, StoreCategory? category) {
    // Aplicar filtro no provider
    ref.read(storesNotifierProvider.notifier).filterByCategory(category);
    
    // Executar callback customizado se fornecido
    onCategorySelected?.call(category);
  }
}

/// Classe auxiliar para representar uma opção de categoria
class CategoryOption {
  final StoreCategory? category;
  final String label;
  final int? count;

  const CategoryOption({
    required this.category,
    required this.label,
    this.count,
  });
}

/// Widget de categorias com layout em wrap (não scrollável)
class StoreCategoriesWrap extends ConsumerWidget {
  /// Callback executado quando uma categoria é selecionada
  final Function(StoreCategory?)? onCategorySelected;
  
  /// Se deve mostrar contador de lojas por categoria
  final bool showStoreCount;
  
  /// Padding interno do widget
  final EdgeInsetsGeometry? padding;
  
  /// Espaçamento entre chips
  final double spacing;
  
  /// Espaçamento entre linhas
  final double runSpacing;

  const StoreCategoriesWrap({
    super.key,
    this.onCategorySelected,
    this.showStoreCount = false,
    this.padding,
    this.spacing = 8,
    this.runSpacing = 8,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final storesState = ref.watch(storesNotifierProvider);
    final categories = storesState.categories;
    final selectedCategory = storesState.selectedCategory;
    
    if (categories.isEmpty) {
      return const SizedBox.shrink();
    }

    // Lista com "Todas" + categorias
    final allOptions = <CategoryOption>[
      CategoryOption(
        category: null,
        label: 'Todas',
        count: showStoreCount ? storesState.stores.length : null,
      ),
      ...categories.map((category) => CategoryOption(
        category: category,
        label: category.name,
        count: showStoreCount ? _getStoreCountForCategory(storesState.stores, category) : null,
      )),
    ];

    return Padding(
      padding: padding ?? const EdgeInsets.all(AppDimensions.paddingMedium),
      child: Wrap(
        spacing: spacing,
        runSpacing: runSpacing,
        children: allOptions.map((option) => 
          _buildCategoryChip(
            context,
            ref,
            option,
            selectedCategory,
          )
        ).toList(),
      ),
    );
  }

  /// Constrói um chip de categoria (mesmo método do widget principal)
  Widget _buildCategoryChip(
    BuildContext context,
    WidgetRef ref,
    CategoryOption option,
    StoreCategory? selectedCategory,
  ) {
    final isSelected = (option.category == null && selectedCategory == null) ||
                      (option.category != null && selectedCategory?.id == option.category!.id);
    
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: () => _onChipTapped(ref, option.category),
        borderRadius: BorderRadius.circular(20),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          height: 40,
          padding: const EdgeInsets.symmetric(
            horizontal: AppDimensions.paddingMedium,
            vertical: AppDimensions.paddingXSmall,
          ),
          decoration: BoxDecoration(
            color: isSelected ? AppColors.primary : AppColors.surface,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(
              color: isSelected ? AppColors.primary : AppColors.border,
              width: isSelected ? 2 : 1,
            ),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (option.category?.icon != null) ...[
                Icon(
                  _getCategoryIconData(option.category!.icon!),
                  size: 16,
                  color: isSelected ? AppColors.onPrimary : AppColors.textSecondary,
                ),
                const SizedBox(width: AppDimensions.spacingXSmall),
              ],
              Text(
                option.label,
                style: TextStyle(
                  color: isSelected ? AppColors.onPrimary : AppColors.textSecondary,
                  fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
                  fontSize: 14,
                ),
              ),
              if (option.count != null && option.count! > 0) ...[
                const SizedBox(width: AppDimensions.spacingXSmall),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: isSelected 
                        ? AppColors.onPrimary.withOpacity(0.2)
                        : AppColors.primary.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    option.count.toString(),
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: isSelected ? AppColors.onPrimary : AppColors.primary,
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  /// Métodos auxiliares (mesmos do widget principal)
  IconData _getCategoryIconData(String iconName) {
    switch (iconName.toLowerCase()) {
      case 'food':
      case 'alimentacao':
        return Icons.restaurant;
      case 'fashion':
      case 'moda':
        return Icons.shopping_bag;
      case 'services':
      case 'servicos':
        return Icons.build;
      case 'electronics':
      case 'eletronicos':
        return Icons.devices;
      case 'health':
      case 'saude':
        return Icons.health_and_safety;
      case 'beauty':
      case 'beleza':
        return Icons.face;
      case 'home':
      case 'casa':
        return Icons.home;
      case 'sports':
      case 'esportes':
        return Icons.sports;
      case 'travel':
      case 'viagem':
        return Icons.flight;
      case 'education':
      case 'educacao':
        return Icons.school;
      default:
        return Icons.category;
    }
  }

  int _getStoreCountForCategory(List stores, StoreCategory category) {
    return stores.where((store) => store.category.id == category.id).length;
  }

  void _onChipTapped(WidgetRef ref, StoreCategory? category) {
    ref.read(storesNotifierProvider.notifier).filterByCategory(category);
    onCategorySelected?.call(category);
  }
}