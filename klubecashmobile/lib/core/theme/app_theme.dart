// lib/core/theme/app_theme.dart
// Este arquivo contém a configuração completa do tema do app Klube Cash
// Define temas claro e escuro baseados no design system da marca

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import 'text_styles.dart';

class AppTheme {
  // ==================== COLOR SCHEMES ====================
  
  /// ColorScheme para tema claro
  static const ColorScheme lightColorScheme = ColorScheme(
    brightness: Brightness.light,
    primary: AppColors.primary,
    onPrimary: AppColors.textOnPrimary,
    primaryContainer: AppColors.primaryLight,
    onPrimaryContainer: AppColors.textPrimary,
    secondary: AppColors.secondary,
    onSecondary: AppColors.textOnDark,
    secondaryContainer: AppColors.secondaryLight,
    onSecondaryContainer: AppColors.textOnDark,
    tertiary: AppColors.info,
    onTertiary: AppColors.textOnPrimary,
    tertiaryContainer: AppColors.infoLight,
    onTertiaryContainer: AppColors.infoDark,
    error: AppColors.error,
    onError: AppColors.textOnPrimary,
    errorContainer: AppColors.errorLight,
    onErrorContainer: AppColors.errorDark,
    background: AppColors.background,
    onBackground: AppColors.textPrimary,
    surface: AppColors.background,
    onSurface: AppColors.textPrimary,
    surfaceVariant: AppColors.gray100,
    onSurfaceVariant: AppColors.textSecondary,
    outline: AppColors.gray300,
    outlineVariant: AppColors.gray200,
    shadow: AppColors.black,
    scrim: AppColors.backgroundOverlay,
    inverseSurface: AppColors.gray800,
    onInverseSurface: AppColors.textOnDark,
    inversePrimary: AppColors.primaryLight,
    surfaceTint: AppColors.primary,
  );
  
  /// ColorScheme para tema escuro
  static const ColorScheme darkColorScheme = ColorScheme(
    brightness: Brightness.dark,
    primary: AppColors.primaryLight,
    onPrimary: AppColors.textPrimary,
    primaryContainer: AppColors.primaryDark,
    onPrimaryContainer: AppColors.textOnPrimary,
    secondary: AppColors.gray200,
    onSecondary: AppColors.textPrimary,
    secondaryContainer: AppColors.gray700,
    onSecondaryContainer: AppColors.textOnDark,
    tertiary: AppColors.info,
    onTertiary: AppColors.textOnPrimary,
    tertiaryContainer: AppColors.infoDark,
    onTertiaryContainer: AppColors.infoLight,
    error: AppColors.error,
    onError: AppColors.textOnPrimary,
    errorContainer: AppColors.errorDark,
    onErrorContainer: AppColors.errorLight,
    background: AppColors.gray900,
    onBackground: AppColors.textOnDark,
    surface: AppColors.gray800,
    onSurface: AppColors.textOnDark,
    surfaceVariant: AppColors.gray700,
    onSurfaceVariant: AppColors.gray300,
    outline: AppColors.gray600,
    outlineVariant: AppColors.gray700,
    shadow: AppColors.black,
    scrim: AppColors.backgroundOverlay,
    inverseSurface: AppColors.gray100,
    onInverseSurface: AppColors.textPrimary,
    inversePrimary: AppColors.primary,
    surfaceTint: AppColors.primaryLight,
  );

  // ==================== LIGHT THEME ====================
  
