// lib/core/constants/app_colors.dart
// Este arquivo contém todas as cores utilizadas no app Klube Cash
// Baseado no design system e paleta de cores oficial da marca

import 'package:flutter/material.dart';

class AppColors {
  // ==================== CORES PRIMÁRIAS ====================
  
  /// Cor primária principal - Laranja Klube Cash
  static const Color primary = Color(0xFFFF7A00);
  
  /// Variações da cor primária
  static const Color primaryDark = Color(0xFFE06600);
  static const Color primaryLight = Color(0xFFFFB366);
  static const Color primaryUltraLight = Color(0xFFFFF4E6);
  
  /// Gradientes primários
  static const LinearGradient primaryGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [primary, primaryLight],
  );
  
  static const LinearGradient heroGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFFFF7A00), Color(0xFFFF9A40), Color(0xFFFFB366)],
  );

  // ==================== CORES SECUNDÁRIAS ====================
  
  static const Color secondary = Color(0xFF1A1A1A);
  static const Color secondaryLight = Color(0xFF333333);
  static const Color secondaryDark = Color(0xFF000000);

  // ==================== CORES NEUTRAS ====================
  
  static const Color white = Color(0xFFFFFFFF);
  static const Color black = Color(0xFF000000);
  
  // Escala de cinzas
  static const Color gray50 = Color(0xFFF9FAFB);
  static const Color gray100 = Color(0xFFF3F4F6);
  static const Color gray200 = Color(0xFFE5E7EB);
  static const Color gray300 = Color(0xFFD1D5DB);
  static const Color gray400 = Color(0xFF9CA3AF);
  static const Color gray500 = Color(0xFF6B7280);
  static const Color gray600 = Color(0xFF4B5563);
  static const Color gray700 = Color(0xFF374151);
  static const Color gray800 = Color(0xFF1F2937);
  static const Color gray900 = Color(0xFF111827);

  // ==================== CORES DE STATUS ====================
  
  /// Cor de sucesso
  static const Color success = Color(0xFF10B981);
  static const Color successLight = Color(0xFFD1FAE5);
  static const Color successDark = Color(0xFF047857);
  
  /// Cor de aviso
  static const Color warning = Color(0xFFF59E0B);
  static const Color warningLight = Color(0xFFFEF3C7);
  static const Color warningDark = Color(0xFFD97706);
  
  /// Cor de erro
  static const Color error = Color(0xFFEF4444);
  static const Color errorLight = Color(0xFFFEE2E2);
  static const Color errorDark = Color(0xFFDC2626);
  
  /// Cor de informação
  static const Color info = Color(0xFF3B82F6);
  static const Color infoLight = Color(0xFFDBEAFE);
  static const Color infoDark = Color(0xFF2563EB);

  // ==================== CORES DE TEXTO ====================
  
  static const Color textPrimary = Color(0xFF1F2937);
  static const Color textSecondary = Color(0xFF4B5563);
  static const Color textMuted = Color(0xFF6B7280);
  static const Color textLight = Color(0xFF9CA3AF);
  static const Color textOnPrimary = Color(0xFFFFFFFF);
  static const Color textOnDark = Color(0xFFFFFFFF);

  // ==================== CORES DE FUNDO ====================
  
  static const Color background = Color(0xFFFFFFFF);
  static const Color backgroundSecondary = Color(0xFFF9FAFB);
  static const Color backgroundTertiary = Color(0xFFF3F4F6);
  static const Color backgroundOverlay = Color(0x80000000);
  
  /// Gradiente de fundo
  static const LinearGradient backgroundGradient = LinearGradient(
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
    colors: [backgroundSecondary, backgroundTertiary],
  );

  // ==================== CORES DE BORDA ====================
  
  static const Color borderLight = Color(0xFFE5E7EB);
  static const Color borderMedium = Color(0xFFD1D5DB);
  static const Color borderDark = Color(0xFF9CA3AF);
  static const Color borderFocus = primary;

  // ==================== CORES DE SUPERFÍCIE ====================
  
  static const Color surface = Color(0xFFFFFFFF);
  static const Color surfaceVariant = Color(0xFFF3F4F6);
  static const Color surfaceContainer = Color(0xFFF9FAFB);

  // ==================== CORES ESPECÍFICAS DO CASHBACK ====================
  
  /// Verde para valores de cashback ganho
  static const Color cashbackEarned = success;
  static const Color cashbackEarnedLight = successLight;
  
  /// Azul para cashback pendente
  static const Color cashbackPending = info;
  static const Color cashbackPendingLight = infoLight;
  
  /// Laranja para cashback em processamento
  static const Color cashbackProcessing = warning;
  static const Color cashbackProcessingLight = warningLight;

  // ==================== CORES DE TRANSAÇÃO ====================
  
  static const Color transactionApproved = success;
  static const Color transactionPending = warning;
  static const Color transactionCanceled = error;
  static const Color transactionRefunded = info;

  // ==================== SOMBRAS ====================
  
  static const List<BoxShadow> shadowSm = [
    BoxShadow(
      color: Color(0x0D000000),
      offset: Offset(0, 1),
      blurRadius: 2,
    ),
  ];
  
  static const List<BoxShadow> shadowMd = [
    BoxShadow(
      color: Color(0x1A000000),
      offset: Offset(0, 4),
      blurRadius: 6,
      spreadRadius: -1,
    ),
  ];
  
  static const List<BoxShadow> shadowLg = [
    BoxShadow(
      color: Color(0x1A000000),
      offset: Offset(0, 10),
      blurRadius: 15,
      spreadRadius: -3,
    ),
  ];
  
  static const List<BoxShadow> shadowXl = [
    BoxShadow(
      color: Color(0x1A000000),
      offset: Offset(0, 20),
      blurRadius: 25,
      spreadRadius: -5,
    ),
  ];

  // ==================== MÉTODOS AUXILIARES ====================
  
  /// Retorna a cor com opacidade especificada
  static Color withOpacity(Color color, double opacity) {
    return color.withOpacity(opacity);
  }
  
  /// Retorna uma cor mais clara
  static Color lighten(Color color, [double amount = 0.1]) {
    assert(amount >= 0 && amount <= 1);
    final hsl = HSLColor.fromColor(color);
    final hslLight = hsl.withLightness((hsl.lightness + amount).clamp(0.0, 1.0));
    return hslLight.toColor();
  }
  
  /// Retorna uma cor mais escura
  static Color darken(Color color, [double amount = 0.1]) {
    assert(amount >= 0 && amount <= 1);
    final hsl = HSLColor.fromColor(color);
    final hslDark = hsl.withLightness((hsl.lightness - amount).clamp(0.0, 1.0));
    return hslDark.toColor();
  }
  
  /// Cores baseadas no tipo de transação
  static Color getTransactionColor(String status) {
    switch (status.toLowerCase()) {
      case 'aprovado':
      case 'approved':
        return transactionApproved;
      case 'pendente':
      case 'pending':
        return transactionPending;
      case 'cancelado':
      case 'canceled':
        return transactionCanceled;
      case 'estornado':
      case 'refunded':
        return transactionRefunded;
      default:
        return gray500;
    }
  }
  
  /// Cores baseadas no tipo de usuário
  static Color getUserTypeColor(String userType) {
    switch (userType.toLowerCase()) {
      case 'cliente':
      case 'client':
        return primary;
      case 'loja':
      case 'store':
        return info;
      case 'admin':
        return success;
      default:
        return gray500;
    }
  }
}