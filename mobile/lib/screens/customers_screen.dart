import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/customer.dart';
import '../providers/auth_provider.dart';
import '../services/customer_service.dart';
import '../theme.dart';
import 'new_customer_screen.dart';

class CustomersScreen extends StatefulWidget {
  const CustomersScreen({super.key});

  @override
  State<CustomersScreen> createState() => _CustomersScreenState();
}

class _CustomersScreenState extends State<CustomersScreen> {
  late final CustomerService _customerService;
  late Future<List<Customer>> _future;

  @override
  void initState() {
    super.initState();
    _customerService = CustomerService(context.read<AuthProvider>().api);
    _future = _customerService.list();
  }

  void _reload() {
    setState(() => _future = _customerService.list());
  }

  Future<void> _openNewCustomer() async {
    final created = await Navigator.push<bool>(
      context,
      MaterialPageRoute(builder: (_) => const NewCustomerScreen()),
    );
    if (created == true) _reload();
  }

  Future<void> _showPaymentDialog(Customer customer) async {
    final amountController = TextEditingController();

    await showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Record payment', style: const TextStyle(fontSize: 17)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(customer.name, style: const TextStyle(color: AppColors.inkSoft, fontSize: 13)),
            const SizedBox(height: 16),
            TextField(
              controller: amountController,
              autofocus: true,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                labelText: 'Amount',
                prefixText: 'Rs. ',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          ElevatedButton(
            onPressed: () async {
              final amount = double.tryParse(amountController.text);
              if (amount == null || amount <= 0) return;
              Navigator.pop(ctx);
              await _customerService.recordPayment(customerId: customer.id, amount: amount);
              _reload();
            },
            child: const Text('Save'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Customers')),
      floatingActionButton: FloatingActionButton(
        onPressed: _openNewCustomer,
        tooltip: 'New Customer',
        child: const Icon(Icons.person_add_alt_1_rounded),
      ),
      body: FutureBuilder<List<Customer>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('Error: ${snapshot.error}'));
          }
          final customers = snapshot.data ?? [];
          if (customers.isEmpty) {
            return const Center(
              child: Text('No customers yet.', style: TextStyle(color: AppColors.inkSoft)),
            );
          }
          return RefreshIndicator(
            color: AppColors.accent,
            onRefresh: () async => _reload(),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: customers.length,
              separatorBuilder: (_, __) => const SizedBox(height: 10),
              itemBuilder: (context, i) {
                final c = customers[i];
                final overLimit = c.currentBalance > c.creditLimit;
                final initial = c.name.isNotEmpty ? c.name[0].toUpperCase() : '?';

                return Card(
                  child: InkWell(
                    borderRadius: BorderRadius.circular(14),
                    onTap: () => _showPaymentDialog(c),
                    child: Padding(
                      padding: const EdgeInsets.all(14),
                      child: Row(
                        children: [
                          CircleAvatar(
                            radius: 22,
                            backgroundColor: AppColors.accentSoft,
                            child: Text(
                              initial,
                              style: const TextStyle(color: AppColors.accent, fontWeight: FontWeight.w700),
                            ),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  c.name,
                                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15.5, color: AppColors.ink),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'Rs. ${c.currentBalance.toStringAsFixed(2)} of Rs. ${c.creditLimit.toStringAsFixed(2)}',
                                  style: TextStyle(
                                    fontSize: 12.5,
                                    color: overLimit ? AppColors.critical : AppColors.inkSoft,
                                    fontWeight: overLimit ? FontWeight.w600 : FontWeight.w400,
                                  ),
                                ),
                                if (overLimit) ...[
                                  const SizedBox(height: 6),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: AppColors.criticalSoft,
                                      borderRadius: BorderRadius.circular(20),
                                    ),
                                    child: const Text(
                                      'Over limit',
                                      style: TextStyle(fontSize: 11, color: AppColors.critical, fontWeight: FontWeight.w600),
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          ),
                          Container(
                            width: 40,
                            height: 40,
                            decoration: BoxDecoration(
                              color: AppColors.goodSoft,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: IconButton(
                              padding: EdgeInsets.zero,
                              icon: const Icon(Icons.payments_outlined, size: 19, color: AppColors.good),
                              tooltip: 'Record payment',
                              onPressed: () => _showPaymentDialog(c),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}
