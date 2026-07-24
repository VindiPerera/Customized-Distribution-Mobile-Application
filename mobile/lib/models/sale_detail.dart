class SaleDetailItem {
  final String productName;
  final int quantity;
  final double unitPrice;
  final double lineTotal;

  SaleDetailItem({
    required this.productName,
    required this.quantity,
    required this.unitPrice,
    required this.lineTotal,
  });

  factory SaleDetailItem.fromJson(Map<String, dynamic> json) {
    return SaleDetailItem(
      productName: json['product']['name'],
      quantity: json['quantity'],
      unitPrice: double.parse(json['unit_price'].toString()),
      lineTotal: double.parse(json['line_total'].toString()),
    );
  }
}

class SaleDetail {
  final int id;
  final String invoiceNumber;
  final String paymentType;
  final double totalAmount;
  final DateTime saleDate;
  final String? customerName;
  final String cashierName;
  final List<SaleDetailItem> items;

  SaleDetail({
    required this.id,
    required this.invoiceNumber,
    required this.paymentType,
    required this.totalAmount,
    required this.saleDate,
    this.customerName,
    required this.cashierName,
    required this.items,
  });

  factory SaleDetail.fromJson(Map<String, dynamic> json) {
    return SaleDetail(
      id: json['id'],
      invoiceNumber: json['invoice_number'],
      paymentType: json['payment_type'],
      totalAmount: double.parse(json['total_amount'].toString()),
      saleDate: DateTime.parse(json['sale_date']),
      customerName: json['customer'] != null ? json['customer']['name'] : null,
      cashierName: json['user'] != null ? json['user']['name'] : '-',
      items: (json['items'] as List).map((e) => SaleDetailItem.fromJson(e)).toList(),
    );
  }
}
