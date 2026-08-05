import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/customer.dart';
import '../models/customer_category.dart';
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

enum DiscountType { percent, amount }

class _CartLine {
  final Product product;
  int quantity;
  DiscountType discountType;

  /// Per-line discount value, entered at billing time.
  final TextEditingController discountController;

  _CartLine(this.product, this.quantity, {double discountPercent = 0, this.discountType = DiscountType.percent})
      : discountController = TextEditingController(
          text: discountPercent > 0 ? _trimZeros(discountPercent) : '',
        );

  static String _trimZeros(double v) {
    final s = v.toStringAsFixed(2);
    return s.replaceFirst(RegExp(r'\.?0+$'), '');
  }

  double get discountValue => double.tryParse(discountController.text) ?? 0;

  double get discountPercent {
    final raw = discountValue;
    if (discountType == DiscountType.percent) {
      return raw < 0 ? 0 : (raw > 100 ? 100 : raw);
    } else {
      if (product.sellingPrice <= 0) return 0;
      final pct = (raw / product.sellingPrice) * 100;
      return pct < 0 ? 0 : (pct > 100 ? 100 : pct);
    }
  }

  double get discountedPrice {
    if (discountType == DiscountType.percent) {
      return product.sellingPrice * (1 - discountPercent / 100);
    } else {
      final val = discountValue;
      final price = product.sellingPrice - val;
      return price < 0 ? 0 : price;
    }
  }

  double get lineTotal => discountedPrice * quantity;

  void dispose() => discountController.dispose();
}

const _paymentTypeOptions = [
  (value: 'cash', label: 'Cash', icon: Icons.payments_outlined),
  (value: 'credit', label: 'Credit', icon: Icons.receipt_long_outlined),
  (value: 'cheque', label: 'Cheque', icon: Icons.request_quote_outlined),
];

