class OrderLine {
  final String productName;
  final double unitPrice;
  final int quantity;

  const OrderLine({
    required this.productName,
    required this.unitPrice,
    required this.quantity,
  });

  factory OrderLine.fromJson(Map<String, dynamic> json) => OrderLine(
        productName: json['product_name'] as String,
        unitPrice: (json['unit_price'] as num).toDouble(),
        quantity: json['quantity'] as int,
      );
}

class Order {
  final int id;
  final String status;
  final double total;
  final DateTime? createdAt;
  final List<OrderLine> items;

  const Order({
    required this.id,
    required this.status,
    required this.total,
    required this.items,
    this.createdAt,
  });

  static const statusLabels = {
    'pending': 'En attente',
    'paid': 'Payée',
    'shipped': 'Expédiée',
    'delivered': 'Livrée',
    'cancelled': 'Annulée',
  };

  String get statusLabel => statusLabels[status] ?? status;

  factory Order.fromJson(Map<String, dynamic> json) => Order(
        id: json['id'] as int,
        status: json['status'] as String,
        total: (json['total'] as num).toDouble(),
        createdAt: json['created_at'] == null
            ? null
            : DateTime.parse(json['created_at'] as String),
        items: ((json['items'] as List?) ?? [])
            .map((e) => OrderLine.fromJson(e as Map<String, dynamic>))
            .toList(),
      );
}
