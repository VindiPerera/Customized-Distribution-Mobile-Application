import 'package:flutter/foundation.dart';
import '../services/api_client.dart';
import '../services/auth_service.dart';

class AuthProvider extends ChangeNotifier {
  final ApiClient api = ApiClient();
  late final AuthService authService = AuthService(api);

  bool _isLoggedIn = false;
  Map<String, dynamic>? currentUser;
  bool isLoading = true;

  bool get isLoggedIn => _isLoggedIn;

  AuthProvider() {
    _checkLoginStatus();
  }

  Future<void> _checkLoginStatus() async {
    _isLoggedIn = await authService.isLoggedIn();
    isLoading = false;
    notifyListeners();
  }

  Future<void> login(String email, String password) async {
    currentUser = await authService.login(email, password);
    _isLoggedIn = true;
    notifyListeners();
  }

  Future<void> logout() async {
    await authService.logout();
    _isLoggedIn = false;
    currentUser = null;
    notifyListeners();
  }
}
