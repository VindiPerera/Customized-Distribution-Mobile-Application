import '../models/product.dart';
import 'api_client.dart';

class ProductService {
  final ApiClient _api;

  ProductService(this._api);

  Future<List<Product>> list() async {
    final data = await _api.get('/products');
    final items = data['data'] as List;
    return items.map((e) => Product.fromJson(e)).toList();
  }
}
