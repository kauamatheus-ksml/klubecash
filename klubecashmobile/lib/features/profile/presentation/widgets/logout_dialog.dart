// lib/features/profile/presentation/widgets/logout_dialog.dart
// ARQUIVO #108 - LogoutDialog - Diálogo de confirmação para logout

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../auth/presentation/providers/auth_provider.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/theme/text_styles.dart';
import '../../../../core/widgets/custom_button.dart';

/// Diálogo de confirmação para logout
class LogoutDialog extends ConsumerWidget {
  /// Callback de sucesso (opcional)
  final VoidCallback? onLogoutSuccess;

  const LogoutDialog({
    super.key,
    this.onLogoutSuccess,
  });

  /// Método estático para mostrar o diálogo
  static Future<void> show(
    BuildContext context, {
    VoidCallback? onLogoutSuccess,
  }) {
    return showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (context) => LogoutDialog(
        onLogoutSuccess: onLogoutSuccess,
      ),
    );
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProviderProvider);

    return AlertDialog(
      backgroundColor: AppColors.background,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
      ),
      contentPadding: const EdgeInsets.all(AppDimensions.paddingLarge),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Ícone
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              color: AppColors.error.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(
              Icons.logout_rounded,
              size: 32,
              color: AppColors.error,
            ),
          ),

          const SizedBox(height: AppDimensions.spacingLarge),

          // Título
          Text(
            'Sair da Conta',
            style: AppTextStyles.h4.copyWith(
              fontWeight: FontWeight.w600,
            ),
            textAlign: TextAlign.center,
          ),

          const SizedBox(height: AppDimensions.spacingMedium),

          // Mensagem
          Text(
            'Tem certeza que deseja sair da sua conta? Você precisará fazer login novamente para acessar o app.',
            style: AppTextStyles.bodyMedium.copyWith(
              color: AppColors.textSecondary,
            ),
            textAlign: TextAlign.center,
          ),

          const SizedBox(height: AppDimensions.spacingLarge),

          // Botões
          Row(
            children: [
              Expanded(
                child: CustomButton(
                  text: 'Cancelar',
                  onPressed: authState.isLoading 
                      ? null 
                      : () => Navigator.of(context).pop(),
                  type: ButtonType.outline,
                ),
              ),

              const SizedBox(width: AppDimensions.spacingMedium),

              Expanded(
                child: CustomButton(
                  text: 'Sair',
                  onPressed: authState.isLoading 
                      ? null 
                      : () => _handleLogout(context, ref),
                  type: ButtonType.primary,
                  backgroundColor: AppColors.error,
                  isLoading: authState.isLoading,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Future<void> _handleLogout(BuildContext context, WidgetRef ref) async {
    final navigator = Navigator.of(context);
    final authNotifier = ref.read(authProviderProvider.notifier);

    final success = await authNotifier.logout();

    if (success) {
      navigator.pop(); // Fecha o diálogo
      onLogoutSuccess?.call();
    }
  }
}