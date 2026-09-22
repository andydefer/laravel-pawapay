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
7. [Hooks du service PawaPay](#7-hooks-du-service-pawapay)
8. [Gestion des erreurs](#8-gestion-des-erreurs)
9. [Tests](#9-tests)
10. [Sécurité](#10-sécurité)
11. [Compatibilité](#11-compatibilité)

---

## 1. Introduction

### Objectif

`andydefer/laravel-pawapay` expose les opérations PawaPay (dépôts Mobile Money, statuts, webhooks, pages de paiement) sous forme d'endpoints HTTP Laravel prêts à l'emploi.

Le package s'appuie sur `andydefer/php-pawapay`, qui contient toute la logique métier et les objets typés (`Record`, `Data`, `ErrorResponseData`). Laravel PawaPay n'ajoute que la couche transport : routes, validation, injection.

### Ce que le package fournit

- **4 endpoints HTTP** : initier un dépôt, vérifier un dépôt, renvoyer un webhook, créer une page de paiement.
- **4 `FormRequest`** : validation stricte, application des whitelists et construction de `Record` typés. Chaque `Record` expose un `data` bag optionnel pour porter les champs non réservés.
- **4 `Action`** : résolvent le service PawaPay via la configuration, appellent l'opération correspondante et renvoient une `Data` typée. Si le service retourne une `ErrorResponseData`, l'action propage le code HTTP porté par cette erreur.
- **Une configuration unique** : token, URL, `service_fqcn`, devises, langues, pays, providers et types de payeur autorisés.
- **Un point d'extension** : remplacer entièrement le service PawaPay via `service_fqcn` sans modifier le package.
- **Des hooks applicatifs** : le service `php-pawapay` expose des points d'extension `before*` / `after*` sur les quatre opérations. Voir section 7.

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
use AndyDefer\PhpPawapay\Enums\PayerType;
use AndyDefer\PhpPawapay\Enums\Provider;
use AndyDefer\PhpPawapay\Services\PawapayService;

return [
    'api_token' => env('PAWAPAY_API_TOKEN', ''),
    'base_url' => env('PAWAPAY_BASE_URL', 'https://api.sandbox.pawapay.io/'),
    'service_fqcn' => PawapayService::class,
    'currencies' => Currency::cases(),
    'languages' => Language::cases(),
    'countries' => Country::cases(),
    'providers' => Provider::cases(),
    'payer_types' => PayerType::cases(),
];
```

### 3.4 Clés de configuration

| Clé | Type | Défaut | Description |
|-----|------|--------|-------------|
| `api_token` | `string` | `''` | Token d'authentification fourni par PawaPay |
| `base_url` | `string` | URL sandbox | URL de base de l'API PawaPay |
| `service_fqcn` | `class-string` | `PawapayService::class` | Implémentation de `PawapayInterface` |
| `currencies` | `array<int, Currency\|string>` | Toutes les cases | Devises acceptées par les `FormRequest` |
| `languages` | `array<int, Language\|string>` | Toutes les cases | Langues acceptées par les `FormRequest` |
| `countries` | `array<int, Country\|string>` | Toutes les cases | Pays acceptés par les `FormRequest` |
| `providers` | `array<int, Provider\|string>` | Toutes les cases | Providers acceptés par `InitiateDepositRequest` |
| `payer_types` | `array<int, PayerType\|string>` | Toutes les cases | Types de payeur acceptés par `InitiateDepositRequest` |

### 3.5 Variables d'environnement

```env
PAWAPAY_API_TOKEN=eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ...
PAWAPAY_BASE_URL=https://api.sandbox.pawapay.io/
```

### 3.6 Restreindre les valeurs autorisées

```php
// config/pawapay.php
return [
    'currencies' => [Currency::USD, Currency::CDF],
    'languages' => [Language::FR],
    'countries' => [Country::COD],
    'providers' => [
        Provider::VODACOM_MPESA_COD,
        Provider::AIRTEL_COD,
        Provider::ORANGE_COD,
    ],
    // ...
];
```

Toute valeur absente de ces listes est rejetée par les `FormRequest` avec un `422`.

### 3.7 Cas concret : configuration Afya (RDC)

```php
// config/pawapay.php
return [
    'api_token' => env('PAWAPAY_API_TOKEN', ''),
    'base_url' => env('PAWAPAY_BASE_URL', 'https://api.sandbox.pawapay.io/'),
    'service_fqcn' => \App\Services\AfyaPawapayService::class,

    'currencies' => [Currency::USD, Currency::CDF],
    'languages' => [Language::FR],
    'countries' => [Country::COD],
    'providers' => [
        Provider::VODACOM_MPESA_COD,
        Provider::AIRTEL_COD,
        Provider::ORANGE_COD,
    ],
    'payer_types' => PayerType::cases(),
];
```

---

## 4. Routes exposées

Le fichier `routes/laravel-pawapay.php` déclare quatre endpoints, tous en `POST`, sous le préfixe `pawapay`.

| Méthode | URI | Nom | Description |
|---------|-----|-----|-------------|
| POST | `/pawapay/initiate-deposit` | `pawapay.initiate-deposit` | Initier un dépôt Mobile Money |
| POST | `/pawapay/check-deposit-status` | `pawapay.check-deposit-status` | Vérifier l'état d'un dépôt |
| POST | `/pawapay/resend-deposit-callback` | `pawapay.resend-deposit-callback` | Renvoyer le webhook d'un dépôt |
| POST | `/pawapay/create-payment-page` | `pawapay.create-payment-page` | Créer une page de paiement hébergée |

---

## 5. Utilisation

### 5.1 Initier un dépôt

Requête :

```bash
curl -X POST https://app.test/pawapay/initiate-deposit \
  -H "Content-Type: application/json" \
  -d '{
    "deposit_id": "f4401bd2-1568-4140-bf2d-eb77d2b2b639",
    "phone_number": "243812345678",
    "provider": "VODACOM_MPESA_COD",
    "amount": 15.00,
    "currency": "USD",
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
| `provider` | `Provider` | ✅ | Doit figurer dans `pawapay.providers` |
| `amount` | `numeric` | ✅ | > 0 |
| `currency` | `Currency` | ✅ | Doit figurer dans `pawapay.currencies` |
| `payer_type` | `PayerType` | ✅ | Doit figurer dans `pawapay.payer_types` |
| `pre_authorisation_code` | `string` | ❌ | Libre |
| `client_reference_id` | `string` | ❌ | 4 à 64 caractères |
| `customer_message` | `string` | ❌ | 4 à 22 caractères |
| `metadata` | `array` | ❌ | 10 clés maximum |

Les champs non listés dans `rules()` mais présents dans la requête sont collectés dans le `data` bag du `Record` (voir section 6.4).

Réponse succès (200) :

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

```bash
curl -X POST https://app.test/pawapay/check-deposit-status \
  -H "Content-Type: application/json" \
  -d '{"deposit_id": "f4401bd2-1568-4140-bf2d-eb77d2b2b639"}'
```

Réponse succès (200) :

```json
{
  "searchStatus": "FOUND",
  "depositData": {
    "depositId": "f4401bd2-1568-4140-bf2d-eb77d2b2b639",
    "status": "COMPLETED",
    "amount": "15.00",
    "currency": "USD",
    "country": "COD",
    "payer": {
      "type": "MMO",
      "accountDetails": {
        "phoneNumber": "243812345678",
        "provider": "VODACOM_MPESA_COD"
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

Statuts de recherche : `FOUND`, `NOT_FOUND`, `REJECTED`.

Statuts de dépôt : `ACCEPTED`, `PROCESSING`, `IN_RECONCILIATION`, `COMPLETED`, `FAILED`, `REJECTED`.

### 5.3 Renvoyer un webhook

```bash
curl -X POST https://app.test/pawapay/resend-deposit-callback \
  -H "Content-Type: application/json" \
  -d '{"deposit_id": "9b724dbf-32a7-4e63-96bb-59a4747e43ca"}'
```

Réponse succès (200) :

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

```bash
curl -X POST https://app.test/pawapay/create-payment-page \
  -H "Content-Type: application/json" \
  -d '{
    "deposit_id": "9b724dbf-32a7-4e63-96bb-59a4747e43ca",
    "return_url": "https://merchant.example.com/checkout-result",
    "amount": 25.50,
    "currency": "USD",
    "phone_number": "243812345678",
    "language": "FR",
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

Réponse succès (200) :

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
use AndyDefer\PhpPawapay\Collections\PayerTypeCollection;
use AndyDefer\PhpPawapay\Collections\ProviderCollection;
use AndyDefer\PhpPawapay\Enums\PawaPayBaseUrl;

interface PawapayConfigInterface
{
    public function getApiToken(): string;

    public function getBaseUrl(): PawaPayBaseUrl;

    public function getServiceFqcn(): string;

    public function getCurrencies(): CurrencyCollection;

    public function getLanguages(): LanguageCollection;

    public function getCountries(): CountryCollection;

    public function getProviders(): ProviderCollection;

    public function getPayerTypes(): PayerTypeCollection;
}
```

### 6.2 `PawapayInterface`

Contrat du service PawaPay. Les `Action` ne connaissent que cette interface.

```php
interface PawapayInterface
{
    public function initiateDeposit(InitiateDepositRecord $record): InitiateDepositData;

    public function checkDepositStatus(CheckDepositStatusRecord $record): CheckDepositStatusData;

    public function resendDepositCallback(ResendDepositCallbackRecord $record): ResendDepositCallbackData;

    public function createPaymentPage(CreatePaymentPageRecord $record): CreatePaymentPageData;
}
```

### 6.3 Remplacer le service PawaPay

**Option A — Modifier `service_fqcn`.**

```php
// config/pawapay.php
'service_fqcn' => \App\Services\AfyaPawapayService::class,
```

Contraintes :

- La classe doit étendre `PawapayService` ou implémenter `PawapayInterface`.
- Son constructeur doit être auto-résolvable par le conteneur Laravel.

**Option B — Binder l'interface.**

```php
$this->app->bind(PawapayInterface::class, AfyaPawapayService::class);
```

L'option B n'a d'effet que si `service_fqcn` reste égal à `PawapayService::class`.

### 6.4 Le `data` bag des `Record`

Chaque `FormRequest` construit un `Record` avec un `data` bag optionnel. Ce bag contient tous les champs présents dans la requête mais non listés dans `rules()`. Il permet à l'application hôte d'attacher des informations contextuelles (identifiant interne, marqueur, note) sans étendre le `Record` du SDK.

```json
{
  "deposit_id": "f4401bd2-1568-4140-bf2d-eb77d2b2b639",
  "phone_number": "243812345678",
  "provider": "VODACOM_MPESA_COD",
  "amount": 15.00,
  "currency": "USD",
  "payer_type": "MMO",
  "order_id": "ORD-123456",
  "customer_segment": "premium"
}
```

`order_id` et `customer_segment` ne sont pas dans `rules()`. Ils aboutissent dans `$record->data`, un `StrictAssociative` accessible depuis les hooks du service ou depuis toute couche applicative qui consomme le `Record`.

---

## 7. Hooks du service PawaPay

Le service `andydefer/php-pawapay` expose huit hooks `protected` — deux par opération — que l'application hôte peut surcharger pour greffer sa logique métier sans réécrire le comportement PawaPay.

Les hooks sont appelés dans cet ordre, à chaque invocation de l'opération :

```
run(Record)
    ↓
before<Operation>(Record)         ← surchargeable
    ↓
PawapayClient HTTP call
    ↓
Data::from(Response)
    ↓
after<Operation>(Record, Data)    ← surchargeable
    ↓
return Data
```

### 7.1 Liste des hooks

| Hook | Signature | Déclenché |
|------|-----------|-----------|
| `beforeInitiateDeposit` | `(InitiateDepositRecord $record): void` | Avant l'appel `initiateDeposit` |
| `afterInitiateDeposit` | `(InitiateDepositRecord $record, InitiateDepositData $data): void` | Après l'appel `initiateDeposit` |
| `beforeCheckDepositStatus` | `(CheckDepositStatusRecord $record): void` | Avant l'appel `checkDepositStatus` |
| `afterCheckDepositStatus` | `(CheckDepositStatusRecord $record, CheckDepositStatusData $data): void` | Après l'appel `checkDepositStatus` |
| `beforeResendDepositCallback` | `(ResendDepositCallbackRecord $record): void` | Avant l'appel `resendDepositCallback` |
| `afterResendDepositCallback` | `(ResendDepositCallbackRecord $record, ResendDepositCallbackData $data): void` | Après l'appel `resendDepositCallback` |
| `beforeCreatePaymentPage` | `(CreatePaymentPageRecord $record): void` | Avant l'appel `createPaymentPage` |
| `afterCreatePaymentPage` | `(CreatePaymentPageRecord $record, CreatePaymentPageData $data): void` | Après l'appel `createPaymentPage` |

### 7.2 Créer un service applicatif

```php
<?php

declare(strict_types=1);

namespace App\Services;

use AndyDefer\PhpPawapay\Datas\InitiateDepositData;
use AndyDefer\PhpPawapay\Records\InitiateDepositRecord;
use AndyDefer\PhpPawapay\Services\PawapayService;

/**
 * Afya-specific PawaPay service.
 *
 * Relies on the parent service hooks to plug application-level behaviour
 * without altering the PawaPay contract.
 */
final class AfyaPawapayService extends PawapayService
{
    protected function beforeInitiateDeposit(InitiateDepositRecord $record): void
    {
        // Par exemple : vérifier que le contexte applicatif est cohérent.
    }

    protected function afterInitiateDeposit(InitiateDepositRecord $record, InitiateDepositData $data): void
    {
        // Par exemple : persister la tentative de dépôt et notifier l'utilisateur.
    }
}
```

Puis déclare-le dans la config :

```php
// config/pawapay.php
'service_fqcn' => \App\Services\AfyaPawapayService::class,
```

Les `Action` du package résolvent automatiquement cette classe via le conteneur.

### 7.3 Accéder au `data` bag depuis un hook

```php
protected function beforeInitiateDeposit(InitiateDepositRecord $record): void
{
    $orderId = $record->data?->get('order_id');

    if ($orderId !== null) {
        // Résoudre la commande applicative à partir de l'identifiant transmis par le client.
    }
}
```

### 7.4 Ce qu'il ne faut pas faire dans un hook

- Émettre un appel HTTP vers PawaPay : ce n'est pas le rôle du hook, et cela provoquerait un double appel.
- Lever une exception pour « annuler » l'opération : les hooks `before*` peuvent techniquement le faire, mais ce comportement n'est pas prévu par le contrat. Si tu as besoin d'une validation qui bloque l'appel, place-la dans la `FormRequest`.
- Modifier la `Data` : elle est `readonly`. Un hook `after*` observe le résultat, il ne le transforme pas.

---

## 8. Gestion des erreurs

Le package distingue trois niveaux.

### 8.1 Validation (HTTP 422)

Les `FormRequest` rejettent les payloads invalides avant toute exécution.

| Situation | Message type |
|-----------|--------------|
| Champ requis manquant | `The deposit id field is required.` |
| UUID invalide | `The deposit id field must be a valid UUID.` |
| Provider non autorisé | `The selected provider is invalid.` |
| Devise non autorisée | `The selected currency is invalid.` |
| Langue non autorisée | `The selected language is invalid.` |
| Pays non autorisé | `The selected country is invalid.` |
| Type de payeur non autorisé | `The selected payer type is invalid.` |
| Montant ≤ 0 | `The amount field must be at least 0.01.` |
| `return_url` invalide | `The return url field must be a valid URL.` |
| `customer_message` trop court | `The customer message field must be at least 4 characters.` |
| `customer_message` trop long | `The customer message field must not be greater than 22 characters.` |

### 8.2 Refus PawaPay

Deux cas, distingués par le type de retour du service.

**Cas A — `Data` avec `failureReason` non-null (HTTP 200).**

```json
{
  "depositId": null,
  "status": "REJECTED",
  "failureReason": {
    "failureCode": "INVALID_PHONE_NUMBER",
    "failureMessage": "The phone number '2438' seems to be invalid for the provider 'VODACOM_MPESA_COD'."
  },
  "hasFailureReason": true
}
```

**Cas B — `ErrorResponseData` (HTTP non-200).**

L'action propage le code HTTP embarqué.

```json
{
  "message": "The API token in the request is invalid.",
  "status": 401,
  "errorCode": "AUTHENTICATION_ERROR"
}
```

Codes d'erreur possibles :

| Code | Signification |
|------|---------------|
| `NO_AUTHENTICATION` | Aucun token fourni |
| `AUTHENTICATION_ERROR` | Token API invalide |
| `AUTHORISATION_ERROR` | Token non autorisé sur cet endpoint |
| `HTTP_SIGNATURE_ERROR` | Signature HTTP invalide |
| `INVALID_INPUT` | Corps de requête invalide |
| `MISSING_PARAMETER` | Paramètre obligatoire manquant |
| `UNSUPPORTED_PARAMETER` | Paramètre non supporté |
| `INVALID_PARAMETER` | Paramètre invalide |
| `AMOUNT_OUT_OF_BOUNDS` | Montant hors bornes pour le provider |
| `INVALID_AMOUNT` | Montant non supporté par le provider |
| `INVALID_PHONE_NUMBER` | Numéro incompatible avec le provider |
| `INVALID_CURRENCY` | Devise non supportée par le provider |
| `INVALID_PROVIDER` | Provider inconnu |
| `DUPLICATE_METADATA_FIELD` | Champ `metadata` dupliqué |
| `DEPOSITS_NOT_ALLOWED` | Dépôts non autorisés pour ce compte |
| `PAYOUTS_NOT_ALLOWED` | Payouts non autorisés pour ce compte |
| `REFUNDS_NOT_ALLOWED` | Refunds non autorisés pour ce compte |
| `PROVIDER_TEMPORARILY_UNAVAILABLE` | Provider momentanément indisponible |
| `PAYMENT_NOT_APPROVED` | Client n'a pas approuvé le paiement |
| `INSUFFICIENT_BALANCE` | Solde client insuffisant |
| `PAYMENT_IN_PROGRESS` | Paiement en cours, callback non disponible |
| `PAYER_NOT_FOUND` | Payeur introuvable |
| `RECIPIENT_NOT_FOUND` | Bénéficiaire introuvable |
| `MANUALLY_CANCELLED` | Paiement annulé manuellement |
| `PAWAPAY_WALLET_OUT_OF_FUNDS` | Wallet PawaPay à sec |
| `DEPOSIT_ALREADY_REFUNDED` | Dépôt déjà remboursé |
| `AMOUNT_TOO_LARGE` | Montant trop élevé |
| `REFUND_IN_PROGRESS` | Remboursement en cours |
| `WALLET_LIMIT_REACHED` | Plafond wallet atteint |
| `UNSPECIFIED_FAILURE` | Échec non spécifié |
| `UNKNOWN_ERROR` | Erreur non catégorisée |

### 8.3 Erreurs techniques

Levées par le conteneur ou le service si :

- `service_fqcn` n'est pas résolvable (`BindingResolutionException`) ;
- l'implémentation n'implémente pas `PawapayInterface` (`TypeError`) ;
- une erreur réseau se produit dans le SDK sous-jacent.

---

## 9. Tests

```bash
composer test
```

Le package fournit un `IntegrationTestCase` basé sur `orchestra/testbench`. Chaque test enregistre le vrai endpoint via `action_route(...)` et remplace le client HTTP bas niveau par un `MockPawapayClient`.

Pour injecter une configuration alternative, utilise une classe de test dédiée :

```php
<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Tests\Fixtures\Configs;

use AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface;
use AndyDefer\PhpPawapay\Collections\CountryCollection;
use AndyDefer\PhpPawapay\Collections\CurrencyCollection;
use AndyDefer\PhpPawapay\Collections\LanguageCollection;
use AndyDefer\PhpPawapay\Collections\PayerTypeCollection;
use AndyDefer\PhpPawapay\Collections\ProviderCollection;
use AndyDefer\PhpPawapay\Enums\Country;
use AndyDefer\PhpPawapay\Enums\Currency;
use AndyDefer\PhpPawapay\Enums\Language;
use AndyDefer\PhpPawapay\Enums\PawaPayBaseUrl;
use AndyDefer\PhpPawapay\Enums\PayerType;
use AndyDefer\PhpPawapay\Enums\Provider;
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
        return LanguageCollection::from([Language::FR]);
    }

    public function getCountries(): CountryCollection
    {
        return CountryCollection::from([Country::COD]);
    }

    public function getProviders(): ProviderCollection
    {
        return ProviderCollection::from([Provider::VODACOM_MPESA_COD]);
    }

    public function getPayerTypes(): PayerTypeCollection
    {
        return PayerTypeCollection::from([PayerType::MMO]);
    }
}
```

Puis dans un test :

```php
$this->app->instance(PawapayConfigInterface::class, new RestrictedPawapayConfig);
```

---

## 10. Sécurité

- **Routes non protégées par défaut.** Ajoute tes middlewares (`auth:sanctum`, `throttle:60,1`, signature HMAC…) dans le fichier publié.
- **`PAWAPAY_API_TOKEN` côté serveur uniquement.** Ne jamais l'exposer au frontend.
- **Restriction des valeurs** via la config : devises, langues, pays, providers, types de payeur.
- **`customer_message` limité à 22 caractères** conformément à PawaPay.
- **`metadata` limité à 10 clés**.
- **`client_reference_id` limité à 64 caractères**, alphanumérique avec tirets.
- **`phone_number` au format E.164 sans `+`** validé par `FormRequest`.
- **`data` bag** : les champs non réservés sont conservés dans un `StrictAssociative` typé, pas dans un tableau brut non contrôlé.

---

## 11. Compatibilité

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
