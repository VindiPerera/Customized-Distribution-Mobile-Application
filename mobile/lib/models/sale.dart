class SaleItemInput {
  final int productId;
  final int quantity;
  final double discountPercent;

  SaleItemInput({required this.productId, required this.quantity, this.discountPercent = 0});

  Map<String, dynamic> toJson() => {
        'product_id': productId,
        'quantity': quantity,
        'discount_percent': discountPercent,
      };
}

class Sale {
  final int id;
  final String invoiceNumber;
  final String paymentType;
  final double totalAmount;
  final DateTime saleDate;
  final String? customerName;
  final String? customerPhone;

  Sale({
    required this.id,
    required this.invoiceNumber,
    required this.paymentType,
    required this.totalAmount,
    required this.saleDate,
    this.customerName,
    this.customerPhone,
  });

  factory Sale.fromJson(Map<String, dynamic> json) {
    return Sale(
      id: json['id'],
      invoiceNumber: json['invoice_number'],
      paymentType: json['payment_type'],
      totalAmount: double.parse(json['total_amount'].toString()),
      saleDate: DateTime.parse(json['sale_date']),
      customerName: json['customer'] != null ? json['customer']['name'] : null,
      customerPhone: json['customer'] != null ? json['customer']['phone'] : null,
    );
  }
}
