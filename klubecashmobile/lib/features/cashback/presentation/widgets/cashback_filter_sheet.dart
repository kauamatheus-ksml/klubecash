// lib/features/cashback/presentation/widgets/cashback_filter_sheet.dart
// Bottom sheet para filtros avançados de transações de cashback

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/utils/date_utils.dart';
import '../../../../core/utils/currency_utils.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/widgets/custom_text_field.dart';
import '../../domain/entities/cashback_filter.dart';
import '../../domain/entities/cashback_transaction.dart';
import '../providers/cashback_provider.dart';
import 'cashback_status_chip.dart';

/// Widget para exibir e configurar filtros de cashback
class CashbackFilterSheet extends ConsumerStatefulWidget {
  /// Filtro atual aplicado
  final CashbackFilter currentFilter;
  
  /// Callback quando filtros são aplicados
  final Function(CashbackFilter) onApplyFilter;
  
  /// Lista de lojas disponíveis para filtro
  final List<StoreOption>? availableStores;

  const CashbackFilterSheet({
    super.key,
    required this.currentFilter,
    required this.onApplyFilter,
    this.availableStores,
  });

  @override
  ConsumerState<CashbackFilterSheet> createState() => _CashbackFilterSheetState();
}

class _CashbackFilterSheetState extends ConsumerState<CashbackFilterSheet> {
  late CashbackFilter _workingFilter;
  late TextEditingController _searchController;
  late TextEditingController _minAmountController;
  late TextEditingController _maxAmountController;
  late TextEditingController _minCashbackController;
  late TextEditingController _maxCashbackController;
  
  String _selectedPeriod = '';
  DateTime? _customStartDate;
  DateTime? _customEndDate;

  @override
  void initState() {
    super.initState();
    _workingFilter = widget.currentFilter;
    _searchController = TextEditingController(text: _workingFilter.textoBusca ?? '');
    _minAmountController = TextEditingController(
      text: _workingFilter.valorMinimoTransacao?.toStringAsFixed(2) ?? '',
    );
    _maxAmountController = TextEditingController(
      text: _workingFilter.valorMaximoTransacao?.toStringAsFixed(2) ?? '',
    );
    _minCashbackController = TextEditingController(
      text: _workingFilter.cashbackMinimo?.toStringAsFixed(2) ?? '',
    );
    _maxCashbackController = TextEditingController(
      text: _workingFilter.cashbackMaximo?.toStringAsFixed(2) ?? '',
    );
    
    _initializePeriodFilter();
  }

  @override
  void dispose() {
    _searchController.dispose();
    _minAmountController.dispose();
    _maxAmountController.dispose();
    _minCashbackController.dispose();
    _maxCashbackController.dispose();
    super.dispose();
  }

