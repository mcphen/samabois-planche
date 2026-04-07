### Documentation des Models (Eloquent)

Cette documentation présente tous les principaux Models Eloquent de l'application, leurs attributs et leurs relations, afin de mieux comprendre la base de données et la logique métier.

#### Table des matières
- [Client](#client-appmodelsclientphp)
- [Supplier](#supplier-appmodelssupplierphp)
- [Article](#article-appmodelsarticlephp)
- [ArticleItem](#articleitem-appmodelsarticleitemphp)
- [Invoice](#invoice-appmodelsinvoicephp)
- [InvoiceItem](#invoiceitem-appmodelsinvoiceitemphp)
- [Payment](#payment-appmodelspaymentphp)
- [InvoicePayment](#invoicepayment-appmodelsinvoicepaymentphp)
- [Transaction](#transaction-appmodelstransactionphp)
- [CaisseTransaction](#caissetransaction-appmodelscaissetransactionphp)
- [AccountingHistory](#accountinghistory-appmodelsaccountinghistoryphp)
- [HistoriqueClientSolde](#historiquedlientsolde-appmodelshistoriquedlientsoldephp)
- [User](#user-appmodelsuserphp)

---

#### Client (`app/Models/Client.php`)
- Traits: `HasFactory`
- Fillable: `name`, `slug`, `address`, `phone`, `email`, `amount_due`, `amount_solde`, `amount_payment`
- Relations:
  - `transactions()` — hasMany `Transaction`
  - `invoices()` — hasMany `Invoice`
  - `payments()` — hasMany `Payment`
- Attributs calculés:
  - `balance` — somme des montants de `transactions` de type `invoice` moins ceux de type `payment`
  - `total_due` — somme des `invoices.total_price` avec `status != 'payé'`
- Hooks Eloquent:
  - `creating` — génère un `slug` unique à partir de `name` (erreur si doublon)

#### Supplier (`app/Models/Supplier.php`)
- Traits: `HasFactory`
- Fillable: `name`, `slug_name`, `address`, `phone`, `email`
- Hooks Eloquent:
  - `creating` — génère un `slug_name` unique (erreur si doublon)
- Relations attendues:
  - Inverse présentes dans `Article` via `supplier()`

#### Article (`app/Models/Article.php`)
- Traits: `HasFactory`, `SoftDeletes`
- Fillable: `essence`, `supplier_id`, `contract_number`, `indisponible`, `price_per_m3`
- Relations:
  - `supplier()` — belongsTo `Supplier`
  - `items()` — hasMany `ArticleItem`

#### ArticleItem (`app/Models/ArticleItem.php`)
- Traits: `HasFactory`, `SoftDeletes`
- Fillable: `article_id`, `numero_colis`, `longueur`, `largeur`, `epaisseur`, `nombre_piece`, `volume`, `indisponible`
- Relations:
  - `article()` — belongsTo `Article`

#### Invoice (`app/Models/Invoice.php`)
- Traits: `HasFactory`
- Fillable: `client_id`, `date`, `total_price`, `matricule`, `status`, `montant_solde`
- Relations:
  - `client()` — belongsTo `Client`
  - `items()` — hasMany `InvoiceItem`
  - `payments()` — belongsToMany `Payment` (pivot `amount_paid` + timestamps)
- Hooks Eloquent:
  - `creating` — calcule `sequence` (max + 1) et définit `matricule` comme `<sequence>/<année>`
- Méthodes principales:
  - `updateTotalPrice()` — recalcule `total_price` depuis `items.total_price_item`, upsert la `Transaction` de type `invoice` liée, puis met à jour `amount_due` et `amount_solde` du `Client`.
  - `scopeWithArticleItemCriteria(array $criteria)` — filtre les factures selon des critères appliqués aux `ArticleItem` liés.

#### InvoiceItem (`app/Models/InvoiceItem.php`)
- Traits: `HasFactory`, `SoftDeletes`
- Fillable: `invoice_id`, `article_item_id`, `price`, `total_price_item`
- Relations:
  - `invoice()` — belongsTo `Invoice`
  - `articleItem()` — belongsTo `ArticleItem`

#### Payment (`app/Models/Payment.php`)
- Traits: `HasFactory`
- Fillable: `client_id`, `amount`, `date`
- Relations:
  - `client()` — belongsTo `Client`
  - `invoices()` — belongsToMany `Invoice` (pivot `amount_paid` + timestamps)

#### InvoicePayment (`app/Models/InvoicePayment.php`)
- Table: `invoice_payment` (pivot explicite)
- Traits: `HasFactory`
- Fillable: `payment_id`, `invoice_id`, `amount_paid`
- Relations:
  - `invoice()` — belongsTo `Invoice`

#### Transaction (`app/Models/Transaction.php`)
- Traits: `HasFactory`, `SoftDeletes`
- Fillable: `client_id`, `type`, `amount`, `transaction_date`, `invoice_id`, `old_transaction`
- Relations:
  - `client()` — belongsTo `Client`
  - `invoice()` — belongsTo `Invoice`
- Notes:
  - Représente un mouvement comptable pour un client (ex: création de facture `invoice` ou paiement `payment`). Certaines transactions sont générées automatiquement (ex: `Invoice::updateTotalPrice`).

#### CaisseTransaction (`app/Models/CaisseTransaction.php`)
- Traits: `HasFactory`
- Fillable: `type`, `amount`, `objet`, `description`, `payment_id`, `date`, `transaction_id`
- Relations:
  - `payment()` — belongsTo `Payment`
  - `transaction()` — belongsTo `Transaction`

#### AccountingHistory (`app/Models/AccountingHistory.php`)
- Traits: `HasFactory`
- Fillable: `user_id`, `client_id`, `amount_due`, `amount_payment`, `amount_solde`, `notes`
- Relations:
  - `user()` — belongsTo `User`
  - `client()` — belongsTo `Client`

#### HistoriqueClientSolde (`app/Models/HistoriqueClientSolde.php`)
- Traits: `HasFactory`
- Fillable: `client_id`, `amount`, `date`

#### User (`app/Models/User.php`)
- Hérite de `Authenticatable`
- Traits: `HasApiTokens`, `HasFactory`, `Notifiable`
- Fillable: `name`, `email`, `password`
- Hidden: `password`, `remember_token`
- Casts: `email_verified_at` → `datetime`, `password` → `hashed`

---

#### Vue d'ensemble relationnelle
- Client: 1–N `Invoice`, 1–N `Payment`, 1–N `Transaction`
- Supplier: 1–N `Article`
- Article: 1–N `ArticleItem`
- Invoice: 1–N `InvoiceItem`, N–N `Payment` (pivot `invoice_payment` avec `amount_paid`)
- Transaction: N–1 `Client`, N–1 (optionnel) `Invoice`
- CaisseTransaction: N–1 (optionnel) `Payment`, N–1 (optionnel) `Transaction`
