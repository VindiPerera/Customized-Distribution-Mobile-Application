class SaleDetailItem {
  final String productName;
  final int quantity;
  final double unitPrice;
  final double discountPercent;
  final double discountedPrice;
  final double lineTotal;

  SaleDetailItem({
    required this.productName,
    required this.quantity,
    required this.unitPrice,
    this.discountPercent = 0,
    required this.discountedPrice,
    required this.lineTotal,
  });

  factory SaleDetailItem.fromJson(Map<String, dynamic> json) {
    final unitPrice = double.parse(json['unit_price'].toString());
    return SaleDetailItem(
      productName: json['product']['name'],
      quantity: json['quantity'],
      unitPrice: unitPrice,
      discountPercent: double.parse((json['discount_percent'] ?? 0).toString()),
      discountedPrice: double.parse((json['discounted_price'] ?? unitPrice).toString()),
      lineTotal: double.parse(json['line_total'].toString()),
    );
  }
}

class SaleDetail {
  final int id;
  final String invoiceNumber;
  final String paymentType;
  final String? paymentReference;
  final double subtotal;
  final double totalAmount;
  final DateTime saleDate;
  final String? customerName;
  final String? customerPhone;
  final String cashierName;
  final List<SaleDetailItem> items;

  SaleDetail({
    required this.id,
    required this.invoiceNumber,
    required this.paymentType,
    this.paymentReference,
    required this.subtotal,
    required this.totalAmount,
    required this.saleDate,
    this.customerName,
    this.customerPhone,
    required this.cashierName,
    required this.items,
  });

  factory SaleDetail.fromJson(Map<String, dynamic> json) {
    return SaleDetail(
      id: json['id'],
      invoiceNumber: json['invoice_number'],
      paymentType: json['payment_type'],
      paymentReference: json['payment_reference'],
      subtotal: double.parse((json['subtotal'] ?? json['total_amount']).toString()),
      totalAmount: double.parse(json['total_amount'].toString()),
      saleDate: DateTime.parse(json['sale_date']),
      customerName: json['customer'] != null ? json['customer']['name'] : null,
      customerPhone: json['customer'] != null ? json['customer']['phone'] : null,
      cashierName: json['user'] != null ? json['user']['name'] : '-',
      items: (json['items'] as List).map((e) => SaleDetailItem.fromJson(e)).toList(),
    );
  }
}