  void _initializePeriodFilter() {
    if (_workingFilter.dataInicio != null && _workingFilter.dataFim != null) {
      // Verificar se é um período predefinido
      final start = _workingFilter.dataInicio!;
      final end = _workingFilter.dataFim!;
      final now = DateTime.now();
      
      if (AppDateUtils.isSameDay(start, now) && AppDateUtils.isSameDay(end, now)) {
        _selectedPeriod = 'today';
      } else if (AppDateUtils.isSameDay(start, now.subtract(const Duration(days: 1))) &&
                 AppDateUtils.isSameDay(end, now.subtract(const Duration(days: 1)))) {
        _selectedPeriod = 'yesterday';
      } else if (AppDateUtils.isSameDay(start, now.subtract(const Duration(days: 7))) &&
                 AppDateUtils.isSameDay(end, now)) {
        _selectedPeriod = 'last_week';
      } else if (AppDateUtils.isSameDay(start, now.subtract(const Duration(days: 30))) &&
                 AppDateUtils.isSameDay(end, now)) {
        _selectedPeriod = 'last_month';
      } else {
        _selectedPeriod = 'custom';
        _customStartDate = start;
        _customEndDate = end;
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: MediaQuery.of(context).size.height * 0.9,
      decoration: const BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(AppDimensions.radiusLarge),
          topRight: Radius.circular(AppDimensions.radiusLarge),
        ),
      ),
      child: Column(
        children: [
          _buildHeader(),
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(AppDimensions.paddingMedium),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSearchSection(),
                  const SizedBox(height: AppDimensions.spacingLarge),
                  _buildPeriodSection(),
                  const SizedBox(height: AppDimensions.spacingLarge),
                  _buildStatusSection(),
                  if (widget.availableStores != null) ...[
                    const SizedBox(height: AppDimensions.spacingLarge),
                    _buildStoreSection(),
                  ],
                  const SizedBox(height: AppDimensions.spacingLarge),
                  _buildAmountSection(),
                  const SizedBox(height: AppDimensions.spacingLarge),
                  _buildCashbackSection(),
                  const SizedBox(height: AppDimensions.spacingLarge),
                  _buildSortingSection(),
                  const SizedBox(height: 100),
                ],
              ),
            ),
          ),
          _buildActionButtons(),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      decoration: BoxDecoration(
        border: Border(
          bottom: BorderSide(
            color: AppColors.gray200,
            width: 1,
          ),
        ),
      ),
      child: Row(
        children: [
          IconButton(
            onPressed: () => Navigator.of(context).pop(),
            icon: const Icon(Icons.close),
            color: AppColors.textSecondary,
          ),
          Expanded(
            child: Text(
              'Filtros',
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w600,
                color: AppColors.textPrimary,
              ),
              textAlign: TextAlign.center,
            ),
          ),
          TextButton(
            onPressed: _clearAllFilters,
            child: Text(
              'Limpar',
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w500,
                color: AppColors.primary,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSearchSection() {
    return _FilterSection(
      title: 'Buscar',
      icon: Icons.search,
      child: CustomTextField(
        controller: _searchController,
        hintText: 'Nome da loja, código da transação...',
        prefixIcon: Icons.search,
        onChanged: (value) {
          _updateFilter((filter) => filter.copyWith(textoBusca: value.isEmpty ? null : value));
        },
      ),
    );
  }

  Widget _buildPeriodSection() {
    return _FilterSection(
      title: 'Período',
      icon: Icons.calendar_today,
      child: Column(
        children: [
          _buildPeriodOptions(),
          if (_selectedPeriod == 'custom') ...[
            const SizedBox(height: AppDimensions.spacingMedium),
            _buildCustomDatePickers(),
          ],
        ],
      ),
    );
  }

  Widget _buildPeriodOptions() {
    final periods = [
      ('today', 'Hoje'),
      ('yesterday', 'Ontem'),
      ('last_week', 'Última semana'),
      ('last_month', 'Último mês'),
      ('last_3_months', 'Últimos 3 meses'),
      ('custom', 'Período personalizado'),
    ];

    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: periods.map((period) {
        final isSelected = _selectedPeriod == period.$1;
        return FilterChip(
          label: Text(period.$2),
          selected: isSelected,
          onSelected: (selected) {
            setState(() {
              _selectedPeriod = selected ? period.$1 : '';
              if (selected && period.$1 != 'custom') {
                _applyPredefinedPeriod(period.$1);
              } else if (!selected) {
                _clearPeriodFilter();
              }
            });
          },
          backgroundColor: AppColors.gray100,
          selectedColor: AppColors.primaryLight,
          checkmarkColor: AppColors.primary,
          labelStyle: TextStyle(
            color: isSelected ? AppColors.primary : AppColors.textSecondary,
            fontWeight: isSelected ? FontWeight.w600 : FontWeight.w400,
          ),
        );
      }).toList(),
    );
  }

  Widget _buildCustomDatePickers() {
    return Row(
      children: [
        Expanded(
          child: _DatePickerField(
            label: 'Data inicial',
            selectedDate: _customStartDate,
            onDateSelected: (date) {
              setState(() {
                _customStartDate = date;
                _updatePeriodFilter();
              });
            },
          ),
        ),
        const SizedBox(width: AppDimensions.spacingMedium),
        Expanded(
          child: _DatePickerField(
            label: 'Data final',
            selectedDate: _customEndDate,
            onDateSelected: (date) {
              setState(() {
                _customEndDate = date;
                _updatePeriodFilter();
              });
            },
          ),
        ),
      ],
    );
  }

  Widget _buildStatusSection() {
    return _FilterSection(
      title: 'Status',
      icon: Icons.check_circle,
      child: Wrap(
        spacing: 8,
        runSpacing: 8,
        children: CashbackTransactionStatus.values.map((status) {
          final isSelected = _workingFilter.status?.contains(status) ?? false;
          return FilterChip(
            label: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                CashbackStatusChip(
                  status: status,
                  isCompact: true,
                ),
                const SizedBox(width: 8),
                Text(_getStatusDisplayName(status)),
              ],
            ),
            selected: isSelected,
            onSelected: (selected) {
              _toggleStatusFilter(status, selected);
            },
            backgroundColor: AppColors.gray100,
            selectedColor: AppColors.primaryLight,
            checkmarkColor: AppColors.primary,
          );
        }).toList(),
      ),
    );
  }

  Widget _buildStoreSection() {
    if (widget.availableStores == null || widget.availableStores!.isEmpty) {
      return const SizedBox.shrink();
    }

    return _FilterSection(
      title: 'Lojas',
      icon: Icons.store,
      child: Column(
        children: [
          if (widget.availableStores!.length > 5)
            Text(
              'Selecione até 5 lojas para filtrar',
              style: const TextStyle(
                fontSize: 12,
                color: AppColors.textSecondary,
              ),
            ),
          const SizedBox(height: AppDimensions.spacingSmall),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: widget.availableStores!.take(10).map((store) {
              final isSelected = _workingFilter.lojaIds?.contains(store.id) ?? false;
              return FilterChip(
                label: Text(store.name),
                selected: isSelected,
                onSelected: (selected) {
                  _toggleStoreFilter(store.id, selected);
                },
                backgroundColor: AppColors.gray100,
                selectedColor: AppColors.primaryLight,
                checkmarkColor: AppColors.primary,
              );
            }).toList(),
          ),
        ],
      ),
    );
  }

  Widget _buildAmountSection() {
    return _FilterSection(
      title: 'Valor da transação',
      icon: Icons.payments,
      child: Row(
        children: [
          Expanded(
            child: CustomTextField(
              controller: _minAmountController,
              hintText: 'Mín.',
              prefixText: 'R\$ ',
              keyboardType: TextInputType.number,
              onChanged: (value) {
                final amount = double.tryParse(value.replaceAll(',', '.'));
                _updateFilter((filter) => filter.copyWith(valorMinimoTransacao: amount));
              },
            ),
          ),
          const SizedBox(width: AppDimensions.spacingMedium),
          Expanded(
            child: CustomTextField(
              controller: _maxAmountController,
              hintText: 'Máx.',
              prefixText: 'R\$ ',
              keyboardType: TextInputType.number,
              onChanged: (value) {
                final amount = double.tryParse(value.replaceAll(',', '.'));
                _updateFilter((filter) => filter.copyWith(valorMaximoTransacao: amount));
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCashbackSection() {
    return _FilterSection(
      title: 'Valor do cashback',
      icon: Icons.account_balance_wallet,
      child: Row(
        children: [
          Expanded(
            child: CustomTextField(
              controller: _minCashbackController,
              hintText: 'Mín.',
              prefixText: 'R\$ ',
              keyboardType: TextInputType.number,
              onChanged: (value) {
                final amount = double.tryParse(value.replaceAll(',', '.'));
                _updateFilter((filter) => filter.copyWith(cashbackMinimo: amount));
              },
            ),
          ),
          const SizedBox(width: AppDimensions.spacingMedium),
          Expanded(
            child: CustomTextField(
              controller: _maxCashbackController,
              hintText: 'Máx.',
              prefixText: 'R\$ ',
              keyboardType: TextInputType.number,
              onChanged: (value) {
                final amount = double.tryParse(value.replaceAll(',', '.'));
                _updateFilter((filter) => filter.copyWith(cashbackMaximo: amount));
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSortingSection() {
    return _FilterSection(
      title: 'Ordenação',
      icon: Icons.sort,
      child: Column(
        children: [
          DropdownButtonFormField<CashbackSortOption>(
            value: _workingFilter.orderBy,
            decoration: InputDecoration(
              labelText: 'Ordenar por',
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
              ),
            ),
            items: CashbackSortOption.values.map((option) {
              return DropdownMenuItem(
                value: option,
                child: Text(_getSortOptionDisplayName(option)),
              );
            }).toList(),
            onChanged: (value) {
              if (value != null) {
                _updateFilter((filter) => filter.copyWith(orderBy: value));
              }
            },
          ),
          const SizedBox(height: AppDimensions.spacingMedium),
          DropdownButtonFormField<SortDirection>(
            value: _workingFilter.sortDirection,
            decoration: InputDecoration(
              labelText: 'Direção',
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
              ),
            ),
            items: SortDirection.values.map((direction) {
              return DropdownMenuItem(
                value: direction,
                child: Text(_getSortDirectionDisplayName(direction)),
              );
            }).toList(),
            onChanged: (value) {
              if (value != null) {
                _updateFilter((filter) => filter.copyWith(sortDirection: value));
              }
            },
          ),
        ],
      ),
    );
  }

  Widget _buildActionButtons() {
    final activeFiltersCount = _workingFilter.activeFiltersCount;
    
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      decoration: BoxDecoration(
        color: AppColors.white,
        border: Border(
          top: BorderSide(
            color: AppColors.gray200,
            width: 1,
          ),
        ),
      ),
      child: Row(
        children: [
          if (activeFiltersCount > 0)
            Expanded(
              child: CustomButton(
                text: 'Limpar ($activeFiltersCount)',
                onPressed: _clearAllFilters,
                type: ButtonType.outline,
              ),
            ),
          if (activeFiltersCount > 0)
            const SizedBox(width: AppDimensions.spacingMedium),
          Expanded(
            flex: activeFiltersCount > 0 ? 2 : 1,
            child: CustomButton(
              text: activeFiltersCount > 0 ? 'Aplicar Filtros' : 'Fechar',
              onPressed: () {
                widget.onApplyFilter(_workingFilter);
                Navigator.of(context).pop();
              },
            ),
          ),
        ],
      ),
    );
  }

  void _updateFilter(CashbackFilter Function(CashbackFilter) updater) {
    setState(() {
      _workingFilter = updater(_workingFilter);
    });
  }

  void _applyPredefinedPeriod(String period) {
    final now = DateTime.now();
    DateTime? startDate;
    DateTime? endDate;

    switch (period) {
      case 'today':
        startDate = AppDateUtils.startOfDay(now);
        endDate = AppDateUtils.endOfDay(now);
        break;
      case 'yesterday':
        final yesterday = now.subtract(const Duration(days: 1));
        startDate = AppDateUtils.startOfDay(yesterday);
        endDate = AppDateUtils.endOfDay(yesterday);
        break;
      case 'last_week':
        startDate = AppDateUtils.startOfDay(now.subtract(const Duration(days: 7)));
        endDate = AppDateUtils.endOfDay(now);
        break;
      case 'last_month':
        startDate = AppDateUtils.startOfDay(now.subtract(const Duration(days: 30)));
        endDate = AppDateUtils.endOfDay(now);
        break;
      case 'last_3_months':
        startDate = AppDateUtils.startOfDay(now.subtract(const Duration(days: 90)));
        endDate = AppDateUtils.endOfDay(now);
        break;
    }

    if (startDate != null && endDate != null) {
      _updateFilter((filter) => filter.copyWith(
        dataInicio: startDate,
        dataFim: endDate,
      ));
    }
  }

  void _updatePeriodFilter() {
    if (_customStartDate != null && _customEndDate != null) {
      _updateFilter((filter) => filter.copyWith(
        dataInicio: AppDateUtils.startOfDay(_customStartDate!),
        dataFim: AppDateUtils.endOfDay(_customEndDate!),
      ));
    }
  }

  void _clearPeriodFilter() {
    _updateFilter((filter) => filter.copyWith(
      dataInicio: null,
      dataFim: null,
    ));
    _customStartDate = null;
    _customEndDate = null;
  }

  void _toggleStatusFilter(CashbackTransactionStatus status, bool selected) {
    final currentStatus = _workingFilter.status ?? <CashbackTransactionStatus>[];
    List<CashbackTransactionStatus> newStatus;

    if (selected) {
      newStatus = [...currentStatus, status];
    } else {
      newStatus = currentStatus.where((s) => s != status).toList();
    }

    _updateFilter((filter) => filter.copyWith(
      status: newStatus.isEmpty ? null : newStatus,
    ));
  }

  void _toggleStoreFilter(int storeId, bool selected) {
    final currentStores = _workingFilter.lojaIds ?? <int>[];
    List<int> newStores;

    if (selected) {
      newStores = [...currentStores, storeId];
    } else {
      newStores = currentStores.where((id) => id != storeId).toList();
    }

    _updateFilter((filter) => filter.copyWith(
      lojaIds: newStores.isEmpty ? null : newStores,
    ));
  }

  void _clearAllFilters() {
    setState(() {
      _workingFilter = const CashbackFilter();
      _selectedPeriod = '';
      _customStartDate = null;
      _customEndDate = null;
      _searchController.clear();
      _minAmountController.clear();
      _maxAmountController.clear();
      _minCashbackController.clear();
      _maxCashbackController.clear();
    });
  }

  String _getStatusDisplayName(CashbackTransactionStatus status) {
    switch (status) {
      case CashbackTransactionStatus.pendente:
        return 'Aguardando';
      case CashbackTransactionStatus.aprovado:
        return 'Confirmado';
      case CashbackTransactionStatus.cancelado:
        return 'Cancelado';
      case CashbackTransactionStatus.pagamentoPendente:
        return 'Pag. Pendente';
    }
  }

  String _getSortOptionDisplayName(CashbackSortOption option) {
    switch (option) {
      case CashbackSortOption.data:
        return 'Data';
      case CashbackSortOption.valorTransacao:
        return 'Valor da transação';
      case CashbackSortOption.valorCashback:
        return 'Valor do cashback';
      case CashbackSortOption.nomeLoja:
        return 'Nome da loja';
      case CashbackSortOption.categoria:
        return 'Categoria';
    }
  }

  String _getSortDirectionDisplayName(SortDirection direction) {
    switch (direction) {
      case SortDirection.asc:
        return 'Crescente';
      case SortDirection.desc:
        return 'Decrescente';
    }
  }
}

/// Widget para seção de filtro com título e ícone
class _FilterSection extends StatelessWidget {
  final String title;
  final IconData icon;
  final Widget child;

  const _FilterSection({
    required this.title,
    required this.icon,
    required this.child,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(
              icon,
              size: 20,
              color: AppColors.primary,
            ),
            const SizedBox(width: AppDimensions.spacingSmall),
            Text(
              title,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: AppColors.textPrimary,
              ),
            ),
          ],
        ),
        const SizedBox(height: AppDimensions.spacingMedium),
        child,
      ],
    );
  }
}

/// Widget para seleção de data
class _DatePickerField extends StatelessWidget {
  final String label;
  final DateTime? selectedDate;
  final Function(DateTime) onDateSelected;

  const _DatePickerField({
    required this.label,
    required this.selectedDate,
    required this.onDateSelected,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () async {
        final date = await showDatePicker(
          context: context,
          initialDate: selectedDate ?? DateTime.now(),
          firstDate: DateTime.now().subtract(const Duration(days: 365 * 2)),
          lastDate: DateTime.now(),
          locale: const Locale('pt', 'BR'),
        );
        
        if (date != null) {
          onDateSelected(date);
        }
      },
      child: Container(
        padding: const EdgeInsets.all(AppDimensions.paddingMedium),
        decoration: BoxDecoration(
          border: Border.all(color: AppColors.gray300),
          borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
        ),
        child: Row(
          children: [
            Icon(
              Icons.calendar_today,
              size: 16,
              color: AppColors.textSecondary,
            ),
            const SizedBox(width: AppDimensions.spacingSmall),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.textSecondary,
                    ),
                  ),
                  Text(
                    selectedDate != null
                        ? AppDateUtils.formatDate(selectedDate!)
                        : 'Selecionar',
                    style: TextStyle(
                      fontSize: 14,
                      color: selectedDate != null
                          ? AppColors.textPrimary
                          : AppColors.textSecondary,
                      fontWeight: selectedDate != null
                          ? FontWeight.w500
                          : FontWeight.w400,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Classe auxiliar para opções de loja
class StoreOption {
  final int id;
  final String name;
  final String? logo;

  const StoreOption({
    required this.id,
    required this.name,
    this.logo,
  });
}

/// Função para exibir o sheet de filtros
Future<void> showCashbackFilterSheet(
  BuildContext context, {
  required CashbackFilter currentFilter,
  required Function(CashbackFilter) onApplyFilter,
  List<StoreOption>? availableStores,
}) {
  return showModalBottomSheet(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (context) => CashbackFilterSheet(
      currentFilter: currentFilter,
      onApplyFilter: onApplyFilter,
      availableStores: availableStores,
    ).animate().slideY(begin: 1, end: 0, duration: 300.ms),
  );
}