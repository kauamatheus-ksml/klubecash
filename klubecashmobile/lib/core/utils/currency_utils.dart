// lib/core/utils/currency_utils.dart
// Utilitários para valores monetários, cashback e cálculos financeiros do Klube Cash

import 'package:intl/intl.dart';

class CurrencyUtils {
  /// Formatador monetário brasileiro
  static final _currencyFormatter = NumberFormat.currency(
    locale: 'pt_BR',
    symbol: 'R\$ ',
    decimalDigits: 2,
  );

  /// Formatador apenas com vírgula (sem símbolo)
  static final _numberFormatter = NumberFormat('#,##0.00', 'pt_BR');

  /// Formata valor para exibição monetária (R$ 1.234,56)
  static String format(double value) {
    return _currencyFormatter.format(value);
  }

  /// Formata valor sem símbolo (1.234,56)
  static String formatNumber(double value) {
    return _numberFormatter.format(value);
  }

  /// Formata valor compacto (R$ 1,2K, R$ 1,5M)
  static String formatCompact(double value) {
    if (value < 1000) {
      return format(value);
    } else if (value < 1000000) {
      return 'R\$ ${(value / 1000).toStringAsFixed(1).replaceAll('.', ',')}K';
    } else if (value < 1000000000) {
      return 'R\$ ${(value / 1000000).toStringAsFixed(1).replaceAll('.', ',')}M';
    } else {
      return 'R\$ ${(value / 1000000000).toStringAsFixed(1).replaceAll('.', ',')}B';
    }
  }

  /// Converte string monetária para double
  static double parse(String value) {
    final cleanValue = value
        .replaceAll('R\$', '')
        .replaceAll(' ', '')
        .replaceAll('.', '')
        .replaceAll(',', '.');
    return double.tryParse(cleanValue) ?? 0.0;
  }

  /// Calcula cashback
  static double calculateCashback(double amount, double percentage) {
    return roundCurrency(amount * percentage / 100);
  }

  /// Calcula valor com desconto
  static double applyDiscount(double amount, double discount) {
    return roundCurrency(amount - discount);
  }

  /// Calcula porcentagem de um valor
  static double calculatePercentage(double value, double total) {
    return total > 0 ? (value / total) * 100 : 0.0;
  }

  /// Arredonda valor monetário (2 casas decimais)
  static double roundCurrency(double value) {
    return double.parse(value.toStringAsFixed(2));
  }

  /// Distribui cashback conforme regras do sistema
  static Map<String, double> distributeCashback(double totalAmount, {
    double clientPercentage = 5.0,
    double adminPercentage = 5.0,
    double storePercentage = 0.0,
  }) {
    final clientValue = calculateCashback(totalAmount, clientPercentage);
    final adminValue = calculateCashback(totalAmount, adminPercentage);
    final storeValue = calculateCashback(totalAmount, storePercentage);
    final totalCashback = clientValue + adminValue + storeValue;

    return {
      'client': clientValue,
      'admin': adminValue,
      'store': storeValue,
      'total': totalCashback,
    };
  }

  /// Calcula valor efetivamente cobrado (com desconto de saldo)
  static double calculateEffectiveAmount(double originalAmount, double balanceUsed) {
    return roundCurrency(originalAmount - balanceUsed);
  }

  /// Formata porcentagem (5,0%)
  static String formatPercentage(double percentage, {int decimals = 1}) {
    return '${percentage.toStringAsFixed(decimals).replaceAll('.', ',')}%';
  }

  /// Valida se valor é positivo e maior que mínimo
  static bool isValidAmount(double value, {double minValue = 0.01}) {
    return value >= minValue;
  }

  /// Calcula diferença percentual entre dois valores
  static double calculateGrowthPercentage(double oldValue, double newValue) {
    if (oldValue == 0) return newValue > 0 ? 100.0 : 0.0;
    return ((newValue - oldValue) / oldValue) * 100;
  }

