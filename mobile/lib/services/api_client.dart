import 'dart:convert';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiException implements Exception {
  final int statusCode;
  final String message;

  ApiException(this.statusCode, this.message);

  @override
  String toString() => message;
}

class ApiClient {
  // Web/desktop can reach the host directly at localhost. The Android
  // emulator's host loopback is 10.0.2.2 instead. Change baseUrl for a
  // real device or production deployment (e.g. https://yourdomain.com/api).
  static final String baseUrl = kIsWeb ? 'http://127.0.0.1:8000/api' : 'http://10.0.2.2:8000/api';

  static const _tokenKey = 'auth_token';

  Future<String?> get token async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }

  Future<void> clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }

  Future<Map<String, String>> _headers({bool withAuth = true}) async {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (withAuth) {
      final t = await token;
      if (t != null) headers['Authorization'] = 'Bearer $t';
    }
    return headers;
  }

  Future<dynamic> get(String path) async {
    final res = await http.get(Uri.parse('$baseUrl$path'), headers: await _headers());
    return _handle(res);
  }

  Future<dynamic> post(String path, Map<String, dynamic> body, {bool withAuth = true}) async {
    final res = await http.post(
      Uri.parse('$baseUrl$path'),
      headers: await _headers(withAuth: withAuth),
      body: jsonEncode(body),
    );
    return _handle(res);
  }

  Future<dynamic> put(String path, Map<String, dynamic> body) async {
    final res = await http.put(
      Uri.parse('$baseUrl$path'),
      headers: await _headers(),
      body: jsonEncode(body),
    );
    return _handle(res);
  }

  Future<dynamic> delete(String path) async {
    final res = await http.delete(Uri.parse('$baseUrl$path'), headers: await _headers());
    return _handle(res);
  }

  dynamic _handle(http.Response res) {
    final body = res.body.isNotEmpty ? jsonDecode(res.body) : null;

    if (res.statusCode >= 200 && res.statusCode < 300) {
      return body;
    }

    final message = body is Map && body['message'] != null
        ? body['message'] as String
        : 'Request failed with status ${res.statusCode}';

    throw ApiException(res.statusCode, message);
  }
}
