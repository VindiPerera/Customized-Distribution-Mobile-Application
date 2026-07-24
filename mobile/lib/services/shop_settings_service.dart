import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/shop_settings.dart';

class ShopSettingsService {
  static const _key = 'shop_settings';

  Future<ShopSettings> load() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_key);
    if (raw == null) return ShopSettings.mock;
    return ShopSettings.fromJson(jsonDecode(raw));
  }

  Future<void> save(ShopSettings settings) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_key, jsonEncode(settings.toJson()));
  }
}
