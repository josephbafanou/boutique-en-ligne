import 'dart:convert';

import 'package:ecommerce_app/services/api_client.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

Map<String, dynamic> productJson({int id = 1, double price = 12.5, int stock = 3}) => {
      'id': id,
      'name': 'Pagne wax',
      'slug': 'pagne-wax',
      'description': null,
      'price': price,
      'stock': stock,
      'in_stock': stock > 0,
      'image_url': null,
      'category': {'id': 1, 'name': 'Mode', 'slug': 'mode'},
    };

void main() {
  test('products() lit la liste paginée et envoie les filtres', () async {
    late Uri calledUri;
    final api = ApiClient(
      baseUrl: 'http://api.test/api',
      client: MockClient((request) async {
        calledUri = request.url;
        return http.Response(
          jsonEncode({
            'data': [productJson(), productJson(id: 2, stock: 0)],
          }),
          200,
        );
      }),
    );

    final products = await api.products(category: 'mode', search: 'wax');

    expect(calledUri.path, '/api/products');
    expect(calledUri.queryParameters['category'], 'mode');
    expect(calledUri.queryParameters['search'], 'wax');
    expect(products, hasLength(2));
    expect(products.first.category?.name, 'Mode');
    expect(products.last.inStock, isFalse);
  });

  test('login() stocke le token et l\'envoie ensuite', () async {
    final seenAuth = <String?>[];
    final api = ApiClient(
      baseUrl: 'http://api.test/api',
      client: MockClient((request) async {
        seenAuth.add(request.headers['Authorization']);
        if (request.url.path.endsWith('/auth/login')) {
          return http.Response(jsonEncode({'token': 'abc', 'user': {}}), 200);
        }
        return http.Response(
          jsonEncode({'items': [], 'items_count': 0, 'total': 0}),
          200,
        );
      }),
    );

    await api.login('jody@example.com', 'secret123');
    final cart = await api.cart();

    expect(api.token, 'abc');
    expect(seenAuth, [null, 'Bearer abc']);
    expect(cart.items, isEmpty);
  });

  test('les erreurs de validation Laravel deviennent une ApiException lisible', () async {
    final api = ApiClient(
      baseUrl: 'http://api.test/api',
      client: MockClient((_) async => http.Response(
            jsonEncode({
              'message': 'The given data was invalid.',
              'errors': {
                'quantity': ['Il ne reste que 1 exemplaire(s) de « Pagne wax ».'],
              },
            }),
            422,
          )),
    );

    expect(
      () => api.addToCart(1, quantity: 5),
      throwsA(isA<ApiException>()
          .having((e) => e.statusCode, 'statusCode', 422)
          .having((e) => e.message, 'message', contains('Il ne reste que 1'))),
    );
  });
}
