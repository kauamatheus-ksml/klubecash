// lib/features/dashboard/presentation/widgets/cashback_chart.dart
// 📊 Cashback Chart - Gráfico de evolução do cashback no dashboard

import 'package:flutter/material.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:intl/intl.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/utils/currency_utils.dart';

/// Enum para períodos do gráfico
enum ChartPeriod {
  week('7 dias', 7),
  month('30 dias', 30),
  quarter('90 dias', 90);

  const ChartPeriod(this.label, this.days);
  final String label;
  final int days;
}

/// Modelo de dados para pontos do gráfico
class ChartDataPoint {
  final DateTime date;
  final double received;
  final double used;

  const ChartDataPoint({
    required this.date,
    required this.received,
    required this.used,
  });
}

/// Widget de gráfico de evolução do cashback
class CashbackChart extends StatefulWidget {
  /// Dados para o gráfico
  final List<ChartDataPoint> data;
  
  /// Período selecionado
  final ChartPeriod period;
  
  /// Callback para mudança de período
  final ValueChanged<ChartPeriod>? onPeriodChanged;
  
  /// Se deve mostrar animação
  final bool showAnimation;
  
  /// Altura do gráfico
  final double height;

  const CashbackChart({
    super.key,
    required this.data,
    this.period = ChartPeriod.month,
    this.onPeriodChanged,
    this.showAnimation = true,
    this.height = 200,
  });

  @override
  State<CashbackChart> createState() => _CashbackChartState();
}

