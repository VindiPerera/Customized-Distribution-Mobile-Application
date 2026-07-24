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

  static const mock = ShopSettings(
    name: 'Jaan Retail Store',
    address: '123 Main Street, Colombo',
    phone: '011-2345678',
    taxId: 'TIN: 000000000',
    footerNote: 'Thank you! Come again.',
  );

  ShopSettings copyWith({
    String? name,
    String? address,
    String? phone,
    String? taxId,
    String? footerNote,
  }) {
    return ShopSettings(
      name: name ?? this.name,
      address: address ?? this.address,
      phone: phone ?? this.phone,
      taxId: taxId ?? this.taxId,
      footerNote: footerNote ?? this.footerNote,
    );
  }

  Map<String, String> toJson() => {
        'name': name,
        'address': address,
        'phone': phone,
        'taxId': taxId,
        'footerNote': footerNote,
      };

  factory ShopSettings.fromJson(Map<String, dynamic> json) {
    return ShopSettings(
      name: json['name'] ?? mock.name,
      address: json['address'] ?? mock.address,
      phone: json['phone'] ?? mock.phone,
      taxId: json['taxId'] ?? mock.taxId,
      footerNote: json['footerNote'] ?? mock.footerNote,
    );
  }
}
