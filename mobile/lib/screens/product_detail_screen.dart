import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/product.dart';
import '../state/auth_provider.dart';
import '../state/cart_provider.dart';
import '../widgets/price.dart';
import 'login_screen.dart';

class ProductDetailScreen extends StatelessWidget {
  const ProductDetailScreen({super.key, required this.product});

  final Product product;

  Future<void> _addToCart(BuildContext context) async {
    final messenger = ScaffoldMessenger.of(context);
    if (!context.read<AuthProvider>().isLoggedIn) {
      await Navigator.push(
        context,
        MaterialPageRoute(builder: (_) => const LoginScreen()),
      );
      if (!context.mounted || !context.read<AuthProvider>().isLoggedIn) return;
    }
    try {
      await context.read<CartProvider>().add(product.id);
      messenger.showSnackBar(
        SnackBar(content: Text('« ${product.name} » ajouté au panier')),
      );
    } catch (e) {
      messenger.showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(product.name)),
      body: ListView(
        children: [
          if (product.imageUrl != null)
            AspectRatio(
              aspectRatio: 1,
              child: Image.network(product.imageUrl!, fit: BoxFit.cover),
            ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (product.category != null)
                  Text(product.category!.name, style: theme.textTheme.labelLarge),
                Text(product.name, style: theme.textTheme.headlineSmall),
                const SizedBox(height: 8),
                Text(formatPrice(product.price),
                    style: theme.textTheme.titleLarge
                        ?.copyWith(color: theme.colorScheme.primary)),
                const SizedBox(height: 4),
                Text(product.inStock ? '${product.stock} en stock' : 'Rupture de stock'),
                const SizedBox(height: 16),
                Text(product.description ?? ''),
              ],
            ),
          ),
        ],
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: FilledButton.icon(
            onPressed: product.inStock ? () => _addToCart(context) : null,
            icon: const Icon(Icons.add_shopping_cart),
            label: const Text('Ajouter au panier'),
          ),
        ),
      ),
    );
  }
}
