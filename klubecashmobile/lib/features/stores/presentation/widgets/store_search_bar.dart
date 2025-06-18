// lib/features/stores/presentation/widgets/store_search_bar.dart
// 🔍 Store Search Bar - Widget de barra de pesquisa e filtros para lojas parceiras

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../domain/entities/store_category.dart';
import '../providers/stores_provider.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/widgets/custom_text_field.dart';
import '../../../../core/widgets/custom_button.dart';

/// Widget de barra de pesquisa e filtros para lojas parceiras
/// 
/// Oferece busca por texto, filtros por categoria, ordenação e opções avançadas.
/// Integra-se diretamente com o StoresProvider para aplicar filtros em tempo real.
class StoreSearchBar extends ConsumerStatefulWidget {
  /// Callback executado quando filtros são aplicados
  final VoidCallback? onFiltersApplied;
  
  /// Callback executado quando filtros são limpos
  final VoidCallback? onFiltersCleared;
  
  /// Se deve mostrar filtros expandidos por padrão
  final bool expandedByDefault;
  
  /// Placeholder personalizado para busca
  final String? searchPlaceholder;
  
  /// Se deve mostrar contagem de resultados
  final bool showResultsCount;

  const StoreSearchBar({
    super.key,
    this.onFiltersApplied,
    this.onFiltersCleared,
    this.expandedByDefault = false,
    this.searchPlaceholder,
    this.showResultsCount = true,
  });

  @override
  ConsumerState<StoreSearchBar> createState() => _StoreSearchBarState();
}

