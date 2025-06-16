// custom_app_bar.dart - AppBar customizado reutilizável para o app Klube Cash  
// Arquivo: lib/core/widgets/custom_app_bar.dart

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../constants/app_colors.dart';
import '../constants/app_dimensions.dart';

/// AppBar customizado para uso consistente na aplicação
/// 
/// Oferece diferentes tipos, gradientes, ações personalizadas e integração
/// com o tema do Klube Cash.
class CustomAppBar extends StatelessWidget implements PreferredSizeWidget {
  /// Título da AppBar
  final String? title;
  
  /// Widget personalizado do título
  final Widget? titleWidget;
  
  /// Subtitle da AppBar
  final String? subtitle;
  
  /// Se deve mostrar o botão de voltar
  final bool showBackButton;
  
  /// Callback customizado para o botão voltar
  final VoidCallback? onBackPressed;
  
  /// Lista de ações da AppBar
  final List<Widget>? actions;
  
  /// Tipo da AppBar
  final AppBarType type;
  
  /// Cor de fundo customizada
  final Color? backgroundColor;
  
  /// Cor do texto customizada
  final Color? foregroundColor;
  
  /// Se deve mostrar sombra
  final bool showShadow;
  
  /// Altura customizada
  final double? height;
  
  /// Se deve ser transparente
  final bool isTransparent;
  
  /// Widget leading customizado
  final Widget? leading;
  
  /// Se a AppBar está centralizada
  final bool centerTitle;

  const CustomAppBar({
    super.key,
    this.title,
    this.titleWidget,
    this.subtitle,
    this.showBackButton = true,
    this.onBackPressed,
    this.actions,
    this.type = AppBarType.primary,
    this.backgroundColor,
    this.foregroundColor,
    this.showShadow = true,
    this.height,
    this.isTransparent = false,
    this.leading,
    this.centerTitle = true,
  });

  /// AppBar primária (padrão do app)
  const CustomAppBar.primary({
    super.key,
    required this.title,
    this.showBackButton = true,
    this.actions,
    this.onBackPressed,
    this.centerTitle = true,
  })  : titleWidget = null,
        subtitle = null,
        type = AppBarType.primary,
        backgroundColor = null,
        foregroundColor = null,
        showShadow = true,
        height = null,
        isTransparent = false,
        leading = null;

  /// AppBar transparente
  const CustomAppBar.transparent({
    super.key,
    this.title,
    this.titleWidget,
    this.showBackButton = true,
    this.actions,
    this.onBackPressed,
    this.leading,
    this.centerTitle = true,
  })  : subtitle = null,
        type = AppBarType.transparent,
        backgroundColor = null,
        foregroundColor = null,
        showShadow = false,
        height = null,
        isTransparent = true;

  /// AppBar com gradiente
  const CustomAppBar.gradient({
    super.key,
    required this.title,
    this.subtitle,
    this.showBackButton = true,
    this.actions,
    this.onBackPressed,
    this.centerTitle = true,
  })  : titleWidget = null,
        type = AppBarType.gradient,
        backgroundColor = null,
        foregroundColor = null,
        showShadow = true,
        height = null,
        isTransparent = false,
        leading = null;

  /// AppBar secundária
  const CustomAppBar.secondary({
    super.key,
    required this.title,
    this.showBackButton = true,
    this.actions,
    this.onBackPressed,
    this.centerTitle = true,
  })  : titleWidget = null,
        subtitle = null,
        type = AppBarType.secondary,
        backgroundColor = null,
        foregroundColor = null,
        showShadow = true,
        height = null,
        isTransparent = false,
        leading = null;

  @override
  Widget build(BuildContext context) {
    final appBarConfig = _getAppBarConfig();
    
    return Container(
      decoration: _buildDecoration(),
      child: AppBar(
        title: _buildTitle(context),
        centerTitle: centerTitle,
        backgroundColor: Colors.transparent,
        foregroundColor: appBarConfig.foregroundColor,
        elevation: 0,
        automaticallyImplyLeading: false,
        leading: _buildLeading(context),
        actions: _buildActions(context),
        systemOverlayStyle: _getSystemOverlayStyle(),
        toolbarHeight: height ?? kToolbarHeight,
        flexibleSpace: type == AppBarType.gradient ? _buildGradientBackground() : null,
      ),
    );
  }

