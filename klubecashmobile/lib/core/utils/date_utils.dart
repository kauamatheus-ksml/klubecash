// lib/core/utils/date_utils.dart
// Utilitários para manipulação e formatação de datas do Klube Cash
// Inclui períodos de filtro, formatação brasileira e cálculos temporais

import 'package:intl/intl.dart';

class AppDateUtils {
  /// Nomes dos meses em português
  static const List<String> monthNames = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
  ];

  /// Nomes dos dias da semana em português
  static const List<String> weekDayNames = [
    'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'
  ];

  /// Períodos predefinidos para filtros
  static const Map<String, String> predefinedPeriods = {
    'today': 'Hoje',
    'yesterday': 'Ontem',
    'last_week': 'Última semana',
    'last_month': 'Último mês',
    'last_3_months': 'Últimos 3 meses',
    'last_6_months': 'Últimos 6 meses',
    'current_year': 'Este ano',
    'last_year': 'Ano passado',
  };

  /// Retorna data/hora atual
  static DateTime now() => DateTime.now();

  /// Retorna data atual (apenas data, sem hora)
  static DateTime today() {
    final now = DateTime.now();
    return DateTime(now.year, now.month, now.day);
  }

  /// Retorna início do dia para uma data
  static DateTime startOfDay(DateTime date) {
    return DateTime(date.year, date.month, date.day);
  }

  /// Retorna fim do dia para uma data
  static DateTime endOfDay(DateTime date) {
    return DateTime(date.year, date.month, date.day, 23, 59, 59, 999);
  }

  /// Retorna início da semana (segunda-feira)
  static DateTime startOfWeek(DateTime date) {
    final weekday = date.weekday;
    final daysToSubtract = weekday - 1;
    return startOfDay(date.subtract(Duration(days: daysToSubtract)));
  }

  /// Retorna fim da semana (domingo)
  static DateTime endOfWeek(DateTime date) {
    final weekday = date.weekday;
    final daysToAdd = 7 - weekday;
    return endOfDay(date.add(Duration(days: daysToAdd)));
  }

  /// Retorna início do mês
  static DateTime startOfMonth(DateTime date) {
    return DateTime(date.year, date.month, 1);
  }

  /// Retorna fim do mês
  static DateTime endOfMonth(DateTime date) {
    final nextMonth = date.month == 12 
        ? DateTime(date.year + 1, 1, 1)
        : DateTime(date.year, date.month + 1, 1);
    return endOfDay(nextMonth.subtract(const Duration(days: 1)));
  }

  /// Retorna início do ano
  static DateTime startOfYear(DateTime date) {
    return DateTime(date.year, 1, 1);
  }

  /// Retorna fim do ano
  static DateTime endOfYear(DateTime date) {
    return DateTime(date.year, 12, 31, 23, 59, 59, 999);
  }

  /// Calcula período baseado em string predefinida
  static Map<String, DateTime> getPredefinedPeriod(String period) {
    final now = DateTime.now();
    final today = startOfDay(now);

    switch (period) {
      case 'today':
        return {'start': today, 'end': endOfDay(now)};
      
      case 'yesterday':
        final yesterday = today.subtract(const Duration(days: 1));
        return {'start': yesterday, 'end': endOfDay(yesterday)};
      
      case 'last_week':
        final lastWeekStart = startOfWeek(today.subtract(const Duration(days: 7)));
        final lastWeekEnd = endOfWeek(today.subtract(const Duration(days: 7)));
        return {'start': lastWeekStart, 'end': lastWeekEnd};
      
      case 'last_month':
        final lastMonth = DateTime(now.year, now.month - 1, 1);
        return {'start': startOfMonth(lastMonth), 'end': endOfMonth(lastMonth)};
      
      case 'last_3_months':
        final threeMonthsAgo = DateTime(now.year, now.month - 3, now.day);
        return {'start': startOfDay(threeMonthsAgo), 'end': endOfDay(now)};
      
      case 'last_6_months':
        final sixMonthsAgo = DateTime(now.year, now.month - 6, now.day);
        return {'start': startOfDay(sixMonthsAgo), 'end': endOfDay(now)};
      
      case 'current_year':
        return {'start': startOfYear(now), 'end': endOfDay(now)};
      
      case 'last_year':
        final lastYear = DateTime(now.year - 1, 1, 1);
        return {'start': startOfYear(lastYear), 'end': endOfYear(lastYear)};
      
      default:
        return {'start': today, 'end': endOfDay(now)};
    }
  }

  /// Gera lista de períodos para dashboard (7, 30, 90 dias)
  static List<Map<String, dynamic>> getDashboardPeriods() {
    final now = DateTime.now();
    
    return [
      {
        'label': '7 dias',
        'days': 7,
        'start': startOfDay(now.subtract(const Duration(days: 7))),
        'end': endOfDay(now),
      },
      {
        'label': '30 dias',
        'days': 30,
        'start': startOfDay(now.subtract(const Duration(days: 30))),
        'end': endOfDay(now),
      },
      {
        'label': '90 dias',
        'days': 90,
        'start': startOfDay(now.subtract(const Duration(days: 90))),
        'end': endOfDay(now),
      },
    ];
  }

  /// Formata mês/ano em português (Janeiro/2024)
  static String formatMonthYear(DateTime date) {
    return '${monthNames[date.month - 1]}/${date.year}';
  }

  /// Formata mês abreviado/ano (Jan/24)
  static String formatMonthYearShort(DateTime date) {
    final shortMonth = monthNames[date.month - 1].substring(0, 3);
    final shortYear = date.year.toString().substring(2);
    return '$shortMonth/$shortYear';
  }

  /// Gera lista de meses dos últimos N meses
  static List<Map<String, dynamic>> getLastMonths(int count) {
    final now = DateTime.now();
    final months = <Map<String, dynamic>>[];
    
    for (int i = count - 1; i >= 0; i--) {
      final month = DateTime(now.year, now.month - i, 1);
      months.add({
        'date': month,
        'label': formatMonthYear(month),
        'shortLabel': formatMonthYearShort(month),
        'start': startOfMonth(month),
        'end': endOfMonth(month),
      });
    }
    
    return months;
  }

  /// Calcula diferença em dias entre duas datas
  static int daysBetween(DateTime start, DateTime end) {
    return end.difference(start).inDays;
  }

  /// Verifica se uma data é hoje
  static bool isToday(DateTime date) {
    final now = DateTime.now();
    return date.year == now.year && 
           date.month == now.month && 
           date.day == now.day;
  }

  /// Verifica se uma data é ontem
  static bool isYesterday(DateTime date) {
    final yesterday = DateTime.now().subtract(const Duration(days: 1));
    return date.year == yesterday.year && 
           date.month == yesterday.month && 
           date.day == yesterday.day;
  }

  /// Verifica se uma data está na semana atual
  static bool isThisWeek(DateTime date) {
    final now = DateTime.now();
    final weekStart = startOfWeek(now);
    final weekEnd = endOfWeek(now);
    return date.isAfter(weekStart.subtract(const Duration(seconds: 1))) && 
           date.isBefore(weekEnd.add(const Duration(seconds: 1)));
  }

  /// Verifica se uma data está no mês atual
  static bool isThisMonth(DateTime date) {
    final now = DateTime.now();
    return date.year == now.year && date.month == now.month;
  }

  /// Formata data relativa (hoje, ontem, há X dias)
  static String formatRelative(DateTime date) {
    if (isToday(date)) return 'Hoje';
    if (isYesterday(date)) return 'Ontem';
    
    final now = DateTime.now();
    final difference = now.difference(date).inDays;
    
    if (difference < 7) {
      return 'Há $difference ${difference == 1 ? 'dia' : 'dias'}';
    } else if (difference < 30) {
      final weeks = (difference / 7).floor();
      return 'Há $weeks ${weeks == 1 ? 'semana' : 'semanas'}';
    } else if (difference < 365) {
      final months = (difference / 30).floor();
      return 'Há $months ${months == 1 ? 'mês' : 'meses'}';
    } else {
      final years = (difference / 365).floor();
      return 'Há $years ${years == 1 ? 'ano' : 'anos'}';
    }
  }

  /// Agrupa datas por período (dia, semana, mês, ano)
  static String groupByPeriod(DateTime date, String period) {
    switch (period.toLowerCase()) {
      case 'day':
        return DateFormat('yyyy-MM-dd').format(date);
      case 'week':
        final weekStart = startOfWeek(date);
        return DateFormat('yyyy-MM-dd').format(weekStart);
      case 'month':
        return DateFormat('yyyy-MM').format(date);
      case 'year':
        return DateFormat('yyyy').format(date);
      default:
        return DateFormat('yyyy-MM-dd').format(date);
    }
  }

  /// Converte string de período para label em português
  static String periodToPortuguese(String period, DateTime date) {
    switch (period.toLowerCase()) {
      case 'day':
        return DateFormat('dd/MM/yyyy').format(date);
      case 'week':
        final weekStart = startOfWeek(date);
        final weekEnd = endOfWeek(date);
        return '${DateFormat('dd/MM').format(weekStart)} - ${DateFormat('dd/MM').format(weekEnd)}';
      case 'month':
        return formatMonthYear(date);
      case 'year':
        return date.year.toString();
      default:
        return DateFormat('dd/MM/yyyy').format(date);
    }
  }

  /// Gera lista de datas entre duas datas
  static List<DateTime> getDaysBetween(DateTime start, DateTime end) {
    final days = <DateTime>[];
    DateTime current = startOfDay(start);
    final endDay = startOfDay(end);
    
    while (!current.isAfter(endDay)) {
      days.add(current);
      current = current.add(const Duration(days: 1));
    }
    
    return days;
  }

  /// Calcula idade em anos
  static int calculateAge(DateTime birthDate) {
    final now = DateTime.now();
    int age = now.year - birthDate.year;
    
    if (now.month < birthDate.month || 
        (now.month == birthDate.month && now.day < birthDate.day)) {
      age--;
    }
    
    return age;
  }

  /// Converte DateTime para timestamp Unix
  static int toTimestamp(DateTime date) {
    return date.millisecondsSinceEpoch ~/ 1000;
  }

  /// Converte timestamp Unix para DateTime
  static DateTime fromTimestamp(int timestamp) {
    return DateTime.fromMillisecondsSinceEpoch(timestamp * 1000);
  }

  /// Formata duração em formato legível
  static String formatDuration(Duration duration) {
    final days = duration.inDays;
    final hours = duration.inHours % 24;
    final minutes = duration.inMinutes % 60;
    
    if (days > 0) {
      return '$days ${days == 1 ? 'dia' : 'dias'}';
    } else if (hours > 0) {
      return '$hours ${hours == 1 ? 'hora' : 'horas'}';
    } else {
      return '$minutes ${minutes == 1 ? 'minuto' : 'minutos'}';
    }
  }

  /// Verifica se um ano é bissexto
  static bool isLeapYear(int year) {
    return (year % 4 == 0 && year % 100 != 0) || (year % 400 == 0);
  }

  /// Retorna o número de dias no mês
  static int daysInMonth(int year, int month) {
    return DateTime(year, month + 1, 0).day;
  }
}