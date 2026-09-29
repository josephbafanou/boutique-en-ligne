import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../services/api_client.dart';

class AuthProvider extends ChangeNotifier {
  AuthProvider(this.api);

  final ApiClient api;
  static const _tokenKey = 'auth_token';

  bool get isLoggedIn => api.token != null;

  /// Restaure la session enregistrée au démarrage de l'application.
  Future<void> restore() async {
    final prefs = await SharedPreferences.getInstance();
    api.token = prefs.getString(_tokenKey);
    notifyListeners();
  }

  Future<void> login(String email, String password) async {
    await _save(await api.login(email, password));
  }

  Future<void> register(String name, String email, String password) async {
    await _save(await api.register(name, email, password));
  }

  Future<void> logout() async {
    try {
      await api.logout();
    } finally {
      api.token = null;
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_tokenKey);
      notifyListeners();
    }
  }

  Future<void> _save(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
    notifyListeners();
  }
}
