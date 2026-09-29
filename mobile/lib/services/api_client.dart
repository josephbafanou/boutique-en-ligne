import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config.dart';
import '../models/cart.dart';
import '../models/order.dart';
import '../models/product.dart';

class ApiException implements Exception {
  final int statusCode;
  final String message;

  const ApiException(this.statusCode, this.message);

  @override
  String toString() => message;
}

/// Client HTTP de l'API Laravel. Le [http.Client] est injectable pour les tests.
class ApiClient {
  ApiClient({http.Client? client, this.baseUrl = apiUrl})
      : _client = client ?? http.Client();

  final http.Client _client;
  final String baseUrl;
  String? token;

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  Future<dynamic> _send(String method, String path,
      {Map<String, dynamic>? body, Map<String, String>? query}) async {
    final uri = Uri.parse('$baseUrl$path').replace(queryParameters: query);
    final request = http.Request(method, uri)..headers.addAll(_headers);
    if (body != null) request.body = jsonEncode(body);

    final response = await http.Response.fromStream(await _client.send(request));
    final decoded = response.body.isEmpty ? null : jsonDecode(response.body);

    if (response.statusCode >= 400) {
      throw ApiException(response.statusCode, _errorMessage(decoded));
    }
    return decoded;
  }

  /// Récupère le premier message d'erreur de validation Laravel, sinon le message global.
  static String _errorMessage(dynamic body) {
    if (body is Map<String, dynamic>) {
      final errors = body['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) return first.first.toString();
      }
      if (body['message'] is String) return body['message'] as String;
    }
    return 'Une erreur est survenue.';
  }

  // --- Authentification -------------------------------------------------

  Future<String> login(String email, String password) async {
    final data = await _send('POST', '/auth/login',
        body: {'email': email, 'password': password});
    return token = data['token'] as String;
  }

  Future<String> register(String name, String email, String password) async {
    final data = await _send('POST', '/auth/register', body: {
      'name': name,
      'email': email,
      'password': password,
      'password_confirmation': password,
    });
    return token = data['token'] as String;
  }

  Future<void> logout() async {
    await _send('POST', '/auth/logout');
    token = null;
  }

  // --- Catalogue --------------------------------------------------------

  Future<List<Category>> categories() async {
    final data = await _send('GET', '/categories') as List;
    return data.map((e) => Category.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<List<Product>> products({String? category, String? search}) async {
    final data = await _send('GET', '/products', query: {
      if (category != null) 'category': category,
      if (search != null && search.isNotEmpty) 'search': search,
      'per_page': '50',
    });
    return (data['data'] as List)
        .map((e) => Product.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  // --- Panier -----------------------------------------------------------

  Future<Cart> cart() async =>
      Cart.fromJson(await _send('GET', '/cart') as Map<String, dynamic>);

  Future<Cart> addToCart(int productId, {int quantity = 1}) async => Cart.fromJson(
      await _send('POST', '/cart/items',
          body: {'product_id': productId, 'quantity': quantity}) as Map<String, dynamic>);

  Future<Cart> updateCartItem(int itemId, int quantity) async => Cart.fromJson(
      await _send('PATCH', '/cart/items/$itemId', body: {'quantity': quantity})
          as Map<String, dynamic>);

  Future<Cart> removeCartItem(int itemId) async => Cart.fromJson(
      await _send('DELETE', '/cart/items/$itemId') as Map<String, dynamic>);

  // --- Commandes --------------------------------------------------------

  Future<Order> checkout(String shippingAddress) async => Order.fromJson(
      await _send('POST', '/orders', body: {'shipping_address': shippingAddress})
          as Map<String, dynamic>);

  Future<List<Order>> orders() async {
    final data = await _send('GET', '/orders');
    return (data['data'] as List)
        .map((e) => Order.fromJson(e as Map<String, dynamic>))
        .toList();
  }
}
