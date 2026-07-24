import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../services/api_client.dart';
import '../services/customer_service.dart';
import '../theme.dart';

class NewCustomerScreen extends StatefulWidget {
  const NewCustomerScreen({super.key});

  @override
  State<NewCustomerScreen> createState() => _NewCustomerScreenState();
}

class _NewCustomerScreenState extends State<NewCustomerScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _emailController = TextEditingController();

  late final CustomerService _customerService;
  bool _isSubmitting = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _customerService = CustomerService(context.read<AuthProvider>().api);
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isSubmitting = true;
      _error = null;
    });

    try {
      await _customerService.create(
        name: _nameController.text.trim(),
        phone: _phoneController.text.trim().isEmpty ? null : _phoneController.text.trim(),
        email: _emailController.text.trim().isEmpty ? null : _emailController.text.trim(),
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Customer created.')),
        );
        Navigator.pop(context, true);
      }
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (e) {
      setState(() => _error = 'Could not connect to server.');
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('New Customer')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TextFormField(
                controller: _nameController,
                textCapitalization: TextCapitalization.words,
                decoration: const InputDecoration(
                  label: RequiredLabel('Name'),
                  prefixIcon: Icon(Icons.person_outline_rounded, size: 20),
                ),
                validator: (v) => (v == null || v.trim().isEmpty) ? 'Name is required' : null,
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _phoneController,
                keyboardType: TextInputType.phone,
                decoration: const InputDecoration(
                  labelText: 'Phone (optional)',
                  prefixIcon: Icon(Icons.phone_outlined, size: 20),
                ),
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _emailController,
                keyboardType: TextInputType.emailAddress,
                decoration: const InputDecoration(
                  labelText: 'Email (optional)',
                  prefixIcon: Icon(Icons.mail_outline_rounded, size: 20),
                ),
                validator: (v) {
                  if (v == null || v.trim().isEmpty) return null;
                  return v.contains('@') ? null : 'Enter a valid email';
                },
              ),
              if (_error != null) ...[
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  decoration: BoxDecoration(
                    color: AppColors.criticalSoft,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.error_outline_rounded, size: 18, color: AppColors.critical),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(_error!, style: const TextStyle(color: AppColors.critical, fontSize: 13)),
                      ),
                    ],
                  ),
                ),
              ],
              const SizedBox(height: 24),
              SizedBox(
                height: 50,
                child: ElevatedButton(
                  onPressed: _isSubmitting ? null : _submit,
                  child: _isSubmitting
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : const Text('Create Customer'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
