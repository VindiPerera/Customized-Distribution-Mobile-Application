class SaleItemInput {
  final int productId;
  final int quantity;

  SaleItemInput({required this.productId, required this.quantity});

  Map<String, dynamic> toJson() => {
        'product_id': productId,
        'quantity': quantity,
      };
}

class SalePaymentInput {
  final String method;
  final double amount;

  SalePaymentInput({required this.method, required this.amount});

  Map<String, dynamic> toJson() => {
        'method': method,
        'amount': amount,
      };
}

class Sale {
  final int id;
  final String invoiceNumber;
  final String paymentType;
  final double totalAmount;
  final DateTime saleDate;

  Sale({
    required this.id,
    required this.invoiceNumber,
    required this.paymentType,
    required this.totalAmount,
    required this.saleDate,
  });

  factory Sale.fromJson(Map<String, dynamic> json) {
    return Sale(
      id: json['id'],
      invoiceNumber: json['invoice_number'],
      paymentType: json['payment_type'],
      totalAmount: double.parse(json['total_amount'].toString()),
      saleDate: DateTime.parse(json['sale_date']),
    );
  }
}