class _CashbackChartState extends State<CashbackChart>
    with TickerProviderStateMixin {
  late AnimationController _animationController;
  late Animation<double> _animation;
  int? _touchedIndex;

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
      duration: const Duration(milliseconds: 1500),
      vsync: this,
    );
    _animation = Tween<double>(begin: 0, end: 1).animate(
      CurvedAnimation(parent: _animationController, curve: Curves.easeInOut),
    );
    
    if (widget.showAnimation) {
      _animationController.forward();
    }
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (widget.data.isEmpty) {
      return _buildEmptyState();
    }

    Widget chart = Container(
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildHeader(),
          const SizedBox(height: AppDimensions.spacingMedium),
          _buildChart(),
          const SizedBox(height: AppDimensions.spacingMedium),
          _buildLegend(),
        ],
      ),
    );

    if (widget.showAnimation) {
      return chart
          .animate()
          .fadeIn(duration: 400.ms, delay: 200.ms)
          .slideY(begin: 0.3, end: 0, duration: 500.ms);
    }

    return chart;
  }

  Widget _buildHeader() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              '📊 Evolução do Cashback',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              'Últimos ${widget.period.label}',
              style: TextStyle(
                fontSize: 14,
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
        if (widget.onPeriodChanged != null) _buildPeriodSelector(),
      ],
    );
  }

  Widget _buildPeriodSelector() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: AppColors.gray100,
        borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<ChartPeriod>(
          value: widget.period,
          isDense: true,
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w600,
            color: AppColors.textPrimary,
          ),
          onChanged: widget.onPeriodChanged,
          items: ChartPeriod.values.map((period) {
            return DropdownMenuItem(
              value: period,
              child: Text(period.label),
            );
          }).toList(),
        ),
      ),
    );
  }

  Widget _buildChart() {
    return SizedBox(
      height: widget.height,
      child: AnimatedBuilder(
        animation: _animation,
        builder: (context, child) {
          return LineChart(
            _buildLineChartData(),
            curve: Curves.easeInOut,
          );
        },
      ),
    );
  }

  LineChartData _buildLineChartData() {
    final spots = _generateSpots();
    final maxValue = _getMaxValue();

    return LineChartData(
      gridData: FlGridData(
        show: true,
        drawHorizontalLine: true,
        drawVerticalLine: false,
        horizontalInterval: maxValue / 4,
        getDrawingHorizontalLine: (value) => FlLine(
          color: AppColors.gray200,
          strokeWidth: 1,
        ),
      ),
      titlesData: FlTitlesData(
        leftTitles: AxisTitles(
          sideTitles: SideTitles(
            showTitles: true,
            interval: maxValue / 4,
            getTitlesWidget: (value, meta) => Text(
              CurrencyUtils.formatCompact(value),
              style: TextStyle(
                fontSize: 12,
                color: AppColors.textSecondary,
              ),
            ),
            reservedSize: 60,
          ),
        ),
        bottomTitles: AxisTitles(
          sideTitles: SideTitles(
            showTitles: true,
            interval: _getBottomInterval(),
            getTitlesWidget: _buildBottomTitle,
            reservedSize: 32,
          ),
        ),
        rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
        topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
      ),
      borderData: FlBorderData(show: false),
      lineBarsData: [
        // Linha de cashback recebido
        LineChartBarData(
          spots: spots.received.map((spot) => FlSpot(
            spot.x,
            spot.y * _animation.value,
          )).toList(),
          isCurved: true,
          color: AppColors.success,
          barWidth: 3,
          isStrokeCapRound: true,
          belowBarData: BarAreaData(
            show: true,
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [
                AppColors.success.withOpacity(0.3),
                AppColors.success.withOpacity(0.1),
                Colors.transparent,
              ],
            ),
          ),
          dotData: FlDotData(
            show: true,
            getDotPainter: (spot, percent, barData, index) => FlDotCirclePainter(
              radius: 4,
              color: AppColors.success,
              strokeWidth: 2,
              strokeColor: Colors.white,
            ),
          ),
        ),
        // Linha de saldo usado
        LineChartBarData(
          spots: spots.used.map((spot) => FlSpot(
            spot.x,
            spot.y * _animation.value,
          )).toList(),
          isCurved: true,
          color: AppColors.warning,
          barWidth: 3,
          isStrokeCapRound: true,
          belowBarData: BarAreaData(
            show: true,
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [
                AppColors.warning.withOpacity(0.3),
                AppColors.warning.withOpacity(0.1),
                Colors.transparent,
              ],
            ),
          ),
          dotData: FlDotData(
            show: true,
            getDotPainter: (spot, percent, barData, index) => FlDotCirclePainter(
              radius: 4,
              color: AppColors.warning,
              strokeWidth: 2,
              strokeColor: Colors.white,
            ),
          ),
        ),
      ],
      lineTouchData: LineTouchData(
        enabled: true,
        touchTooltipData: LineTouchTooltipData(
          tooltipBgColor: AppColors.gray800.withOpacity(0.9),
          tooltipRoundedRadius: 8,
          tooltipPadding: const EdgeInsets.all(8),
          getTooltipItems: _buildTooltipItems,
        ),
        handleBuiltInTouches: true,
        getTouchedSpotIndicator: (barData, spotIndexes) {
          return spotIndexes.map((index) {
            return TouchedSpotIndicatorData(
              FlLine(
                color: barData.color,
                strokeWidth: 2,
              ),
              FlDotData(
                getDotPainter: (spot, percent, barData, index) =>
                    FlDotCirclePainter(
                  radius: 6,
                  color: barData.color,
                  strokeWidth: 3,
                  strokeColor: Colors.white,
                ),
              ),
            );
          }).toList();
        },
      ),
      minX: 0,
      maxX: widget.data.length.toDouble() - 1,
      minY: 0,
      maxY: maxValue,
    );
  }

  ({List<FlSpot> received, List<FlSpot> used}) _generateSpots() {
    final receivedSpots = <FlSpot>[];
    final usedSpots = <FlSpot>[];

    for (int i = 0; i < widget.data.length; i++) {
      final dataPoint = widget.data[i];
      receivedSpots.add(FlSpot(i.toDouble(), dataPoint.received));
      usedSpots.add(FlSpot(i.toDouble(), dataPoint.used));
    }

    return (received: receivedSpots, used: usedSpots);
  }

  double _getMaxValue() {
    double max = 0;
    for (final point in widget.data) {
      max = [max, point.received, point.used].reduce((a, b) => a > b ? a : b);
    }
    return max > 0 ? max * 1.1 : 100; // Adiciona 10% de margem
  }

  double _getBottomInterval() {
    final length = widget.data.length;
    if (length <= 7) return 1;
    if (length <= 30) return 5;
    return 10;
  }

  Widget _buildBottomTitle(double value, TitleMeta meta) {
    final index = value.toInt();
    if (index < 0 || index >= widget.data.length) {
      return const SizedBox.shrink();
    }

    final date = widget.data[index].date;
    final formatter = widget.period == ChartPeriod.week
        ? DateFormat('dd/MM')
        : DateFormat('dd');

    return Text(
      formatter.format(date),
      style: TextStyle(
        fontSize: 12,
        color: AppColors.textSecondary,
      ),
    );
  }

  List<LineTooltipItem> _buildTooltipItems(List<LineBarSpot> touchedSpots) {
    return touchedSpots.map((touchedSpot) {
      final dataPoint = widget.data[touchedSpot.spotIndex];
      final isReceived = touchedSpot.barIndex == 0;
      
      return LineTooltipItem(
        '${isReceived ? 'Recebido' : 'Usado'}: ${CurrencyUtils.format(touchedSpot.y)}\n${DateFormat('dd/MM').format(dataPoint.date)}',
        TextStyle(
          color: Colors.white,
          fontWeight: FontWeight.w600,
          fontSize: 12,
        ),
      );
    }).toList();
  }

  Widget _buildLegend() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        _buildLegendItem(
          color: AppColors.success,
          label: 'Cashback Recebido',
          icon: '📈',
        ),
        const SizedBox(width: AppDimensions.spacingLarge),
        _buildLegendItem(
          color: AppColors.warning,
          label: 'Saldo Usado',
          icon: '💸',
        ),
      ],
    );
  }

  Widget _buildLegendItem({
    required Color color,
    required String label,
    required String icon,
  }) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(icon, style: const TextStyle(fontSize: 16)),
        const SizedBox(width: 6),
        Container(
          width: 12,
          height: 12,
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(6),
          ),
        ),
        const SizedBox(width: 6),
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w500,
            color: AppColors.textSecondary,
          ),
        ),
      ],
    );
  }

  Widget _buildEmptyState() {
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingLarge),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          const Text('📊', style: TextStyle(fontSize: 48)),
          const SizedBox(height: 16),
          Text(
            'Sem dados para exibir',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Seus dados de cashback aparecerão aqui conforme você usar o app',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 14,
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}

/// Factory methods para criar gráficos específicos
extension CashbackChartFactory on CashbackChart {
  /// Cria gráfico com dados de exemplo para demonstração
  static CashbackChart demo({
    ChartPeriod period = ChartPeriod.month,
    ValueChanged<ChartPeriod>? onPeriodChanged,
  }) {
    final now = DateTime.now();
    final data = List.generate(30, (index) {
      final date = now.subtract(Duration(days: 29 - index));
      return ChartDataPoint(
        date: date,
        received: (index * 2.5) + (index % 3 * 5),
        used: (index * 1.8) + (index % 4 * 3),
      );
    });

    return CashbackChart(
      data: data,
      period: period,
      onPeriodChanged: onPeriodChanged,
    );
  }
}