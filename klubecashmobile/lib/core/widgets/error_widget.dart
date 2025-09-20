// error_widget.dart - Widget de exibição de erros reutilizável para o app Klube Cash
// Arquivo: lib/core/widgets/error_widget.dart

import 'package:flutter/material.dart';
import '../constants/app_colors.dart';
import '../constants/app_dimensions.dart';
import '../errors/failures.dart';

/// Widget para exibição de erros de forma consistente na aplicação
/// 
/// Suporta diferentes tipos de exibição, ações de retry e customização
/// visual baseada no tipo de erro e contexto de uso.
class CustomErrorWidget extends StatelessWidget {
  /// Mensagem de erro a ser exibida
  final String message;
  
  /// Título do erro (opcional)
  final String? title;
  
  /// Ícone a ser exibido
  final IconData? icon;
  
  /// Cor do ícone e elementos principais
  final Color? color;
  
  /// Callback para ação de retry
  final VoidCallback? onRetry;
  
  /// Texto do botão de retry
  final String? retryText;
  
  /// Tipo de exibição do widget
  final ErrorWidgetType type;
  
  /// Se deve mostrar detalhes técnicos
  final bool showDetails;
  
  /// Detalhes técnicos do erro
  final String? details;
  
  /// Falha que originou o erro
  final Failure? failure;

  const CustomErrorWidget({
    super.key,
    required this.message,
    this.title,
    this.icon,
    this.color,
    this.onRetry,
    this.retryText,
    this.type = ErrorWidgetType.card,
    this.showDetails = false,
    this.details,
    this.failure,
  });

  /// Construtor para erro inline (pequeno)
  const CustomErrorWidget.inline({
    super.key,
    required this.message,
    this.onRetry,
    this.retryText = 'Tentar novamente',
    this.color,
    this.failure,
  })  : title = null,
        icon = Icons.error_outline,
        type = ErrorWidgetType.inline,
        showDetails = false,
        details = null;

  /// Construtor para erro em card
  const CustomErrorWidget.card({
    super.key,
    required this.message,
    this.title = 'Ops! Algo deu errado',
    this.onRetry,
    this.retryText = 'Tentar novamente',
    this.color,
    this.showDetails = false,
    this.details,
    this.failure,
  })  : icon = Icons.error_outline,
        type = ErrorWidgetType.card;

  /// Construtor para erro de tela cheia
  const CustomErrorWidget.fullscreen({
    super.key,
    required this.message,
    this.title = 'Ops! Algo deu errado',
    this.onRetry,
    this.retryText = 'Tentar novamente',
    this.color,
    this.showDetails = false,
    this.details,
    this.failure,
  })  : icon = Icons.error_outline,
        type = ErrorWidgetType.fullscreen;

  /// Construtor para erro de rede
  const CustomErrorWidget.network({
    super.key,
    this.message = 'Verifique sua conexão com a internet e tente novamente.',
    this.title = 'Sem conexão',
    this.onRetry,
    this.retryText = 'Tentar novamente',
    this.showDetails = false,
    this.details,
    this.failure,
  })  : icon = Icons.wifi_off,
        color = AppColors.warning,
        type = ErrorWidgetType.card;

  /// Construtor para erro de servidor
  const CustomErrorWidget.server({
    super.key,
    this.message = 'Nossos servidores estão temporariamente indisponíveis. Tente novamente em alguns instantes.',
    this.title = 'Serviço indisponível',
    this.onRetry,
    this.retryText = 'Tentar novamente',
    this.showDetails = false,
    this.details,
    this.failure,
  })  : icon = Icons.cloud_off,
        color = AppColors.error,
        type = ErrorWidgetType.card;

  /// Construtor para erro de autenticação
  const CustomErrorWidget.auth({
    super.key,
    this.message = 'Sua sessão expirou. Faça login novamente para continuar.',
    this.title = 'Sessão expirada',
    this.onRetry,
    this.retryText = 'Fazer login',
    this.showDetails = false,
    this.details,
    this.failure,
  })  : icon = Icons.lock_outline,
        color = AppColors.warning,
        type = ErrorWidgetType.card;

  /// Construtor para erro de validação
  const CustomErrorWidget.validation({
    super.key,
    required this.message,
    this.title = 'Dados inválidos',
    this.onRetry,
    this.retryText = 'Corrigir',
    this.failure,
  })  : icon = Icons.warning_amber_outlined,
        color = AppColors.warning,
        type = ErrorWidgetType.inline,
        showDetails = false,
        details = null;

