// lib/core/theme/text_styles.dart
// Este arquivo contém todos os estilos de texto utilizados no app Klube Cash
// Define tipografia padronizada baseada no design system da marca

import 'package:flutter/material.dart';
import '../constants/app_colors.dart';

class AppTextStyles {
  // ==================== HEADINGS ====================
  
  /// Título principal da aplicação
  static const TextStyle h1 = TextStyle(
    fontSize: 32,
    fontWeight: FontWeight.bold,
    color: AppColors.textPrimary,
    height: 1.2,
    letterSpacing: -0.5,
  );
  
  /// Títulos de seções principais
  static const TextStyle h2 = TextStyle(
    fontSize: 28,
    fontWeight: FontWeight.bold,
    color: AppColors.textPrimary,
    height: 1.3,
    letterSpacing: -0.3,
  );
  
  /// Títulos de subseções
  static const TextStyle h3 = TextStyle(
    fontSize: 24,
    fontWeight: FontWeight.w600,
    color: AppColors.textPrimary,
    height: 1.3,
    letterSpacing: -0.2,
  );
  
  /// Títulos de cards e componentes
  static const TextStyle h4 = TextStyle(
    fontSize: 20,
    fontWeight: FontWeight.w600,
    color: AppColors.textPrimary,
    height: 1.4,
  );
  
  /// Subtítulos menores
  static const TextStyle h5 = TextStyle(
    fontSize: 18,
    fontWeight: FontWeight.w500,
    color: AppColors.textPrimary,
    height: 1.4,
  );
  
  /// Títulos pequenos
  static const TextStyle h6 = TextStyle(
    fontSize: 16,
    fontWeight: FontWeight.w500,
    color: AppColors.textPrimary,
    height: 1.4,
  );

  // ==================== BODY TEXT ====================
  
  /// Texto principal do corpo
  static const TextStyle bodyLarge = TextStyle(
    fontSize: 16,
    fontWeight: FontWeight.w400,
    color: AppColors.textPrimary,
    height: 1.5,
  );
  
  /// Texto médio do corpo
  static const TextStyle bodyMedium = TextStyle(
    fontSize: 14,
    fontWeight: FontWeight.w400,
    color: AppColors.textPrimary,
    height: 1.5,
  );
  
  /// Texto pequeno do corpo
  static const TextStyle bodySmall = TextStyle(
    fontSize: 12,
    fontWeight: FontWeight.w400,
    color: AppColors.textSecondary,
    height: 1.4,
  );

  // ==================== LABELS ====================
  
  /// Labels grandes (formulários, botões principais)
  static const TextStyle labelLarge = TextStyle(
    fontSize: 16,
    fontWeight: FontWeight.w500,
    color: AppColors.textPrimary,
    height: 1.3,
  );
  
  /// Labels médios (formulários padrão)
  static const TextStyle labelMedium = TextStyle(
    fontSize: 14,
    fontWeight: FontWeight.w500,
    color: AppColors.textPrimary,
    height: 1.3,
  );
  
  /// Labels pequenos (hints, descrições)
  static const TextStyle labelSmall = TextStyle(
    fontSize: 12,
    fontWeight: FontWeight.w500,
    color: AppColors.textSecondary,
    height: 1.3,
  );

  // ==================== BOTÕES ====================
  
  /// Texto de botões grandes
  static const TextStyle buttonLarge = TextStyle(
    fontSize: 16,
    fontWeight: FontWeight.w600,
    color: AppColors.textOnPrimary,
    height: 1.2,
    letterSpacing: 0.1,
  );
  
  /// Texto de botões médios
  static const TextStyle buttonMedium = TextStyle(
    fontSize: 14,
    fontWeight: FontWeight.w600,
    color: AppColors.textOnPrimary,
    height: 1.2,
    letterSpacing: 0.1,
  );
  
  /// Texto de botões pequenos
  static const TextStyle buttonSmall = TextStyle(
    fontSize: 12,
    fontWeight: FontWeight.w600,
    color: AppColors.textOnPrimary,
    height: 1.2,
    letterSpacing: 0.1,
  );

  // ==================== ESPECIAIS ====================
  
  /// Valores monetários em destaque
  static const TextStyle cashValue = TextStyle(
    fontSize: 28,
    fontWeight: FontWeight.bold,
    color: AppColors.primary,
    height: 1.2,
    letterSpacing: -0.3,
  );
  
  /// Valores monetários médios
  static const TextStyle cashValueMedium = TextStyle(
    fontSize: 20,
    fontWeight: FontWeight.w600,
    color: AppColors.primary,
    height: 1.2,
  );
  
  /// Valores monetários pequenos
  static const TextStyle cashValueSmall = TextStyle(
    fontSize: 16,
    fontWeight: FontWeight.w500,
    color: AppColors.primary,
    height: 1.2,
  );
  
  /// Porcentagens de cashback
  static const TextStyle cashbackPercent = TextStyle(
    fontSize: 24,
    fontWeight: FontWeight.bold,
    color: AppColors.success,
    height: 1.2,
  );
  
  /// Texto de erro
  static const TextStyle error = TextStyle(
    fontSize: 14,
    fontWeight: FontWeight.w400,
    color: AppColors.error,
    height: 1.4,
  );
  
  /// Texto de sucesso
  static const TextStyle success = TextStyle(
    fontSize: 14,
    fontWeight: FontWeight.w400,
    color: AppColors.success,
    height: 1.4,
  );
  
  /// Texto de aviso
  static const TextStyle warning = TextStyle(
    fontSize: 14,
    fontWeight: FontWeight.w400,
    color: AppColors.warning,
    height: 1.4,
  );
  
  /// Texto de link
  static const TextStyle link = TextStyle(
    fontSize: 14,
    fontWeight: FontWeight.w500,
    color: AppColors.primary,
    height: 1.4,
    decoration: TextDecoration.underline,
  );
  
  /// Texto placeholder/hint
  static const TextStyle hint = TextStyle(
    fontSize: 14,
    fontWeight: FontWeight.w400,
    color: AppColors.textMuted,
    height: 1.4,
  );
  
  /// Caption/legendas pequenas
  static const TextStyle caption = TextStyle(
    fontSize: 12,
    fontWeight: FontWeight.w400,
    color: AppColors.textLight,
    height: 1.3,
  );

  // ==================== VARIAÇÕES TEMÁTICAS ====================
  
  /// Texto sobre fundo primário
  static const TextStyle onPrimary = TextStyle(
    fontSize: 14,
    fontWeight: FontWeight.w400,
    color: AppColors.textOnPrimary,
    height: 1.4,
  );
  
  /// Texto sobre fundo escuro
  static const TextStyle onDark = TextStyle(
    fontSize: 14,
    fontWeight: FontWeight.w400,
    color: AppColors.textOnDark,
    height: 1.4,
  );

  // ==================== MÉTODOS AUXILIARES ====================
  
  /// Aplica cor personalizada mantendo outras propriedades
  static TextStyle withColor(TextStyle style, Color color) {
    return style.copyWith(color: color);
  }
  
  /// Aplica tamanho personalizado mantendo outras propriedades
  static TextStyle withSize(TextStyle style, double fontSize) {
    return style.copyWith(fontSize: fontSize);
  }
  
  /// Aplica peso personalizado mantendo outras propriedades
  static TextStyle withWeight(TextStyle style, FontWeight fontWeight) {
    return style.copyWith(fontWeight: fontWeight);
  }
}