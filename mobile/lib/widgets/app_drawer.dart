import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../screens/login_screen.dart';
import '../screens/orders_screen.dart';
import '../state/auth_provider.dart';
import '../state/cart_provider.dart';

class AppDrawer extends StatelessWidget {
  const AppDrawer({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    return Drawer(
      child: ListView(
        children: [
          const DrawerHeader(child: Text('Boutique', style: TextStyle(fontSize: 24))),
          if (!auth.isLoggedIn)
            ListTile(
              leading: const Icon(Icons.login),
              title: const Text('Se connecter'),
              onTap: () => Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const LoginScreen()),
              ),
            )
          else ...[
            ListTile(
              leading: const Icon(Icons.receipt_long_outlined),
              title: const Text('Mes commandes'),
              onTap: () => Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const OrdersScreen()),
              ),
            ),
            ListTile(
              leading: const Icon(Icons.logout),
              title: const Text('Se déconnecter'),
              onTap: () async {
                final cart = context.read<CartProvider>();
                await auth.logout();
                cart.clear();
              },
            ),
          ],
        ],
      ),
    );
  }
}
