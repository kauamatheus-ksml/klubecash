// lib/features/dashboard/presentation/widgets/quick_actions_grid.dart
// ⚡ Quick Actions Grid - Grid de ações rápidas do dashboard

import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/constants/app_strings.dart';

/// Modelo para uma ação rápida
class QuickAction {
  final String id;
  final String title;
  final String description;
  final IconData icon;
  final String emoji;
  final Color? color;
  final VoidCallback? onTap;

  const QuickAction({
    required this.id,
    required this.title,
    required this.description,
    required this.icon,
    required this.emoji,
    this.color,
    this.onTap,
  });
}

/// Widget grid de ações rápidas
class QuickActionsGrid extends StatelessWidget {
  /// Lista de ações
  final List<QuickAction> actions;
  
  /// Se deve mostrar animação
  final bool showAnimation;
  
  /// Espaçamento entre cards
  final double spacing;
  
  /// Proporção de aspecto dos cards
  final double aspectRatio;

  const QuickActionsGrid({
    super.key,
    required this.actions,
    this.showAnimation = true,
    this.spacing = 16,
    this.aspectRatio = 1.2,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildHeader(),
        const SizedBox(height: AppDimensions.spacingMedium),
        _buildGrid(),
      ],
    );
  }

  Widget _buildHeader() {
    return Text(
      AppStrings.quickActions,
      style: TextStyle(
        fontSize: 18,
        fontWeight: FontWeight.w700,
        color: AppColors.textPrimary,
      ),
    );
  }

  Widget _buildGrid() {
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: spacing,
        mainAxisSpacing: spacing,
        childAspectRatio: aspectRatio,
      ),
      itemCount: actions.length,
      itemBuilder: (context, index) {
        final action = actions[index];
        
        Widget card = QuickActionCard(action: action);
        
        if (showAnimation) {
          card = card
              .animate(delay: (index * 100).ms)
              .fadeIn(duration: 300.ms)
              .slideY(begin: 0.3, end: 0, duration: 400.ms);
        }
        
        return card;
      },
    );
  }
}

/// Card individual de ação rápida
class QuickActionCard extends StatefulWidget {
  final QuickAction action;

  const QuickActionCard({
    super.key,
    required this.action,
  });

  @override
  State<QuickActionCard> createState() => _QuickActionCardState();
}

class _QuickActionCardState extends State<QuickActionCard>
    with SingleTickerProviderStateMixin {
  late AnimationController _animationController;
  late Animation<double> _scaleAnimation;

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
      duration: const Duration(milliseconds: 150),
      vsync: this,
    );
    _scaleAnimation = Tween<double>(
      begin: 1.0,
      end: 0.95,
    ).animate(CurvedAnimation(
      parent: _animationController,
      curve: Curves.easeInOut,
    ));
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _scaleAnimation,
      builder: (context, child) {
        return Transform.scale(
          scale: _scaleAnimation.value,
          child: Container(
            decoration: BoxDecoration(
              color: AppColors.white,
              borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.05),
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Material(
              color: Colors.transparent,
              child: InkWell(
                onTap: widget.action.onTap,
                onTapDown: (_) => _animationController.forward(),
                onTapUp: (_) => _animationController.reverse(),
                onTapCancel: () => _animationController.reverse(),
                borderRadius: BorderRadius.circular(AppDimensions.radiusLarge),
                child: Padding(
                  padding: const EdgeInsets.all(AppDimensions.paddingMedium),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      _buildIcon(),
                      const SizedBox(height: AppDimensions.spacingSmall),
                      _buildTitle(),
                      const SizedBox(height: 4),
                      _buildDescription(),
                    ],
                  ),
                ),
              ),
            ),
          ),
        );
      },
    );
  }

  Widget _buildIcon() {
    final color = widget.action.color ?? AppColors.primary;
    
    return Container(
      width: 56,
      height: 56,
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(28),
      ),
      child: Stack(
        alignment: Alignment.center,
        children: [
          Icon(
            widget.action.icon,
            size: 24,
            color: color,
          ),
          Positioned(
            top: 4,
            right: 4,
            child: Text(
              widget.action.emoji,
              style: const TextStyle(fontSize: 16),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTitle() {
    return Text(
      widget.action.title,
      style: TextStyle(
        fontSize: 14,
        fontWeight: FontWeight.w600,
        color: AppColors.textPrimary,
      ),
      textAlign: TextAlign.center,
      maxLines: 1,
      overflow: TextOverflow.ellipsis,
    );
  }

  Widget _buildDescription() {
    return Text(
      widget.action.description,
      style: TextStyle(
        fontSize: 12,
        color: AppColors.textSecondary,
      ),
      textAlign: TextAlign.center,
      maxLines: 2,
      overflow: TextOverflow.ellipsis,
    );
  }
}

/// Factory methods para ações predefinidas
extension QuickActionsFactory on QuickActionsGrid {
  /// Cria grid padrão do dashboard
  static QuickActionsGrid dashboard({
    VoidCallback? onStores,
    VoidCallback? onHistory,
    VoidCallback? onInvite,
    VoidCallback? onHelp,
    bool showAnimation = true,
  }) {
    final actions = [
      QuickAction(
        id: 'stores',
        title: 'Lojas Parceiras',
        description: 'Descubra onde ganhar cashback',
        icon: Icons.store,
        emoji: '🏪',
        color: AppColors.info,
        onTap: onStores,
      ),
      QuickAction(
        id: 'history',
        title: 'Histórico Completo',
        description: 'Veja todas suas transações',
        icon: Icons.history,
        emoji: '📊',
        color: AppColors.success,
        onTap: onHistory,
      ),
      QuickAction(
        id: 'invite',
        title: 'Indique Amigos',
        description: 'Ganhe bônus por indicação',
        icon: Icons.people,
        emoji: '🎁',
        color: AppColors.warning,
        onTap: onInvite,
      ),
      QuickAction(
        id: 'help',
        title: 'Ajuda & Suporte',
        description: 'Tire suas dúvidas',
        icon: Icons.help_outline,
        emoji: '💬',
        color: AppColors.secondary,
        onTap: onHelp,
      ),
    ];

    return QuickActionsGrid(
      actions: actions,
      showAnimation: showAnimation,
    );
  }

  /// Cria grid simplificado (2 ações apenas)
  static QuickActionsGrid simple({
    VoidCallback? onStores,
    VoidCallback? onHistory,
    bool showAnimation = true,
  }) {
    final actions = [
      QuickAction(
        id: 'stores',
        title: 'Ver Lojas',
        description: 'Encontre parceiros',
        icon: Icons.store,
        emoji: '🏪',
        onTap: onStores,
      ),
      QuickAction(
        id: 'history',
        title: 'Meu Histórico',
        description: 'Ver transações',
        icon: Icons.history,
        emoji: '📊',
        onTap: onHistory,
      ),
    ];

    return QuickActionsGrid(
      actions: actions,
      showAnimation: showAnimation,
    );
  }
}