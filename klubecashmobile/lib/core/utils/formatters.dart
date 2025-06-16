// lib/core/utils/formatters.dart
// Arquivo de formatadores para campos de entrada e exibição de dados
// Contém máscaras e formatações para CPF, CNPJ, telefone, moeda, data, etc.

import 'package:flutter/services.dart';
import 'package:mask_text_input_formatter/mask_text_input_formatter.dart';
import 'package:intl/intl.dart';

class AppFormatters {
  /// Formatador de CPF (000.000.000-00)
  static final cpfFormatter = MaskTextInputFormatter(
    mask: '###.###.###-##',
    filter: {"#": RegExp(r'[0-9]')},
    type: MaskAutoCompletionType.lazy,
  );

  /// Formatador de CNPJ (00.000.000/0000-00)
  static final cnpjFormatter = MaskTextInputFormatter(
    mask: '##.###.###/####-##',
    filter: {"#": RegExp(r'[0-9]')},
    type: MaskAutoCompletionType.lazy,
  );

  /// Formatador de telefone fixo ((00) 0000-0000)
  static final phoneFormatter = MaskTextInputFormatter(
    mask: '(##) ####-####',
    filter: {"#": RegExp(r'[0-9]')},
    type: MaskAutoCompletionType.lazy,
  );

  /// Formatador de celular ((00) 00000-0000)
  static final cellPhoneFormatter = MaskTextInputFormatter(
    mask: '(##) #####-####',
    filter: {"#": RegExp(r'[0-9]')},
    type: MaskAutoCompletionType.lazy,
  );

  /// Formatador de CEP (00000-000)
  static final cepFormatter = MaskTextInputFormatter(
    mask: '#####-###',
    filter: {"#": RegExp(r'[0-9]')},
    type: MaskAutoCompletionType.lazy,
  );

  /// Formatador dinâmico de telefone (detecta se é fixo ou celular)
  static final dynamicPhoneFormatter = MaskTextInputFormatter(
    mask: '(##) #####-####',
    filter: {"#": RegExp(r'[0-9]')},
    type: MaskAutoCompletionType.lazy,
  );

  /// Formatador de data (dd/mm/aaaa)
  static final dateFormatter = MaskTextInputFormatter(
    mask: '##/##/####',
    filter: {"#": RegExp(r'[0-9]')},
    type: MaskAutoCompletionType.lazy,
  );

  /// Formatador de horário (hh:mm)
  static final timeFormatter = MaskTextInputFormatter(
    mask: '##:##',
    filter: {"#": RegExp(r'[0-9]')},
    type: MaskAutoCompletionType.lazy,
  );

  /// Formatador monetário customizado
  static final currencyFormatter = FilteringTextInputFormatter.allow(
    RegExp(r'[0-9,.]'),
  );

  /// Remove formatação de CPF
  static String cleanCPF(String cpf) {
    return cpf.replaceAll(RegExp(r'[^0-9]'), '');
  }

  /// Remove formatação de CNPJ
  static String cleanCNPJ(String cnpj) {
    return cnpj.replaceAll(RegExp(r'[^0-9]'), '');
  }

  /// Remove formatação de telefone
  static String cleanPhone(String phone) {
    return phone.replaceAll(RegExp(r'[^0-9]'), '');
  }

  /// Remove formatação de CEP
  static String cleanCEP(String cep) {
    return cep.replaceAll(RegExp(r'[^0-9]'), '');
  }

  /// Formata CPF para exibição
  static String formatCPF(String cpf) {
    final cleanCpf = cleanCPF(cpf);
    if (cleanCpf.length != 11) return cpf;
    
    return '${cleanCpf.substring(0, 3)}.${cleanCpf.substring(3, 6)}.${cleanCpf.substring(6, 9)}-${cleanCpf.substring(9)}';
  }

  /// Formata CNPJ para exibição
  static String formatCNPJ(String cnpj) {
    final cleanCnpj = cleanCNPJ(cnpj);
    if (cleanCnpj.length != 14) return cnpj;
    
    return '${cleanCnpj.substring(0, 2)}.${cleanCnpj.substring(2, 5)}.${cleanCnpj.substring(5, 8)}/${cleanCnpj.substring(8, 12)}-${cleanCnpj.substring(12)}';
  }

  /// Formata telefone para exibição (detecta automaticamente fixo/celular)
  static String formatPhone(String phone) {
    final cleanedPhone = cleanPhone(phone);
    
    if (cleanedPhone.length == 10) {
      // Telefone fixo: (00) 0000-0000
      return '(${cleanedPhone.substring(0, 2)}) ${cleanedPhone.substring(2, 6)}-${cleanedPhone.substring(6)}';
    } else if (cleanedPhone.length == 11) {
      // Celular: (00) 00000-0000
      return '(${cleanedPhone.substring(0, 2)}) ${cleanedPhone.substring(2, 7)}-${cleanedPhone.substring(7)}';
    }
    
    return phone; // Retorna original se não for válido
  }

  /// Formata CEP para exibição
  static String formatCEP(String cep) {
    final cleanedCep = cleanCEP(cep);
    if (cleanedCep.length != 8) return cep;
    
    return '${cleanedCep.substring(0, 5)}-${cleanedCep.substring(5)}';
  }

