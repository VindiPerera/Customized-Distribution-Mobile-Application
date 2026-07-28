import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/customer.dart';
import '../models/product.dart';
import '../models/receipt_data.dart';
import '../models/sale.dart';
import '../providers/auth_provider.dart';
import '../services/api_client.dart';
import '../services/customer_service.dart';
import '../services/product_service.dart';
import '../services/sale_service.dart';
import '../theme.dart';
import 'receipt_preview_screen.dart';

class _CartLine {
  final Product product;
  int quantity;

  _CartLine(this.product, this.quantity);

  double get lineTotal => product.sellingPrice * quantity;
}

class _SplitLine {
  String method;
  final TextEditingController amountController;

  _SplitLine({required this.method, String initialAmount = ''})
      : amountController = TextEditingController(text: initialAmount);

  double get amount => double.tryParse(amountController.text) ?? 0;
}

const _paymentTypeOptions = [
  (value: 'cash', label: 'Cash', icon: Icons.payments_outlined),
  (value: 'card', label: 'Card', icon: Icons.credit_card_outlined),
  (value: 'bank_transfer', label: 'Bank', icon: Icons.account_balance_outlined),
  (value: 'credit', label: 'Credit', icon: Icons.receipt_long_outlined),
  (value: 'split', label: 'Split', icon: Icons.call_split_rounded),
];

const _splitMethodOptions = [
  (value: 'cash', label: 'Cash'),
  (value: 'card', label: 'Card'),
  (value: 'bank_transfer', label: 'Bank Transfer'),
];

const _settlementMethodOptions = [
  (value: 'cash', label: 'Cash', icon: Icons.payments_outlined),
  (value: 'card', label: 'Card', icon: Icons.credit_card_outlined),
  (value: 'bank_transfer', label: 'Bank', icon: Icons.account_balance_outlined),
];

class NewSaleScreen extends StatefulWidget {
  /// When set, the screen opens in "settle credit" mode for this customer
  /// instead of the normal product/cart sale flow: the cart is replaced by
  /// a single fixed line for the outstanding balance, and completing it
  /// records a payment (not a sale) before showing a receipt.
  final Customer? settlementCustomer;

  const NewSaleScreen({super.key, this.settlementCustomer});

  bool get isSettlement => settlementCustomer != null;

  @override
  State<NewSaleScreen> createState() => _NewSaleScreenState();
}

class _NewSaleScreenState extends State<NewSaleScreen> {
  late final ProductService _productService;
  late final CustomerService _customerService;
  late final SaleService _saleService;

  List<Product> _products = [];
  List<Customer> _customers = [];
  final Map<int, _CartLine> _cart = {};

  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';

  final TextEditingController _discountController = TextEditingController();

  String _paymentType = 'cash';
  Customer? _selectedCustomer;
  bool _isLoading = true;
  bool _isSubmitting = false;
  String? _error;

  final List<_SplitLine> _splitLines = [
    _SplitLine(method: 'cash'),
    _SplitLine(method: 'card'),
  ];

  late final TextEditingController _settlementAmountController;
  String _settlementMethod = 'cash';

  bool get _isSettlement => widget.isSettlement;

  @override
  void initState() {
    super.initState();
    final api = context.read<AuthProvider>().api;
    _productService = ProductService(api);
    _customerService = CustomerService(api);
    _saleService = SaleService(api);
    _settlementAmountController = TextEditingController(
      text: _isSettlement ? widget.settlementCustomer!.currentBalance.toStringAsFixed(2) : '',
    );
    _loadData();
  }

  @override
  void dispose() {
    _searchController.dispose();
    _discountController.dispose();
    _settlementAmountController.dispose();
    for (final line in _splitLines) {
      line.amountController.dispose();
    }
    super.dispose();
  }

  Future<void> _loadData() async {
    if (_isSettlement) {
      setState(() {
        _selectedCustomer = widget.settlementCustomer;
        _isLoading = false;
      });
      return;
    }
    final products = await _productService.list();
    final customers = await _customerService.list();
    setState(() {
      _products = products;
      _customers = customers;
      _isLoading = false;
    });
  }

