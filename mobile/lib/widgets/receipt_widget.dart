import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/receipt_data.dart';
import '../models/shop_settings.dart';

/// Renders a printable receipt at a fixed 384px width (58mm / 203dpi thermal
/// printer). Uses NotoSansSinhala throughout so Sinhala product/shop names
/// print correctly, since most thermal printer firmware has no Sinhala font.
class ReceiptWidget extends StatelessWidget {
  static const double widthPx = 384;

  final ShopSettings shop;
  final ReceiptData receipt;

  const ReceiptWidget({super.key, required this.shop, required this.receipt});

  static const _mono = TextStyle(
    fontFamily: 'NotoSansSinhala',
    color: Colors.black,
    fontSize: 15,
    height: 1.3,
  );

  @override
  Widget build(BuildContext context) {
    final dateFmt = DateFormat('yyyy-MM-dd  HH:mm');

    return Container(
      width: widthPx,
      color: Colors.white,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            shop.name,
            textAlign: TextAlign.center,
            style: _mono.copyWith(fontSize: 20, fontWeight: FontWeight.w700),
          ),
          if (shop.address.isNotEmpty) ...[
            const SizedBox(height: 2),
            Text(shop.address, textAlign: TextAlign.center, style: _mono.copyWith(fontSize: 13)),
          ],
          if (shop.phone.isNotEmpty) ...[
            const SizedBox(height: 2),
            Text(shop.phone, textAlign: TextAlign.center, style: _mono.copyWith(fontSize: 13)),
          ],
          if (shop.taxId.isNotEmpty) ...[
            const SizedBox(height: 2),
            Text(shop.taxId, textAlign: TextAlign.center, style: _mono.copyWith(fontSize: 13)),
          ],
          const SizedBox(height: 10),
          const _DashedDivider(),
          const SizedBox(height: 6),
          _kv('Invoice', receipt.invoiceNumber),
          _kv('Date', dateFmt.format(receipt.date)),
          _kv('Payment', _paymentLabel(receipt.paymentType)),
          if (receipt.customerName != null) _kv('Customer', receipt.customerName!),
          _kv('Cashier', receipt.cashierName),
          const SizedBox(height: 6),
          const _DashedDivider(),
          const SizedBox(height: 6),
          Row(
            children: [
              Expanded(flex: 5, child: Text('Item', style: _mono.copyWith(fontWeight: FontWeight.w700))),
              Expanded(flex: 2, child: Text('Qty', textAlign: TextAlign.right, style: _mono.copyWith(fontWeight: FontWeight.w700))),
              Expanded(flex: 3, child: Text('Amount', textAlign: TextAlign.right, style: _mono.copyWith(fontWeight: FontWeight.w700))),
            ],
          ),
          const SizedBox(height: 4),
          const _DashedDivider(),
          const SizedBox(height: 6),
          for (final line in receipt.lines) ...[
            _ReceiptLineRow(line: line),
            const SizedBox(height: 4),
          ],
          const SizedBox(height: 4),
          const _DashedDivider(),
          const SizedBox(height: 8),
          Row(
            children: [
              Text('TOTAL', style: _mono.copyWith(fontSize: 18, fontWeight: FontWeight.w700)),
              const Spacer(),
              Text(
                'Rs. ${receipt.total.toStringAsFixed(2)}',
                style: _mono.copyWith(fontSize: 18, fontWeight: FontWeight.w700),
              ),
            ],
          ),
          const SizedBox(height: 14),
          if (shop.footerNote.isNotEmpty)
            Text(shop.footerNote, textAlign: TextAlign.center, style: _mono.copyWith(fontSize: 13)),
          const SizedBox(height: 4),
        ],
      ),
    );
  }

  String _paymentLabel(String type) {
    switch (type) {
      case 'bank_transfer':
        return 'Bank Transfer';
      case 'cash':
      case 'card':
      case 'credit':
      case 'split':
        return type[0].toUpperCase() + type.substring(1);
      default:
        return type;
    }
  }

  Widget _kv(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 2),
      child: Row(
        children: [
          Text('$label:', style: _mono.copyWith(fontSize: 13, color: Colors.black87)),
          const SizedBox(width: 6),
          Expanded(
            child: Text(value, style: _mono.copyWith(fontSize: 13), textAlign: TextAlign.right),
          ),
        ],
      ),
    );
  }
}

class _ReceiptLineRow extends StatelessWidget {
  final ReceiptLine line;

  const _ReceiptLineRow({required this.line});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(line.name, style: ReceiptWidget._mono.copyWith(fontSize: 14)),
        Row(
          children: [
            Expanded(
              flex: 5,
              child: Text(
                'Rs. ${line.unitPrice.toStringAsFixed(2)} each',
                style: ReceiptWidget._mono.copyWith(fontSize: 12.5, color: Colors.black54),
              ),
            ),
            Expanded(
              flex: 2,
              child: Text('x${line.quantity}', textAlign: TextAlign.right, style: ReceiptWidget._mono.copyWith(fontSize: 13)),
            ),
            Expanded(
              flex: 3,
              child: Text(
                'Rs. ${line.lineTotal.toStringAsFixed(2)}',
                textAlign: TextAlign.right,
                style: ReceiptWidget._mono.copyWith(fontSize: 13, fontWeight: FontWeight.w600),
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _DashedDivider extends StatelessWidget {
  const _DashedDivider();

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        const dashWidth = 4.0;
        const dashSpace = 3.0;
        final count = (constraints.maxWidth / (dashWidth + dashSpace)).floor();
        return Row(
          children: List.generate(
            count,
            (_) => const Padding(
              padding: EdgeInsets.symmetric(horizontal: dashSpace / 2),
              child: SizedBox(width: dashWidth, height: 1.4, child: ColoredBox(color: Colors.black)),
            ),
          ),
        );
      },
    );
  }
}
