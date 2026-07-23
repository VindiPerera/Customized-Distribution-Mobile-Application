class Customer {
  final int id;
  final String name;
  final String? phone;
  final String? email;
  final String? address;
  final double creditLimit;
  final double currentBalance;
  final bool isActive;

  Customer({
    required this.id,
    required this.name,
    this.phone,
    this.email,
    this.address,
    required this.creditLimit,
    required this.currentBalance,
    required this.isActive,
  });

  double get availableCredit => creditLimit - currentBalance;

  factory Customer.fromJson(Map<String, dynamic> json) {
    return Customer(
      id: json['id'],
      name: json['name'],
      phone: json['phone'],
      email: json['email'],
      address: json['address'],
      creditLimit: double.parse(json['credit_limit'].toString()),
      currentBalance: double.parse(json['current_balance'].toString()),
      isActive: json['is_active'] ?? true,
    );
  }
}
