import '../models/shop_settings.dart';
import 'api_client.dart';

class ShopSettingsService {
  final ApiClient _api;

  ShopSettingsService(this._api);

  Future<ShopSettings> load() async {
    final data = await _api.get('/shop-settings');
    return ShopSettings.fromJson(data);
  }
}
