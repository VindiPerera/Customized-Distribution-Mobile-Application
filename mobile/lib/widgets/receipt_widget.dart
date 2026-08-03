import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/receipt_data.dart';
import '../models/shop_settings.dart';
import '../services/api_client.dart';

/// Contact details printed on every receipt below the shop phone number.
/// Static because they're fixed business contact info, not a per-shop
/// setting configurable via the admin panel.
const String _receiptWhatsapp = '072 665 0786';
const String _receiptEmail = 'info.damsascreations@gmail.com';

/// Labels for the receipt's fixed chrome text (everything that isn't shop
/// or sale data), keyed by [ShopSettings.receiptLanguage].
const Map<String, Map<String, String>> _receiptStrings = {
  'en': {
    'invoice': 'Invoice',
    'date': 'Date',
    'payment': 'Payment',
    'customer': 'Customer',
    'cashier': 'Cashier',
    'item': 'Item',
    'qty': 'Qty',
    'amount': 'Amount',
    'each': 'each',
    'subtotal': 'Subtotal',
    'discount': 'Discount',
    'total': 'TOTAL',
    'cash': 'Cash',
    'card': 'Card',
    'credit': 'Credit',
    'split': 'Split',
    'bank_transfer': 'Bank Transfer',
    'credit_settlement': 'Credit Settlement',
  },
  'si': {
    'invoice': 'ඉන්වොයිසිය',
    'date': 'දිනය',
    'payment': 'ගෙවීම',
    'customer': 'පාරිභෝගිකයා',
    'cashier': 'කැෂියර්',
    'item': 'භාණ්ඩය',
    'qty': 'ප්‍රමාණය',
    'amount': 'මුදල',
    'each': 'බැගින්',
    'subtotal': 'උප එකතුව',
    'discount': 'වට්ටම',
    'total': 'එකතුව',
    'cash': 'මුදල්',
    'card': 'කාඩ්පත',
    'credit': 'ණය',
    'split': 'බෙදුම',
    'bank_transfer': 'බැංකු මාරුව',
    'credit_settlement': 'ණය පියවීම',
  },
};

/// Renders a printable receipt sized to the shop's configured thermal paper
/// width (58mm = 384px or 80mm = 576px at 203dpi) and in the shop's
/// configured receipt language. Uses NotoSansSinhala throughout so Sinhala
/// product/shop names and labels print correctly, since most thermal
/// printer firmware has no Sinhala font.
class ReceiptWidget extends StatelessWidget {
  static const double _width58mm = 384;
  // 576 dots (72mm at 203dpi) is the Xprinter P801A's actual printhead
  // width even on 80mm paper stock - the spec sheet lists a 72mm print
  // width on 80mm paper, so a small margin on each side is a physical
  // property of the printer, not a bug. Sending anything wider than 576
  // exceeds the printhead and gets clamped/distorted by the printer instead
  // of actually using more of the paper.
  static const double _width80mm = 576;

  final ShopSettings shop;
  final ReceiptData receipt;

  const ReceiptWidget({super.key, required this.shop, required this.receipt});

  static double widthPxFor(String paperSize) => paperSize == 'mm80' ? _width80mm : _width58mm;

  double get _widthPx => widthPxFor(shop.paperSize);

  /// 80mm paper has more room, so labels scale up a bit rather than just
  /// leaving extra whitespace on wider stock.
  double get _scale => shop.paperSize == 'mm80' ? 2.0 : 1.15;

  /// Fixed (not scaled) horizontal padding: scaling padding along with text
  /// eats into the width gain from a bigger _scale, leaving the printed
  /// content looking small relative to the paper even though nothing is
  /// clipped.
  double get _hPadding => 10;

  Map<String, String> get _t => _receiptStrings[shop.receiptLanguage] ?? _receiptStrings['en']!;

  /// Bold by default: thermal printing (especially via image raster for
  /// Sinhala) washes out thin/regular-weight strokes, so everything needs
  /// to be at least semi-bold to stay legible on paper even though it looks
  /// heavier than necessary on screen.
  TextStyle get _mono => TextStyle(
        fontFamily: 'NotoSansSinhala',
        color: Colors.black,
        fontSize: 15 * _scale,
        fontWeight: FontWeight.w600,
        height: 1.3,
      );

