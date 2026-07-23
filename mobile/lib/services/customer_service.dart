import '../models/customer.dart';
import 'api_client.dart';

class CustomerService {
  final ApiClient _api;

  CustomerService(this._api);

  Future<List<Customer>> list() async {
    final data = await _api.get('/customers');
    final items = data['data'] as List;
    return items.map((e) => Customer.fromJson(e)).toList();
  }

  Future<Customer> create({
    required String name,
    String? phone,
    String? email,
    double creditLimit = 0,
  }) async {
    final data = await _api.post('/customers', {
      'name': name,
      'phone': phone,
      'email': email,
      'credit_limit': creditLimit,
    });
    return Customer.fromJson(data);
  }

  Future<Map<String, dynamic>> recordPayment({
    required int customerId,
    required double amount,
    String method = 'cash',
    String? notes,
  }) async {
    return await _api.post('/payments', {
      'customer_id': customerId,
      'amount': amount,
      'method': method,
      'notes': notes,
    });
  }
}
