import 'sale_detail.dart';

class ReceiptLine {
  final String name;
  final int quantity;
  final double unitPrice;
  final double discountedPrice;
  final double lineTotal;

  ReceiptLine({
    required this.name,
    required this.quantity,
    required this.unitPrice,
    required this.discountedPrice,
    required this.lineTotal,
  });
}

class ReceiptData {
  final String invoiceNumber;
  final DateTime date;
  final List<ReceiptLine> lines;
  final double subtotal;
  final double total;
  final String paymentType;
  final String? customerName;
  final String? customerPhone;
  final String cashierName;

  ReceiptData({
    required this.invoiceNumber,
    required this.date,
    required this.lines,
    required this.subtotal,
    required this.total,
    required this.paymentType,
    this.customerName,
    this.customerPhone,
    required this.cashierName,
  });

  factory ReceiptData.fromSaleDetail(SaleDetail sale) {
    return ReceiptData(
      invoiceNumber: sale.invoiceNumber,
      date: sale.saleDate,
      lines: sale.items
          .map((i) => ReceiptLine(
                name: i.productName,
                quantity: i.quantity,
                unitPrice: i.unitPrice,
                discountedPrice: i.discountedPrice,
                lineTotal: i.lineTotal,
              ))
          .toList(),
      subtotal: sale.subtotal,
      total: sale.totalAmount,
      paymentType: sale.paymentType,
      customerName: sale.customerName,
      customerPhone: sale.customerPhone,
      cashierName: sale.cashierName,
    );
  }
}
