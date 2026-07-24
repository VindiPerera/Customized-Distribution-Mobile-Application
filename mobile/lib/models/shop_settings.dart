class ShopSettings {
  final String name;
  final String address;
  final String phone;
  final String taxId;
  final String footerNote;

  const ShopSettings({
    required this.name,
    required this.address,
    required this.phone,
    required this.taxId,
    required this.footerNote,
  });

  static const fallback = ShopSettings(
    name: 'Your Shop',
    address: '',
    phone: '',
    taxId: '',
    footerNote: 'Thank you! Come again.',
  );

  factory ShopSettings.fromJson(Map<String, dynamic> json) {
    return ShopSettings(
      name: (json['name'] as String?)?.isNotEmpty == true ? json['name'] : fallback.name,
      address: json['address'] ?? '',
      phone: json['phone'] ?? '',
      taxId: json['tax_id'] ?? '',
      footerNote: json['footer_note'] ?? '',
    );
  }
}
