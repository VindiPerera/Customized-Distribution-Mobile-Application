import 'dart:typed_data';
import 'dart:ui' as ui;
import 'package:esc_pos_utils_plus/esc_pos_utils_plus.dart';
import 'package:flutter/rendering.dart';
import 'package:flutter/widgets.dart';
import 'package:image/image.dart' as img;
import 'package:permission_handler/permission_handler.dart';
import 'package:print_bluetooth_thermal/print_bluetooth_thermal.dart';

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

  /// Captures [boundaryKey]'s widget as a bitmap and prints it as an ESC/POS
  /// raster image, so Sinhala Unicode text renders correctly regardless of
  /// the printer's built-in font support.
  Future<bool> printReceipt(GlobalKey boundaryKey, {String paperSize = 'mm58'}) async {
    final boundary = boundaryKey.currentContext!.findRenderObject() as RenderRepaintBoundary;
    // pixelRatio must stay 1.0: ReceiptWidget's width (384/576) is chosen to
    // map 1:1 to printer dots, and imageRaster() doesn't resize the bitmap,
    // so any higher ratio sends a raster line wider than the paper's dot
    // width and the printer silently drops it.
    final ui.Image uiImage;
    try {
      uiImage = await boundary.toImage(pixelRatio: 1.0);
    } catch (e, st) {
      throw Exception('[stage:capture] $e\n$st');
    }

    final Uint8List pngBytes;
    try {
      final byteData = await uiImage.toByteData(format: ui.ImageByteFormat.png);
      pngBytes = byteData!.buffer.asUint8List();
    } catch (e, st) {
      throw Exception('[stage:encodePng] $e\n$st');
    }

    late final img.Image decoded;
    try {
      decoded = img.decodePng(pngBytes)!;
    } catch (e, st) {
      throw Exception('[stage:decodePng] $e\n$st');
    }

    late final CapabilityProfile profile;
    late final Generator generator;
    try {
      profile = await CapabilityProfile.load();
      generator = Generator(paperSize == 'mm80' ? PaperSize.mm80 : PaperSize.mm58, profile);
    } catch (e, st) {
      throw Exception('[stage:generatorInit] $e\n$st');
    }

    final bytes = <int>[];
    try {
      bytes.addAll(generator.reset());
    } catch (e, st) {
      throw Exception('[stage:reset] $e\n$st');
    }
    try {
      bytes.addAll(generator.imageRaster(decoded));
    } catch (e, st) {
      throw Exception('[stage:imageRaster] $e\n$st');
    }
    try {
      bytes.addAll(generator.feed(2));
    } catch (e, st) {
      throw Exception('[stage:feed] $e\n$st');
    }
    try {
      bytes.addAll(generator.cut());
    } catch (e, st) {
      throw Exception('[stage:cut] $e\n$st');
    }

    try {
      return await PrintBluetoothThermal.writeBytes(Uint8List.fromList(bytes));
    } catch (e, st) {
      throw Exception('[stage:writeBytes] $e\n$st');
    }
  }
}
