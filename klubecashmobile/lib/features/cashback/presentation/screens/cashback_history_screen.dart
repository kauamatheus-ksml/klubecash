// lib/features/cashback/presentation/screens/cashback_history_screen.dart
// Tela principal de histórico de transações de cashback

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/widgets/custom_app_bar.dart';
import '../../../../core/widgets/loading_indicator.dart';
import '../../../../core/widgets/error_widget.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/utils/currency_utils.dart';
import '../../domain/entities/cashback_filter.dart';
import '../../domain/entities/cashback_transaction.dart';
import '../providers/cashback_provider.dart';
import '../widgets/cashback_list_item.dart';
import '../widgets/cashback_filter_sheet.dart';
import '../widgets/cashback_statistics_card.dart';

class CashbackHistoryScreen extends ConsumerStatefulWidget {
  const CashbackHistoryScreen({super.key});

  @override
  ConsumerState<CashbackHistoryScreen> createState() => _CashbackHistoryScreenState();
}

class _CashbackHistoryScreenState extends ConsumerState<CashbackHistoryScreen> {
  final ScrollController _scrollController = ScrollController();
  bool _isLoadingMore = false;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.removeListener(_onScroll);
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= 
        _scrollController.position.maxScrollExtent - 200) {
      _loadMoreTransactions();
    }
  }

  Future<void> _loadMoreTransactions() async {
    if (_isLoadingMore) return;
    
    final cashbackState = ref.read(cashbackNotifierProvider);
    if (!cashbackState.hasMorePages) return;

    setState(() {
      _isLoadingMore = true;
    });

    await ref.read(cashbackNotifierProvider.notifier).loadMoreTransactions();

    if (mounted) {
      setState(() {
        _isLoadingMore = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final cashbackState = ref.watch(cashbackNotifierProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: _buildAppBar(cashbackState),
      body: RefreshIndicator(
        onRefresh: () => ref.read(cashbackNotifierProvider.notifier).refreshData(),
        color: AppColors.primary,
        child: _buildBody(cashbackState),
      ),
    );
  }

  PreferredSizeWidget _buildAppBar(CashbackState state) {
    return CustomAppBar(
      title: 'Meu Histórico de Cashback',
      subtitle: 'Acompanhe todo o dinheiro que você ganhou de volta.',
      actions: [
        IconButton(
          onPressed: () => _showFilterSheet(state.currentFilter),
          icon: Stack(
            children: [
              const Icon(Icons.filter_list),
              if (state.hasActiveFilters)
                Positioned(
                  right: 0,
                  top: 0,
                  child: Container(
                    width: 8,
                    height: 8,
                    decoration: const BoxDecoration(
                      color: AppColors.error,
                      shape: BoxShape.circle,
                    ),
                  ),
                ),
            ],
          ),
          tooltip: 'Filtros',
        ),
        const SizedBox(width: AppDimensions.spacingMedium),
      ],
    );
  }

  Widget _buildBody(CashbackState state) {
    if (state.isLoading && state.transactions.isEmpty) {
      return const Center(child: LoadingIndicator());
    }

    if (state.hasError && state.transactions.isEmpty) {
      return Center(
        child: CustomErrorWidget(
          message: state.errorMessage!,
          onRetry: () => ref.read(cashbackNotifierProvider.notifier).refreshData(),
        ),
      );
    }

    return CustomScrollView(
      controller: _scrollController,
      slivers: [
        // Header com resumo
        SliverToBoxAdapter(
          child: _buildSummaryHeader(state),
        ),
        
        // Seção de transações
        SliverToBoxAdapter(
          child: _buildTransactionsHeader(state),
        ),
        
        // Lista de transações
        if (state.transactions.isEmpty && !state.isLoading)
          SliverToBoxAdapter(
            child: _buildEmptyState(),
          )
        else
          SliverList(
            delegate: SliverChildBuilderDelegate(
              (context, index) {
                if (index < state.transactions.length) {
                  return CashbackListItem(
                    transaction: state.transactions[index],
                    onTap: () => _navigateToDetails(state.transactions[index]),
                    onViewDetails: () => _navigateToDetails(state.transactions[index]),
                    animationIndex: index,
                  );
                } else if (state.hasMorePages) {
                  return _buildLoadMoreButton();
                }
                return null;
              },
              childCount: state.transactions.length + (state.hasMorePages ? 1 : 0),
            ),
          ),
        
        // Padding inferior
        const SliverToBoxAdapter(
          child: SizedBox(height: 100),
        ),
      ],
    );
  }

  Widget _buildSummaryHeader(CashbackState state) {
    if (state.summary == null) return const SizedBox.shrink();

    return Container(
      margin: const EdgeInsets.all(AppDimensions.paddingMedium),
      padding: const EdgeInsets.all(AppDimensions.paddingLarge),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFFFF8A00), Color(0xFFE65100)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFFFF8A00).withOpacity(0.3),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.2),
                  borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
                ),
                child: const Text(
                  '📊',
                  style: TextStyle(fontSize: 24),
                ),
              ),
              const SizedBox(width: AppDimensions.spacingMedium),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Meu Histórico de Cashback',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    Text(
                      'Acompanhe todo o dinheiro que você ganhou de volta.',
                      style: TextStyle(
                        color: Colors.white.withOpacity(0.9),
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    ).animate().fadeIn(duration: 400.ms).slideY(begin: -0.2, end: 0);
  }

  Widget _buildTransactionsHeader(CashbackState state) {
    return Padding(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Suas Compras e Cashback',
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w700,
                  color: AppColors.textPrimary,
                ),
              ),
              if (state.hasActiveFilters)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppColors.primaryLight,
                    borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
                  ),
                  child: Text(
                    '${state.currentFilter.activeFiltersCount} ${state.currentFilter.activeFiltersCount == 1 ? 'filtro' : 'filtros'}',
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppColors.primary,
                    ),
                  ),
                ),
            ],
          ),
          if (state.hasActiveFilters) ...[
            const SizedBox(height: AppDimensions.spacingSmall),
            Row(
              children: [
                Expanded(
                  child: Text(
                    _getFilterDescription(state.currentFilter),
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.textSecondary,
                    ),
                  ),
                ),
                TextButton(
                  onPressed: () {
                    ref.read(cashbackNotifierProvider.notifier).clearFilters();
                  },
                  child: const Text(
                    'Limpar filtros',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
          ],
          if (state.transactions.isNotEmpty) ...[
            const SizedBox(height: AppDimensions.spacingSmall),
            Text(
              '${state.pagination?.totalItems ?? state.transactions.length} ${(state.pagination?.totalItems ?? state.transactions.length) == 1 ? 'transação encontrada' : 'transações encontradas'}',
              style: const TextStyle(
                fontSize: 14,
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingLarge),
      margin: const EdgeInsets.all(AppDimensions.paddingMedium),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
        border: Border.all(color: AppColors.gray200),
      ),
      child: Column(
        children: [
          const Text(
            '📝',
            style: TextStyle(fontSize: 64),
          ),
          const SizedBox(height: AppDimensions.spacingMedium),
          Text(
            'Nenhuma transação encontrada',
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w600,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: AppDimensions.spacingSmall),
          Text(
            'Faça sua primeira compra em uma loja parceira\npara começar a acumular cashback!',
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 14,
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: AppDimensions.spacingLarge),
          CustomButton(
            text: 'Ver Lojas Parceiras',
            onPressed: () => context.push('/stores'),
            icon: Icons.store,
          ),
        ],
      ),
    ).animate().fadeIn(duration: 500.ms);
  }

  Widget _buildLoadMoreButton() {
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      child: CustomButton(
        text: _isLoadingMore ? 'Carregando...' : 'Carregar mais',
        onPressed: _isLoadingMore ? null : _loadMoreTransactions,
        type: ButtonType.outline,
        isLoading: _isLoadingMore,
        icon: _isLoadingMore ? null : Icons.expand_more,
      ),
    );
  }

  void _showFilterSheet(CashbackFilter currentFilter) {
    showCashbackFilterSheet(
      context,
      currentFilter: currentFilter,
      onApplyFilter: (filter) {
        ref.read(cashbackNotifierProvider.notifier).applyFilter(filter);
      },
      availableStores: _getAvailableStores(),
    );
  }

  List<StoreOption>? _getAvailableStores() {
    final state = ref.read(cashbackNotifierProvider);
    final storeNames = state.transactions
        .where((t) => t.storeName != null)
        .map((t) => StoreOption(
              id: t.storeId,
              name: t.storeName!,
              logo: t.storeLogo,
            ))
        .toSet()
        .toList();
    
    return storeNames.isNotEmpty ? storeNames : null;
  }

  void _navigateToDetails(CashbackTransaction transaction) {
    context.push('/cashback/details/${transaction.id}');
  }

  String _getFilterDescription(CashbackFilter filter) {
    final descriptions = <String>[];
    
    if (filter.hasPeriodFilter) {
      descriptions.add('Período personalizado');
    }
    
    if (filter.hasStatusFilter) {
      descriptions.add('${filter.status!.length} status selecionado${filter.status!.length > 1 ? 's' : ''}');
    }
    
    if (filter.hasStoreFilter) {
      descriptions.add('${filter.lojaIds!.length} loja${filter.lojaIds!.length > 1 ? 's' : ''} selecionada${filter.lojaIds!.length > 1 ? 's' : ''}');
    }
    
    if (filter.textoBusca?.isNotEmpty == true) {
      descriptions.add('Busca: "${filter.textoBusca}"');
    }
    
    return descriptions.join(' • ');
  }
}

