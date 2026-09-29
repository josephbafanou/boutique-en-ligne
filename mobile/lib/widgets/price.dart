import 'package:intl/intl.dart';

final _format = NumberFormat.currency(locale: 'fr_FR', symbol: '€');

String formatPrice(double value) => _format.format(value);
