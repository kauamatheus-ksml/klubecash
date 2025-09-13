// lib/features/stores/presentation/screens/store_details_screen.dart
// 🏪 Store Details Screen - Tela de detalhes da loja com informações completas

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';

import '../providers/stores_provider.dart';
import '../widgets/store_info_section.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/widgets/loading_indicator.dart';
import '../../../../core/widgets/error_widget.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/utils/currency_utils.dart';

/// Tela de detalhes da loja
/// 
/// Exibe informações completas de uma loja específica com tabs organizadas
/// e botão flutuante para ação principal.
class StoreDetailsScreen extends ConsumerStatefulWidget {
  /// ID da loja
  final String storeId;

  const StoreDetailsScreen({
    super.key,
    required this.storeId,
  });

  @override
  ConsumerState<StoreDetailsScreen> createState() => _StoreDetailsScreenState();
}

class _StoreDetailsScreenState extends ConsumerState<StoreDetailsScreen>
    with TickerProviderStateMixin {
  late TabController _tabController;
  late ScrollController _scrollController;
  
  bool _isHeaderCollapsed = false;
  static const double _headerExpandedHeight = 300.0;
  static const double _headerCollapsedHeight = 120.0;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _scrollController = ScrollController();
    _scrollController.addListener(_onScroll);
    
    // Carregar detalhes da loja
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(storesNotifierProvider.notifier).getStoreDetails(widget.storeId);
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final storesState = ref.watch(storesNotifierProvider);
    final store = storesState.selectedStore;
    
    return Scaffold(
      backgroundColor: AppColors.background,
      body: _buildBody(context, storesState, store),
      floatingActionButton: store != null ? _buildFloatingActionButton(store) : null,
      floatingActionButtonLocation: FloatingActionButtonLocation.endFloat,
    );
  }

  /// Constrói o corpo da tela
  Widget _buildBody(BuildContext context, StoresState storesState, Store? store) {
    // Estado de loading
    if (storesState.isLoading && store == null) {
      return const Center(child: LoadingIndicator());
    }
    
    // Estado de erro
    if (storesState.errorMessage != null && store == null) {
      return Center(
        child: CustomErrorWidget(
          message: storesState.errorMessage!,
          onRetry: () => ref.read(storesNotifierProvider.notifier).getStoreDetails(widget.storeId),
        ),
      );
    }
    
    // Loja não encontrada
    if (store == null) {
      return Center(
        child: CustomErrorWidget(
          message: 'Loja não encontrada',
          onRetry: () => context.pop(),
        ),
      );
    }
    
    return NestedScrollView(
      controller: _scrollController,
      headerSliverBuilder: (context, innerBoxIsScrolled) => [
        _buildSliverAppBar(context, store),
      ],
      body: _buildTabBarView(context, store),
    );
  }

  /// Constrói a SliverAppBar com header da loja
  Widget _buildSliverAppBar(BuildContext context, Store store) {
    return SliverAppBar(
      expandedHeight: _headerExpandedHeight,
      collapsedHeight: _headerCollapsedHeight,
      pinned: true,
      elevation: 0,
      backgroundColor: AppColors.primary,
      foregroundColor: AppColors.onPrimary,
      leading: IconButton(
        onPressed: () => context.pop(),
        icon: Container(
          decoration: BoxDecoration(
            color: Colors.black.withOpacity(0.3),
            shape: BoxShape.circle,
          ),
          padding: const EdgeInsets.all(8),
          child: const Icon(
            Icons.arrow_back,
            color: Colors.white,
            size: 20,
          ),
        ),
      ),
      actions: [
        IconButton(
          onPressed: () => _toggleFavorite(store.id),
          icon: Container(
            decoration: BoxDecoration(
              color: Colors.black.withOpacity(0.3),
              shape: BoxShape.circle,
            ),
            padding: const EdgeInsets.all(8),
            child: Icon(
              store.isFavorite ? Icons.favorite : Icons.favorite_border,
              color: store.isFavorite ? AppColors.error : Colors.white,
              size: 20,
            ),
          ),
        ),
        IconButton(
          onPressed: () => _shareStore(store),
          icon: Container(
            decoration: BoxDecoration(
              color: Colors.black.withOpacity(0.3),
              shape: BoxShape.circle,
            ),
            padding: const EdgeInsets.all(8),
            child: const Icon(
              Icons.share,
              color: Colors.white,
              size: 20,
            ),
          ),
        ),
      ],
      flexibleSpace: FlexibleSpaceBar(
        background: _buildHeaderBackground(context, store),
        title: _isHeaderCollapsed ? Text(
          store.name,
          style: const TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.w600,
            fontSize: 16,
          ),
        ) : null,
        titlePadding: const EdgeInsets.only(left: 16, bottom: 16),
      ),
      bottom: _buildTabBar(),
    );
  }

  /// Constrói o background do header
  Widget _buildHeaderBackground(BuildContext context, Store store) {
    return Stack(
      fit: StackFit.expand,
      children: [
        // Imagem de capa
        if (store.coverImageUrl != null && store.coverImageUrl!.isNotEmpty)
          CachedNetworkImage(
            imageUrl: store.coverImageUrl!,
            fit: BoxFit.cover,
            placeholder: (context, url) => Container(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    AppColors.primary,
                    AppColors.primary.withOpacity(0.8),
                  ],
                ),
              ),
            ),
            errorWidget: (context, url, error) => Container(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    AppColors.primary,
                    AppColors.primary.withOpacity(0.8),
                  ],
                ),
              ),
            ),
          )
        else
          Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [
                  AppColors.primary,
                  AppColors.primary.withOpacity(0.8),
                ],
              ),
            ),
          ),
        
        // Overlay escuro
        Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [
                Colors.transparent,
                Colors.black.withOpacity(0.7),
              ],
            ),
          ),
        ),
        
        // Informações da loja
        if (!_isHeaderCollapsed) ...[
          Positioned(
            left: 0,
            right: 0,
            bottom: 80,
            child: _buildStoreInfo(context, store),
          ),
        ],
      ],
    );
  }

  /// Constrói as informações da loja no header
  Widget _buildStoreInfo(BuildContext context, Store store) {
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Logo e nome
          Row(
            children: [
              // Logo da loja
              Container(
                width: 64,
                height: 64,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.2),
                      blurRadius: 8,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
                  child: store.logoUrl != null && store.logoUrl!.isNotEmpty
                      ? CachedNetworkImage(
                          imageUrl: store.logoUrl!,
                          fit: BoxFit.cover,
                          placeholder: (context, url) => _buildLogoPlaceholder(store.name),
                          errorWidget: (context, url, error) => _buildLogoPlaceholder(store.name),
                        )
                      : _buildLogoPlaceholder(store.name),
                ),
              ),
              
              const SizedBox(width: AppDimensions.spacingMedium),
              
              // Nome e categoria
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      store.name,
                      style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        color: Colors.white,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: AppDimensions.spacingXSmall),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: AppDimensions.paddingSmall,
                        vertical: AppDimensions.paddingXSmall,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.2),
                        borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
                      ),
                      child: Text(
                        store.category.name,
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: Colors.white,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          
          const SizedBox(height: AppDimensions.spacingMedium),
          
          // Cashback e avaliação
          Row(
            children: [
              // Cashback
              _buildInfoChip(
                context,
                icon: Icons.monetization_on,
                label: 'Cashback',
                value: '${store.cashbackPercentage.toStringAsFixed(1)}%',
                color: AppColors.success,
              ),
              
              const SizedBox(width: AppDimensions.spacingMedium),
              
              // Avaliação
              _buildInfoChip(
                context,
                icon: Icons.star,
                label: 'Avaliação',
                value: store.rating.toStringAsFixed(1),
                color: AppColors.warning,
              ),
              
              // Badge "Nova" se aplicável
              if (store.isNew) ...[
                const SizedBox(width: AppDimensions.spacingMedium),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: AppDimensions.paddingSmall,
                    vertical: AppDimensions.paddingXSmall,
                  ),
                  decoration: BoxDecoration(
                    color: AppColors.error,
                    borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
                  ),
                  child: Text(
                    'NOVA',
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: Colors.white,
                      fontWeight: FontWeight.w700,
                      fontSize: 10,
                    ),
                  ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }

  /// Constrói um chip de informação
  Widget _buildInfoChip(
    BuildContext context, {
    required IconData icon,
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
        color: Colors.white.withOpacity(0.9),
        borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            icon,
            size: 16,
            color: color,
          ),
          const SizedBox(width: AppDimensions.spacingXSmall),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                label,
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: AppColors.textSecondary,
                  fontSize: 10,
                ),
              ),
              Text(
                value,
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: color,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  /// Constrói o placeholder do logo
  Widget _buildLogoPlaceholder(String storeName) {
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            AppColors.primary.withOpacity(0.2),
            AppColors.primary.withOpacity(0.4),
          ],
        ),
      ),
      child: Center(
        child: Text(
          storeName.isNotEmpty ? storeName[0].toUpperCase() : '?',
          style: TextStyle(
            fontSize: 24,
            fontWeight: FontWeight.bold,
            color: AppColors.primary,
          ),
        ),
      ),
    );
  }

  /// Constrói a TabBar
  PreferredSizeWidget _buildTabBar() {
    return TabBar(
      controller: _tabController,
      labelColor: Colors.white,
      unselectedLabelColor: Colors.white.withOpacity(0.7),
      indicatorColor: Colors.white,
      indicatorWeight: 3,
      tabs: const [
        Tab(text: 'Sobre'),
        Tab(text: 'Localização'),
        Tab(text: 'Avaliações'),
      ],
    );
  }

  /// Constrói o TabBarView
  Widget _buildTabBarView(BuildContext context, Store store) {
    return TabBarView(
      controller: _tabController,
      children: [
        // Tab Sobre
        _buildAboutTab(context, store),
        
        // Tab Localização
        _buildLocationTab(context, store),
        
        // Tab Avaliações
        _buildReviewsTab(context, store),
      ],
    );
  }

  /// Constrói a tab "Sobre"
  Widget _buildAboutTab(BuildContext context, Store store) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      child: StoreInfoSection(
        store: store,
        showAddress: false,
        showRatings: false,
        onCallStore: () => _callStore(store),
        onVisitWebsite: () => _visitWebsite(store),
      ),
    );
  }

  /// Constrói a tab "Localização"
  Widget _buildLocationTab(BuildContext context, Store store) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      child: StoreInfoSection(
        store: store,
        showDescription: false,
        showContact: false,
        showOperatingHours: false,
        showRatings: false,
        showTags: false,
        onViewLocation: () => _viewLocation(store),
      ),
    );
  }

  /// Constrói a tab "Avaliações"
  Widget _buildReviewsTab(BuildContext context, Store store) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      child: Column(
        children: [
          // Resumo das avaliações
          StoreInfoSection(
            store: store,
            showDescription: false,
            showContact: false,
            showAddress: false,
            showOperatingHours: false,
            showTags: false,
          ),
          
          const SizedBox(height: AppDimensions.spacingLarge),
          
          // Lista de avaliações (placeholder)
          _buildReviewsList(context, store),
        ],
      ),
    );
  }

  /// Constrói a lista de avaliações
  Widget _buildReviewsList(BuildContext context, Store store) {
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          Icon(
            Icons.rate_review_outlined,
            size: 48,
            color: AppColors.textSecondary,
          ),
          const SizedBox(height: AppDimensions.spacingMedium),
          Text(
            'Avaliações em breve',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: AppDimensions.spacingSmall),
          Text(
            'Em breve você poderá ver e deixar avaliações desta loja',
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: AppColors.textSecondary,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  /// Constrói o botão flutuante
  Widget _buildFloatingActionButton(Store store) {
    return FloatingActionButton.extended(
      onPressed: () => _visitStore(store),
      backgroundColor: AppColors.primary,
      foregroundColor: AppColors.onPrimary,
      icon: const Icon(Icons.shopping_bag_outlined),
      label: const Text(
        'Ir às compras',
        style: TextStyle(fontWeight: FontWeight.w600),
      ),
    );
  }

  /// Listener do scroll
  void _onScroll() {
    final isCollapsed = _scrollController.hasClients &&
        _scrollController.offset > _headerExpandedHeight - _headerCollapsedHeight;
    
    if (isCollapsed != _isHeaderCollapsed) {
      setState(() {
        _isHeaderCollapsed = isCollapsed;
      });
    }
  }

  /// Toggle favorito
  void _toggleFavorite(String storeId) {
    ref.read(storesNotifierProvider.notifier).toggleFavorite(storeId);
  }

  /// Compartilhar loja
  void _shareStore(Store store) {
    // TODO: Implementar compartilhamento
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Compartilhando ${store.name}...'),
        backgroundColor: AppColors.info,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
        ),
      ),
    );
  }

  /// Ligar para a loja
  Future<void> _callStore(Store store) async {
    if (store.phone != null && store.phone!.isNotEmpty) {
      final uri = Uri(scheme: 'tel', path: store.phone!);
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri);
      }
    }
  }

  /// Visitar website
  Future<void> _visitWebsite(Store store) async {
    if (store.website != null && store.website!.isNotEmpty) {
      final uri = Uri.parse(store.website!);
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
    }
  }

  /// Ver localização
  Future<void> _viewLocation(Store store) async {
    if (store.address != null) {
      final query = Uri.encodeComponent(store.address!.fullAddress);
      final uri = Uri.parse('https://www.google.com/maps/search/?api=1&query=$query');
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
    }
  }

  /// Visitar loja (ação principal)
  Future<void> _visitStore(Store store) async {
    if (store.website != null && store.website!.isNotEmpty) {
      await _visitWebsite(store);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Website não disponível para esta loja'),
          backgroundColor: AppColors.warning,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
          ),
        ),
      );
    }
  }
}