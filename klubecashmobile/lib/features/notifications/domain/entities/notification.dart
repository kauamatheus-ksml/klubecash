// lib/features/notifications/domain/entities/notification.dart
// Arquivo: Entidade Notification - Representa uma notificação do sistema Klube Cash

/// Entidade que representa uma notificação do sistema
///
/// Esta classe define a estrutura de uma notificação,
/// contendo todas as informações necessárias para exibir
/// notificações aos usuários no app Klube Cash
class Notification {
  final String id;
  final String userId;
  final String title;
  final String message;
  final NotificationType type;
  final String? link;
  final DateTime createdAt;
  final bool isRead;
  final DateTime? readAt;

  const Notification({
    required this.id,
    required this.userId,
    required this.title,
    required this.message,
    required this.type,
    this.link,
    required this.createdAt,
    this.isRead = false,
    this.readAt,
  });

  /// Cria uma cópia da notificação com campos atualizados
  Notification copyWith({
    String? id,
    String? userId,
    String? title,
    String? message,
    NotificationType? type,
    String? link,
    DateTime? createdAt,
    bool? isRead,
    DateTime? readAt,
  }) {
    return Notification(
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

  /// Marca a notificação como lida
  Notification markAsRead() {
    return copyWith(
      isRead: true,
      readAt: DateTime.now(),
    );
  }

  /// Marca a notificação como não lida
  Notification markAsUnread() {
    return copyWith(
      isRead: false,
      readAt: null,
    );
  }

  /// Verifica se a notificação é recente (últimas 24 horas)
  bool get isRecent {
    final now = DateTime.now();
    final difference = now.difference(createdAt);
    return difference.inHours < 24;
  }

  /// Verifica se a notificação possui um link de ação
  bool get hasAction => link != null && link!.isNotEmpty;

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is Notification &&
        other.id == id &&
        other.userId == userId &&
        other.title == title &&
        other.message == message &&
        other.type == type &&
        other.link == link &&
        other.createdAt == createdAt &&
        other.isRead == isRead &&
        other.readAt == readAt;
  }

  @override
  int get hashCode {
    return Object.hash(
      id,
      userId,
      title,
      message,
      type,
      link,
      createdAt,
      isRead,
      readAt,
    );
  }

  @override
  String toString() {
    return 'Notification('
        'id: $id, '
        'userId: $userId, '
        'title: $title, '
        'message: $message, '
        'type: $type, '
        'link: $link, '
        'createdAt: $createdAt, '
        'isRead: $isRead, '
        'readAt: $readAt)';
  }
}

/// Enum que define os tipos de notificação disponíveis
enum NotificationType {
  info('info'),
  success('success'),
  warning('warning'),
  error('error');

  const NotificationType(this.value);

  final String value;

  /// Converte string para enum
  static NotificationType fromString(String type) {
    switch (type.toLowerCase()) {
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

  /// Retorna a cor associada ao tipo de notificação
  String get colorName {
    switch (this) {
      case NotificationType.success:
        return 'green';
      case NotificationType.warning:
        return 'orange';
      case NotificationType.error:
        return 'red';
      case NotificationType.info:
        return 'blue';
    }
  }

  /// Retorna o ícone associado ao tipo de notificação
  String get iconName {
    switch (this) {
      case NotificationType.success:
        return 'check_circle';
      case NotificationType.warning:
        return 'warning';
      case NotificationType.error:
        return 'error';
      case NotificationType.info:
        return 'info';
    }
  }
}