import 'dart:typed_data';
import 'package:esc_pos_utils_plus/esc_pos_utils_plus.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:print_bluetooth_thermal/print_bluetooth_thermal.dart';
import '../models/receipt_data.dart';
import '../models/shop_settings.dart';

const String _receiptWhatsapp = '072 665 0786';
const String _receiptEmail = 'info.damsascreations@gmail.com';

const Map<String, String> _paymentLabels = {
  'cash': 'Cash',
  'card': 'Card',
  'credit': 'Credit',
  'split': 'Split',
  'bank_transfer': 'Bank Transfer',
  'credit_settlement': 'Credit Settlement',
};

class PrinterDevice {
  final String name;
  final String address;

  PrinterDevice({required this.name, required this.address});
}

class BluetoothPrinterService {
  /// Android 12+ (API 31+) treats BLUETOOTH_CONNECT/SCAN as runtime
  /// permissions: declaring them in the manifest alone does nothing, they
  /// must be explicitly granted or every connect/write call silently fails
  /// even though the printer shows up as paired at the OS level.
  Future<bool> requestPermissions() async {
    final statuses = await [
      Permission.bluetoothConnect,
      Permission.bluetoothScan,
    ].request();
    return statuses.values.every((s) => s.isGranted);
  }

  Future<bool> isBluetoothEnabled() => PrintBluetoothThermal.bluetoothEnabled;

  Future<List<PrinterDevice>> listPairedDevices() async {
    final results = await PrintBluetoothThermal.pairedBluetooths;
    return results.map((d) => PrinterDevice(name: d.name, address: d.macAdress)).toList();
  }

  Future<bool> connect(String address) {
    return PrintBluetoothThermal.connect(macPrinterAddress: address);
  }

  Future<bool> get isConnected => PrintBluetoothThermal.connectionStatus;

  Future<void> disconnect() => PrintBluetoothThermal.disconnect;

  /// Prints the receipt using the printer's built-in font via native
  /// ESC/POS text commands (English/Latin only - no font rendering, no
  /// image raster, no printer-dot-width pitfalls).
  Future<bool> printReceipt(ReceiptData receipt, ShopSettings shop) async {
    late final CapabilityProfile profile;
    late final Generator generator;
    try {
      profile = await CapabilityProfile.load();
      generator = Generator(shop.paperSize == 'mm80' ? PaperSize.mm80 : PaperSize.mm58, profile);
    } catch (e, st) {
      throw Exception('[stage:generatorInit] $e\n$st');
    }

    final bytes = <int>[];
    try {
      bytes.addAll(generator.reset());
      bytes.addAll(_buildHeader(generator, shop));
      bytes.addAll(_buildMeta(generator, receipt));
      bytes.addAll(_buildItemsTable(generator, receipt));
      bytes.addAll(_buildTotals(generator, receipt));
      bytes.addAll(_buildFooter(generator, shop));
      bytes.addAll(generator.feed(2));
      bytes.addAll(generator.cut());
    } catch (e, st) {
      throw Exception('[stage:build] $e\n$st');
    }

    try {
      return await PrintBluetoothThermal.writeBytes(Uint8List.fromList(bytes));
    } catch (e, st) {
      throw Exception('[stage:writeBytes] $e\n$st');
    }
  }

  List<int> _buildHeader(Generator generator, ShopSettings shop) {
    final bytes = <int>[];
    bytes.addAll(generator.text(
      shop.name,
      styles: const PosStyles(align: PosAlign.center, bold: true, height: PosTextSize.size2, width: PosTextSize.size2),
    ));
    if (shop.address.isNotEmpty) {
      bytes.addAll(generator.text(shop.address, styles: const PosStyles(align: PosAlign.center)));
    }
    if (shop.phone.isNotEmpty) {
      bytes.addAll(generator.text(shop.phone, styles: const PosStyles(align: PosAlign.center)));
    }
    bytes.addAll(generator.text('WhatsApp: $_receiptWhatsapp', styles: const PosStyles(align: PosAlign.center)));
    bytes.addAll(generator.text(_receiptEmail, styles: const PosStyles(align: PosAlign.center)));
    if (shop.taxId.isNotEmpty) {
      bytes.addAll(generator.text(shop.taxId, styles: const PosStyles(align: PosAlign.center)));
    }
    bytes.addAll(generator.hr(linesAfter: 1));
    return bytes;
  }

