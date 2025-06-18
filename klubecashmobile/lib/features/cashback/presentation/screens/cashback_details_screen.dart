// lib/features/cashback/presentation/screens/cashback_details_screen.dart
// 📋 Cashback Details Screen - Tela de detalhes de uma transação de cashback específica

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/widgets/custom_app_bar.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/widgets/loading_indicator.dart';
import '../../../../core/widgets/error_widget.dart';
import '../../../../core/utils/currency_utils.dart';
import '../../../../core/utils/date_utils.dart';
import '../../domain/entities/cashback_transaction.dart';
import '../providers/cashback_provider.dart';
import '../widgets/cashback_status_chip.dart';

/// Tela de detalhes de uma transação de cashback
/// 
/// Exibe informações completas sobre uma transação específica,
/// incluindo valores, datas, status e ações disponíveis.
class CashbackDetailsScreen extends ConsumerStatefulWidget {
  /// ID da transação de cashback
  final String transactionId;

  const CashbackDetailsScreen({
    super.key,
    required this.transactionId,
  });

  @override
  ConsumerState<CashbackDetailsScreen> createState() => _CashbackDetailsScreenState();
}

class _CashbackDetailsScreenState extends ConsumerState<CashbackDetailsScreen>
    with TickerProviderStateMixin {
  
  // Controladores de animação
  late AnimationController _fadeAnimationController;
  late AnimationController _slideAnimationController;
  
  // Animações
  late Animation<double> _fadeAnimation;
  late Animation<Offset> _slideAnimation;

  @override
  void initState() {
    super.initState();
    _setupAnimations();
    _loadTransactionDetails();
  }

  @override
  void dispose() {
    _fadeAnimationController.dispose();
    _slideAnimationController.dispose();
    super.dispose();
  }

  void _setupAnimations() {
    _fadeAnimationController = AnimationController(
      duration: const Duration(milliseconds: 600),
      vsync: this,
    );
    
    _slideAnimationController = AnimationController(
      duration: const Duration(milliseconds: 800),
      vsync: this,
    );

    _fadeAnimation = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _fadeAnimationController,
      curve: Curves.easeInOut,
    ));

    _slideAnimation = Tween<Offset>(
      begin: const Offset(0, 0.3),
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: _slideAnimationController,
      curve: Curves.easeOutCubic,
    ));

    // Inicia animações
    _fadeAnimationController.forward();
    _slideAnimationController.forward();
  }

  void _loadTransactionDetails() {
    // Carrega os detalhes da transação via provider
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(cashbackNotifierProvider.notifier)
          .getCashbackDetails(widget.transactionId);
    });
  }

  @override
  Widget build(BuildContext context) {
    final cashbackState = ref.watch(cashbackNotifierProvider);
    
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: _buildAppBar(),
      body: _buildBody(cashbackState),
    );
  }

  PreferredSizeWidget _buildAppBar() {
    return const CustomAppBar(
      type: AppBarType.secondary,
      title: 'Detalhes do Cashback',
      showBackButton: true,
      showShadow: true,
    );
  }

  Widget _buildBody(CashbackState state) {
    if (state.isLoading && state.selectedTransaction == null) {
      return const Center(child: LoadingIndicator());
    }

    if (state.hasError && state.selectedTransaction == null) {
      return Center(
        child: CustomErrorWidget(
          message: state.errorMessage ?? 'Erro ao carregar detalhes',
          onRetry: _loadTransactionDetails,
        ),
      );
    }

    final transaction = state.selectedTransaction;
    if (transaction == null) {
      return const Center(
        child: CustomErrorWidget(
          message: 'Transação não encontrada',
        ),
      );
    }

    return FadeTransition(
      opacity: _fadeAnimation,
      child: SlideTransition(
        position: _slideAnimation,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(AppDimensions.paddingMedium),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _buildStoreHeader(transaction),
              const SizedBox(height: AppDimensions.spacingLarge),
              _buildTransactionSummary(transaction),
              const SizedBox(height: AppDimensions.spacingLarge),
              _buildTransactionDetails(transaction),
              const SizedBox(height: AppDimensions.spacingLarge),
              _buildStatusSection(transaction),
              const SizedBox(height: AppDimensions.spacingLarge),
              _buildActionButtons(transaction),
              const SizedBox(height: AppDimensions.spacingXLarge),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStoreHeader(CashbackTransaction transaction) {
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
        boxShadow: AppColors.shadowSm,
      ),
      child: Row(
        children: [
          // Logo da loja
          Container(
            width: 60,
            height: 60,
            decoration: BoxDecoration(
              color: AppColors.primary.withOpacity(0.1),
              borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
            ),
            child: transaction.storeLogo != null
                ? ClipRRect(
                    borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
                    child: Image.network(
                      transaction.storeLogo!,
                      fit: BoxFit.cover,
                      errorBuilder: (context, error, stackTrace) => 
                          _buildStoreInitials(transaction.storeName),
                    ),
                  )
                : _buildStoreInitials(transaction.storeName),
          ),
          const SizedBox(width: AppDimensions.spacingMedium),
          // Informações da loja
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  transaction.storeName,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.w600,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Compra realizada',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
          // Status chip
          CashbackStatusChip(status: transaction.status),
        ],
      ),
    ).animate().fadeIn(delay: 100.ms).slideY(begin: 0.2, end: 0);
  }

  Widget _buildStoreInitials(String storeName) {
    final initials = storeName.split(' ')
        .take(2)
        .map((word) => word.isNotEmpty ? word[0].toUpperCase() : '')
        .join();
    
    return Center(
      child: Text(
        initials,
        style: TextStyle(
          color: AppColors.primary,
          fontSize: 24,
          fontWeight: FontWeight.bold,
        ),
      ),
    );
  }

  Widget _buildTransactionSummary(CashbackTransaction transaction) {
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            AppColors.success.withOpacity(0.1),
            AppColors.success.withOpacity(0.05),
          ],
        ),
        borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
        border: Border.all(
          color: AppColors.success.withOpacity(0.2),
          width: 1,
        ),
      ),
      child: Column(
        children: [
          // Título
          Text(
            'Resumo da Transação',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w600,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: AppDimensions.spacingMedium),
          
          // Valor da compra
          _buildSummaryRow(
            label: 'Valor da compra',
            value: CurrencyUtils.formatBRL(transaction.totalAmount),
            isHighlight: false,
          ),
          
          const SizedBox(height: AppDimensions.spacingSmall),
          
          // Cashback ganho
          _buildSummaryRow(
            label: 'Cashback ganho',
            value: CurrencyUtils.formatBRL(transaction.cashbackAmount),
            isHighlight: true,
          ),
          
          const SizedBox(height: AppDimensions.spacingSmall),
          
          // Percentual
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Percentual',
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: AppColors.textSecondary,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 8,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: AppColors.success,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '${transaction.cashbackPercentage.toStringAsFixed(1)}%',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: AppColors.textOnPrimary,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    ).animate().fadeIn(delay: 200.ms).slideY(begin: 0.2, end: 0);
  }

  Widget _buildSummaryRow({
    required String label,
    required String value,
    required bool isHighlight,
  }) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
            color: AppColors.textSecondary,
          ),
        ),
        Text(
          value,
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
            color: isHighlight ? AppColors.success : AppColors.textPrimary,
          ),
        ),
      ],
    );
  }

  Widget _buildTransactionDetails(CashbackTransaction transaction) {
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
        boxShadow: AppColors.shadowSm,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Detalhes da Transação',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w600,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: AppDimensions.spacingMedium),
          
          _buildDetailRow(
            label: 'Data da compra',
            value: DateUtils.formatToBrazilian(transaction.transactionDate),
          ),
          
          if (transaction.approvalDate != null) ...[
            const SizedBox(height: AppDimensions.spacingSmall),
            _buildDetailRow(
              label: 'Data de aprovação',
              value: DateUtils.formatToBrazilian(transaction.approvalDate!),
            ),
          ],
          
          if (transaction.transactionCode != null) ...[
            const SizedBox(height: AppDimensions.spacingSmall),
            _buildDetailRow(
              label: 'Código da transação',
              value: transaction.transactionCode!,
            ),
          ],
          
          if (transaction.description != null) ...[
            const SizedBox(height: AppDimensions.spacingSmall),
            _buildDetailRow(
              label: 'Descrição',
              value: transaction.description!,
            ),
          ],
        ],
      ),
    ).animate().fadeIn(delay: 300.ms).slideY(begin: 0.2, end: 0);
  }

  Widget _buildDetailRow({
    required String label,
    required String value,
  }) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 120,
          child: Text(
            label,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: AppColors.textSecondary,
            ),
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: AppColors.textPrimary,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildStatusSection(CashbackTransaction transaction) {
    final statusInfo = _getStatusInfo(transaction.status);
    
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      decoration: BoxDecoration(
        color: statusInfo.backgroundColor,
        borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
        border: Border.all(
          color: statusInfo.borderColor,
          width: 1,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(
                statusInfo.icon,
                color: statusInfo.iconColor,
                size: 24,
              ),
              const SizedBox(width: AppDimensions.spacingSmall),
              Text(
                statusInfo.title,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                  color: statusInfo.titleColor,
                ),
              ),
            ],
          ),
          const SizedBox(height: AppDimensions.spacingSmall),
          Text(
            statusInfo.description,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: statusInfo.descriptionColor,
            ),
          ),
        ],
      ),
    ).animate().fadeIn(delay: 400.ms).slideY(begin: 0.2, end: 0);
  }

  _StatusInfo _getStatusInfo(CashbackTransactionStatus status) {
    switch (status) {
      case CashbackTransactionStatus.approved:
        return _StatusInfo(
          title: 'Cashback Aprovado',
          description: 'Seu cashback foi aprovado e já está disponível para uso.',
          icon: Icons.check_circle,
          backgroundColor: AppColors.success.withOpacity(0.1),
          borderColor: AppColors.success.withOpacity(0.3),
          iconColor: AppColors.success,
          titleColor: AppColors.success,
          descriptionColor: AppColors.textSecondary,
        );
      
      case CashbackTransactionStatus.pending:
        return _StatusInfo(
          title: 'Aguardando Aprovação',
          description: 'Seu cashback está sendo processado. Isso pode levar até 3 dias úteis.',
          icon: Icons.schedule,
          backgroundColor: AppColors.warning.withOpacity(0.1),
          borderColor: AppColors.warning.withOpacity(0.3),
          iconColor: AppColors.warning,
          titleColor: AppColors.warning,
          descriptionColor: AppColors.textSecondary,
        );
      
      case CashbackTransactionStatus.canceled:
        return _StatusInfo(
          title: 'Transação Cancelada',
          description: 'Esta transação foi cancelada e o cashback não será creditado.',
          icon: Icons.cancel,
          backgroundColor: AppColors.error.withOpacity(0.1),
          borderColor: AppColors.error.withOpacity(0.3),
          iconColor: AppColors.error,
          titleColor: AppColors.error,
          descriptionColor: AppColors.textSecondary,
        );
      
      case CashbackTransactionStatus.paymentPending:
        return _StatusInfo(
          title: 'Pagamento Pendente',
          description: 'A transação foi aprovada, mas o pagamento ainda está sendo processado.',
          icon: Icons.payment,
          backgroundColor: AppColors.info.withOpacity(0.1),
          borderColor: AppColors.info.withOpacity(0.3),
          iconColor: AppColors.info,
          titleColor: AppColors.info,
          descriptionColor: AppColors.textSecondary,
        );
    }
  }

  Widget _buildActionButtons(CashbackTransaction transaction) {
    return Column(
      children: [
        // Botão principal baseado no status
        if (transaction.status == CashbackTransactionStatus.approved) ...[
          CustomButton(
            text: 'Usar Cashback',
            onPressed: () => _navigateToStores(),
            type: ButtonType.primary,
            isFullWidth: true,
            icon: Icons.shopping_bag,
          ),
          const SizedBox(height: AppDimensions.spacingMedium),
        ],
        
        // Botão para visitar a loja
        CustomButton(
          text: 'Visitar Loja',
          onPressed: () => _navigateToStore(transaction.storeId),
          type: ButtonType.outline,
          isFullWidth: true,
          icon: Icons.store,
        ),
        
        const SizedBox(height: AppDimensions.spacingMedium),
        
        // Botão para compartilhar
        CustomButton(
          text: 'Compartilhar',
          onPressed: () => _shareTransaction(transaction),
          type: ButtonType.text,
          isFullWidth: true,
          icon: Icons.share,
        ),
      ],
    ).animate().fadeIn(delay: 500.ms).slideY(begin: 0.2, end: 0);
  }

  void _navigateToStores() {
    context.push('/stores');
  }

  void _navigateToStore(String storeId) {
    context.push('/stores/details/$storeId');
  }

  void _shareTransaction(CashbackTransaction transaction) {
    // Implementar funcionalidade de compartilhamento
    // Pode usar o package share_plus
  }
}

/// Classe auxiliar para informações de status
class _StatusInfo {
  final String title;
  final String description;
  final IconData icon;
  final Color backgroundColor;
  final Color borderColor;
  final Color iconColor;
  final Color titleColor;
  final Color descriptionColor;

  _StatusInfo({
    required this.title,
    required this.description,
    required this.icon,
    required this.backgroundColor,
    required this.borderColor,
    required this.iconColor,
    required this.titleColor,
    required this.descriptionColor,
  });
}