  Widget? _buildTitle(BuildContext context) {
    if (titleWidget != null) return titleWidget;
    if (title == null) return null;
    
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          title!,
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
            color: _getAppBarConfig().foregroundColor,
            fontWeight: FontWeight.w600,
          ),
        ),
        if (subtitle != null) ...[
          const SizedBox(height: 2),
          Text(
            subtitle!,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: _getAppBarConfig().foregroundColor.withOpacity(0.8),
            ),
          ),
        ],
      ],
    );
  }

  Widget? _buildLeading(BuildContext context) {
    if (leading != null) return leading;
    
    if (showBackButton && Navigator.of(context).canPop()) {
      return IconButton(
        icon: Icon(
          Icons.arrow_back_ios,
          color: _getAppBarConfig().foregroundColor,
        ),
        onPressed: onBackPressed ?? () => Navigator.of(context).pop(),
      );
    }
    
    return null;
  }

  List<Widget>? _buildActions(BuildContext context) {
    if (actions == null) return null;
    
    return actions!.map((action) {
      if (action is IconButton) {
        return IconButton(
          icon: action.icon,
          onPressed: action.onPressed,
          color: _getAppBarConfig().foregroundColor,
        );
      }
      return action;
    }).toList();
  }

  Decoration? _buildDecoration() {
    if (isTransparent) return null;
    
    final config = _getAppBarConfig();
    
    return BoxDecoration(
      color: config.backgroundColor,
      boxShadow: showShadow ? AppColors.shadowMd : null,
      gradient: type == AppBarType.gradient ? _buildGradient() : null,
    );
  }

  Widget? _buildGradientBackground() {
    if (type != AppBarType.gradient) return null;
    
    return Container(
      decoration: BoxDecoration(
        gradient: _buildGradient(),
      ),
    );
  }

  LinearGradient _buildGradient() {
    return const LinearGradient(
      begin: Alignment.topLeft,
      end: Alignment.bottomRight,
      colors: [
        AppColors.primary,
        AppColors.primaryDark,
      ],
    );
  }

  _AppBarConfig _getAppBarConfig() {
    switch (type) {
      case AppBarType.primary:
        return _AppBarConfig(
          backgroundColor: backgroundColor ?? AppColors.primary,
          foregroundColor: foregroundColor ?? AppColors.textOnPrimary,
        );
      
      case AppBarType.secondary:
        return _AppBarConfig(
          backgroundColor: backgroundColor ?? AppColors.background,
          foregroundColor: foregroundColor ?? AppColors.textPrimary,
        );
      
      case AppBarType.transparent:
        return _AppBarConfig(
          backgroundColor: backgroundColor ?? Colors.transparent,
          foregroundColor: foregroundColor ?? AppColors.textPrimary,
        );
      
      case AppBarType.gradient:
        return _AppBarConfig(
          backgroundColor: Colors.transparent,
          foregroundColor: foregroundColor ?? AppColors.textOnPrimary,
        );
    }
  }

  SystemUiOverlayStyle _getSystemOverlayStyle() {
    final isDark = type == AppBarType.primary || type == AppBarType.gradient;
    
    return SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: isDark ? Brightness.light : Brightness.dark,
      statusBarBrightness: isDark ? Brightness.dark : Brightness.light,
    );
  }

  @override
  Size get preferredSize => Size.fromHeight(height ?? kToolbarHeight);
}

/// Tipos de AppBar disponíveis
enum AppBarType {
  /// AppBar primária (cor principal)
  primary,
  
  /// AppBar secundária (fundo claro)
  secondary,
  
  /// AppBar transparente
  transparent,
  
  /// AppBar com gradiente
  gradient,
}

/// Configuração da AppBar
class _AppBarConfig {
  final Color backgroundColor;
  final Color foregroundColor;

  _AppBarConfig({
    required this.backgroundColor,
    required this.foregroundColor,
  });
}

/// SliverAppBar customizada para uso em CustomScrollView
class CustomSliverAppBar extends StatelessWidget {
  final String? title;
  final Widget? titleWidget;
  final List<Widget>? actions;
  final bool pinned;
  final bool floating;
  final bool snap;
  final double? expandedHeight;
  final Widget? flexibleSpace;
  final AppBarType type;
  final Color? backgroundColor;
  final Color? foregroundColor;

  const CustomSliverAppBar({
    super.key,
    this.title,
    this.titleWidget,
    this.actions,
    this.pinned = true,
    this.floating = false,
    this.snap = false,
    this.expandedHeight,
    this.flexibleSpace,
    this.type = AppBarType.primary,
    this.backgroundColor,
    this.foregroundColor,
  });

  @override
  Widget build(BuildContext context) {
    final config = _getAppBarConfig();
    
    return SliverAppBar(
      title: titleWidget ?? (title != null ? Text(title!) : null),
      centerTitle: true,
      pinned: pinned,
      floating: floating,
      snap: snap,
      expandedHeight: expandedHeight,
      backgroundColor: config.backgroundColor,
      foregroundColor: config.foregroundColor,
      elevation: 0,
      actions: actions,
      flexibleSpace: flexibleSpace ?? _buildFlexibleSpace(),
    );
  }

  Widget? _buildFlexibleSpace() {
    if (type != AppBarType.gradient) return null;
    
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppColors.primary, AppColors.primaryDark],
        ),
      ),
    );
  }

  _AppBarConfig _getAppBarConfig() {
    switch (type) {
      case AppBarType.primary:
        return _AppBarConfig(
          backgroundColor: backgroundColor ?? AppColors.primary,
          foregroundColor: foregroundColor ?? AppColors.textOnPrimary,
        );
      case AppBarType.secondary:
        return _AppBarConfig(
          backgroundColor: backgroundColor ?? AppColors.background,
          foregroundColor: foregroundColor ?? AppColors.textPrimary,
        );
      case AppBarType.transparent:
        return _AppBarConfig(
          backgroundColor: backgroundColor ?? Colors.transparent,
          foregroundColor: foregroundColor ?? AppColors.textPrimary,
        );
      case AppBarType.gradient:
        return _AppBarConfig(
          backgroundColor: Colors.transparent,
          foregroundColor: foregroundColor ?? AppColors.textOnPrimary,
        );
    }
  }
}