const _settlementMethodOptions = [
  (value: 'cash', label: 'Cash', icon: Icons.payments_outlined),
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
  List<CustomerCategory> _categories = [];
  CustomerCategory? _categoryFilter;
  final Map<int, _CartLine> _cart = {};

  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';
  int? _productCategoryFilter;

  final TextEditingController _paidAmountController = TextEditingController();
  final TextEditingController _paymentReferenceController = TextEditingController();

  String _paymentType = 'cash';
  Customer? _selectedCustomer;
  bool _isLoading = true;
  bool _isSubmitting = false;
  String? _error;

  late final TextEditingController _settlementAmountController;
  final String _settlementMethod = 'cash';

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
    _paidAmountController.dispose();
    _paymentReferenceController.dispose();
    _settlementAmountController.dispose();
    for (final line in _cart.values) {
      line.dispose();
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
    List<CustomerCategory> categories = [];
    try {
      categories = await _customerService.categories();
    } catch (_) {
      // Category filter is optional — skip silently if unavailable.
    }
    setState(() {
      _products = products;
      _customers = customers;
      _categories = categories;
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
        line.dispose();
        _cart.remove(productId);
      } else {
        line.quantity = newQty;
      }
    });
  }

  double get _settlementAmount => double.tryParse(_settlementAmountController.text) ?? 0;

  double get _subtotal => _isSettlement ? _settlementAmount : _cart.values.fold(0, (sum, line) => sum + line.lineTotal);
  int get _itemCount => _isSettlement ? (_settlementAmount > 0 ? 1 : 0) : _cart.values.fold(0, (sum, line) => sum + line.quantity);

  double get _total => _subtotal;

  /// Credit sales are never paid up front (the whole total becomes the
  /// customer's balance). Cash sales are normally paid in full, but the
  /// cashier can enter a smaller amount if the customer only pays part now.
  bool get _allowsPartialPayment => _paymentType == 'cash';

  /// Amount the customer actually handed over right now. Defaults to the
  /// full total when the field is blank (untouched) so a normal, fully-paid
  /// sale doesn't require the cashier to type anything.
  double get _paidAmount {
    if (!_allowsPartialPayment) return 0;
    final text = _paidAmountController.text.trim();
    if (text.isEmpty) return _total;
    final parsed = double.tryParse(text) ?? _total;
    return parsed < 0 ? 0 : (parsed > _total ? _total : parsed);
  }

  /// Unpaid portion of this sale, tracked on the customer's account balance.
  double get _balanceDue => _paymentType == 'credit' ? _total : ((_total - _paidAmount) * 100).round() / 100;

  /// Distinct product categories present in the loaded catalog, sorted by
  /// name. Derived from the products themselves (each already carries its
  /// category via the `/products` response) instead of a separate API call.
  List<({int id, String name})> get _productCategories {
    final seen = <int, String>{};
    for (final p in _products) {
      if (p.categoryId != null && p.categoryName != null) {
        seen[p.categoryId!] = p.categoryName!;
      }
    }
    final entries = seen.entries.map((e) => (id: e.key, name: e.value)).toList();
    entries.sort((a, b) => a.name.toLowerCase().compareTo(b.name.toLowerCase()));
    return entries;
  }

  List<Product> get _filteredProducts {
    final query = _searchQuery.trim().toLowerCase();
    return _products.where((p) {
      final matchesQuery = query.isEmpty || p.name.toLowerCase().contains(query) || p.sku.toLowerCase().contains(query);
      final matchesCategory = _productCategoryFilter == null || p.categoryId == _productCategoryFilter;
      return matchesQuery && matchesCategory;
    }).toList();
  }

  List<Customer> _filterCustomers(String query, {CustomerCategory? category}) {
    final q = query.trim().toLowerCase();
    return _customers.where((c) {
      final matchesQuery = q.isEmpty || c.name.toLowerCase().contains(q) || (c.phone ?? '').toLowerCase().contains(q);
      final matchesCategory = category == null || c.categoryId == category.id;
      return matchesQuery && matchesCategory;
    }).toList();
  }

  Widget _productPlaceholder() {
    return Container(
      width: 44,
      height: 44,
      color: AppColors.lineSoft,
      alignment: Alignment.center,
      child: const Icon(Icons.inventory_2_outlined, size: 20, color: AppColors.inkSoft),
    );
  }

  Future<void> _pickCustomer() async {
    final searchController = TextEditingController();
    CustomerCategory? sheetCategoryFilter = _categoryFilter;
    final picked = await showModalBottomSheet<Customer>(
      context: context,
      isScrollControlled: true,
      builder: (sheetContext) {
        return StatefulBuilder(
          builder: (sheetContext, setSheetState) {
            final query = searchController.text;
            final results = _filterCustomers(query, category: sheetCategoryFilter);
            return Padding(
              padding: EdgeInsets.only(bottom: MediaQuery.of(sheetContext).viewInsets.bottom),
              child: SizedBox(
                height: MediaQuery.of(sheetContext).size.height * 0.75,
                child: Column(
                  children: [
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                      child: Row(
                        children: [
                          const Expanded(
                            child: Text('Select Customer', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.ink)),
                          ),
                          IconButton(
                            icon: const Icon(Icons.close_rounded),
                            onPressed: () => Navigator.pop(sheetContext),
                          ),
                        ],
                      ),
                    ),
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: TextField(
                        controller: searchController,
                        autofocus: true,
                        onChanged: (_) => setSheetState(() {}),
                        decoration: InputDecoration(
                          hintText: 'Search by name or phone',
                          prefixIcon: const Icon(Icons.search_rounded, size: 20),
                          suffixIcon: searchController.text.isEmpty
                              ? null
                              : IconButton(
                                  icon: const Icon(Icons.clear_rounded, size: 18),
                                  onPressed: () => setSheetState(() => searchController.clear()),
                                ),
                        ),
                      ),
                    ),
                    if (_categories.isNotEmpty) ...[
                      const SizedBox(height: 10),
                      SizedBox(
                        height: 34,
                        child: ListView(
                          scrollDirection: Axis.horizontal,
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          children: [
                            _CategoryChip(
                              label: 'All',
                              selected: sheetCategoryFilter == null,
                              onTap: () => setSheetState(() {
                                sheetCategoryFilter = null;
                                _categoryFilter = null;
                              }),
                            ),
                            const SizedBox(width: 8),
                            for (final cat in _categories) ...[
                              _CategoryChip(
                                label: cat.name,
                                selected: sheetCategoryFilter?.id == cat.id,
                                onTap: () => setSheetState(() {
                                  sheetCategoryFilter = cat;
                                  _categoryFilter = cat;
                                }),
                              ),
                              const SizedBox(width: 8),
                            ],
                          ],
                        ),
                      ),
                    ],
                    const SizedBox(height: 8),
                    Expanded(
                      child: results.isEmpty
                          ? const Center(
                              child: Text('No customers match your search.', style: TextStyle(color: AppColors.inkSoft)),
                            )
                          : ListView.separated(
                              padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                              itemCount: results.length,
                              separatorBuilder: (_, __) => const SizedBox(height: 8),
                              itemBuilder: (context, i) {
                                final c = results[i];
                                return Card(
                                  child: ListTile(
                                    title: Text(c.name, style: const TextStyle(fontWeight: FontWeight.w600)),
                                    subtitle: Text(
                                      [
                                        if (c.phone != null && c.phone!.isNotEmpty) c.phone!,
                                        if (c.categoryName != null) c.categoryName!,
                                        if (c.currentBalance > 0) 'Owes Rs. ${c.currentBalance.toStringAsFixed(0)}',
                                      ].join('  ·  '),
                                    ),
                                    onTap: () => Navigator.pop(sheetContext, c),
                                  ),
                                );
                              },
                            ),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
    searchController.dispose();
    if (picked != null) {
      setState(() => _selectedCustomer = picked);
    }
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

  Future<void> _submitSale() {
    return _isSettlement ? _submitSettlement() : _submitRegularSale();
  }

  Future<void> _submitRegularSale() async {
    if (_cart.isEmpty) {
      setState(() => _error = 'Add at least one item.');
      return;
    }
    if (_selectedCustomer == null) {
      setState(() => _error = 'Select a customer to issue the bill.');
      return;
    }

    setState(() {
      _isSubmitting = true;
      _error = null;
    });

    try {
      final result = await _saleService.createSale(
        customerId: _selectedCustomer!.id,
        paymentType: _paymentType,
        paymentReference: _paymentReferenceController.text.trim(),
        paidAmount: _allowsPartialPayment ? _paidAmount : null,
        items: _cart.values
            .map((l) => SaleItemInput(productId: l.product.id, quantity: l.quantity, discountPercent: l.discountPercent))
            .toList(),
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
                  discountedPrice: l.discountedPrice,
                  lineTotal: l.lineTotal,
                ))
            .toList(),
        subtotal: _subtotal,
        total: _total,
        paymentType: _paymentType,
        customerName: _selectedCustomer?.name,
        customerPhone: _selectedCustomer?.phone,
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
            discountedPrice: amount,
            lineTotal: amount,
          ),
        ],
        subtotal: amount,
        total: amount,
        paymentType: 'credit_settlement',
        customerName: customer.name,
        customerPhone: customer.phone,
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
          if (_productCategories.isNotEmpty) ...[
            const SizedBox(height: 10),
            SizedBox(
              height: 34,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                children: [
                  _CategoryChip(
                    label: 'All',
                    selected: _productCategoryFilter == null,
                    onTap: () => setState(() => _productCategoryFilter = null),
                  ),
                  const SizedBox(width: 8),
                  for (final cat in _productCategories) ...[
                    _CategoryChip(
                      label: cat.name,
                      selected: _productCategoryFilter == cat.id,
                      onTap: () => setState(() => _productCategoryFilter = cat.id),
                    ),
                    const SizedBox(width: 8),
                  ],
                ],
              ),
            ),
          ],
          Expanded(
            child: _products.isEmpty
                ? const Center(
                    child: Text('No products available.', style: TextStyle(color: AppColors.inkSoft)),
                  )
                : _filteredProducts.isEmpty
                    ? const Center(
                        child: Text('No products match your filters.', style: TextStyle(color: AppColors.inkSoft)),
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
                  Row(
                    children: const [
                      Expanded(flex: 4, child: Text('Product', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.inkSoft))),
                      SizedBox(
                        width: 72,
                        child: Text('Discount', textAlign: TextAlign.center, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.inkSoft)),
                      ),
                      SizedBox(width: 6),
                      SizedBox(
                        width: 60,
                        child: Text('Price', textAlign: TextAlign.right, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.inkSoft)),
                      ),
                      SizedBox(width: 6),
                      SizedBox(
                        width: 66,
                        child: Text('Total', textAlign: TextAlign.right, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.inkSoft)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  ConstrainedBox(
                    constraints: const BoxConstraints(maxHeight: 180),
                    child: SingleChildScrollView(
                      child: Column(
                        children: _cart.values
                            .map((line) => Padding(
                                  padding: const EdgeInsets.only(bottom: 8),
                                  child: Row(
                                    crossAxisAlignment: CrossAxisAlignment.center,
                                    children: [
                                      Expanded(
                                        flex: 4,
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              line.product.name,
                                              style: const TextStyle(fontSize: 13.5, color: AppColors.ink),
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                            _QtyStepper(
                                              quantity: line.quantity,
                                              onDecrement: () => _updateQuantity(line.product.id, -1),
                                              onIncrement: () => _updateQuantity(line.product.id, 1),
                                            ),
                                          ],
                                        ),
                                      ),
                                      SizedBox(
                                        width: 72,
                                        child: TextField(
                                          controller: line.discountController,
                                          keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                          textAlign: TextAlign.center,
                                          onChanged: (_) => setState(() {}),
                                          decoration: InputDecoration(
                                            isDense: true,
                                            contentPadding: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
                                            hintText: '0',
                                            suffixIcon: GestureDetector(
                                              onTap: () {
                                                setState(() {
                                                  line.discountType = line.discountType == DiscountType.percent
                                                      ? DiscountType.amount
                                                      : DiscountType.percent;
                                                });
                                              },
                                              child: Container(
                                                padding: const EdgeInsets.only(right: 6),
                                                alignment: Alignment.centerRight,
                                                width: 24,
                                                child: Text(
                                                  line.discountType == DiscountType.percent ? '%' : 'Rs',
                                                  style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppColors.accent),
                                                ),
                                              ),
                                            ),
                                            suffixIconConstraints: const BoxConstraints(minWidth: 24, minHeight: 0),
                                          ),
                                        ),
                                      ),
                                      const SizedBox(width: 6),
                                      SizedBox(
                                        width: 60,
                                        child: Text(
                                          line.discountedPrice.toStringAsFixed(2),
                                          textAlign: TextAlign.right,
                                          style: const TextStyle(fontSize: 12.5, color: AppColors.inkSoft),
                                        ),
                                      ),
                                      const SizedBox(width: 6),
                                      SizedBox(
                                        width: 66,
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
                    onSelectionChanged: (s) => setState(() {
                      _paymentType = s.first;
                      if (_paymentType != 'cheque') {
                        _paymentReferenceController.clear();
                      }
                    }),
                  ),
                ),
                if (_paymentType == 'cheque') ...[
                  const SizedBox(height: 10),
                  TextField(
                    controller: _paymentReferenceController,
                    decoration: const InputDecoration(
                      labelText: 'Cheque Details / Reference',
                      hintText: 'e.g. Cheque No: 123456 BOC',
                      prefixIcon: Icon(Icons.description_outlined, size: 20),
                    ),
                  ),
                ],
                const SizedBox(height: 10),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: InkWell(
                        borderRadius: BorderRadius.circular(12),
                        onTap: _pickCustomer,
                        child: InputDecorator(
                          decoration: InputDecoration(
                            label: const RequiredLabel('Customer'),
                            prefixIcon: const Icon(Icons.person_search_rounded, size: 20),
                          ),
                          child: Text(
                            _selectedCustomer == null
                                ? 'Search customer by name or phone'
                                : _selectedCustomer!.phone != null && _selectedCustomer!.phone!.isNotEmpty
                                    ? '${_selectedCustomer!.name}  ·  ${_selectedCustomer!.phone}'
                                    : _selectedCustomer!.name,
                            style: TextStyle(
                              color: _selectedCustomer == null ? AppColors.inkSoft : AppColors.ink,
                              fontWeight: _selectedCustomer == null ? FontWeight.w400 : FontWeight.w600,
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ),
                    ),
                    if (_selectedCustomer != null && _selectedCustomer!.currentBalance > 0) ...[
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
                if (_allowsPartialPayment) ...[
                  const SizedBox(height: 10),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      const Text('Customer Paid', style: TextStyle(fontSize: 13.5, color: AppColors.inkSoft)),
                      const SizedBox(width: 12),
                      Expanded(
                        child: TextField(
                          controller: _paidAmountController,
                          keyboardType: const TextInputType.numberWithOptions(decimal: true),
                          onChanged: (_) => setState(() {}),
                          decoration: InputDecoration(
                            isDense: true,
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                            hintText: _total.toStringAsFixed(2),
                            prefixText: 'Rs. ',
                          ),
                        ),
                      ),
                    ],
                  ),
                  if (_balanceDue > 0) ...[
                    const SizedBox(height: 6),
                    Text(
                      'Rs. ${_balanceDue.toStringAsFixed(2)} will be added to ${_selectedCustomer?.name ?? 'the customer\'s'} balance.',
                      style: const TextStyle(fontSize: 12, color: AppColors.warn, fontWeight: FontWeight.w600),
                    ),
                  ],
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
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Total', style: TextStyle(fontSize: 14, color: AppColors.inkSoft)),
                    Text(
                      'Rs. ${_total.toStringAsFixed(2)}',
                      style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w700, color: AppColors.ink),
                    ),
                  ],
                ),
                if (_paymentType == 'credit') ...[
                  const SizedBox(height: 4),
                  const Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('Balance due', style: TextStyle(fontSize: 13, color: AppColors.warn, fontWeight: FontWeight.w600)),
                      Text('Full amount', style: TextStyle(fontSize: 13, color: AppColors.warn, fontWeight: FontWeight.w600)),
                    ],
                  ),
                ] else if (_allowsPartialPayment && _balanceDue > 0) ...[
                  const SizedBox(height: 4),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Paid now', style: TextStyle(fontSize: 13, color: AppColors.inkSoft)),
                      Text(
                        'Rs. ${_paidAmount.toStringAsFixed(2)}',
                        style: const TextStyle(fontSize: 13, color: AppColors.inkSoft),
                      ),
                    ],
                  ),
                  const SizedBox(height: 2),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Balance due', style: TextStyle(fontSize: 13, color: AppColors.warn, fontWeight: FontWeight.w600)),
                      Text(
                        'Rs. ${_balanceDue.toStringAsFixed(2)}',
                        style: const TextStyle(fontSize: 13, color: AppColors.warn, fontWeight: FontWeight.w600),
                      ),
                    ],
                  ),
                ],
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
            onSelectionChanged: (s) {},
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

class _CategoryChip extends StatelessWidget {
  final String label;
  final bool selected;
  final VoidCallback onTap;

  const _CategoryChip({required this.label, required this.selected, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(17),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
        decoration: BoxDecoration(
          color: selected ? AppColors.accent : AppColors.lineSoft,
          borderRadius: BorderRadius.circular(17),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12.5,
            fontWeight: FontWeight.w600,
            color: selected ? Colors.white : AppColors.inkSoft,
          ),
        ),
      ),
    );
  }
}