  /// Formata diferença com sinal (+/-)
  static String formatDifference(double difference, {bool showSign = true}) {
    final sign = showSign && difference > 0 ? '+' : '';
    return '$sign${format(difference)}';
  }

  /// Calcula média de valores
  static double calculateAverage(List<double> values) {
    if (values.isEmpty) return 0.0;
    final sum = values.reduce((a, b) => a + b);
    return roundCurrency(sum / values.length);
  }

  /// Formata valor de entrada (para campos de texto)
  static String formatInput(String input) {
    // Remove caracteres não numéricos exceto vírgula e ponto
    final cleanInput = input.replaceAll(RegExp(r'[^\d,.]'), '');
    
    // Converte para double e formata
    final value = parse(cleanInput);
    return formatNumber(value);
  }

  /// Converte centavos para reais
  static double centavosToReais(int centavos) {
    return roundCurrency(centavos / 100.0);
  }

  /// Converte reais para centavos
  static int reaisToCentavos(double reais) {
    return (reais * 100).round();
  }

  /// Calcula juros simples
  static double calculateSimpleInterest(double principal, double rate, int periods) {
    return roundCurrency(principal * rate * periods / 100);
  }

  /// Calcula valor futuro com juros
  static double calculateFutureValue(double principal, double rate, int periods) {
    return roundCurrency(principal + calculateSimpleInterest(principal, rate, periods));
  }

  /// Formata range de valores (R$ 10,00 - R$ 50,00)
  static String formatRange(double min, double max) {
    return '${format(min)} - ${format(max)}';
  }

  /// Calcula proporção de um valor
  static double calculateProportion(double value, double total) {
    return total > 0 ? roundCurrency(value / total) : 0.0;
  }

  /// Distribui valor proporcionalmente
  static List<double> distributeProportionally(double total, List<double> weights) {
    final totalWeight = weights.reduce((a, b) => a + b);
    if (totalWeight == 0) return List.filled(weights.length, 0.0);

    return weights.map((weight) => 
        roundCurrency(total * weight / totalWeight)
    ).toList();
  }

  /// Calcula desconto percentual
  static double calculateDiscountPercentage(double originalPrice, double finalPrice) {
    if (originalPrice <= 0) return 0.0;
    return ((originalPrice - finalPrice) / originalPrice) * 100;
  }

  /// Aplica porcentagem sobre valor
  static double applyPercentage(double value, double percentage) {
    return roundCurrency(value * percentage / 100);
  }

  /// Soma lista de valores monetários
  static double sumValues(List<double> values) {
    if (values.isEmpty) return 0.0;
    return roundCurrency(values.reduce((a, b) => a + b));
  }

  /// Converte valor para formato de API (sem formatação)
  static String toApiFormat(double value) {
    return value.toStringAsFixed(2);
  }

  /// Converte valor da API para double
  static double fromApiFormat(dynamic value) {
    if (value is String) return double.tryParse(value) ?? 0.0;
    if (value is num) return value.toDouble();
    return 0.0;
  }

  /// Valida se string representa valor monetário válido
  static bool isValidCurrencyString(String value) {
    final parsed = parse(value);
    return parsed > 0;
  }

  /// Formata valor para entrada de usuário (remove zeros à direita)
  static String formatForInput(double value) {
    if (value == value.toInt()) {
      return value.toInt().toString();
    }
    return value.toStringAsFixed(2).replaceAll('.', ',');
  }

  /// Calcula valor máximo de uma lista
  static double getMaxValue(List<double> values) {
    if (values.isEmpty) return 0.0;
    return values.reduce((a, b) => a > b ? a : b);
  }

  /// Calcula valor mínimo de uma lista
  static double getMinValue(List<double> values) {
    if (values.isEmpty) return 0.0;
    return values.reduce((a, b) => a < b ? a : b);
  }

  /// Formata valor para notificação/toast
  static String formatForNotification(double value) {
    return format(value).replaceAll('R\$ ', '');
  }
}