  /// Construtor baseado em Failure
  factory CustomErrorWidget.fromFailure({
    Key? key,
    required Failure failure,
    VoidCallback? onRetry,
    String? retryText,
    ErrorWidgetType type = ErrorWidgetType.card,
    bool showDetails = false,
  }) {
    switch (failure.runtimeType) {
      case NetworkFailure:
        return CustomErrorWidget.network(
          key: key,
          onRetry: onRetry,
          retryText: retryText,
          failure: failure,
        );
      case ServerFailure:
        return CustomErrorWidget.server(
          key: key,
          onRetry: onRetry,
          retryText: retryText,
          failure: failure,
        );
      case AuthFailure:
        return CustomErrorWidget.auth(
          key: key,
          onRetry: onRetry,
          retryText: retryText,
          failure: failure,
        );
      case ValidationFailure:
        return CustomErrorWidget.validation(
          key: key,
          message: failure.message,
          onRetry: onRetry,
          retryText: retryText,
          failure: failure,
        );
      default:
        return CustomErrorWidget(
          key: key,
          message: failure.message,
          onRetry: onRetry,
          retryText: retryText ?? 'Tentar novamente',
          type: type,
          showDetails: showDetails,
          failure: failure,
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    switch (type) {
      case ErrorWidgetType.inline:
        return _buildInlineError(context);
      case ErrorWidgetType.card:
        return _buildCardError(context);
      case ErrorWidgetType.fullscreen:
        return _buildFullscreenError(context);
    }
  }

  Widget _buildInlineError(BuildContext context) {
    final errorColor = color ?? AppColors.error;
    
    return Container(
      padding: const EdgeInsets.all(AppDimensions.paddingSmall),
      decoration: BoxDecoration(
        color: errorColor.withOpacity(0.1),
        borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
        border: Border.all(
          color: errorColor.withOpacity(0.3),
          width: 1,
        ),
      ),
      child: Row(
        children: [
          Icon(
            icon ?? Icons.error_outline,
            color: errorColor,
            size: 20,
          ),
          const SizedBox(width: AppDimensions.marginSmall),
          Expanded(
            child: Text(
              message,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: errorColor,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          if (onRetry != null) ...[
            const SizedBox(width: AppDimensions.marginSmall),
            InkWell(
              onTap: onRetry,
              child: Text(
                retryText ?? 'Tentar novamente',
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: errorColor,
                  fontWeight: FontWeight.w600,
                  decoration: TextDecoration.underline,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildCardError(BuildContext context) {
    final errorColor = color ?? AppColors.error;
    
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppDimensions.paddingLarge),
      decoration: BoxDecoration(
        color: AppColors.background,
        borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
        border: Border.all(
          color: errorColor.withOpacity(0.2),
          width: 1,
        ),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            padding: const EdgeInsets.all(AppDimensions.paddingMedium),
            decoration: BoxDecoration(
              color: errorColor.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(
              icon ?? Icons.error_outline,
              color: errorColor,
              size: 48,
            ),
          ),
          const SizedBox(height: AppDimensions.marginMedium),
          if (title != null) ...[
            Text(
              title!,
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                color: AppColors.textPrimary,
                fontWeight: FontWeight.w600,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: AppDimensions.marginSmall),
          ],
          Text(
            message,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: AppColors.textSecondary,
            ),
            textAlign: TextAlign.center,
          ),
          if (showDetails && details != null) ...[
            const SizedBox(height: AppDimensions.marginMedium),
            ExpansionTile(
              title: Text(
                'Detalhes técnicos',
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: AppColors.textMuted,
                ),
              ),
              children: [
                Padding(
                  padding: const EdgeInsets.all(AppDimensions.paddingSmall),
                  child: Text(
                    details!,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: AppColors.textMuted,
                      fontFamily: 'monospace',
                    ),
                  ),
                ),
              ],
            ),
          ],
          if (onRetry != null) ...[
            const SizedBox(height: AppDimensions.marginLarge),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: onRetry,
                icon: const Icon(Icons.refresh),
                label: Text(retryText ?? 'Tentar novamente'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: errorColor,
                  foregroundColor: AppColors.textOnDark,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildFullscreenError(BuildContext context) {
    final errorColor = color ?? AppColors.error;
    
    return Scaffold(
      backgroundColor: AppColors.backgroundSecondary,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(AppDimensions.paddingLarge),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(AppDimensions.paddingXLarge),
                decoration: BoxDecoration(
                  color: errorColor.withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  icon ?? Icons.error_outline,
                  color: errorColor,
                  size: 80,
                ),
              ),
              const SizedBox(height: AppDimensions.marginXLarge),
              if (title != null) ...[
                Text(
                  title!,
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    color: AppColors.textPrimary,
                    fontWeight: FontWeight.w600,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: AppDimensions.marginMedium),
              ],
              Text(
                message,
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                  color: AppColors.textSecondary,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: AppDimensions.marginXLarge),
              if (onRetry != null)
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: onRetry,
                    icon: const Icon(Icons.refresh),
                    label: Text(retryText ?? 'Tentar novamente'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: errorColor,
                      foregroundColor: AppColors.textOnDark,
                      padding: const EdgeInsets.symmetric(
                        vertical: AppDimensions.paddingMedium,
                      ),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Tipos de exibição do widget de erro
enum ErrorWidgetType {
  /// Erro inline (pequeno, usado em formulários)
  inline,
  
  /// Erro em card (médio, usado em listas e conteúdo)
  card,
  
  /// Erro de tela cheia (grande, usado como tela de erro)
  fullscreen,
}

/// Extensão para facilitar o uso com qualquer widget
extension ErrorWidgetExtension on Widget {
  /// Envolve o widget com tratamento de erro
  Widget withErrorHandling({
    required bool hasError,
    required String errorMessage,
    VoidCallback? onRetry,
    Failure? failure,
  }) {
    if (hasError) {
      return failure != null
          ? CustomErrorWidget.fromFailure(
              failure: failure,
              onRetry: onRetry,
            )
          : CustomErrorWidget.card(
              message: errorMessage,
              onRetry: onRetry,
            );
    }
    return this;
  }
}