class _StoreSearchBarState extends ConsumerState<StoreSearchBar>
    with TickerProviderStateMixin {
  late TextEditingController _searchController;
  late AnimationController _filterAnimationController;
  late Animation<double> _filterAnimation;
  
  bool _filtersExpanded = false;
  String? _selectedSortBy;
  StoreCategory? _selectedCategory;
  
  @override
  void initState() {
    super.initState();
    _searchController = TextEditingController();
    _filtersExpanded = widget.expandedByDefault;
    
    _filterAnimationController = AnimationController(
      duration: const Duration(milliseconds: 300),
      vsync: this,
    );
    
    _filterAnimation = CurvedAnimation(
      parent: _filterAnimationController,
      curve: Curves.easeInOut,
    );
    
    if (_filtersExpanded) {
      _filterAnimationController.forward();
    }
    
    // Inicializar com estado atual do provider
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final storesState = ref.read(storesNotifierProvider);
      _searchController.text = storesState.searchQuery ?? '';
      _selectedCategory = storesState.selectedCategory;
      _selectedSortBy = storesState.sortBy;
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    _filterAnimationController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final storesState = ref.watch(storesNotifierProvider);
    final categories = storesState.categories;
    
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        border: Border(
          bottom: BorderSide(
            color: AppColors.border,
            width: 1,
          ),
        ),
      ),
      child: Column(
        children: [
          // Barra principal de busca
          _buildMainSearchBar(context, storesState),
          
          // Filtros expandidos
          AnimatedBuilder(
            animation: _filterAnimation,
            builder: (context, child) {
              return ClipRect(
                child: Align(
                  alignment: Alignment.topCenter,
                  heightFactor: _filterAnimation.value,
                  child: child,
                ),
              );
            },
            child: _buildExpandedFilters(context, categories),
          ),
        ],
      ),
    );
  }

  /// Constrói a barra principal de busca
  Widget _buildMainSearchBar(BuildContext context, StoresState storesState) {
    return Padding(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      child: Column(
        children: [
          // Campo de busca e botão de filtro
          Row(
            children: [
              // Campo de busca
              Expanded(
                child: CustomTextField.search(
                  controller: _searchController,
                  hint: widget.searchPlaceholder ?? 'Buscar lojas...',
                  onChanged: _onSearchChanged,
                  onFieldSubmitted: (_) => _performSearch(),
                ),
              ),
              
              const SizedBox(width: AppDimensions.spacingSmall),
              
              // Botão de filtros
              _buildFilterToggleButton(),
            ],
          ),
          
          // Indicadores de filtros ativos e contagem
          if (_hasActiveFilters() || widget.showResultsCount) ...[
            const SizedBox(height: AppDimensions.spacingSmall),
            _buildActiveFiltersRow(storesState),
          ],
        ],
      ),
    );
  }

  /// Constrói o botão de toggle dos filtros
  Widget _buildFilterToggleButton() {
    return Container(
      decoration: BoxDecoration(
        color: _hasActiveFilters() ? AppColors.primary : AppColors.background,
        borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
        border: Border.all(
          color: _hasActiveFilters() ? AppColors.primary : AppColors.border,
          width: 1,
        ),
      ),
      child: IconButton(
        onPressed: _toggleFilters,
        icon: Icon(
          _filtersExpanded ? Icons.keyboard_arrow_up : Icons.tune,
          color: _hasActiveFilters() ? AppColors.onPrimary : AppColors.textSecondary,
        ),
        tooltip: 'Filtros',
      ),
    );
  }

  /// Constrói a linha de filtros ativos e contagem
  Widget _buildActiveFiltersRow(StoresState storesState) {
    return Row(
      children: [
        // Chips de filtros ativos
        if (_hasActiveFilters()) ...[
          Expanded(
            child: Wrap(
              spacing: AppDimensions.spacingXSmall,
              runSpacing: AppDimensions.spacingXSmall,
              children: _buildActiveFilterChips(),
            ),
          ),
        ] else ...[
          const Spacer(),
        ],
        
        // Contagem de resultados
        if (widget.showResultsCount) ...[
          Text(
            '${storesState.stores.length} ${storesState.stores.length == 1 ? 'loja' : 'lojas'}',
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ],
    );
  }

  /// Constrói os chips de filtros ativos
  List<Widget> _buildActiveFilterChips() {
    final chips = <Widget>[];
    
    // Chip de busca
    if (_searchController.text.isNotEmpty) {
      chips.add(_buildFilterChip(
        label: '"${_searchController.text}"',
        onRemove: () {
          _searchController.clear();
          _performSearch();
        },
      ));
    }
    
    // Chip de categoria
    if (_selectedCategory != null) {
      chips.add(_buildFilterChip(
        label: _selectedCategory!.name,
        onRemove: () {
          _selectedCategory = null;
          _applyFilters();
        },
      ));
    }
    
    // Chip de ordenação (apenas se não for a padrão)
    if (_selectedSortBy != null && _selectedSortBy != 'name') {
      chips.add(_buildFilterChip(
        label: _getSortDisplayName(_selectedSortBy!),
        onRemove: () {
          _selectedSortBy = 'name';
          _applyFilters();
        },
      ));
    }
    
    return chips;
  }

  /// Constrói um chip de filtro ativo
  Widget _buildFilterChip({
    required String label,
    required VoidCallback onRemove,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: AppDimensions.paddingXSmall,
        vertical: 2,
      ),
      decoration: BoxDecoration(
        color: AppColors.primary.withOpacity(0.1),
        borderRadius: BorderRadius.circular(AppDimensions.radiusXSmall),
        border: Border.all(
          color: AppColors.primary.withOpacity(0.3),
          width: 1,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            label,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: AppColors.primary,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(width: AppDimensions.spacingXSmall),
          GestureDetector(
            onTap: onRemove,
            child: Icon(
              Icons.close,
              size: 14,
              color: AppColors.primary,
            ),
          ),
        ],
      ),
    );
  }

  /// Constrói os filtros expandidos
  Widget _buildExpandedFilters(BuildContext context, List<StoreCategory> categories) {
    return Container(
      padding: const EdgeInsets.only(
        left: AppDimensions.paddingMedium,
        right: AppDimensions.paddingMedium,
        bottom: AppDimensions.paddingMedium,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Divider
          Container(
            height: 1,
            color: AppColors.border,
            margin: const EdgeInsets.only(bottom: AppDimensions.spacingMedium),
          ),
          
          // Filtros
          Row(
            children: [
              // Filtro de categoria
              Expanded(
                flex: 2,
                child: _buildCategoryFilter(categories),
              ),
              
              const SizedBox(width: AppDimensions.spacingMedium),
              
              // Filtro de ordenação
              Expanded(
                flex: 2,
                child: _buildSortFilter(),
              ),
              
              const SizedBox(width: AppDimensions.spacingMedium),
              
              // Botão limpar
              _buildClearButton(),
            ],
          ),
        ],
      ),
    );
  }

  /// Constrói o filtro de categoria
  Widget _buildCategoryFilter(List<StoreCategory> categories) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Categoria',
          style: Theme.of(context).textTheme.labelMedium?.copyWith(
            color: AppColors.textSecondary,
            fontWeight: FontWeight.w500,
          ),
        ),
        const SizedBox(height: AppDimensions.spacingXSmall),
        DropdownButtonFormField<StoreCategory?>(
          value: _selectedCategory,
          decoration: InputDecoration(
            hintText: 'Todas',
            contentPadding: const EdgeInsets.symmetric(
              horizontal: AppDimensions.paddingSmall,
              vertical: AppDimensions.paddingXSmall,
            ),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
              borderSide: BorderSide(color: AppColors.border),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
              borderSide: BorderSide(color: AppColors.border),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
              borderSide: BorderSide(color: AppColors.primary, width: 2),
            ),
          ),
          items: [
            const DropdownMenuItem<StoreCategory?>(
              value: null,
              child: Text('Todas'),
            ),
            ...categories.map((category) => DropdownMenuItem<StoreCategory?>(
              value: category,
              child: Text(category.name),
            )),
          ],
          onChanged: (category) {
            setState(() {
              _selectedCategory = category;
            });
            _applyFilters();
          },
        ),
      ],
    );
  }

  /// Constrói o filtro de ordenação
  Widget _buildSortFilter() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Ordenar por',
          style: Theme.of(context).textTheme.labelMedium?.copyWith(
            color: AppColors.textSecondary,
            fontWeight: FontWeight.w500,
          ),
        ),
        const SizedBox(height: AppDimensions.spacingXSmall),
        DropdownButtonFormField<String>(
          value: _selectedSortBy ?? 'name',
          decoration: InputDecoration(
            contentPadding: const EdgeInsets.symmetric(
              horizontal: AppDimensions.paddingSmall,
              vertical: AppDimensions.paddingXSmall,
            ),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
              borderSide: BorderSide(color: AppColors.border),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
              borderSide: BorderSide(color: AppColors.border),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
              borderSide: BorderSide(color: AppColors.primary, width: 2),
            ),
          ),
          items: const [
            DropdownMenuItem(
              value: 'name',
              child: Text('Nome'),
            ),
            DropdownMenuItem(
              value: 'cashback',
              child: Text('% Cashback'),
            ),
            DropdownMenuItem(
              value: 'category',
              child: Text('Categoria'),
            ),
            DropdownMenuItem(
              value: 'newest',
              child: Text('Mais recentes'),
            ),
          ],
          onChanged: (sortBy) {
            setState(() {
              _selectedSortBy = sortBy;
            });
            _applyFilters();
          },
        ),
      ],
    );
  }

  /// Constrói o botão limpar
  Widget _buildClearButton() {
    return Column(
      children: [
        const SizedBox(height: 20), // Para alinhar com os outros campos
        CustomButton.outlined(
          text: 'Limpar',
          onPressed: _hasActiveFilters() ? _clearFilters : null,
          size: ButtonSize.small,
          icon: Icons.clear,
        ),
      ],
    );
  }

  /// Verifica se há filtros ativos
  bool _hasActiveFilters() {
    return _searchController.text.isNotEmpty ||
           _selectedCategory != null ||
           (_selectedSortBy != null && _selectedSortBy != 'name');
  }

  /// Obtém o nome de exibição para ordenação
  String _getSortDisplayName(String sortBy) {
    switch (sortBy) {
      case 'cashback':
        return '% Cashback';
      case 'category':
        return 'Categoria';
      case 'newest':
        return 'Mais recentes';
      default:
        return 'Nome';
    }
  }

  /// Toggle dos filtros expandidos
  void _toggleFilters() {
    setState(() {
      _filtersExpanded = !_filtersExpanded;
    });
    
    if (_filtersExpanded) {
      _filterAnimationController.forward();
    } else {
      _filterAnimationController.reverse();
    }
  }

  /// Callback de mudança na busca
  void _onSearchChanged(String value) {
    // Implementar debounce se necessário
    // Por enquanto, busca em tempo real está desabilitada
  }

  /// Executa a busca
  void _performSearch() {
    final query = _searchController.text.trim();
    ref.read(storesNotifierProvider.notifier).searchStores(query);
    widget.onFiltersApplied?.call();
  }

  /// Aplica filtros selecionados
  void _applyFilters() {
    final notifier = ref.read(storesNotifierProvider.notifier);
    
    // Aplicar filtro de categoria
    notifier.filterByCategory(_selectedCategory);
    
    // Aplicar ordenação
    if (_selectedSortBy != null) {
      notifier.sortStores(_selectedSortBy!);
    }
    
    widget.onFiltersApplied?.call();
  }

  /// Limpa todos os filtros
  void _clearFilters() {
    setState(() {
      _searchController.clear();
      _selectedCategory = null;
      _selectedSortBy = 'name';
    });
    
    ref.read(storesNotifierProvider.notifier).clearFilters();
    widget.onFiltersCleared?.call();
  }
}

/// Enum para definir tamanhos de botão
enum ButtonSize {
  small,
  medium,
  large,
}