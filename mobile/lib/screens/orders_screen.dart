import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../models/order.dart';
import '../services/api_client.dart';
import '../widgets/price.dart';

class OrdersScreen extends StatelessWidget {
  const OrdersScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final dateFormat = DateFormat('dd/MM/yyyy HH:mm');
    return Scaffold(
      appBar: AppBar(title: const Text('Mes commandes')),
      body: FutureBuilder<List<Order>>(
        future: context.read<ApiClient>().orders(),
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('Erreur : ${snapshot.error}'));
          }
          final orders = snapshot.data!;
          if (orders.isEmpty) {
            return const Center(child: Text('Aucune commande pour le moment.'));
          }
          return ListView.builder(
            itemCount: orders.length,
            itemBuilder: (context, i) {
              final order = orders[i];
              return ExpansionTile(
                title: Text('Commande n°${order.id} · ${formatPrice(order.total)}'),
                subtitle: Text([
                  order.statusLabel,
                  if (order.createdAt != null) dateFormat.format(order.createdAt!.toLocal()),
                ].join(' · ')),
                children: [
                  for (final line in order.items)
                    ListTile(
                      dense: true,
                      title: Text(line.productName),
                      trailing: Text('${line.quantity} × ${formatPrice(line.unitPrice)}'),
                    ),
                ],
              );
            },
          );
        },
      ),
    );
  }
}
