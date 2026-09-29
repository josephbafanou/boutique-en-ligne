import 'product.dart';

class CartItem {
  final int id;
  final int quantity;
  final double subtotal;
  final Product product;

  const CartItem({
    required this.id,
    required this.quantity,
    required this.subtotal,
    required this.product,
  });

  factory CartItem.fromJson(Map<String, dynamic> json) => CartItem(
        id: json['id'] as int,
        quantity: json['quantity'] as int,
        subtotal: (json['subtotal'] as num).toDouble(),
        product: Product.fromJson(json['product'] as Map<String, dynamic>),
      );
}

class Cart {
  final List<CartItem> items;
  final int itemsCount;
  final double total;

  const Cart({required this.items, required this.itemsCount, required this.total});

  static const empty = Cart(items: [], itemsCount: 0, total: 0);

  factory Cart.fromJson(Map<String, dynamic> json) => Cart(
        items: (json['items'] as List)
            .map((e) => CartItem.fromJson(e as Map<String, dynamic>))
            .toList(),
        itemsCount: json['items_count'] as int,
        total: (json['total'] as num).toDouble(),
      );
}