  @override
  Widget build(BuildContext context) {
    final dateFmt = DateFormat('yyyy-MM-dd  HH:mm');
    final mono = _mono;
    final t = _t;

    return Container(
      width: _widthPx,
      color: Colors.white,
      padding: EdgeInsets.symmetric(horizontal: _hPadding, vertical: 14 * _scale),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Center(
            child: Image.network(
              ApiClient.logoUrl,
              width: 64 * _scale,
              height: 64 * _scale,
              fit: BoxFit.cover,
              errorBuilder: (context, error, stackTrace) => const SizedBox.shrink(),
            ),
          ),
          SizedBox(height: 6 * _scale),
          Text(
            shop.name,
            textAlign: TextAlign.center,
            style: mono.copyWith(fontSize: 22 * _scale, fontWeight: FontWeight.w900),
          ),
          if (shop.address.isNotEmpty) ...[
            SizedBox(height: 2 * _scale),
            Text(shop.address, textAlign: TextAlign.center, style: mono.copyWith(fontSize: 13 * _scale)),
          ],
          if (shop.phone.isNotEmpty) ...[
            SizedBox(height: 2 * _scale),
            Text(shop.phone, textAlign: TextAlign.center, style: mono.copyWith(fontSize: 13 * _scale)),
          ],
          SizedBox(height: 2 * _scale),
          Text('WhatsApp: $_receiptWhatsapp', textAlign: TextAlign.center, style: mono.copyWith(fontSize: 13 * _scale)),
          SizedBox(height: 2 * _scale),
          Text(_receiptEmail, textAlign: TextAlign.center, style: mono.copyWith(fontSize: 13 * _scale)),
          if (shop.taxId.isNotEmpty) ...[
            SizedBox(height: 2 * _scale),
            Text(shop.taxId, textAlign: TextAlign.center, style: mono.copyWith(fontSize: 13 * _scale)),
          ],
          SizedBox(height: 10 * _scale),
          const _DashedDivider(),
          SizedBox(height: 6 * _scale),
          _kv(t['invoice']!, receipt.invoiceNumber),
          _kv(t['date']!, dateFmt.format(receipt.date)),
          _kv(t['payment']!, _paymentLabel(receipt.paymentType)),
          if (receipt.customerName != null) _kv(t['customer']!, receipt.customerName!),
          _kv(t['cashier']!, receipt.cashierName),
          SizedBox(height: 6 * _scale),
          const _DashedDivider(),
          SizedBox(height: 6 * _scale),
          Row(
            children: [
              Expanded(
                flex: 4,
                child: Text(t['item']!, style: mono.copyWith(fontWeight: FontWeight.w900), softWrap: false, overflow: TextOverflow.visible),
              ),
              Expanded(
                flex: 3,
                child: Text(
                  t['qty']!,
                  textAlign: TextAlign.right,
                  style: mono.copyWith(fontWeight: FontWeight.w900),
                  softWrap: false,
                  overflow: TextOverflow.visible,
                ),
              ),
              Expanded(
                flex: 3,
                child: Text(t['amount']!, textAlign: TextAlign.right, style: mono.copyWith(fontWeight: FontWeight.w900), softWrap: false),
              ),
            ],
          ),
          SizedBox(height: 4 * _scale),
          const _DashedDivider(),
          SizedBox(height: 6 * _scale),
          for (final line in receipt.lines) ...[
            _ReceiptLineRow(line: line, mono: mono, eachLabel: t['each']!, scale: _scale),
            SizedBox(height: 4 * _scale),
          ],
          SizedBox(height: 4 * _scale),
          const _DashedDivider(),
          SizedBox(height: 8 * _scale),
          if (receipt.discount > 0) ...[
            _kv(t['subtotal']!, 'Rs. ${receipt.subtotal.toStringAsFixed(2)}'),
            _kv(t['discount']!, '- Rs. ${receipt.discount.toStringAsFixed(2)}'),
            SizedBox(height: 4 * _scale),
          ],
          Row(
            children: [
              Text(t['total']!, style: mono.copyWith(fontSize: 20 * _scale, fontWeight: FontWeight.w900)),
              const Spacer(),
              Text(
                'Rs. ${receipt.total.toStringAsFixed(2)}',
                style: mono.copyWith(fontSize: 20 * _scale, fontWeight: FontWeight.w900),
              ),
            ],
          ),
          SizedBox(height: 14 * _scale),
          if (shop.footerNote.isNotEmpty)
            Text(shop.footerNote, textAlign: TextAlign.center, style: mono.copyWith(fontSize: 13 * _scale)),
          SizedBox(height: 4 * _scale),
        ],
      ),
    );
  }

  String _paymentLabel(String type) {
    return _t[type] ?? (type.isEmpty ? type : type[0].toUpperCase() + type.substring(1));
  }

  Widget _kv(String label, String value) {
    final mono = _mono;
    return Padding(
      padding: EdgeInsets.only(bottom: 2 * _scale),
      child: Row(
        children: [
          Text('$label:', style: mono.copyWith(fontSize: 14 * _scale)),
          SizedBox(width: 6 * _scale),
          Expanded(
            child: Text(value, style: mono.copyWith(fontSize: 14 * _scale), textAlign: TextAlign.right),
          ),
        ],
      ),
    );
  }
}

class _ReceiptLineRow extends StatelessWidget {
  final ReceiptLine line;
  final TextStyle mono;
  final String eachLabel;
  final double scale;

  const _ReceiptLineRow({required this.line, required this.mono, required this.eachLabel, required this.scale});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(line.name, style: mono.copyWith(fontSize: 15 * scale, fontWeight: FontWeight.w700)),
        Row(
          children: [
            Expanded(
              flex: 4,
              child: Text(
                'Rs. ${line.unitPrice.toStringAsFixed(2)} $eachLabel',
                style: mono.copyWith(fontSize: 13.5 * scale),
              ),
            ),
            Expanded(
              flex: 3,
              child: Text('x${line.quantity}', textAlign: TextAlign.right, style: mono.copyWith(fontSize: 14 * scale)),
            ),
            Expanded(
              flex: 3,
              child: Text(
                'Rs. ${line.lineTotal.toStringAsFixed(2)}',
                textAlign: TextAlign.right,
                style: mono.copyWith(fontSize: 14 * scale, fontWeight: FontWeight.w800),
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