  List<int> _buildMeta(Generator generator, ReceiptData receipt) {
    final bytes = <int>[];
    final dateStr =
        '${receipt.date.year}-${receipt.date.month.toString().padLeft(2, '0')}-${receipt.date.day.toString().padLeft(2, '0')}  '
        '${receipt.date.hour.toString().padLeft(2, '0')}:${receipt.date.minute.toString().padLeft(2, '0')}';

    bytes.addAll(_kv(generator, 'Invoice', receipt.invoiceNumber));
    bytes.addAll(_kv(generator, 'Date', dateStr));
    bytes.addAll(_kv(generator, 'Payment', _paymentLabel(receipt.paymentType)));
    if (receipt.customerName != null) {
      bytes.addAll(_kv(generator, 'Customer', receipt.customerName!));
    }
    bytes.addAll(_kv(generator, 'Cashier', receipt.cashierName));
    bytes.addAll(generator.hr(linesAfter: 1));
    return bytes;
  }

  List<int> _buildItemsTable(Generator generator, ReceiptData receipt) {
    final bytes = <int>[];
    bytes.addAll(generator.row([
      PosColumn(text: 'Item', width: 6, styles: const PosStyles(bold: true)),
      PosColumn(text: 'Qty', width: 2, styles: const PosStyles(align: PosAlign.right, bold: true)),
      PosColumn(text: 'Amount', width: 4, styles: const PosStyles(align: PosAlign.right, bold: true)),
    ]));
    bytes.addAll(generator.hr(linesAfter: 1));

    for (final line in receipt.lines) {
      bytes.addAll(generator.text(line.name));
      bytes.addAll(generator.row([
        PosColumn(text: 'Rs. ${line.unitPrice.toStringAsFixed(2)} each', width: 6),
        PosColumn(text: 'x${line.quantity}', width: 2, styles: const PosStyles(align: PosAlign.right)),
        PosColumn(text: 'Rs. ${line.lineTotal.toStringAsFixed(2)}', width: 4, styles: const PosStyles(align: PosAlign.right)),
      ]));
    }
    bytes.addAll(generator.hr(linesAfter: 1));
    return bytes;
  }

  List<int> _buildTotals(Generator generator, ReceiptData receipt) {
    final bytes = <int>[];
    if (receipt.discount > 0) {
      bytes.addAll(_kv(generator, 'Subtotal', 'Rs. ${receipt.subtotal.toStringAsFixed(2)}'));
      bytes.addAll(_kv(generator, 'Discount', '- Rs. ${receipt.discount.toStringAsFixed(2)}'));
    }
    bytes.addAll(generator.row([
      PosColumn(text: 'TOTAL', width: 6, styles: const PosStyles(bold: true, height: PosTextSize.size2)),
      PosColumn(
        text: 'Rs. ${receipt.total.toStringAsFixed(2)}',
        width: 6,
        styles: const PosStyles(align: PosAlign.right, bold: true, height: PosTextSize.size2),
      ),
    ]));
    return bytes;
  }

  List<int> _buildFooter(Generator generator, ShopSettings shop) {
    if (shop.footerNote.isEmpty) return const [];
    return generator.text(shop.footerNote, styles: const PosStyles(align: PosAlign.center), linesAfter: 1);
  }

  List<int> _kv(Generator generator, String label, String value) {
    return generator.row([
      PosColumn(text: label, width: 5),
      PosColumn(text: value, width: 7, styles: const PosStyles(align: PosAlign.right)),
    ]);
  }

  String _paymentLabel(String type) {
    return _paymentLabels[type] ?? (type.isEmpty ? type : type[0].toUpperCase() + type.substring(1));
  }
}
