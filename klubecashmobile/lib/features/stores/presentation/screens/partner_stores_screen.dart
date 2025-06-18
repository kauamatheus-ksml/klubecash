// lib/features/stores/presentation/screens/partner_stores_screen.dart
// 🏪 Partner Stores Screen - Tela de lojas parceiras do Klube Cash

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../providers/stores_provider.dart';
import '../widgets/store_search_bar.dart';
import '../widgets/store_categories_chips.dart';
import '../widgets/store_card.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/widgets/custom_app_bar.dart';
import '../../../../core/widgets/loading_indicator.dart';
import '../../../../core/widgets/error_widget.dart';
import '../../../../core/widgets/empty_state_widget.dart';

/// Tela de lojas parceiras
/// 
/// Exibe lista de lojas parceiras com funcionalidades de busca, filtro e navegação.
/// Integra-se com o StoresProvider para gerenciamento de estado reativo.
class PartnerStoresScreen extends ConsumerStatefulWidget {
  const PartnerStoresScreen({super.key});

  @override
  ConsumerState<PartnerStoresScreen> createState() => _PartnerStoresScreenState();
}

class _PartnerStoresScreenState extends ConsumerState<PartnerStoresScreen> {
  late ScrollController _scrollController;
  StoreViewMode _viewMode = StoreViewMode.grid;

