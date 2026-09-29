# 🛍️ E-commerce Laravel + Flutter

Plateforme e-commerce complète : une **API REST Laravel** (catalogue, panier, commandes, back-office et reporting des ventes) et une **application mobile Flutter** qui la consomme.

![Backend](https://github.com/josephbafanou/boutique-en-ligne/actions/workflows/backend.yml/badge.svg)
![Mobile](https://github.com/josephbafanou/boutique-en-ligne/actions/workflows/mobile.yml/badge.svg)

> Projet personnel inspiré de mon stage chez ZLO Technologies (Lomé, 2024), où j'ai développé une plateforme marchand/client avec Laravel, une app Flutter et une base MySQL. Le code de ce dépôt est entièrement réécrit.

---

## Architecture

```
┌──────────────────┐     HTTPS + JSON      ┌───────────────────────────┐      ┌──────────────┐
│  App Flutter     │ ───────────────────►  │  API Laravel 12           │ ───► │ MySQL/SQLite │
│  (Provider)      │  Bearer token Sanctum │  Controllers → Services   │      └──────────────┘
└──────────────────┘                       │  Resources · Enum statuts │
                                           └───────────────────────────┘
```

| Dossier | Contenu |
|---|---|
| [`backend/`](backend) | API Laravel 12 · Sanctum · Eloquent · tests PHPUnit |
| [`mobile/`](mobile) | App Flutter · Provider · http · shared_preferences |

## Fonctionnalités

**Côté client**
- Inscription / connexion par token (Laravel Sanctum)
- Catalogue avec recherche, filtre par catégorie et par prix, tri, pagination
- Panier persistant côté serveur avec contrôle du stock
- Commande : prix figés, stock décrémenté et panier vidé **dans une transaction** (verrouillage des lignes pour éviter la survente)
- Historique des commandes, annulation avec remise en stock

**Back-office (administrateurs)**
- Gestion des produits (slugs uniques générés automatiquement)
- Suivi et changement de statut des commandes
- **Reporting des ventes** : chiffre d'affaires, nombre de commandes, panier moyen, top 5 produits, ventes par jour

## API

| Méthode | Route | Accès |
|---|---|---|
| `POST` | `/api/auth/register` · `/api/auth/login` | public |
| `GET` | `/api/categories` · `/api/products` · `/api/products/{slug}` | public |
| `GET` `POST` `PATCH` `DELETE` | `/api/cart`, `/api/cart/items/{id}` | client |
| `GET` `POST` | `/api/orders`, `/api/orders/{id}`, `/api/orders/{id}/cancel` | client |
| `POST` `PUT` `DELETE` | `/api/admin/products/{slug}` | admin |
| `GET` `PATCH` | `/api/admin/orders`, `/api/admin/orders/{id}/status` | admin |
| `GET` | `/api/admin/reports/sales?from=2026-09-01&to=2026-09-30` | admin |

Filtres du catalogue : `?category=mode&search=wax&min_price=10&max_price=100&sort=price_asc&per_page=12`

## Lancer le projet

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite        # ou configurer MySQL dans .env
php artisan migrate --seed            # admin@example.com / password
php artisan serve                     # http://localhost:8000
```

### Mobile

```bash
cd mobile
flutter create . --platforms=android,ios   # génère les dossiers natifs
flutter pub get
flutter run                                # émulateur Android → API sur 10.0.2.2:8000
# appareil physique : flutter run --dart-define=API_URL=http://<ip-du-pc>:8000/api
```

Compte de démo : `client@example.com` / `password`

## Tests

```bash
cd backend && php artisan test     # authentification, catalogue, panier, commande, back-office
cd mobile && flutter test          # client HTTP (mocké), modèles, widgets
```

Les deux suites tournent automatiquement à chaque push via GitHub Actions.

## Choix techniques

- **Service métier dédié** (`CheckoutService`) : la logique de commande est isolée des contrôleurs et testée de bout en bout.
- **Prix figés dans `order_items`** : une commande reste juste même si le produit change ou est supprimé.
- **Enum PHP** pour les statuts de commande, validé avec `Rule::enum`.
- **404 plutôt que 403** sur les ressources d'un autre utilisateur, pour ne pas révéler leur existence.
- **Client HTTP injectable** côté Flutter, ce qui permet de le tester sans serveur.

---

Réalisé par **Joseph-Bernardin Afanou** · [LinkedIn](https://www.linkedin.com/in/joseph-bernardin-afanou)
