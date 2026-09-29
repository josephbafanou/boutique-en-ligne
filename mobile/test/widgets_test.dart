import 'package:ecommerce_app/models/cart.dart';
import 'package:ecommerce_app/models/order.dart';
import 'package:ecommerce_app/models/product.dart';
import 'package:ecommerce_app/widgets/product_card.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  testWidgets('ProductCard affiche le nom, le prix et la rupture de stock',
      (tester) async {
    const product = Product(id: 1, name: 'Savon noir', slug: 'savon-noir', price: 12.5, stock: 0);

    await tester.pumpWidget(const MaterialApp(
      home: Scaffold(
        body: SizedBox(width: 200, height: 300, child: ProductCard(product: product)),
      ),
    ));

    expect(find.text('Savon noir'), findsOneWidget);
    expect(find.textContaining('12,50'), findsOneWidget);
    expect(find.text('Rupture de stock'), findsOneWidget);
  });

  test('Cart.fromJson calcule les sous-totaux renvoyés par l\'API', () {
    final cart = Cart.fromJson({
      'items': [
        {
          'id': 7,
          'quantity': 2,
          'subtotal': 25,
          'product': {'id': 1, 'name': 'Pagne', 'slug': 'pagne', 'price': 12.5, 'stock': 4},
        },
      ],
      'items_count': 2,
      'total': 25,
    });

    expect(cart.items.single.subtotal, 25.0);
    expect(cart.total, 25.0);
  });

  test('Order traduit le statut en français', () {
    final order = Order.fromJson({
      'id': 3,
      'status': 'shipped',
      'total': 40.5,
      'created_at': '2026-09-29T10:00:00+00:00',
      'items': [
        {'product_name': 'Sac', 'unit_price': 40.5, 'quantity': 1},
      ],
    });

    expect(order.statusLabel, 'Expédiée');
    expect(order.items.single.productName, 'Sac');
  });
}
