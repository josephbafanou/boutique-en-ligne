import 'package:flutter/foundation.dart';

import '../models/cart.dart';
import '../services/api_client.dart';

class CartProvider extends ChangeNotifier {
  CartProvider(this.api);

  final ApiClient api;
  Cart cart = Cart.empty;
  bool loading = false;

  Future<void> refresh() => _run(api.cart);

  Future<void> add(int productId) => _run(() => api.addToCart(productId));

  Future<void> setQuantity(int itemId, int quantity) => quantity <= 0
      ? _run(() => api.removeCartItem(itemId))
      : _run(() => api.updateCartItem(itemId, quantity));

  void clear() {
    cart = Cart.empty;
    notifyListeners();
  }

  Future<void> _run(Future<Cart> Function() action) async {
    loading = true;
    notifyListeners();
    try {
      cart = await action();
    } finally {
      loading = false;
      notifyListeners();
    }
  }
}
