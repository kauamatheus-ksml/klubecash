// lib/features/notifications/data/models/notification_model.dart
// ARQUIVO #116 - NotificationModel - Modelo de dados de notificação para comunicação com a API

import '../../domain/entities/notification.dart';

/// Modelo de dados para notificações do sistema de cashback
///
/// Representa uma notificação que pode ser enviada para o usuário
/// incluindo informações sobre cashbacks, transações e comunicados do sistema
class NotificationModel extends Notification {
  const NotificationModel({
    required super.id,
    required super.userId,
    required super.title,
    required super.message,
    required super.type,
    required super.createdAt,
    required super.isRead,
    super.link,
    super.readAt,
  });

  /// Cria um NotificationModel a partir de um Map (resposta da API)
  ///
  /// [json] - Map contendo os dados da notificação vindos da API
  factory NotificationModel.fromJson(Map<String, dynamic> json) {
    return NotificationModel(
      id: json['id'] as int,
      userId: json['usuario_id'] as int,
      title: json['titulo'] as String? ?? '',
      message: json['mensagem'] as String? ?? '',
      type: _parseNotificationType(json['tipo'] as String?),
      link: json['link'] as String?,
      createdAt: DateTime.parse(json['data_criacao'] as String),
      isRead: (json['lida'] as int? ?? 0) == 1,
      readAt: json['data_leitura'] != null 
          ? DateTime.parse(json['data_leitura'] as String)
          : null,
    );
  }

  /// Cria uma lista de NotificationModel a partir de uma lista de Maps
  ///
  /// [jsonList] - Lista de Maps contendo dados de notificações
  static List<NotificationModel> fromJsonList(List<dynamic> jsonList) {
    return jsonList
        .map((json) => NotificationModel.fromJson(json as Map<String, dynamic>))
        .toList();
  }

  /// Converte o NotificationModel para Map (para envio à API)
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'usuario_id': userId,
      'titulo': title,
      'mensagem': message,
      'tipo': _notificationTypeToString(type),
      'link': link,
      'data_criacao': createdAt.toIso8601String(),
      'lida': isRead ? 1 : 0,
      'data_leitura': readAt?.toIso8601String(),
    };
  }

  /// Cria uma cópia do NotificationModel com campos atualizados
  @override
  NotificationModel copyWith({
    int? id,
    int? userId,
    String? title,
    String? message,
    NotificationType? type,
    String? link,
    DateTime? createdAt,
    bool? isRead,
    DateTime? readAt,
  }) {
    return NotificationModel(
      id: id ?? this.id,
      userId: userId ?? this.userId,
      title: title ?? this.title,
      message: message ?? this.message,
      type: type ?? this.type,
      link: link ?? this.link,
      createdAt: createdAt ?? this.createdAt,
      isRead: isRead ?? this.isRead,
      readAt: readAt ?? this.readAt,
    );
  }

  /// Converte string do tipo da API para enum NotificationType
  static NotificationType _parseNotificationType(String? typeString) {
    switch (typeString?.toLowerCase()) {
      case 'success':
        return NotificationType.success;
      case 'warning':
        return NotificationType.warning;
      case 'error':
        return NotificationType.error;
      case 'info':
      default:
        return NotificationType.info;
    }
  }

  /// Converte enum NotificationType para string da API
  static String _notificationTypeToString(NotificationType type) {
    switch (type) {
      case NotificationType.success:
        return 'success';
      case NotificationType.warning:
        return 'warning';
      case NotificationType.error:
        return 'error';
      case NotificationType.info:
        return 'info';
    }
  }

  @override
  List<Object?> get props => [
        id,
        userId,
        title,
        message,
        type,
        link,
        createdAt,
        isRead,
        readAt,
      ];
}