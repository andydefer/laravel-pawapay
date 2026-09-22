# CreatePaymentPageAction - Référence Technique

## Description

Action HTTP qui expose l'opération `createPaymentPage` du service PawaPay. Elle traduit un `CreatePaymentPageRecord` en appel de service et renvoie la `CreatePaymentPageData` en JSON, ou une `ErrorResponseData` avec son code HTTP d'origine.

## Hiérarchie / Implémentations

```
AbstractAction
    └── CreatePaymentPageAction
```

Dépendances :
- `Illuminate\Contracts\Container\Container`
- `AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface`
- `AndyDefer\PhpPawapay\Contracts\PawapayInterface`
- `AndyDefer\PhpPawapay\Records\CreatePaymentPageRecord`
- `AndyDefer\PhpPawapay\Datas\ErrorResponseData`

## Rôle principal

Servir de point d'entrée HTTP pour générer une page de paiement hébergée par PawaPay et récupérer l'URL de redirection. L'action résout dynamiquement le service via `PawapayConfigInterface::getServiceFqcn()`, ce qui permet à l'application hôte de substituer sa propre implémentation sans modifier l'action. Elle distingue deux issues :

- succès → `CreatePaymentPageData` sérialisée en JSON avec un statut HTTP `200` ;
- erreur métier → `ErrorResponseData` sérialisée en JSON avec le code HTTP qu'elle porte (`401`, `403`, `422`, `500`, etc.).

## Installation

Aucune installation spécifique. L'action est branchée sur une route via `action_route()`.

## API / Méthodes publiques

### `__construct(Container $container, PawapayConfigInterface $config)`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$container` | `Container` | Conteneur IoC utilisé pour résoudre le service PawaPay |
| `$config` | `PawapayConfigInterface` | Configuration du package (accès au FQCN du service) |

**Retourne :** rien.

### `handle(AbstractRecord $request): ResponseFactory`

Méthode protégée invoquée par `AbstractAction::run()`. Résout le service, exécute l'appel et adapte la réponse au type de retour.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$request` | `AbstractRecord` | Doit être une instance de `CreatePaymentPageRecord` |

**Retourne :** `ResponseFactory` - Réponse JSON contenant soit la `CreatePaymentPageData`, soit l'`ErrorResponseData`.

**Exceptions :**
- `Illuminate\Contracts\Container\BindingResolutionException` si le FQCN configuré n'est pas résolvable.
- Toute exception levée par le service en amont du retour typé.

**Exemple :**

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelPawapay\Http\Actions\CreatePaymentPageAction;
use AndyDefer\PhpPawapay\Records\CreatePaymentPageRecord;

$action = app(CreatePaymentPageAction::class);

$response = $action->run(
    CreatePaymentPageRecord::from([
        'depositId' => '9b724dbf-32a7-4e63-96bb-59a4747e43ca',
        'returnUrl' => 'https://merchant.example.com/checkout-result',
        'amountDetails' => ['amount' => 25.50, 'currency' => 'USD'],
        'phoneNumber' => '243812345678',
        'language' => 'FR',
        'country' => 'COD',
        'customerMessage' => 'Payment order',
        'metadata' => null,
        'data' => null,
    ]),
);
```

## Cas d'utilisation

### Cas 1 : Route HTTP standard

L'action est branchée sur une route POST. La `CreatePaymentPageRequest` valide l'entrée et produit le `Record`.

```php
<?php

use AndyDefer\LaravelPawapay\Http\Actions\CreatePaymentPageAction;
use AndyDefer\LaravelPawapay\Http\Requests\CreatePaymentPageRequest;
use Illuminate\Support\Facades\Route;

