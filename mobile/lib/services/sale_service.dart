import '../models/sale.dart';
import '../models/sale_detail.dart';
import 'api_client.dart';

class SaleService {
  final ApiClient _api;

  SaleService(this._api);

  Future<Map<String, dynamic>> createSale({
    required int customerId,
    required String paymentType,
    required List<SaleItemInput> items,
    double? paidAmount,
  }) async {
    return await _api.post('/sales', {
      'customer_id': customerId,
      'payment_type': paymentType,
      if (paidAmount != null) 'paid_amount': paidAmount,
      'items': items.map((e) => e.toJson()).toList(),
    });
  }

  Future<List<Sale>> list() async {
    final data = await _api.get('/sales');
    final items = data['data'] as List;
    return items.map((e) => Sale.fromJson(e)).toList();
  }

  Future<SaleDetail> get(int id) async {
    final data = await _api.get('/sales/$id');
    return SaleDetail.fromJson(data);
  }
}