  /// Formata valor monetário para exibição (R$ 0,00)
  static String formatCurrency(double value) {
    final formatter = NumberFormat.currency(
      locale: 'pt_BR',
      symbol: 'R\$ ',
      decimalDigits: 2,
    );
    return formatter.format(value);
  }

  /// Formata valor monetário compacto (R$ 1,2K, R$ 1,5M)
  static String formatCurrencyCompact(double value) {
    if (value < 1000) {
      return formatCurrency(value);
    } else if (value < 1000000) {
      return 'R\$ ${(value / 1000).toStringAsFixed(1).replaceAll('.', ',')}K';
    } else if (value < 1000000000) {
      return 'R\$ ${(value / 1000000).toStringAsFixed(1).replaceAll('.', ',')}M';
    } else {
      return 'R\$ ${(value / 1000000000).toStringAsFixed(1).replaceAll('.', ',')}B';
    }
  }

  /// Converte string monetária para double
  static double parseCurrency(String value) {
    // Remove símbolos e converte vírgula para ponto
    final cleanValue = value
        .replaceAll('R\$', '')
        .replaceAll(' ', '')
        .replaceAll('.', '')
        .replaceAll(',', '.');
    
    return double.tryParse(cleanValue) ?? 0.0;
  }

  /// Formata porcentagem para exibição
  static String formatPercentage(double value, {int decimals = 1}) {
    return '${value.toStringAsFixed(decimals).replaceAll('.', ',')}%';
  }

  /// Formata data para exibição (dd/mm/aaaa)
  static String formatDate(DateTime date) {
    return DateFormat('dd/MM/yyyy').format(date);
  }

  /// Formata data e hora para exibição (dd/mm/aaaa às hh:mm)
  static String formatDateTime(DateTime dateTime) {
    return DateFormat('dd/MM/yyyy \'às\' HH:mm').format(dateTime);
  }

  /// Formata data relativa (hoje, ontem, há X dias)
  static String formatDateRelative(DateTime date) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final yesterday = today.subtract(const Duration(days: 1));
    final dateOnly = DateTime(date.year, date.month, date.day);

    if (dateOnly == today) {
      return 'Hoje';
    } else if (dateOnly == yesterday) {
      return 'Ontem';
    } else {
      final difference = today.difference(dateOnly).inDays;
      if (difference < 7) {
        return 'Há $difference ${difference == 1 ? 'dia' : 'dias'}';
      } else if (difference < 30) {
        final weeks = (difference / 7).floor();
        return 'Há $weeks ${weeks == 1 ? 'semana' : 'semanas'}';
      } else if (difference < 365) {
        final months = (difference / 30).floor();
        return 'Há $months ${months == 1 ? 'mês' : 'meses'}';
      } else {
        return formatDate(date);
      }
    }
  }

  /// Formata horário (hh:mm)
  static String formatTime(DateTime time) {
    return DateFormat('HH:mm').format(time);
  }

  /// Formata número de forma legível (1.234, 1,2K, 1,5M)
  static String formatNumber(num value) {
    if (value < 1000) {
      return value.toStringAsFixed(0);
    } else if (value < 1000000) {
      return '${(value / 1000).toStringAsFixed(1).replaceAll('.', ',')}K';
    } else if (value < 1000000000) {
      return '${(value / 1000000).toStringAsFixed(1).replaceAll('.', ',')}M';
    } else {
      return '${(value / 1000000000).toStringAsFixed(1).replaceAll('.', ',')}B';
    }
  }

  /// Capitaliza primeira letra de cada palavra
  static String toTitleCase(String text) {
    return text.toLowerCase().split(' ').map((word) {
      if (word.isEmpty) return word;
      return word[0].toUpperCase() + word.substring(1);
    }).join(' ');
  }

  /// Trunca texto com reticências
  static String truncateText(String text, int maxLength) {
    if (text.length <= maxLength) return text;
    return '${text.substring(0, maxLength)}...';
  }

  /// Formata código de indicação (XXXX-XXXX)
  static String formatReferralCode(String code) {
    final cleanCode = code.replaceAll(RegExp(r'[^A-Z0-9]'), '');
    if (cleanCode.length >= 8) {
      return '${cleanCode.substring(0, 4)}-${cleanCode.substring(4, 8)}';
    }
    return code;
  }

  /// Remove acentos de uma string
  static String removeAccents(String text) {
    const withAccents = 'ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ';
    const withoutAccents = 'AAAAAAaaaaaaOOOOOOooooooEEEEeeeeeCcIIIIiiiiUUUUuuuuyNn';
    
    String result = text;
    for (int i = 0; i < withAccents.length; i++) {
      result = result.replaceAll(withAccents[i], withoutAccents[i]);
    }
    return result;
  }

  /// Valida se uma string contém apenas números
  static bool isNumeric(String value) {
    return RegExp(r'^[0-9]+$').hasMatch(value);
  }

  /// Formata status com primeira letra maiúscula
  static String formatStatus(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        return 'Pendente';
      case 'available':
        return 'Disponível';
      case 'expired':
        return 'Expirado';
      case 'processed':
        return 'Processado';
      case 'cancelled':
        return 'Cancelado';
      default:
        return toTitleCase(status);
    }
  }
}