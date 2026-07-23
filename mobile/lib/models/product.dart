class Product {
  final int id;
  final String sku;
  final String name;
  final String unit;
  final double sellingPrice;
  final int stockQuantity;
  final String? imageUrl;

  Product({
    required this.id,
    required this.sku,
    required this.name,
    required this.unit,
    required this.sellingPrice,
    required this.stockQuantity,
    this.imageUrl,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: json['id'],
      sku: json['sku'],
      name: json['name'],
      unit: json['unit'] ?? 'pcs',
      sellingPrice: double.parse(json['selling_price'].toString()),
      stockQuantity: json['stock_quantity'] ?? 0,
      imageUrl: json['image_url'],
    );
  }
}
