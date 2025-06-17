// lib/features/dashboard/presentation/screens/dashboard_screen.dart
// 🏠 Dashboard Screen - Tela principal do app com resumo de cashback

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:pull_to_refresh/pull_to_refresh.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/widgets/custom_app_bar.dart';
import '../../../../core/widgets/loading_indicator.dart';
import '../../../../core/widgets/error_widget.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../providers/dashboard_provider.dart';
import '../widgets/balance_summary_card.dart';
import '../widgets/cashback_chart.dart';
import '../widgets/recent_transactions_list.dart';
import '../widgets/quick_actions_grid.dart';

class DashboardScreen extends ConsumerStatefulWidget {
  const DashboardScreen({super.key});

  @override
  ConsumerState<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends ConsumerState<DashboardScreen> {
  final RefreshController _refreshController = RefreshController();

  @override
  void dispose() {
    _refreshController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProviderProvider);
    final dashboardState = ref.watch(dashboardNotifierProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: _buildAppBar(authState.user?.name),
      body: SmartRefresher(
        controller: _refreshController,
        onRefresh: _onRefresh,
        header: WaterDropHeader(
          waterDropColor: AppColors.primary,
          complete: Text(
            'Atualizado!',
            style: TextStyle(color: AppColors.primary),
          ),
        ),
        child: _buildBody(dashboardState),
      ),
    );
  }

  PreferredSizeWidget _buildAppBar(String? userName) {
    return CustomAppBar(
      type: AppBarType.transparent,
      titleWidget: _buildWelcomeHeader(userName),
      actions: [
        _buildNotificationButton(),
        const SizedBox(width: AppDimensions.spacingMedium),
      ],
    );
  }

  Widget _buildWelcomeHeader(String? userName) {
    final greeting = _getGreeting();
    final name = userName ?? 'Usuário';
    
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          '$greeting! 👋',
          style: TextStyle(
            fontSize: 24,
            fontWeight: FontWeight.w700,
            color: AppColors.textPrimary,
          ),
        ),
        Text(
          'Aqui está um resumo do seu cashback e economia.',
          style: TextStyle(
            fontSize: 14,
            color: AppColors.textSecondary,
          ),
        ),
      ],
    );
  }

  Widget _buildNotificationButton() {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: IconButton(
        icon: Stack(
          children: [
            Icon(
              Icons.notifications_outlined,
              color: AppColors.textPrimary,
              size: 24,
            ),
            Positioned(
              right: 0,
              top: 0,
              child: Container(
                padding: const EdgeInsets.all(2),
                decoration: BoxDecoration(
                  color: AppColors.error,
                  borderRadius: BorderRadius.circular(6),
                ),
                constraints: const BoxConstraints(
                  minWidth: 12,
                  minHeight: 12,
                ),
                child: Text(
                  '2',
                  style: TextStyle(
                    color: AppColors.white,
                    fontSize: 8,
                    fontWeight: FontWeight.w600,
                  ),
                  textAlign: TextAlign.center,
                ),
              ),
            ),
          ],
        ),
        onPressed: () => context.push('/notifications'),
      ),
    );
  }

  Widget _buildBody(DashboardState state) {
    if (state.isLoading && !state.hasData) {
      return const Center(child: LoadingIndicator());
    }

    if (state.hasError && !state.hasData) {
      return Center(
        child: CustomErrorWidget(
          message: state.errorMessage!,
          onRetry: () => ref.read(dashboardNotifierProvider.notifier).loadDashboardData(),
        ),
      );
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildQuickActions(),
          const SizedBox(height: AppDimensions.spacingLarge),
          _buildBalanceSummary(state),
          const SizedBox(height: AppDimensions.spacingLarge),
          _buildCashbackChart(state),
          const SizedBox(height: AppDimensions.spacingLarge),
          _buildRecentTransactions(state),
          const SizedBox(height: AppDimensions.spacingLarge),
          _buildTip(),
          const SizedBox(height: 100), // Bottom navigation padding
        ],
      ),
    );
  }

  Widget _buildQuickActions() {
    return QuickActionsGrid.dashboard(
      onStores: () => context.push('/stores'),
      onHistory: () => context.push('/cashback/history'),
      onInvite: () => _showInviteDialog(),
      onHelp: () => context.push('/help'),
    ).animate().fadeIn(duration: 300.ms).slideY(begin: 0.2, end: 0);
  }

  Widget _buildBalanceSummary(DashboardState state) {
    return BalanceSummaryCard(
      summary: state.cashbackSummary,
      isLoading: state.isLoading,
      onViewBalance: () => context.push('/balance'),
      onUseBalance: () => context.push('/stores'),
    );
  }

  Widget _buildCashbackChart(DashboardState state) {
    if (state.cashbackSummary?.chartData?.isEmpty ?? true) {
      return const SizedBox.shrink();
    }

    return CashbackChart(
      data: state.cashbackSummary!.chartData!,
      onPeriodChanged: (period) {
        // TODO: Implementar mudança de período
      },
    );
  }

  Widget _buildRecentTransactions(DashboardState state) {
    return RecentTransactionsList(
      transactions: state.recentTransactions,
      isLoading: state.isLoading,
      onTransactionTap: (transaction) {
        context.push('/cashback/details/${transaction.id}');
      },
      onViewAll: () => context.push('/cashback/history'),
    );
  }

  Widget _buildTip() {
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingLarge),
      decoration: BoxDecoration(
        color: AppColors.primaryLight.withOpacity(0.1),
        borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
        border: Border.all(color: AppColors.primary.withOpacity(0.2)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: AppColors.primary.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Text('💡', style: TextStyle(fontSize: 20)),
          ),
          const SizedBox(width: AppDimensions.spacingMedium),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Dica do Dia',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: AppColors.primary,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Seu saldo só pode ser usado na loja onde foi ganho. É como ter uma "carteira" separada para cada estabelecimento.',
                  style: TextStyle(
                    fontSize: 14,
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    ).animate().fadeIn(duration: 400.ms, delay: 600.ms);
  }

  Future<void> _onRefresh() async {
    try {
      await ref.read(dashboardNotifierProvider.notifier).refreshDashboard();
      _refreshController.refreshCompleted();
    } catch (e) {
      _refreshController.refreshFailed();
    }
  }

  void _showInviteDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Indique Amigos'),
        content: const Text(
          'Convide seus amigos para o Klube Cash e ganhem benefícios exclusivos!',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Fechar'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              // TODO: Implementar compartilhamento
            },
            child: const Text('Compartilhar'),
          ),
        ],
      ),
    );
  }

  String _getGreeting() {
    final hour = DateTime.now().hour;
    if (hour < 12) return 'Bom dia';
    if (hour < 18) return 'Boa tarde';
    return 'Boa noite';
  }
}