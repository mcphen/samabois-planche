<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Documentation des Models (Eloquent)

Ce chapitre décrit les principaux Models Eloquent de l'application, leurs attributs et relations, afin de mieux comprendre la base de données et la logique métier.

### Client (`app/Models/Client.php`)
- Traits: `HasFactory`
- Fillable: `name`, `slug`, `address`, `phone`, `email`, `amount_due`, `amount_solde`, `amount_payment`
- Relations:
  - `transactions()` — hasMany `Transaction`
  - `invoices()` — hasMany `Invoice`
  - `payments()` — hasMany `Payment`
- Attributs calculés:
  - `balance` — total des transactions `invoice` moins `payment`
  - `total_due` — somme des factures dont `status != 'payé'`
- Hook `creating`: génère un `slug` unique à partir de `name`.

### Supplier (`app/Models/Supplier.php`)
- Traits: `HasFactory`
- Fillable: `name`, `slug_name`, `address`, `phone`, `email`
- Hook `creating`: génère un `slug_name` unique.
- Relation inverse dans `Article` (ci-dessous).

### Article (`app/Models/Article.php`)
- Traits: `HasFactory`, `SoftDeletes`
- Fillable: `essence`, `supplier_id`, `contract_number`, `indisponible`, `price_per_m3`
- Relations:
  - `supplier()` — belongsTo `Supplier`
  - `items()` — hasMany `ArticleItem`

### ArticleItem (`app/Models/ArticleItem.php`)
- Traits: `HasFactory`, `SoftDeletes`
- Fillable: `article_id`, `numero_colis`, `longueur`, `largeur`, `epaisseur`, `nombre_piece`, `volume`, `indisponible`
- Relations:
  - `article()` — belongsTo `Article`

### Invoice (`app/Models/Invoice.php`)
- Traits: `HasFactory`
- Fillable: `client_id`, `date`, `total_price`, `matricule`, `status`, `montant_solde`
- Relations:
  - `client()` — belongsTo `Client`
  - `items()` — hasMany `InvoiceItem`
  - `payments()` — belongsToMany `Payment` (pivot `amount_paid` + timestamps)
- Hook `creating`: calcule `sequence` et définit `matricule` sous la forme `<sequence>/<année>`.
- Méthodes clés:
  - `updateTotalPrice()` — recalcule `total_price`, crée/maj la `Transaction` de type `invoice`, met à jour les agrégats du `Client`.
  - `scopeWithArticleItemCriteria(array $criteria)` — filtre via des critères sur `items.articleItem`.

### InvoiceItem (`app/Models/InvoiceItem.php`)
- Traits: `HasFactory`, `SoftDeletes`
- Fillable: `invoice_id`, `article_item_id`, `price`, `total_price_item`
- Relations:
  - `invoice()` — belongsTo `Invoice`
  - `articleItem()` — belongsTo `ArticleItem`

### Payment (`app/Models/Payment.php`)
- Traits: `HasFactory`
- Fillable: `client_id`, `amount`, `date`
- Relations:
  - `client()` — belongsTo `Client`
  - `invoices()` — belongsToMany `Invoice` (pivot `amount_paid` + timestamps)

### InvoicePayment (`app/Models/InvoicePayment.php`)
- Table: `invoice_payment` (pivot explicite)
- Traits: `HasFactory`
- Fillable: `payment_id`, `invoice_id`, `amount_paid`
- Relations:
  - `invoice()` — belongsTo `Invoice`

### Transaction (`app/Models/Transaction.php`)
- Traits: `HasFactory`, `SoftDeletes`
- Fillable: `client_id`, `type`, `amount`, `transaction_date`, `invoice_id`, `old_transaction`
- Relations:
  - `client()` — belongsTo `Client`
  - `invoice()` — belongsTo `Invoice`
- Notes: enregistre les mouvements comptables (`type`: `invoice`, `payment`, ...). Certaines transactions sont générées automatiquement (ex: depuis `Invoice::updateTotalPrice`).

### CaisseTransaction (`app/Models/CaisseTransaction.php`)
- Traits: `HasFactory`
- Fillable: `type`, `amount`, `objet`, `description`, `payment_id`, `date`, `transaction_id`
- Relations:
  - `payment()` — belongsTo `Payment`
  - `transaction()` — belongsTo `Transaction`

### AccountingHistory (`app/Models/AccountingHistory.php`)
- Traits: `HasFactory`
- Fillable: `user_id`, `client_id`, `amount_due`, `amount_payment`, `amount_solde`, `notes`
- Relations:
  - `user()` — belongsTo `User`
  - `client()` — belongsTo `Client`

### HistoriqueClientSolde (`app/Models/HistoriqueClientSolde.php`)
- Traits: `HasFactory`
- Fillable: `client_id`, `amount`, `date`

### User (`app/Models/User.php`)
- Extends `Authenticatable`
- Traits: `HasApiTokens`, `HasFactory`, `Notifiable`
- Fillable: `name`, `email`, `password`
- Hidden: `password`, `remember_token`
- Casts: `email_verified_at` → `datetime`, `password` → `hashed`

### Vue d'ensemble relationnelle
- Client: 1–N `Invoice`, 1–N `Payment`, 1–N `Transaction`
- Supplier: 1–N `Article`
- Article: 1–N `ArticleItem`
- Invoice: 1–N `InvoiceItem`, N–N `Payment` (pivot `invoice_payment` avec `amount_paid`)
- Transaction: N–1 `Client`, N–1 (optionnel) `Invoice`
- CaisseTransaction: N–1 (optionnel) `Payment`, N–1 (optionnel) `Transaction`
