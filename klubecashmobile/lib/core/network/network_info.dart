// lib/core/network/network_info.dart
// Este arquivo contém a abstração e implementação para verificação de conectividade de rede.
// Responsável por verificar se o dispositivo tem conexão com a internet antes de fazer requisições HTTP.

import 'package:connectivity_plus/connectivity_plus.dart';

/// Interface abstrata para verificação de conectividade de rede
/// Seguindo os princípios da Clean Architecture, esta interface
/// permite que a camada de domínio use verificação de rede sem
/// depender de implementações específicas
abstract class NetworkInfo {
  /// Verifica se há conexão com a internet
  /// 
  /// Retorna `true` se houver conectividade (WiFi, dados móveis, ethernet)
  /// Retorna `false` se não houver conectividade ou apenas bluetooth
  Future<bool> get isConnected;

  /// Stream que monitora mudanças no status de conectividade
  /// Útil para reagir a mudanças de conectividade em tempo real
  Stream<bool> get connectivityStream;
}

/// Implementação concreta da verificação de conectividade
/// Utiliza o package connectivity_plus para verificar o status da rede
class NetworkInfoImpl implements NetworkInfo {
  final Connectivity _connectivity;

  NetworkInfoImpl(this._connectivity);

  @override
  Future<bool> get isConnected async {
    try {
      final connectivityResult = await _connectivity.checkConnectivity();
      
      // Considera conectado se houver WiFi, dados móveis ou ethernet
      // Bluetooth não é considerado uma conexão válida para internet
      return connectivityResult == ConnectivityResult.wifi ||
             connectivityResult == ConnectivityResult.mobile ||
             connectivityResult == ConnectivityResult.ethernet;
    } catch (e) {
      // Em caso de erro na verificação, assume que não há conectividade
      return false;
    }
  }

  @override
  Stream<bool> get connectivityStream {
    return _connectivity.onConnectivityChanged.map((connectivityResult) {
      // Mapeia o resultado de conectividade para boolean
      return connectivityResult == ConnectivityResult.wifi ||
             connectivityResult == ConnectivityResult.mobile ||
             connectivityResult == ConnectivityResult.ethernet;
    });
  }
}