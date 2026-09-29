/// URL de l'API. Par défaut, 10.0.2.2 pointe vers le localhost de la machine
/// depuis l'émulateur Android. À surcharger avec :
///   flutter run --dart-define=API_URL=http://192.168.1.10:8000/api
const String apiUrl = String.fromEnvironment(
  'API_URL',
  defaultValue: 'http://10.0.2.2:8000/api',
);
