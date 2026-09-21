# Laravel PawaPay

**Intégration Laravel du SDK PawaPay pour les paiements Mobile Money en Afrique.**

---

## Table des matières

1. [Introduction](#1-introduction)
2. [Installation](#2-installation)
3. [Configuration](#3-configuration)
4. [Routes exposées](#4-routes-exposées)
5. [Utilisation](#5-utilisation)
6. [Contrats et extension](#6-contrats-et-extension)
7. [Gestion des erreurs](#7-gestion-des-erreurs)
8. [Tests](#8-tests)
9. [Sécurité](#9-sécurité)
10. [Compatibilité](#10-compatibilité)

---

## 1. Introduction

### Objectif

`andydefer/laravel-pawapay` expose les opérations PawaPay (dépôts Mobile Money, statuts, webhooks, pages de paiement) sous forme d'endpoints HTTP Laravel prêts à l'emploi.

Le package s'appuie sur `andydefer/php-pawapay`, qui contient toute la logique métier et les objets typés (`Record`, `Data`). Laravel PawaPay n'ajoute que la couche transport : routes, validation, injection.

### Ce que le package fournit

- **4 endpoints HTTP** : initier un dépôt, vérifier un dépôt, renvoyer un webhook, créer une page de paiement.
- **4 `FormRequest`** : validation stricte des entrées + construction de `Record` typés.
- **4 `Action`** : appellent le service PawaPay et renvoient des `Data` typées.
- **Une configuration unique** : token, URL, devises, langues et pays autorisés.
- **Un point d'extension** : remplacer entièrement le service PawaPay sans modifier le package.

### Ce que le package ne fait pas

- Aucune authentification. Les routes sont publiées sans middleware.
- Aucune persistance. Le package ne touche pas à la base de données.
- Aucune gestion de webhook entrant. Seul l'appel sortant vers PawaPay est exposé.

---

## 2. Installation

```bash
composer require andydefer/laravel-pawapay
```

Le `PawapayServiceProvider` est découvert automatiquement par Laravel.

---

## 3. Configuration

### 3.1 Publier les fichiers

```bash
php artisan vendor:publish --tag=laravel-pawapay-config
php artisan vendor:publish --tag=laravel-pawapay-routes
```

- Config publiée dans `config/pawapay.php`.
- Routes publiées dans `routes/laravel-pawapay.php`.

### 3.2 Charger les routes

Les routes ne sont pas chargées automatiquement. Charge-les explicitement depuis ton application :

```php
// routes/api.php
require base_path('routes/laravel-pawapay.php');
```

Tu contrôles ainsi le préfixe, les middlewares et l'ordre de chargement.

### 3.3 Fichier de configuration

```php
<?php

declare(strict_types=1);

use AndyDefer\PhpPawapay\Enums\Country;
use AndyDefer\PhpPawapay\Enums\Currency;
use AndyDefer\PhpPawapay\Enums\Language;
use AndyDefer\PhpPawapay\Services\PawapayService;

return [
    'api_token' => env('PAWAPAY_API_TOKEN', ''),
    'base_url' => env('PAWAPAY_BASE_URL', 'https://api.sandbox.pawapay.io/'),
    'service_fqcn' => PawapayService::class,
    'currencies' => Currency::cases(),
    'languages' => Language::cases(),
    'countries' => Country::cases(),
];
```

### 3.4 Clés de configuration

| Clé | Type | Défaut | Description |
|-----|------|--------|-------------|
| `api_token` | `string` | `''` | Token d'authentification fourni par PawaPay |
| `base_url` | `string` | URL sandbox | URL de base de l'API PawaPay |
| `service_fqcn` | `class-string` | `PawapayService::class` | Implémentation de `PawapayInterface` |
| `currencies` | `array<int, Currency\|string>` | Toutes les cases | Devises autorisées par les `FormRequest` |
| `languages` | `array<int, Language\|string>` | Toutes les cases | Langues autorisées par les `FormRequest` |
| `countries` | `array<int, Country\|string>` | Toutes les cases | Pays autorisés par les `FormRequest` |

### 3.5 Variables d'environnement

```env
PAWAPAY_API_TOKEN=eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ...
PAWAPAY_BASE_URL=https://api.sandbox.pawapay.io/
```

### 3.6 Restreindre les valeurs autorisées

Pour n'accepter que certaines devises, langues ou pays :

```php
// config/pawapay.php
use AndyDefer\PhpPawapay\Enums\Country;
use AndyDefer\PhpPawapay\Enums\Currency;
use AndyDefer\PhpPawapay\Enums\Language;

return [
    'currencies' => [Currency::USD, Currency::CDF],
    'languages' => [Language::FR, Language::EN],
    'countries' => [Country::COD],
    // ...
];
```

Toute valeur absente de ces listes est rejetée par les `FormRequest` avec un `422`.

---

## 4. Routes exposées

Le fichier `routes/laravel-pawapay.php` déclare quatre endpoints, tous en `POST`, sous le préfixe `pawapay`.

| Méthode | URI | Nom | Description |
|---------|-----|-----|-------------|
| POST | `/pawapay/deposits` | `pawapay.deposits.initiate` | Initier un dépôt Mobile Money |
| POST | `/pawapay/deposits/status` | `pawapay.deposits.status` | Vérifier l'état d'un dépôt |
| POST | `/pawapay/deposits/resend-callback` | `pawapay.deposits.resend-callback` | Renvoyer le webhook d'un dépôt |
| POST | `/pawapay/payment-page` | `pawapay.payment-page` | Créer une page de paiement hébergée |

Modifie librement le préfixe et ajoute des middlewares dans le fichier publié.

---

## 5. Utilisation

### 5.1 Initier un dépôt

Requête :

```bash
curl -X POST https://app.test/pawapay/deposits \
  -H "Content-Type: application/json" \
  -d '{
    "deposit_id": "f4401bd2-1568-4140-bf2d-eb77d2b2b639",
    "phone_number": "260763456789",
    "provider": "MTN_MOMO_ZMB",
    "amount": 15.00,
    "currency": "ZMW",
    "payer_type": "MMO",
    "client_reference_id": "INV-123456",
    "customer_message": "Payment order"
  }'
```

Paramètres :

| Champ | Type | Requis | Contrainte |
|-------|------|--------|------------|
| `deposit_id` | `uuid` | ✅ | UUID v4 |
| `phone_number` | `string` | ✅ | E.164 sans `+`, 9 à 15 caractères |
| `provider` | `Provider` | ✅ | Enum `Provider` |
| `amount` | `numeric` | ✅ | > 0 |
| `currency` | `Currency` | ✅ | Doit figurer dans `pawapay.currencies` |
| `payer_type` | `PayerType` | ✅ | Enum `PayerType` |
| `pre_authorisation_code` | `string` | ❌ | Libre |
| `client_reference_id` | `string` | ❌ | 4 à 64 caractères |
| `customer_message` | `string` | ❌ | 4 à 22 caractères |
| `metadata` | `array` | ❌ | 10 clés maximum |

Réponse (200) :

```json
{
  "depositId": "f4401bd2-1568-4140-bf2d-eb77d2b2b639",
  "status": "ACCEPTED",
  "created": "2020-10-19T11:17:01Z",
  "failureReason": null,
  "isAccepted": true,
  "isRejected": false,
  "isDuplicateIgnored": false,
  "hasFailureReason": false
}
```

Statuts : `ACCEPTED`, `REJECTED`, `DUPLICATE_IGNORED`.

### 5.2 Vérifier un dépôt

Requête :

```bash
curl -X POST https://app.test/pawapay/deposits/status \
  -H "Content-Type: application/json" \
  -d '{"deposit_id": "f4401bd2-1568-4140-bf2d-eb77d2b2b639"}'
```

Réponse (200) :

```json
{
  "searchStatus": "FOUND",
  "depositData": {
    "depositId": "f4401bd2-1568-4140-bf2d-eb77d2b2b639",
    "status": "COMPLETED",
    "amount": "15.00",
    "currency": "ZMW",
    "country": "ZMB",
    "payer": {
      "type": "MMO",
      "accountDetails": {
        "phoneNumber": "260763456789",
        "provider": "MTN_MOMO_ZMB"
      }
    },
    "created": "2020-10-19T11:17:01Z"
  },
  "isFound": true,
  "isNotFound": false,
  "failureReason": null,
  "hasFailureReason": false
}
```

Statuts de recherche : `FOUND`, `NOT_FOUND`.

Statuts de dépôt : `ACCEPTED`, `PROCESSING`, `IN_RECONCILIATION`, `COMPLETED`, `FAILED`, `REJECTED`.

### 5.3 Renvoyer un webhook

Requête :

```bash
curl -X POST https://app.test/pawapay/deposits/resend-callback \
  -H "Content-Type: application/json" \
  -d '{"deposit_id": "9b724dbf-32a7-4e63-96bb-59a4747e43ca"}'
```

Réponse (200) :

```json
{
  "depositId": "9b724dbf-32a7-4e63-96bb-59a4747e43ca",
  "status": "ACCEPTED",
  "failureReason": null,
  "isAccepted": true,
  "isRejected": false,
  "hasFailureReason": false
}
```

Statuts : `ACCEPTED`, `REJECTED`.

### 5.4 Créer une page de paiement

Requête :

```bash
curl -X POST https://app.test/pawapay/payment-page \
  -H "Content-Type: application/json" \
  -d '{
    "deposit_id": "9b724dbf-32a7-4e63-96bb-59a4747e43ca",
    "return_url": "https://merchant.example.com/checkout-result",
    "amount": 25.50,
    "currency": "USD",
    "phone_number": "243812345678",
    "language": "EN",
    "country": "COD",
    "customer_message": "Payment order"
  }'
```

Paramètres :

| Champ | Type | Requis | Contrainte |
|-------|------|--------|------------|
| `deposit_id` | `uuid` | ✅ | UUID v4 |
| `return_url` | `url` | ✅ | URL valide |
| `amount` | `numeric` | ✅ | > 0 |
| `currency` | `Currency` | ✅ | Doit figurer dans `pawapay.currencies` |
| `phone_number` | `string` | ✅ | E.164 sans `+`, 9 à 15 caractères |
| `language` | `Language` | ✅ | Doit figurer dans `pawapay.languages` |
| `country` | `Country` | ✅ | Doit figurer dans `pawapay.countries` |
| `customer_message` | `string` | ❌ | 4 à 22 caractères |
| `metadata` | `array` | ❌ | 10 clés maximum |

Réponse (200) :

```json
{
  "redirectUrl": "https://sandbox.paywith.pawapay.io/v2?token=xxx",
  "failureReason": null,
  "hasFailureReason": false
}
```

Redirige l'utilisateur vers `redirectUrl` pour qu'il effectue le paiement.

---

## 6. Contrats et extension

### 6.1 `PawapayConfigInterface`

Contrat de la configuration. Utilisé par les `Action` pour résoudre le service et par les `FormRequest` pour valider les valeurs autorisées.

```php
<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Contracts;

use AndyDefer\PhpPawapay\Collections\CountryCollection;
use AndyDefer\PhpPawapay\Collections\CurrencyCollection;
use AndyDefer\PhpPawapay\Collections\LanguageCollection;
use AndyDefer\PhpPawapay\Enums\PawaPayBaseUrl;

interface PawapayConfigInterface
{
    public function getApiToken(): string;

    public function getBaseUrl(): PawaPayBaseUrl;

    public function getServiceFqcn(): string;

    public function getCurrencies(): CurrencyCollection;

    public function getLanguages(): LanguageCollection;

    public function getCountries(): CountryCollection;
}
```

### 6.2 `PawapayInterface`

Contrat du service PawaPay. Les `Action` ne connaissent que cette interface.

```php
<?php

declare(strict_types=1);

namespace AndyDefer\PhpPawapay\Contracts;

use AndyDefer\PhpPawapay\Datas\CheckDepositStatusData;
use AndyDefer\PhpPawapay\Datas\CreatePaymentPageData;
use AndyDefer\PhpPawapay\Datas\InitiateDepositData;
use AndyDefer\PhpPawapay\Datas\ResendDepositCallbackData;
use AndyDefer\PhpPawapay\Records\CheckDepositStatusRecord;
use AndyDefer\PhpPawapay\Records\CreatePaymentPageRecord;
use AndyDefer\PhpPawapay\Records\InitiateDepositRecord;
use AndyDefer\PhpPawapay\Records\ResendDepositCallbackRecord;

interface PawapayInterface
{
    public function initiateDeposit(InitiateDepositRecord $record): InitiateDepositData;

    public function checkDepositStatus(CheckDepositStatusRecord $record): CheckDepositStatusData;

    public function resendDepositCallback(ResendDepositCallbackRecord $record): ResendDepositCallbackData;

    public function createPaymentPage(CreatePaymentPageRecord $record): CreatePaymentPageData;
}
```

### 6.3 Remplacer le service PawaPay

Deux approches possibles. La première est prioritaire.

**Option A — Modifier `service_fqcn`.**

```php
// config/pawapay.php
'service_fqcn' => \App\Payments\MyPawapayService::class,
```

Contraintes :

- La classe doit implémenter `PawapayInterface`.
- Son constructeur doit être auto-résolvable par le conteneur Laravel (dépendances typées et liées).

**Option B — Binder l'interface.**

```php
// app/Providers/AppServiceProvider.php
use AndyDefer\PhpPawapay\Contracts\PawapayInterface;
use App\Payments\MyPawapayService;

public function register(): void
{
    $this->app->bind(PawapayInterface::class, MyPawapayService::class);
}
```

L'option B n'a d'effet que si `service_fqcn` reste égal à `PawapayService::class`. Si tu changes `service_fqcn`, c'est cette valeur qui est utilisée, pas le binding.

---

## 7. Gestion des erreurs

Le package distingue trois niveaux.

### 7.1 Validation (HTTP 422)

Les `FormRequest` rejettent les payloads invalides avant toute exécution.

| Situation | Message type |
|-----------|--------------|
| Champ requis manquant | `The deposit id field is required.` |
| UUID invalide | `The deposit id field must be a valid UUID.` |
| Provider inconnu | `The selected provider is invalid.` |
| Devise non autorisée | `The selected currency is invalid.` |
| Langue non autorisée | `The selected language is invalid.` |
| Pays non autorisé | `The selected country is invalid.` |
| Montant ≤ 0 | `The amount field must be at least 0.01.` |
| `return_url` invalide | `The return url field must be a valid URL.` |
| `customer_message` trop court | `The customer message field must be at least 4 characters.` |
| `customer_message` trop long | `The customer message field must not be greater than 22 characters.` |

### 7.2 Refus PawaPay (HTTP 200 avec `failureReason`)

Requête bien formée mais refusée par PawaPay. La réponse HTTP est `200`, la `Data` porte l'erreur.

```json
{
  "depositId": null,
  "status": "REJECTED",
  "failureReason": {
    "failureCode": "INVALID_PHONE_NUMBER",
    "failureMessage": "The phone number '2438' seems to be invalid for the provider 'MTN_MOMO_ZMB'."
  },
  "hasFailureReason": true
}
```

Codes possibles :

| Code | Signification |
|------|---------------|
| `INVALID_PHONE_NUMBER` | Numéro incompatible avec le provider |
| `INVALID_CURRENCY` | Devise non supportée par le provider |
| `INVALID_AMOUNT` | Montant non supporté par le provider |
| `AMOUNT_OUT_OF_BOUNDS` | Montant hors bornes pour le provider |
| `PROVIDER_TEMPORARILY_UNAVAILABLE` | Provider momentanément indisponible |
| `AUTHENTICATION_ERROR` | Token API invalide |
| `AUTHORISATION_ERROR` | Token non autorisé sur cet endpoint |
| `PAYMENT_NOT_APPROVED` | Client n'a pas approuvé le paiement |
| `PAYMENT_IN_PROGRESS` | Paiement en cours, callback non disponible |
| `UNKNOWN_ERROR` | Erreur non catégorisée |

### 7.3 Erreurs techniques (HTTP 500)

Levées par le conteneur ou le service si :

- `service_fqcn` n'est pas résolvable (`BindingResolutionException`) ;
- l'implémentation n'implémente pas `PawapayInterface` (`TypeError`) ;
- une erreur réseau se produit dans le SDK sous-jacent.

---

## 8. Tests

```bash
composer test
```

Le package fournit un `IntegrationTestCase` basé sur `orchestra/testbench`. Pour injecter une configuration alternative dans un test, utilise une classe de test dédiée :

```php
<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Tests\Fixtures\Configs;

use AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface;
use AndyDefer\PhpPawapay\Collections\CountryCollection;
use AndyDefer\PhpPawapay\Collections\CurrencyCollection;
use AndyDefer\PhpPawapay\Collections\LanguageCollection;
use AndyDefer\PhpPawapay\Enums\Country;
use AndyDefer\PhpPawapay\Enums\Currency;
use AndyDefer\PhpPawapay\Enums\Language;
use AndyDefer\PhpPawapay\Enums\PawaPayBaseUrl;
use AndyDefer\PhpPawapay\Services\PawapayService;

final class RestrictedPawapayConfig implements PawapayConfigInterface
{
    public function getApiToken(): string
    {
        return 'test-token';
    }

    public function getBaseUrl(): PawaPayBaseUrl
    {
        return PawaPayBaseUrl::SANDBOX;
    }

    public function getServiceFqcn(): string
    {
        return PawapayService::class;
    }

    public function getCurrencies(): CurrencyCollection
    {
        return CurrencyCollection::from([Currency::USD]);
    }

    public function getLanguages(): LanguageCollection
    {
        return LanguageCollection::from([Language::EN]);
    }

    public function getCountries(): CountryCollection
    {
        return CountryCollection::from([Country::COD]);
    }
}
```

Puis dans un test :

```php
$this->app->instance(PawapayConfigInterface::class, new RestrictedPawapayConfig);
```

Cette approche est préférable à une classe anonyme : elle est réutilisable, typée et lisible.

---

## 9. Sécurité

- **Routes non protégées par défaut.** Ajoute tes middlewares (`auth:sanctum`, `throttle:60,1`, signature HMAC…) dans le fichier publié.
- **`PAWAPAY_API_TOKEN` côté serveur uniquement.** Ne jamais l'exposer au frontend.
- **Restriction des devises, langues et pays** via la config pour éviter les payloads inattendus.
- **`customer_message` limité à 22 caractères** conformément à PawaPay.
- **`metadata` limité à 10 clés**.
- **`client_reference_id` limité à 64 caractères**, alphanumérique avec tirets.
- **`phone_number` au format E.164 sans `+`** validé par `FormRequest`.

---

## 10. Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.1+ | ✅ Complet |
| Laravel 10 | ✅ Complet |
| Laravel 11 | ✅ Complet |
| Laravel 12 | ✅ Complet |
| Laravel 9 | ⚠️ Dépend de `andydefer/laravel-actions` |

---

## Licence

MIT © Andy Defer