// lib/features/stores/presentation/widgets/store_info_section.dart
// 📋 Store Info Section - Widget para exibir informações detalhadas de uma loja

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../domain/entities/store.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_dimensions.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/utils/date_utils.dart';

/// Widget para exibir informações detalhadas de uma loja
/// 
/// Mostra descrição, contato, endereço, horários e avaliações.
/// Suporta diferentes seções que podem ser habilitadas/desabilitadas.
class StoreInfoSection extends StatelessWidget {
  /// Dados da loja
  final Store store;
  
  /// Se deve mostrar seção de descrição
  final bool showDescription;
  
  /// Se deve mostrar seção de contato
  final bool showContact;
  
  /// Se deve mostrar seção de endereço
  final bool showAddress;
  
  /// Se deve mostrar seção de horários
  final bool showOperatingHours;
  
  /// Se deve mostrar seção de avaliações
  final bool showRatings;
  
  /// Se deve mostrar seção de tags/categorias
  final bool showTags;
  
  /// Callback para ligar para a loja
  final VoidCallback? onCallStore;
  
  /// Callback para visitar o website
  final VoidCallback? onVisitWebsite;
  
  /// Callback para ver localização no mapa
  final VoidCallback? onViewLocation;

  const StoreInfoSection({
    super.key,
    required this.store,
    this.showDescription = true,
    this.showContact = true,
    this.showAddress = true,
    this.showOperatingHours = true,
    this.showRatings = true,
    this.showTags = true,
    this.onCallStore,
    this.onVisitWebsite,
    this.onViewLocation,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Descrição da loja
        if (showDescription && store.description.isNotEmpty) ...[
          _buildDescriptionSection(context),
          const SizedBox(height: AppDimensions.spacingLarge),
        ],
        
        // Informações de contato
        if (showContact) ...[
          _buildContactSection(context),
          const SizedBox(height: AppDimensions.spacingLarge),
        ],
        
        // Endereço
        if (showAddress && store.address != null) ...[
          _buildAddressSection(context),
          const SizedBox(height: AppDimensions.spacingLarge),
        ],
        
        // Horários de funcionamento
        if (showOperatingHours && store.operatingHours != null) ...[
          _buildOperatingHoursSection(context),
          const SizedBox(height: AppDimensions.spacingLarge),
        ],
        
        // Avaliações
        if (showRatings) ...[
          _buildRatingsSection(context),
          const SizedBox(height: AppDimensions.spacingLarge),
        ],
        
        // Tags/Categorias
        if (showTags) ...[
          _buildTagsSection(context),
        ],
      ],
    );
  }

  /// Constrói a seção de descrição
  Widget _buildDescriptionSection(BuildContext context) {
    return _buildSection(
      context,
      title: 'Sobre a loja',
      icon: Icons.info_outline,
      child: Text(
        store.description,
        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
          color: AppColors.textSecondary,
          height: 1.5,
        ),
      ),
    );
  }

  /// Constrói a seção de contato
  Widget _buildContactSection(BuildContext context) {
    return _buildSection(
      context,
      title: 'Contato',
      icon: Icons.contact_phone_outlined,
      child: Column(
        children: [
          // Telefone
          if (store.phone != null && store.phone!.isNotEmpty) ...[
            _buildContactItem(
              context,
              icon: Icons.phone,
              label: 'Telefone',
              value: _formatPhone(store.phone!),
              onTap: () => _makePhoneCall(store.phone!),
            ),
            const SizedBox(height: AppDimensions.spacingMedium),
          ],
          
          // Website
          if (store.website != null && store.website!.isNotEmpty) ...[
            _buildContactItem(
              context,
              icon: Icons.language,
              label: 'Website',
              value: store.website!,
              onTap: () => _openWebsite(store.website!),
            ),
            const SizedBox(height: AppDimensions.spacingMedium),
          ],
          
          // Email (se disponível)
          if (store.email != null && store.email!.isNotEmpty) ...[
            _buildContactItem(
              context,
              icon: Icons.email_outlined,
              label: 'Email',
              value: store.email!,
              onTap: () => _sendEmail(store.email!),
            ),
          ],
        ],
      ),
    );
  }

  /// Constrói a seção de endereço
  Widget _buildAddressSection(BuildContext context) {
    final address = store.address!;
    
    return _buildSection(
      context,
      title: 'Localização',
      icon: Icons.location_on_outlined,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Endereço completo
          Text(
            address.fullAddress,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: AppColors.textSecondary,
              height: 1.4,
            ),
          ),
          
          const SizedBox(height: AppDimensions.spacingMedium),
          
          // Botões de ação
          Row(
            children: [
              Expanded(
                child: CustomButton.outlined(
                  text: 'Ver no mapa',
                  icon: Icons.map_outlined,
                  onPressed: onViewLocation ?? () => _openMaps(address),
                  size: ButtonSize.small,
                ),
              ),
              const SizedBox(width: AppDimensions.spacingSmall),
              Expanded(
                child: CustomButton.outlined(
                  text: 'Copiar endereço',
                  icon: Icons.copy,
                  onPressed: () => _copyAddress(context, address.fullAddress),
                  size: ButtonSize.small,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  /// Constrói a seção de horários
  Widget _buildOperatingHoursSection(BuildContext context) {
    final hours = store.operatingHours!;
    
    return _buildSection(
      context,
      title: 'Horário de funcionamento',
      icon: Icons.access_time,
      child: Column(
        children: hours.entries.map((entry) => 
          _buildOperatingHourItem(context, entry.key, entry.value)
        ).toList(),
      ),
    );
  }

  /// Constrói a seção de avaliações
  Widget _buildRatingsSection(BuildContext context) {
    return _buildSection(
      context,
      title: 'Avaliações',
      icon: Icons.star_outline,
      child: Row(
        children: [
          // Rating stars
          Row(
            children: List.generate(5, (index) => Icon(
              index < store.rating ? Icons.star : Icons.star_border,
              color: AppColors.warning,
              size: 20,
            )),
          ),
          
          const SizedBox(width: AppDimensions.spacingSmall),
          
          // Rating text
          Text(
            '${store.rating.toStringAsFixed(1)}',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w600,
              color: AppColors.textPrimary,
            ),
          ),
          
          const SizedBox(width: AppDimensions.spacingXSmall),
          
          // Reviews count
          Text(
            '(${store.reviewsCount} ${store.reviewsCount == 1 ? 'avaliação' : 'avaliações'})',
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }

  /// Constrói a seção de tags
  Widget _buildTagsSection(BuildContext context) {
    final tags = _getStoreTags();
    
    if (tags.isEmpty) return const SizedBox.shrink();
    
    return _buildSection(
      context,
      title: 'Tags',
      icon: Icons.local_offer_outlined,
      child: Wrap(
        spacing: AppDimensions.spacingSmall,
        runSpacing: AppDimensions.spacingSmall,
        children: tags.map((tag) => _buildTagChip(context, tag)).toList(),
      ),
    );
  }

  /// Constrói uma seção genérica
  Widget _buildSection(
    BuildContext context, {
    required String title,
    required IconData icon,
    required Widget child,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppDimensions.paddingMedium),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppDimensions.radiusMedium),
        border: Border.all(
          color: AppColors.border,
          width: 1,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header da seção
          Row(
            children: [
              Icon(
                icon,
                size: 20,
                color: AppColors.primary,
              ),
              const SizedBox(width: AppDimensions.spacingSmall),
              Text(
                title,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                  color: AppColors.textPrimary,
                ),
              ),
            ],
          ),
          
          const SizedBox(height: AppDimensions.spacingMedium),
          
          // Conteúdo da seção
          child,
        ],
      ),
    );
  }

  /// Constrói um item de contato
  Widget _buildContactItem(
    BuildContext context, {
    required IconData icon,
    required String label,
    required String value,
    VoidCallback? onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
      child: Padding(
        padding: const EdgeInsets.symmetric(
          vertical: AppDimensions.paddingXSmall,
        ),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: AppColors.primary.withOpacity(0.1),
                borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
              ),
              child: Icon(
                icon,
                size: 20,
                color: AppColors.primary,
              ),
            ),
            
            const SizedBox(width: AppDimensions.spacingMedium),
            
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: AppColors.textSecondary,
                    ),
                  ),
                  Text(
                    value,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: AppColors.textPrimary,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
            
            if (onTap != null) ...[
              Icon(
                Icons.chevron_right,
                color: AppColors.textSecondary,
                size: 20,
              ),
            ],
          ],
        ),
      ),
    );
  }

  /// Constrói um item de horário
  Widget _buildOperatingHourItem(BuildContext context, String day, String hours) {
    final isToday = _isToday(day);
    
    return Padding(
      padding: const EdgeInsets.only(bottom: AppDimensions.spacingXSmall),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            day,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: isToday ? AppColors.primary : AppColors.textPrimary,
              fontWeight: isToday ? FontWeight.w600 : FontWeight.normal,
            ),
          ),
          Text(
            hours,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: isToday ? AppColors.primary : AppColors.textSecondary,
              fontWeight: isToday ? FontWeight.w600 : FontWeight.normal,
            ),
          ),
        ],
      ),
    );
  }

  /// Constrói um chip de tag
  Widget _buildTagChip(BuildContext context, String tag) {
    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: AppDimensions.paddingSmall,
        vertical: AppDimensions.paddingXSmall,
      ),
      decoration: BoxDecoration(
        color: AppColors.primary.withOpacity(0.1),
        borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
        border: Border.all(
          color: AppColors.primary.withOpacity(0.3),
          width: 1,
        ),
      ),
      child: Text(
        tag,
        style: Theme.of(context).textTheme.bodySmall?.copyWith(
          color: AppColors.primary,
          fontWeight: FontWeight.w500,
        ),
      ),
    );
  }

  /// Formata o telefone
  String _formatPhone(String phone) {
    final numbers = phone.replaceAll(RegExp(r'\D'), '');
    if (numbers.length == 11) {
      return '(${numbers.substring(0, 2)}) ${numbers.substring(2, 7)}-${numbers.substring(7)}';
    } else if (numbers.length == 10) {
      return '(${numbers.substring(0, 2)}) ${numbers.substring(2, 6)}-${numbers.substring(6)}';
    }
    return phone;
  }

  /// Verifica se é hoje
  bool _isToday(String dayName) {
    final today = DateTime.now().weekday;
    final dayNames = [
      'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira',
      'Sexta-feira', 'Sábado', 'Domingo'
    ];
    return dayNames[today - 1] == dayName;
  }

  /// Obtém tags da loja
  List<String> _getStoreTags() {
    final tags = <String>[];
    
    // Tag da categoria
    tags.add(store.category.name);
    
    // Tag se é nova
    if (store.isNew) {
      tags.add('Nova parceira');
    }
    
    // Tag de cashback
    tags.add('${store.cashbackPercentage.toStringAsFixed(1)}% cashback');
    
    // Outras tags baseadas em características da loja
    if (store.rating >= 4.5) {
      tags.add('Bem avaliada');
    }
    
    return tags;
  }

  /// Faz ligação telefônica
  Future<void> _makePhoneCall(String phoneNumber) async {
    final uri = Uri(scheme: 'tel', path: phoneNumber);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  /// Abre website
  Future<void> _openWebsite(String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  /// Envia email
  Future<void> _sendEmail(String email) async {
    final uri = Uri(
      scheme: 'mailto',
      path: email,
      query: 'subject=Contato via Klube Cash',
    );
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  /// Abre mapa
  Future<void> _openMaps(StoreAddress address) async {
    final query = Uri.encodeComponent(address.fullAddress);
    final uri = Uri.parse('https://www.google.com/maps/search/?api=1&query=$query');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  /// Copia endereço
  Future<void> _copyAddress(BuildContext context, String address) async {
    await Clipboard.setData(ClipboardData(text: address));
    
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Endereço copiado!'),
          backgroundColor: AppColors.success,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppDimensions.radiusSmall),
          ),
        ),
      );
    }
  }
}

/// Classe auxiliar para endereço da loja
class StoreAddress {
  final String street;
  final String number;
  final String? complement;
  final String neighborhood;
  final String city;
  final String state;
  final String zipCode;

  const StoreAddress({
    required this.street,
    required this.number,
    this.complement,
    required this.neighborhood,
    required this.city,
    required this.state,
    required this.zipCode,
  });

  String get fullAddress {
    final parts = [
      '$street, $number',
      if (complement != null && complement!.isNotEmpty) complement!,
      neighborhood,
      '$city - $state',
      'CEP: $zipCode',
    ];
    return parts.join(', ');
  }
}