Route::post('/create-payment-page', action_route(
    CreatePaymentPageRequest::class,
    CreatePaymentPageAction::class,
))->name('create-payment-page');
```

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

Succès (`200`) :

```json
{
  "redirectUrl": "https://sandbox.paywith.pawapay.io/v2?token=xxx",
  "failureReason": null,
  "hasFailureReason": false
}
```

Erreur métier (`422`) :

```json
{
  "message": "The currency is not supported.",
  "status": 422,
  "errorCode": "INVALID_CURRENCY"
}
```

### Cas 2 : Substitution du service via la config

Un service applicatif (`AfyaPawapayService`, par exemple) est déclaré dans `config/pawapay.php`. L'action le résout automatiquement.

```php
// config/pawapay.php
'service_fqcn' => \App\Services\AfyaPawapayService::class,
```

Comme `AfyaPawapayService extends PawapayService` et hérite du contrat `PawapayInterface`, aucune modification de l'action n'est nécessaire.

## Flux d'exécution

```
Requête HTTP POST
    ↓
CreatePaymentPageRequest (validation + getRecord)
    ↓
CreatePaymentPageRecord
    ↓
CreatePaymentPageAction::handle()
    ↓
container->make(config->getServiceFqcn())
    ↓
Service->createPaymentPage($record)
    ↓
├── CreatePaymentPageData → ResponseFactory::json($data)           → 200
└── ErrorResponseData      → ResponseFactory::json($error, $status) → $status
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Champ requis manquant | `Illuminate\Validation\ValidationException` | Renvoyée par `CreatePaymentPageRequest` → `422` |
| `currency`, `language` ou `country` non autorisé | `Illuminate\Validation\ValidationException` | `The selected X is invalid.` |
| FQCN service introuvable | `Illuminate\Contracts\Container\BindingResolutionException` | `Target class [X] does not exist.` |
| Service ne respecte pas le contrat | `TypeError` | Si la classe configurée n'implémente pas `PawapayInterface` |
| Erreur PawaPay (`ErrorResponseData`) | Aucune exception | Renvoyée en JSON avec `ErrorResponseData::$status` |
| Page créée avec `failureReason` | Aucune exception | `CreatePaymentPageData` avec `hasFailureReason = true` |

## Intégration

- **`CreatePaymentPageRequest`** : valide le payload, applique les whitelists de devise/langue/pays et construit le `Record` avec un `data` bag optionnel pour les champs non réservés.
- **`PawapayConfigInterface`** : expose le `service_fqcn` et les whitelists.
- **`PawapayInterface`** : contrat attendu du service.
- **`ErrorResponseData`** : permet à l'action de propager le code HTTP d'origine.
- **`ResponseFactory`** : sérialise le retour en JSON en respectant le type.

## Performance

- **Résolution du service** : `container->make()` en O(1) sur un singleton enregistré.
- **Pas de transformation lourde** : le `Record` est transmis tel quel.
- **Appel HTTP bloquant** : une requête sortante par invocation, coût dominé par la latence réseau.
- **Pas de cache** : chaque appel produit une nouvelle page côté PawaPay.

## Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.1+ | ✅ Complet (`readonly`, enums) |
| Laravel 10+ | ✅ Complet (`action_route`, conteneur) |
| Laravel 9 | ⚠️ Dépend de `andydefer/laravel-actions` |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelPawapay\Http\Actions\CreatePaymentPageAction;
use AndyDefer\PhpPawapay\Records\CreatePaymentPageRecord;

$action = app(CreatePaymentPageAction::class);

$response = $action->run(
    CreatePaymentPageRecord::from([
        'depositId' => '9b724dbf-32a7-4e63-96bb-59a4747e43ca',
        'returnUrl' => 'https://merchant.example.com/checkout-result',
        'amountDetails' => ['amount' => 25.50, 'currency' => 'USD'],
        'phoneNumber' => '243812345678',
        'language' => 'FR',
        'country' => 'COD',
        'customerMessage' => 'Payment order',
        'metadata' => null,
        'data' => null,
    ]),
);

// Deux issues possibles :
// 1. CreatePaymentPageData sérialisée → 200 avec `redirectUrl`
// 2. ErrorResponseData sérialisée → code porté par ErrorResponseData::$status
```

## Voir aussi

- `CreatePaymentPageRequest` — validation, whitelists et construction du `Record`
- `PawapayConfigInterface` — contrat de configuration (`service_fqcn`, `currencies`, `languages`, `countries`)
- `PawapayInterface` — contrat du service PawaPay
- `ErrorResponseData` — représentation typée des erreurs du SDK
- `ResponseFactory` — construction des réponses HTTP typées