  void _addToCart(Product product) {
    setState(() {
      final existing = _cart[product.id];
      if (existing != null) {
        existing.quantity++;
      } else {
        _cart[product.id] = _CartLine(product, 1);
      }
    });
  }

  void _updateQuantity(int productId, int delta) {
    setState(() {
      final line = _cart[productId];
      if (line == null) return;
      final newQty = line.quantity + delta;
      if (newQty <= 0) {
        _cart.remove(productId);
      } else {
        line.quantity = newQty;
      }
    });
  }

  double get _settlementAmount => double.tryParse(_settlementAmountController.text) ?? 0;

  double get _subtotal => _isSettlement ? _settlementAmount : _cart.values.fold(0, (sum, line) => sum + line.lineTotal);
  int get _itemCount => _isSettlement ? (_settlementAmount > 0 ? 1 : 0) : _cart.values.fold(0, (sum, line) => sum + line.quantity);

  double get _discount {
    final raw = double.tryParse(_discountController.text) ?? 0;
    if (raw <= 0) return 0;
    return raw > _subtotal ? _subtotal : raw;
  }

  double get _total => _subtotal - _discount;

  List<Product> get _filteredProducts {
    final query = _searchQuery.trim().toLowerCase();
    if (query.isEmpty) return _products;
    return _products
        .where((p) => p.name.toLowerCase().contains(query) || p.sku.toLowerCase().contains(query))
        .toList();
  }

  double get _splitAssigned => _splitLines.fold(0, (sum, l) => sum + l.amount);
  double get _splitRemaining => ((_total - _splitAssigned) * 100).round() / 100;

  Widget _productPlaceholder() {
    return Container(
      width: 44,
      height: 44,
      color: AppColors.lineSoft,
      alignment: Alignment.center,
      child: const Icon(Icons.inventory_2_outlined, size: 20, color: AppColors.inkSoft),
    );
  }