/// Widget para estatísticas rápidas no topo da tela
class _CashbackQuickStats extends ConsumerWidget {
  const _CashbackQuickStats();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final totalCashback = ref.watch(totalCashbackAmountProvider);
    final transactionCount = ref.watch(cashbackNotifierProvider.select((state) => state.transactions.length));
    
    return Container(
      margin: const EdgeInsets.all(AppDimensions.paddingMedium),
      child: Row(
        children: [
          Expanded(
            child: _StatCard(
              icon: '💰',
              label: 'Total Cashback',
              value: CurrencyUtils.formatCurrency(totalCashback),
            ),
          ),
          const SizedBox(width: AppDimensions.spacingMedium),
          Expanded(
            child: _StatCard(
              icon: '📊',
              label: 'Transações',
              value: transactionCount.toString(),
            ),
          ),
        ],
      ),
    );
  }
}

/// Widget para card de estatística individual
class _StatCard extends StatelessWidget {
  final String icon;
  final String label;
  final String value;

  const _StatCard({
    required this.icon,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
        border: Border.all(color: AppColors.gray200),
      ),
      child: Column(
        children: [
          Text(
            icon,
            style: const TextStyle(fontSize: 24),
          ),
          const SizedBox(height: AppDimensions.spacingSmall),
          Text(
            value,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: AppColors.textPrimary,
            ),
          ),
          Text(
            label,
            style: const TextStyle(
              fontSize: 12,
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}