import 'dart:typed_data';
import 'dart:ui' as ui;
import 'package:esc_pos_utils_plus/esc_pos_utils_plus.dart';
import 'package:flutter/rendering.dart';
import 'package:flutter/widgets.dart';
import 'package:image/image.dart' as img;
import 'package:print_bluetooth_thermal/print_bluetooth_thermal.dart';

class PrinterDevice {
  final String name;
  final String address;

  PrinterDevice({required this.name, required this.address});
}

class BluetoothPrinterService {
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

  /// Captures [boundaryKey]'s widget as a bitmap and prints it as an ESC/POS
  /// raster image, so Sinhala Unicode text renders correctly regardless of
  /// the printer's built-in font support.
  Future<bool> printReceipt(GlobalKey boundaryKey, {String paperSize = 'mm58'}) async {
    final boundary = boundaryKey.currentContext!.findRenderObject() as RenderRepaintBoundary;
    final uiImage = await boundary.toImage(pixelRatio: 2.0);
    final byteData = await uiImage.toByteData(format: ui.ImageByteFormat.png);
    final pngBytes = byteData!.buffer.asUint8List();

    final decoded = img.decodePng(pngBytes)!;
    final profile = await CapabilityProfile.load();
    final generator = Generator(paperSize == 'mm80' ? PaperSize.mm80 : PaperSize.mm58, profile);

    final bytes = <int>[];
    bytes.addAll(generator.reset());
    bytes.addAll(generator.imageRaster(decoded));
    bytes.addAll(generator.feed(2));
    bytes.addAll(generator.cut());

    return PrintBluetoothThermal.writeBytes(Uint8List.fromList(bytes));
  }
}