  Future<void> _openSettleCredit(Customer customer) async {
    await Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => NewSaleScreen(settlementCustomer: customer)),
    );
    final customers = await _customerService.list();
    if (mounted) {
      setState(() {
        _customers = customers;
        _selectedCustomer = customers.firstWhere((c) => c.id == customer.id, orElse: () => customer);
      });
    }
  }

  void _addSplitLine() {
    setState(() => _splitLines.add(_SplitLine(method: 'cash')));
  }

  void _removeSplitLine(int index) {
    setState(() {
      _splitLines[index].amountController.dispose();
      _splitLines.removeAt(index);
    });
  }

  Future<void> _submitSale() {
    return _isSettlement ? _submitSettlement() : _submitRegularSale();
  }

  Future<void> _submitRegularSale() async {
    if (_cart.isEmpty) {
      setState(() => _error = 'Add at least one item.');
      return;
    }
    if (_paymentType == 'credit' && _selectedCustomer == null) {
      setState(() => _error = 'Select a customer for a credit sale.');
      return;
    }
    if (_paymentType == 'split' && _splitRemaining != 0) {
      setState(() => _error = 'Split payment amounts must add up to the total.');
      return;
    }

    setState(() {
      _isSubmitting = true;
      _error = null;
    });

    try {
      final result = await _saleService.createSale(
        customerId: _selectedCustomer?.id,
        paymentType: _paymentType,
        items: _cart.values
            .map((l) => SaleItemInput(productId: l.product.id, quantity: l.quantity))
            .toList(),
        discount: _discount,
        payments: _paymentType == 'split'
            ? _splitLines.map((l) => SalePaymentInput(method: l.method, amount: l.amount)).toList()
            : null,
      );
      if (!mounted) return;
      final sale = Sale.fromJson(result);
      final cashierName = context.read<AuthProvider>().currentUser?['name'] as String? ?? '-';

      final receipt = ReceiptData(
        invoiceNumber: sale.invoiceNumber,
        date: sale.saleDate,
        lines: _cart.values
            .map((l) => ReceiptLine(
                  name: l.product.name,
                  quantity: l.quantity,
                  unitPrice: l.product.sellingPrice,
                  lineTotal: l.lineTotal,
                ))
            .toList(),
        subtotal: _subtotal,
        discount: _discount,
        total: _total,
        paymentType: _paymentType,
        customerName: _selectedCustomer?.name,
        cashierName: cashierName,
      );

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Sale recorded successfully.')),
        );
        await Navigator.push(
          context,
          MaterialPageRoute(builder: (_) => ReceiptPreviewScreen(receipt: receipt)),
        );
        if (mounted) Navigator.pop(context);
      }
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  Future<void> _submitSettlement() async {
    final customer = widget.settlementCustomer!;
    final amount = _settlementAmount;

    if (amount <= 0) {
      setState(() => _error = 'Enter an amount to settle.');
      return;
    }
    if (amount > customer.currentBalance) {
      setState(() => _error = 'Amount cannot exceed the outstanding balance of Rs. ${customer.currentBalance.toStringAsFixed(2)}.');
      return;
    }

    setState(() {
      _isSubmitting = true;
      _error = null;
    });

    try {
      final result = await _customerService.recordPayment(
        customerId: customer.id,
        amount: amount,
        method: _settlementMethod,
      );
      if (!mounted) return;
      final cashierName = context.read<AuthProvider>().currentUser?['name'] as String? ?? '-';
      final paymentId = result['id'];
      final paidAt = result['paid_at'] != null ? DateTime.tryParse(result['paid_at'] as String) : null;

      final receipt = ReceiptData(
        invoiceNumber: 'PMT-$paymentId',
        date: paidAt ?? DateTime.now(),
        lines: [
          ReceiptLine(
            name: 'Credit Settlement',
            quantity: 1,
            unitPrice: amount,
            lineTotal: amount,
          ),
        ],
        subtotal: amount,
        discount: 0,
        total: amount,
        paymentType: 'credit_settlement',
        customerName: customer.name,
        cashierName: cashierName,
      );

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Payment recorded successfully.')),
        );
        await Navigator.push(
          context,
          MaterialPageRoute(builder: (_) => ReceiptPreviewScreen(receipt: receipt)),
        );
        if (mounted) Navigator.pop(context);
      }
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    return Scaffold(
      appBar: AppBar(
        title: Text(_isSettlement ? 'Settle Credit — ${widget.settlementCustomer!.name}' : 'New Sale'),
        actions: [
          if (!_isSettlement && _itemCount > 0)
            Padding(
              padding: const EdgeInsets.only(right: 16),
              child: Center(
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.18),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    '$_itemCount item${_itemCount == 1 ? '' : 's'}',
                    style: const TextStyle(color: Colors.white, fontSize: 12.5, fontWeight: FontWeight.w600),
                  ),
                ),
              ),
            ),
        ],
      ),
      body: _isSettlement ? _buildSettlementBody() : _buildSaleBody(),
    );
  }

  Widget _buildSaleBody() {
    return Column(
      children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
            child: TextField(
              controller: _searchController,
              onChanged: (value) => setState(() => _searchQuery = value),
              decoration: InputDecoration(
                hintText: 'Search products by name or SKU',
                prefixIcon: const Icon(Icons.search_rounded, size: 20),
                suffixIcon: _searchQuery.isEmpty
                    ? null
                    : IconButton(
                        icon: const Icon(Icons.clear_rounded, size: 18),
                        onPressed: () => setState(() {
                          _searchController.clear();
                          _searchQuery = '';
                        }),
                      ),
              ),
            ),
          ),
          Expanded(
            child: _products.isEmpty
                ? const Center(
                    child: Text('No products available.', style: TextStyle(color: AppColors.inkSoft)),
                  )
                : _filteredProducts.isEmpty
                    ? const Center(
                        child: Text('No products match your search.', style: TextStyle(color: AppColors.inkSoft)),
                      )
                    : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: _filteredProducts.length,
                    separatorBuilder: (context, i) => const SizedBox(height: 8),
                    itemBuilder: (context, i) {
                      final p = _filteredProducts[i];
                      final inCart = _cart.containsKey(p.id);
                      final lowStock = p.stockQuantity <= 0;

                      return Card(
                        color: inCart ? AppColors.accentSoft : AppColors.surface,
                        child: InkWell(
                          borderRadius: BorderRadius.circular(14),
                          onTap: lowStock ? null : () => _addToCart(p),
                          child: Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                            child: Row(
                              children: [
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(10),
                                  child: p.imageUrl != null
                                      ? Image.network(
                                          p.imageUrl!,
                                          width: 44,
                                          height: 44,
                                          fit: BoxFit.cover,
                                          errorBuilder: (context, error, stackTrace) => _productPlaceholder(),
                                        )
                                      : _productPlaceholder(),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        p.name,
                                        style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15, color: AppColors.ink),
                                      ),
                                      const SizedBox(height: 3),
                                      Text(
                                        'Rs. ${p.sellingPrice.toStringAsFixed(2)}  ·  Stock: ${p.stockQuantity}',
                                        style: TextStyle(
                                          fontSize: 12.5,
                                          color: lowStock ? AppColors.critical : AppColors.inkSoft,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                Container(
                                  width: 36,
                                  height: 36,
                                  decoration: BoxDecoration(
                                    color: lowStock ? AppColors.lineSoft : AppColors.accent,
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: IconButton(
                                    padding: EdgeInsets.zero,
                                    icon: Icon(
                                      Icons.add_rounded,
                                      size: 20,
                                      color: lowStock ? AppColors.inkSoft : Colors.white,
                                    ),
                                    onPressed: lowStock ? null : () => _addToCart(p),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      );
                    },
                  ),
          ),
          Container(
            decoration: const BoxDecoration(
              color: AppColors.surface,
              border: Border(top: BorderSide(color: AppColors.line)),
            ),
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (_cart.isNotEmpty) ...[
                  ConstrainedBox(
                    constraints: const BoxConstraints(maxHeight: 140),
                    child: SingleChildScrollView(
                      child: Column(
                        children: _cart.values
                            .map((line) => Padding(
                                  padding: const EdgeInsets.only(bottom: 8),
                                  child: Row(
                                    children: [
                                      Expanded(
                                        child: Text(
                                          line.product.name,
                                          style: const TextStyle(fontSize: 13.5, color: AppColors.ink),
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                      _QtyStepper(
                                        quantity: line.quantity,
                                        onDecrement: () => _updateQuantity(line.product.id, -1),
                                        onIncrement: () => _updateQuantity(line.product.id, 1),
                                      ),
                                      SizedBox(
                                        width: 76,
                                        child: Text(
                                          'Rs. ${line.lineTotal.toStringAsFixed(2)}',
                                          textAlign: TextAlign.right,
                                          style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600, color: AppColors.ink),
                                        ),
                                      ),
                                    ],
                                  ),
                                ))
                            .toList(),
                      ),
                    ),
                  ),
                  const Divider(height: 20),
                ],
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: SegmentedButton<String>(
                    segments: _paymentTypeOptions
                        .map((o) => ButtonSegment(
                              value: o.value,
                              label: Text(o.label),
                              icon: Icon(o.icon, size: 16),
                            ))
                        .toList(),
                    selected: {_paymentType},
                    onSelectionChanged: (s) => setState(() => _paymentType = s.first),
                  ),
                ),
                if (_paymentType == 'credit') ...[
                  const SizedBox(height: 10),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: DropdownButtonFormField<Customer>(
                          initialValue: _selectedCustomer,
                          decoration: const InputDecoration(labelText: 'Customer'),
                          items: _customers
                              .map((c) => DropdownMenuItem(
                                    value: c,
                                    child: Text(
                                      c.currentBalance > 0
                                          ? '${c.name} (Owes Rs. ${c.currentBalance.toStringAsFixed(0)})'
                                          : c.name,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ))
                              .toList(),
                          onChanged: (c) => setState(() => _selectedCustomer = c),
                        ),
                      ),
                      if (_selectedCustomer != null) ...[
                        const SizedBox(width: 8),
                        Container(
                          height: 56,
                          width: 44,
                          decoration: BoxDecoration(
                            color: AppColors.goodSoft,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: IconButton(
                            padding: EdgeInsets.zero,
                            icon: const Icon(Icons.payments_outlined, size: 19, color: AppColors.good),
                            tooltip: 'Settle credit',
                            onPressed: () => _openSettleCredit(_selectedCustomer!),
                          ),
                        ),
                      ],
                    ],
                  ),
                ],
                if (_paymentType == 'split') ...[
                  const SizedBox(height: 10),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: AppColors.lineSoft,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Row(
                          children: [
                            const Text('Split Payment', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.ink)),
                            const Spacer(),
                            TextButton(
                              onPressed: _addSplitLine,
                              style: TextButton.styleFrom(padding: EdgeInsets.zero, minimumSize: const Size(0, 0)),
                              child: const Text('+ Add method', style: TextStyle(fontSize: 12.5)),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        ..._splitLines.asMap().entries.map((entry) {
                          final index = entry.key;
                          final line = entry.value;
                          return Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: Row(
                              children: [
                                Expanded(
                                  flex: 3,
                                  child: DropdownButtonFormField<String>(
                                    initialValue: line.method,
                                    isDense: true,
                                    decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10)),
                                    items: _splitMethodOptions
                                        .map((o) => DropdownMenuItem(value: o.value, child: Text(o.label, style: const TextStyle(fontSize: 13))))
                                        .toList(),
                                    onChanged: (v) => setState(() => line.method = v ?? line.method),
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  flex: 2,
                                  child: TextField(
                                    controller: line.amountController,
                                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                    onChanged: (_) => setState(() {}),
                                    decoration: const InputDecoration(
                                      isDense: true,
                                      contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                      hintText: 'Amount',
                                    ),
                                  ),
                                ),
                                if (_splitLines.length > 2)
                                  IconButton(
                                    icon: const Icon(Icons.close_rounded, size: 18),
                                    color: AppColors.critical,
                                    onPressed: () => _removeSplitLine(index),
                                  ),
                              ],
                            ),
                          );
                        }),
                        Text(
                          _splitRemaining == 0
                              ? 'Rs. ${_splitAssigned.toStringAsFixed(2)} assigned'
                              : 'Rs. ${_splitAssigned.toStringAsFixed(2)} of Rs. ${_total.toStringAsFixed(2)} — Rs. ${_splitRemaining.toStringAsFixed(2)} remaining',
                          style: TextStyle(
                            fontSize: 12,
                            color: _splitRemaining == 0 ? AppColors.good : AppColors.inkSoft,
                            fontWeight: _splitRemaining == 0 ? FontWeight.w600 : FontWeight.w400,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
                if (_error != null) ...[
                  const SizedBox(height: 10),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: AppColors.criticalSoft,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(_error!, style: const TextStyle(color: AppColors.critical, fontSize: 13)),
                  ),
                ],
                const SizedBox(height: 10),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    const Text('Discount', style: TextStyle(fontSize: 13.5, color: AppColors.inkSoft)),
                    const SizedBox(width: 12),
                    Expanded(
                      child: TextField(
                        controller: _discountController,
                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        onChanged: (_) => setState(() {}),
                        decoration: const InputDecoration(
                          isDense: true,
                          contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                          hintText: '0.00',
                          prefixText: 'Rs. ',
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Subtotal', style: TextStyle(fontSize: 13, color: AppColors.inkSoft)),
                    Text(
                      'Rs. ${_subtotal.toStringAsFixed(2)}',
                      style: const TextStyle(fontSize: 13, color: AppColors.inkSoft),
                    ),
                  ],
                ),
                if (_discount > 0) ...[
                  const SizedBox(height: 4),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Discount', style: TextStyle(fontSize: 13, color: AppColors.good)),
                      Text(
                        '- Rs. ${_discount.toStringAsFixed(2)}',
                        style: const TextStyle(fontSize: 13, color: AppColors.good),
                      ),
                    ],
                  ),
                ],
                const SizedBox(height: 8),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Total', style: TextStyle(fontSize: 14, color: AppColors.inkSoft)),
                    Text(
                      'Rs. ${_total.toStringAsFixed(2)}',
                      style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w700, color: AppColors.ink),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                SizedBox(
                  height: 50,
                  child: ElevatedButton(
                    onPressed: _isSubmitting ? null : _submitSale,
                    child: _isSubmitting
                        ? const SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                          )
                        : const Text('Complete Sale'),
                  ),
                ),
              ],
            ),
          ),
        ],
    );
  }

  Widget _buildSettlementBody() {
    final customer = widget.settlementCustomer!;
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: AppColors.warnSoft,
              borderRadius: BorderRadius.circular(14),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(customer.name, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 16, color: AppColors.ink)),
                const SizedBox(height: 4),
                Text(
                  'Outstanding balance: Rs. ${customer.currentBalance.toStringAsFixed(2)}',
                  style: const TextStyle(fontSize: 13.5, color: AppColors.inkSoft),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          const Text('Amount to settle', style: TextStyle(fontSize: 13.5, color: AppColors.inkSoft)),
          const SizedBox(height: 8),
          TextField(
            controller: _settlementAmountController,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            onChanged: (_) => setState(() {}),
            decoration: const InputDecoration(prefixText: 'Rs. '),
          ),
          const SizedBox(height: 20),
          const Text('Payment method', style: TextStyle(fontSize: 13.5, color: AppColors.inkSoft)),
          const SizedBox(height: 8),
          SegmentedButton<String>(
            segments: _settlementMethodOptions
                .map((o) => ButtonSegment(
                      value: o.value,
                      label: Text(o.label),
                      icon: Icon(o.icon, size: 16),
                    ))
                .toList(),
            selected: {_settlementMethod},
            onSelectionChanged: (s) => setState(() => _settlementMethod = s.first),
          ),
          if (_error != null) ...[
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: AppColors.criticalSoft,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(_error!, style: const TextStyle(color: AppColors.critical, fontSize: 13)),
            ),
          ],
          const SizedBox(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('Total', style: TextStyle(fontSize: 14, color: AppColors.inkSoft)),
              Text(
                'Rs. ${_settlementAmount.toStringAsFixed(2)}',
                style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w700, color: AppColors.ink),
              ),
            ],
          ),
          const SizedBox(height: 16),
          SizedBox(
            height: 50,
            child: ElevatedButton(
              onPressed: _isSubmitting ? null : _submitSale,
              child: _isSubmitting
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : const Text('Complete Settlement'),
            ),
          ),
        ],
      ),
    );
  }
}

class _QtyStepper extends StatelessWidget {
  final int quantity;
  final VoidCallback onDecrement;
  final VoidCallback onIncrement;

  const _QtyStepper({
    required this.quantity,
    required this.onDecrement,
    required this.onIncrement,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.lineSoft,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          _stepBtn(Icons.remove_rounded, onDecrement),
          SizedBox(
            width: 24,
            child: Text('$quantity', textAlign: TextAlign.center, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
          ),
          _stepBtn(Icons.add_rounded, onIncrement),
        ],
      ),
    );
  }

  Widget _stepBtn(IconData icon, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Padding(
        padding: const EdgeInsets.all(6),
        child: Icon(icon, size: 15, color: AppColors.inkSoft),
      ),
    );
  }
}