  @override
  void initState() {
    super.initState();
    _scrollController = ScrollController();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final storesState = ref.watch(storesNotifierProvider);
    
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: _buildAppBar(context),
      body: RefreshIndicator(
        onRefresh: () => ref.read(storesNotifierProvider.notifier).refresh(),
        color: AppColors.primary,
        child: Column(
          children: [
            // Barra de busca e filtros
            StoreSearchBar(
              onFiltersApplied: () => _scrollToTop(),
              onFiltersCleared: () => _scrollToTop(),
            ),
            
            // Chips de categorias
            StoreCategoriesChips(
              showStoreCount: true,
              onCategorySelected: (_) => _scrollToTop(),
            ),
            
            // Header da seção
            _buildSectionHeader(context, storesState),
            
            // Lista de lojas
            Expanded(
              child: _buildStoresList(context, storesState),
            ),
          ],
        ),
      ),
    );
  }

  /// Constrói a AppBar personalizada
  PreferredSizeWidget _buildAppBar(BuildContext context) {
    return CustomAppBar(
      title: 'Lojas Parceiras',
      backgroundColor: AppColors.primary,
      foregroundColor: AppColors.onPrimary,
      actions: [
        // Toggle de visualização (grid/lista)
        IconButton(
          onPressed: _toggleViewMode,
          icon: Icon(
            _viewMode == StoreViewMode.grid ? Icons.view_list : Icons.grid_view,
            color: AppColors.onPrimary,
          ),
          tooltip: _viewMode == StoreViewMode.grid ? 'Ver em lista' : 'Ver em grade',
        ),
        
        // Botão de favoritos
        IconButton(
          onPressed: _showFavorites,
          icon: Icon(
            Icons.favorite_outline,
            color: AppColors.onPrimary,
          ),
          tooltip: 'Favoritos',
        ),
      ],
    );
  }

  /// Constrói o header da seção com estatísticas
  Widget _buildSectionHeader(BuildContext context, StoresState storesState) {
    final hasActiveFilters = ref.watch(hasActiveFiltersProvider);
    
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      child: Row(
        children: [
          // Título e contagem
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  hasActiveFilters ? 'Resultados da busca' : 'Todas as lojas',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.w700,
                    color: AppColors.textPrimary,
                  ),
                ),
                Text(
                  '${storesState.stores.length} ${storesState.stores.length == 1 ? 'loja encontrada' : 'lojas encontradas'}',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
          
          // Estatísticas rápidas
          if (!hasActiveFilters && storesState.stores.isNotEmpty) ...[
            _buildQuickStats(context, storesState),
          ],
        ],
      ),
    );
  }

  /// Constrói estatísticas rápidas
  Widget _buildQuickStats(BuildContext context, StoresState storesState) {
    // Calcular estatísticas das lojas
    final totalStores = storesState.stores.length;
    final averageCashback = storesState.stores.isNotEmpty
        ? storesState.stores.map((s) => s.cashbackPercentage).reduce((a, b) => a + b) / totalStores
        : 0.0;
    
    return Row(
      children: [
        _buildStatChip(
          context,
          label: 'Média cashback',
          value: '${averageCashback.toStringAsFixed(1)}%',
          color: AppColors.success,
        ),
      ],
    );
  }

  /// Constrói um chip de estatística
  Widget _buildStatChip(
    BuildContext context, {
    required String label,
    required String value,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: AppDimensions.paddingSmall,
        vertical: AppDimensions.paddingXSmall,
      ),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
        border: Border.all(
          color: color.withOpacity(0.3),
          width: 1,
        ),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            value,
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
              color: color,
              fontWeight: FontWeight.w700,
            ),
          ),
          Text(
            label,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: color,
              fontSize: 10,
            ),
          ),
        ],
      ),
    );
  }

  /// Constrói a lista de lojas
  Widget _buildStoresList(BuildContext context, StoresState storesState) {
    // Estado de loading inicial
    if (storesState.isLoading && storesState.stores.isEmpty) {
      return const Center(child: LoadingIndicator());
    }
    
    // Estado de erro
    if (storesState.errorMessage != null && storesState.stores.isEmpty) {
      return Center(
        child: CustomErrorWidget(
          message: storesState.errorMessage!,
          onRetry: () => ref.read(storesNotifierProvider.notifier).loadStores(refresh: true),
        ),
      );
    }
    
    // Estado vazio
    if (storesState.stores.isEmpty) {
      return _buildEmptyState(context);
    }
    
    // Lista/grid de lojas
    return _viewMode == StoreViewMode.grid
        ? _buildStoresGrid(context, storesState)
        : _buildStoresList(context, storesState);
  }

  /// Constrói o grid de lojas
  Widget _buildStoresGrid(BuildContext context, StoresState storesState) {
    return GridView.builder(
      controller: _scrollController,
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        childAspectRatio: 0.75,
        crossAxisSpacing: AppDimensions.spacingMedium,
        mainAxisSpacing: AppDimensions.spacingMedium,
      ),
      itemCount: storesState.stores.length + (storesState.isLoadingMore ? 2 : 0),
      itemBuilder: (context, index) {
        // Loading items no final
        if (index >= storesState.stores.length) {
          return const Center(child: LoadingIndicator(size: LoadingSize.small));
        }
        
        final store = storesState.stores[index];
        return StoreCard.compact(
          store: store,
          onTap: () => _navigateToStoreDetails(store.id),
          onFavoriteTap: () => _toggleFavorite(store.id),
        );
      },
    );
  }

  /// Constrói a lista de lojas
  Widget _buildStoresListView(BuildContext context, StoresState storesState) {
    return ListView.builder(
      controller: _scrollController,
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      itemCount: storesState.stores.length + (storesState.isLoadingMore ? 1 : 0),
      itemBuilder: (context, index) {
        // Loading item no final
        if (index >= storesState.stores.length) {
          return const Padding(
            padding: EdgeInsets.all(AppDimensions.paddingMedium),
            child: Center(child: LoadingIndicator(size: LoadingSize.small)),
          );
        }
        
        final store = storesState.stores[index];
        return Padding(
          padding: const EdgeInsets.only(bottom: AppDimensions.spacingMedium),
          child: StoreCard(
            store: store,
            style: StoreCardStyle.expanded,
            onTap: () => _navigateToStoreDetails(store.id),
            onFavoriteTap: () => _toggleFavorite(store.id),
            onDetailsTap: () => _navigateToStoreDetails(store.id),
            onVisitStoreTap: () => _visitStore(store),
            showActions: true,
          ),
        );
      },
    );
  }

  /// Constrói o estado vazio
  Widget _buildEmptyState(BuildContext context) {
    final hasActiveFilters = ref.watch(hasActiveFiltersProvider);
    
    return EmptyStateWidget(
      icon: hasActiveFilters ? Icons.search_off : Icons.store_outlined,
      title: hasActiveFilters 
          ? 'Nenhuma loja encontrada'
          : 'Nenhuma loja disponível',
      message: hasActiveFilters
          ? 'Tente ajustar os filtros ou buscar por outro termo'
          : 'Ainda não temos lojas parceiras cadastradas',
      actionText: hasActiveFilters ? 'Limpar filtros' : null,
      onActionPressed: hasActiveFilters 
          ? () => ref.read(storesNotifierProvider.notifier).clearFilters()
          : null,
    );
  }

  /// Listener do scroll para paginação
  void _onScroll() {
    if (_scrollController.position.pixels >= 
        _scrollController.position.maxScrollExtent - 200) {
      final storesState = ref.read(storesNotifierProvider);
      if (storesState.hasMoreStores && !storesState.isLoadingMore) {
        ref.read(storesNotifierProvider.notifier).loadMoreStores();
      }
    }
  }

  /// Scroll para o topo
  void _scrollToTop() {
    if (_scrollController.hasClients) {
      _scrollController.animateTo(
        0,
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeOutCubic,
      );
    }
  }

  /// Toggle do modo de visualização
  void _toggleViewMode() {
    setState(() {
      _viewMode = _viewMode == StoreViewMode.grid 
          ? StoreViewMode.list 
          : StoreViewMode.grid;
    });
  }

  /// Mostra apenas favoritos
  void _showFavorites() {
    // TODO: Implementar filtro de favoritos
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: const Text('Filtro de favoritos em desenvolvimento'),
        backgroundColor: AppColors.info,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
        ),
      ),
    );
  }

  /// Toggle favorito
  void _toggleFavorite(String storeId) {
    ref.read(storesNotifierProvider.notifier).toggleFavorite(storeId);
  }

  /// Navega para detalhes da loja
  void _navigateToStoreDetails(String storeId) {
    context.push('/stores/$storeId');
  }

  /// Visita a loja (abre website ou app)
  void _visitStore(Store store) {
    // TODO: Implementar abertura do website/app da loja
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Visitando ${store.name}...'),
        backgroundColor: AppColors.success,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
        ),
      ),
    );
  }
}

/// Enum para modo de visualização das lojas
enum StoreViewMode {
  /// Visualização em grid (grade)
  grid,
  
  /// Visualização em lista
  list,
}