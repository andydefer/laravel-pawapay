# InitiateDepositAction - Référence Technique

## Description

Action HTTP qui expose l'opération `initiateDeposit` du service PawaPay. Elle traduit un `InitiateDepositRecord` en appel de service et renvoie la `InitiateDepositData` en JSON, ou une `ErrorResponseData` avec son code HTTP d'origine.

## Hiérarchie / Implémentations

```
AbstractAction
    └── InitiateDepositAction
```

Dépendances :
- `Illuminate\Contracts\Container\Container`
- `AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface`
- `AndyDefer\PhpPawapay\Contracts\PawapayInterface`
- `AndyDefer\PhpPawapay\Records\InitiateDepositRecord`
- `AndyDefer\PhpPawapay\Datas\ErrorResponseData`

## Rôle principal

Point d'entrée HTTP pour déclencher un dépôt Mobile Money. L'action résout dynamiquement le service via `PawapayConfigInterface::getServiceFqcn()`, ce qui permet à l'application hôte de substituer sa propre implémentation sans modifier l'action. Elle distingue deux issues :

- succès → `InitiateDepositData` sérialisée en JSON avec un statut HTTP `200` ;
- erreur métier → `ErrorResponseData` sérialisée en JSON avec le code HTTP qu'elle porte (`400`, `401`, `403`, `422`, `500`, etc.).

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
| `$request` | `AbstractRecord` | Doit être une instance de `InitiateDepositRecord` |

**Retourne :** `ResponseFactory` - Réponse JSON contenant soit la `InitiateDepositData`, soit l'`ErrorResponseData`.

**Exceptions :**
- `Illuminate\Contracts\Container\BindingResolutionException` si le FQCN configuré n'est pas résolvable.
- Toute exception levée par le service en amont du retour typé.

**Exemple :**

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelPawapay\Http\Actions\InitiateDepositAction;
use AndyDefer\PhpPawapay\Records\InitiateDepositRecord;

$action = app(InitiateDepositAction::class);

$response = $action->run(
    InitiateDepositRecord::from([
        'depositId' => 'f4401bd2-1568-4140-bf2d-eb77d2b2b639',
        'payer' => [
            'type' => 'MMO',
            'accountDetails' => [
                'phoneNumber' => '243812345678',
                'provider' => 'VODACOM_MPESA_COD',
            ],
        ],
        'amount' => 15.00,
        'currency' => 'USD',
        'clientReferenceId' => 'INV-123456',
        'customerMessage' => 'Payment order',
        'metadata' => null,
        'data' => null,
    ]),
);
```

## Cas d'utilisation

### Cas 1 : Route HTTP standard

L'action est branchée sur une route POST. La `InitiateDepositRequest` valide l'entrée et produit le `Record`.

```php
<?php

use AndyDefer\LaravelPawapay\Http\Actions\InitiateDepositAction;
use AndyDefer\LaravelPawapay\Http\Requests\InitiateDepositRequest;
use Illuminate\Support\Facades\Route;

Route::post('/initiate-deposit', action_route(
    InitiateDepositRequest::class,
    InitiateDepositAction::class,
))->name('initiate-deposit');
```

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

Succès (`200`) :

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

Erreur métier (`422`) :

```json
{
  "message": "The phone number '2438' seems to be invalid for the provider 'VODACOM_MPESA_COD'.",
  "status": 422,
  "errorCode": "INVALID_PHONE_NUMBER"
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
InitiateDepositRequest (validation + getRecord)
    ↓
InitiateDepositRecord
    ↓
InitiateDepositAction::handle()
    ↓
container->make(config->getServiceFqcn())
    ↓
Service->initiateDeposit($record)
    ↓
├── InitiateDepositData → ResponseFactory::json($data)             → 200
└── ErrorResponseData    → ResponseFactory::json($error, $status)  → $status
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Champ requis manquant | `Illuminate\Validation\ValidationException` | Renvoyée par `InitiateDepositRequest` → `422` |
| `provider` non autorisé | `Illuminate\Validation\ValidationException` | `The selected provider is invalid.` |
| `currency` non autorisée | `Illuminate\Validation\ValidationException` | `The selected currency is invalid.` |
| `payer_type` non autorisé | `Illuminate\Validation\ValidationException` | `The selected payer type is invalid.` |
| `phone_number` invalide côté PawaPay | Aucune exception | `InitiateDepositData` avec `failureReason.failureCode = INVALID_PHONE_NUMBER` et `status = REJECTED` |
| FQCN service introuvable | `Illuminate\Contracts\Container\BindingResolutionException` | `Target class [X] does not exist.` |
| Service ne respecte pas le contrat | `TypeError` | Si la classe configurée n'implémente pas `PawapayInterface` |
| Erreur PawaPay (`ErrorResponseData`) | Aucune exception | Renvoyée en JSON avec `ErrorResponseData::$status` |

## Intégration

- **`InitiateDepositRequest`** : valide le payload, applique les whitelists de devise/provider/payer type et construit le `Record` avec un `data` bag optionnel pour les champs non réservés.
- **`PawapayConfigInterface`** : expose le `service_fqcn` et les whitelists (`currencies`, `providers`, `payer_types`).
- **`PawapayInterface`** : contrat attendu du service.
- **`ErrorResponseData`** : permet à l'action de propager le code HTTP d'origine.
- **`ResponseFactory`** : sérialise le retour en JSON en respectant le type.

## Performance

- **Résolution du service** : `container->make()` en O(1) sur un singleton enregistré.
- **Pas de transformation lourde** : le `Record` est transmis tel quel.
- **Appel HTTP bloquant** : une requête sortante par invocation, coût dominé par la latence réseau.
- **Idempotence côté PawaPay** : la clé `deposit_id` garantit qu'un appel dupliqué retourne `DUPLICATE_IGNORED` sans rejouer le paiement.

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

use AndyDefer\LaravelPawapay\Http\Actions\InitiateDepositAction;
use AndyDefer\PhpPawapay\Records\InitiateDepositRecord;

$action = app(InitiateDepositAction::class);

$response = $action->run(
    InitiateDepositRecord::from([
        'depositId' => 'f4401bd2-1568-4140-bf2d-eb77d2b2b639',
        'payer' => [
            'type' => 'MMO',
            'accountDetails' => [
                'phoneNumber' => '243812345678',
                'provider' => 'VODACOM_MPESA_COD',
            ],
        ],
        'amount' => 15.00,
        'currency' => 'USD',
        'clientReferenceId' => 'INV-123456',
        'customerMessage' => 'Payment order',
        'metadata' => null,
        'data' => null,
    ]),
);

// Deux issues possibles :
// 1. InitiateDepositData sérialisée → 200 avec `status` (ACCEPTED, REJECTED, DUPLICATE_IGNORED)
// 2. ErrorResponseData sérialisée → code porté par ErrorResponseData::$status
```

## Voir aussi

- `InitiateDepositRequest` — validation, whitelists et construction du `Record`
- `PawapayConfigInterface` — contrat de configuration (`service_fqcn`, `currencies`, `providers`, `payer_types`)
- `PawapayInterface` — contrat du service PawaPay
- `ErrorResponseData` — représentation typée des erreurs du SDK
- `ResponseFactory` — construction des réponses HTTP typées