  static ThemeData get lightTheme {
    return ThemeData(
      useMaterial3: true,
      colorScheme: lightColorScheme,
      fontFamily: 'Roboto',
      
      // Configuração do AppBar
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.background,
        foregroundColor: AppColors.textPrimary,
        elevation: 0,
        scrolledUnderElevation: 1,
        shadowColor: AppColors.gray200,
        surfaceTintColor: AppColors.background,
        centerTitle: true,
        titleTextStyle: AppTextStyles.h4,
        systemOverlayStyle: SystemUiOverlayStyle(
          statusBarColor: Colors.transparent,
          statusBarIconBrightness: Brightness.dark,
          statusBarBrightness: Brightness.light,
        ),
      ),
      
      // Configuração dos Botões Elevados
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: AppColors.textOnPrimary,
          disabledBackgroundColor: AppColors.gray300,
          disabledForegroundColor: AppColors.gray500,
          textStyle: AppTextStyles.buttonMedium,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          elevation: 2,
          shadowColor: AppColors.primary.withOpacity(0.3),
        ),
      ),
      
      // Configuração dos Botões de Texto
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.primary,
          textStyle: AppTextStyles.buttonMedium,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(8),
          ),
        ),
      ),
      
      // Configuração dos Botões com Borda
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.primary,
          side: const BorderSide(color: AppColors.primary, width: 1.5),
          textStyle: AppTextStyles.buttonMedium,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
      
      // Configuração dos Campos de Texto
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.backgroundSecondary,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.gray300),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.gray300),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.error),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.error, width: 2),
        ),
        labelStyle: AppTextStyles.labelMedium,
        hintStyle: AppTextStyles.hint,
        errorStyle: AppTextStyles.error,
        helperStyle: AppTextStyles.caption,
      ),
      
      // Configuração dos Cards
      cardTheme: CardTheme(
        color: AppColors.background,
        shadowColor: AppColors.gray200,
        elevation: 2,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      ),
      
      // Configuração dos Dialogs
      dialogTheme: DialogTheme(
        backgroundColor: AppColors.background,
        shadowColor: AppColors.gray200,
        elevation: 8,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
        ),
        titleTextStyle: AppTextStyles.h4,
        contentTextStyle: AppTextStyles.bodyMedium,
      ),
      
      // Configuração da Bottom Sheet
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: AppColors.background,
        elevation: 8,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
        ),
      ),
      
      // Configuração dos Chips
      chipTheme: ChipThemeData(
        backgroundColor: AppColors.gray100,
        selectedColor: AppColors.primaryUltraLight,
        deleteIconColor: AppColors.gray500,
        disabledColor: AppColors.gray200,
        labelStyle: AppTextStyles.labelSmall,
        secondaryLabelStyle: AppTextStyles.labelSmall,
        brightness: Brightness.light,
        elevation: 0,
        pressElevation: 2,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
        ),
      ),
      
      // Configuração do FloatingActionButton
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: AppColors.primary,
        foregroundColor: AppColors.textOnPrimary,
        elevation: 4,
        shape: CircleBorder(),
      ),
      
      // Configuração da Navigation Bar
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: AppColors.background,
        indicatorColor: AppColors.primaryUltraLight,
        labelTextStyle: MaterialStateProperty.all(AppTextStyles.labelSmall),
        iconTheme: MaterialStateProperty.resolveWith((states) {
          if (states.contains(MaterialState.selected)) {
            return const IconThemeData(color: AppColors.primary);
          }
          return const IconThemeData(color: AppColors.gray500);
        }),
      ),
      
      // Configuração do Drawer
      drawerTheme: const DrawerThemeData(
        backgroundColor: AppColors.background,
        elevation: 8,
        shadowColor: AppColors.gray200,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.horizontal(right: Radius.circular(16)),
        ),
      ),
      
      // Configuração do ListTile
      listTileTheme: const ListTileThemeData(
        contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.all(Radius.circular(12)),
        ),
        titleTextStyle: AppTextStyles.bodyMedium,
        subtitleTextStyle: AppTextStyles.bodySmall,
        leadingAndTrailingTextStyle: AppTextStyles.labelMedium,
      ),
      
      // Configuração do Scaffold
      scaffoldBackgroundColor: AppColors.backgroundSecondary,
      
      // Configuração do Divider
      dividerTheme: const DividerThemeData(
        color: AppColors.gray200,
        thickness: 1,
        space: 1,
      ),
      
      // Configuração do Switch
      switchTheme: SwitchThemeData(
        thumbColor: MaterialStateProperty.resolveWith((states) {
          if (states.contains(MaterialState.selected)) {
            return AppColors.primary;
          }
          return AppColors.gray400;
        }),
        trackColor: MaterialStateProperty.resolveWith((states) {
          if (states.contains(MaterialState.selected)) {
            return AppColors.primaryLight;
          }
          return AppColors.gray200;
        }),
      ),
      
      // Configuração do Checkbox
      checkboxTheme: CheckboxThemeData(
        fillColor: MaterialStateProperty.resolveWith((states) {
          if (states.contains(MaterialState.selected)) {
            return AppColors.primary;
          }
          return AppColors.gray300;
        }),
        checkColor: MaterialStateProperty.all(AppColors.textOnPrimary),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(4),
        ),
      ),
      
      // Configuração do RadioButton
      radioTheme: RadioThemeData(
        fillColor: MaterialStateProperty.resolveWith((states) {
          if (states.contains(MaterialState.selected)) {
            return AppColors.primary;
          }
          return AppColors.gray400;
        }),
      ),
      
      // Configuração do Slider
      sliderTheme: const SliderThemeData(
        activeTrackColor: AppColors.primary,
        inactiveTrackColor: AppColors.gray300,
        thumbColor: AppColors.primary,
        overlayColor: AppColors.primaryUltraLight,
      ),
      
      // Configuração do ProgressIndicator
      progressIndicatorTheme: const ProgressIndicatorThemeData(
        color: AppColors.primary,
        linearTrackColor: AppColors.gray200,
        circularTrackColor: AppColors.gray200,
      ),
      
      // Configuração do Snackbar
      snackBarTheme: const SnackBarThemeData(
        backgroundColor: AppColors.gray800,
        contentTextStyle: AppTextStyles.onDark,
        actionTextColor: AppColors.primaryLight,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.all(Radius.circular(12)),
        ),
      ),
      
      // Configuração do TabBar
      tabBarTheme: const TabBarTheme(
        labelColor: AppColors.primary,
        unselectedLabelColor: AppColors.gray500,
        indicatorColor: AppColors.primary,
        indicatorSize: TabBarIndicatorSize.label,
        labelStyle: AppTextStyles.labelMedium,
        unselectedLabelStyle: AppTextStyles.labelMedium,
      ),
    );
  }

  // ==================== DARK THEME ====================
  
  static ThemeData get darkTheme {
    return ThemeData(
      useMaterial3: true,
      colorScheme: darkColorScheme,
      fontFamily: 'Roboto',
      
      // Configuração do AppBar
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.gray800,
        foregroundColor: AppColors.textOnDark,
        elevation: 0,
        scrolledUnderElevation: 1,
        shadowColor: AppColors.black,
        surfaceTintColor: AppColors.gray800,
        centerTitle: true,
        titleTextStyle: TextStyle(
          fontSize: 20,
          fontWeight: FontWeight.w600,
          color: AppColors.textOnDark,
        ),
        systemOverlayStyle: SystemUiOverlayStyle(
          statusBarColor: Colors.transparent,
          statusBarIconBrightness: Brightness.light,
          statusBarBrightness: Brightness.dark,
        ),
      ),
      
      // Configuração dos Botões Elevados
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primaryLight,
          foregroundColor: AppColors.textPrimary,
          disabledBackgroundColor: AppColors.gray600,
          disabledForegroundColor: AppColors.gray400,
          textStyle: AppTextStyles.buttonMedium,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          elevation: 2,
          shadowColor: AppColors.primaryLight.withOpacity(0.3),
        ),
      ),
      
      // Configuração dos Campos de Texto
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.gray700,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.gray600),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.gray600),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.primaryLight, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.error),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.error, width: 2),
        ),
        labelStyle: const TextStyle(color: AppColors.gray300),
        hintStyle: const TextStyle(color: AppColors.gray400),
        errorStyle: AppTextStyles.error,
        helperStyle: const TextStyle(color: AppColors.gray400),
      ),
      
      // Configuração dos Cards
      cardTheme: CardTheme(
        color: AppColors.gray800,
        shadowColor: AppColors.black,
        elevation: 2,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      ),
      
      // Configuração do Scaffold
      scaffoldBackgroundColor: AppColors.gray900,
      
      // Configuração do Divider
      dividerTheme: const DividerThemeData(
        color: AppColors.gray700,
        thickness: 1,
        space: 1,
      ),
    );
  }

  // ==================== MÉTODOS AUXILIARES ====================
  
  /// Retorna o tema baseado no modo escuro/claro
  static ThemeData getTheme(bool isDarkMode) {
    return isDarkMode ? darkTheme : lightTheme;
  }
  
  /// Retorna o ColorScheme baseado no modo escuro/claro
  static ColorScheme getColorScheme(bool isDarkMode) {
    return isDarkMode ? darkColorScheme : lightColorScheme;
  }
}