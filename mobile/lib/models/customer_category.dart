class CustomerCategory {
  final int id;
  final String name;

  CustomerCategory({required this.id, required this.name});

  factory CustomerCategory.fromJson(Map<String, dynamic> json) {
    return CustomerCategory(
      id: json['id'],
      name: json['name'],
    );
  }
}
