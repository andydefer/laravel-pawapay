# CallbackAction - Référence Technique

## Description

Action HTTP qui expose le point d'entrée webhook PawaPay. Elle lit le payload brut, détecte l'opération à partir des champs discriminants, hydrate le `Struct` correspondant, résout le handler configuré et délègue le dispatch au service PawaPay. Retourne systématiquement un `204 No Content`.

## Hiérarchie / Implémentations

```
AbstractAction
    └── CallbackAction
```

Dépendances :
- `Illuminate\Contracts\Container\Container`
- `Illuminate\Http\Request`
- `AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface`
- `AndyDefer\PhpPawapay\Contracts\PawapayInterface`
- `AndyDefer\PhpPawapay\Contracts\Callbacks\HandlesCallbacksInterface`
- `AndyDefer\PhpPawapay\Enums\CallbackOperationType`
- `AndyDefer\PhpPawapay\Structures\Callbacks\*`

## Rôle principal

Servir de point d'entrée HTTP unique pour les callbacks PawaPay (deposit, payout, refund, checkout). L'action :

1. lit le payload via `$this->request->all()` ;
2. détecte l'opération via `CallbackOperationType::fromPayload()` ;
3. hydrate le `Struct` correspondant via `$operation->structClass()::from()` ;
4. résout le service PawaPay via `config->getServiceFqcn()` ;
5. résout le handler via `config->getHandleCallbackFqcn()` ;
6. délègue au service : `$service->handleCallback($struct, $handler)`.

Le retour est **toujours** `204 No Content`. La vérification de signature et l'idempotence sont à la charge du handler — l'action ne fait que dispatcher.

## Installation

Aucune installation spécifique. L'action est branchée sur une route via `action_route()` avec `EmptyRequest`.

## API / Méthodes publiques

### `__construct(Container $container, PawapayConfigInterface $config, Request $request)`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$container` | `Container` | Conteneur IoC pour résoudre service et handler |
| `$config` | `PawapayConfigInterface` | Configuration du package (FQCN du service et du handler) |
| `$request` | `Request` | Requête HTTP brute contenant le payload PawaPay |

**Retourne :** rien.

### `handle(AbstractRecord $request): ResponseFactory`

Méthode protégée invoquée par `AbstractAction::run()`. Détecte l'opération, hydrate le `Struct`, résout les collaborateurs et délègue au service.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$request` | `AbstractRecord` | Record vide — le payload réel est lu via la `Request` injectée |

**Retourne :** `ResponseFactory` - `ResponseFactory::noContent()` (204).

**Exceptions :**
- `InvalidArgumentException` si le payload ne contient aucun champ discriminant (`checkoutId`, `depositId`, `payoutId`, `refundId`) → propagée en 500 par Laravel.
- `InvalidArgumentException` si un champ requis du `Struct` ciblé est invalide → propagée en 500.
- `Illuminate\Contracts\Container\BindingResolutionException` si un FQCN configuré n'est pas résolvable.

**Exemple :**

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelPawapay\Http\Actions\CallbackAction;
use AndyDefer\DomainStructures\Utils\EmptyRecord;

$action = app(CallbackAction::class);

$response = $action->run(new EmptyRecord);
```

## Cas d'utilisation

### Cas 1 : Route HTTP standard

L'action est branchée sur une route POST avec `EmptyRequest`. Pawapay appelle l'URL sans authentification Laravel (la sécurité est vérifiée dans le handler).

```php
<?php

use AndyDefer\Actions\Http\Requests\EmptyRequest;
use AndyDefer\LaravelPawapay\Http\Actions\CallbackAction;
use Illuminate\Support\Facades\Route;

Route::post('/callback', action_route(
    EmptyRequest::class,
    CallbackAction::class,
))->name('callback');
```

Appel Pawapay (deposit) :

```bash
curl -X POST https://app.test/pawapay/callback \
  -H "Content-Type: application/json" \
  -d '{
    "depositId": "f4401bd2-1568-4140-bf2d-eb77d2b2b639",
    "status": "COMPLETED",
    "amount": "123.00",
    "currency": "ZMW",
    "country": "ZMB",
    "payer": {
      "type": "MMO",
      "accountDetails": {
        "phoneNumber": "260763456789",
        "provider": "MTN_MOMO_ZMB"
      }
    },
    "customerMessage": "To ACME company",
    "clientReferenceId": "REF-987654321",
    "created": "2020-10-19T08:17:01Z",
    "providerTransactionId": "12356789"
  }'
```

Réponse : `204 No Content` (corps vide).

### Cas 2 : Substitution du handler via la config

Un handler applicatif (`AfyaPawapayHandler`, par exemple) est déclaré dans `config/pawapay.php`. L'action le résout automatiquement.

```php
// config/pawapay.php
'handle_callback_fqcn' => \App\Callbacks\AfyaPawapayHandler::class,
```

Comme `AfyaPawapayHandler implements HandlesCallbacksInterface`, aucune modification de l'action n'est nécessaire.

## Flux d'exécution

```
Requête HTTP POST (payload PawaPay)
    ↓
EmptyRequest (aucune validation)
    ↓
EmptyRecord
    ↓
CallbackAction::handle()
    ↓
$payload = $this->request->all()
    ↓
CallbackOperationType::fromPayload($payload)
    ↓
$structClass = $operation->structClass()
    ↓
$struct = $structClass::from($payload)
    ↓
container->make(config->getServiceFqcn())  → PawapayInterface
container->make(config->getHandleCallbackFqcn())  → HandlesCallbacksInterface
    ↓
$service->handleCallback($struct, $handler)
    ↓
ResponseFactory::noContent()  → 204
```

### Ordre de détection du type

| Champ discriminant | Opération |
|--------------------|-----------|
| `checkoutId` (premier) | `CHECKOUT` |
| `depositId` | `DEPOSIT` |
| `payoutId` | `PAYOUT` |
| `refundId` | `REFUND` |

> **Ordre important :** `checkoutId` est vérifié en premier car un checkout contient aussi un objet `deposit`.

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Payload sans champ discriminant | `InvalidArgumentException` | `Unknown Pawapay callback payload: no discriminating field found.` |
| Champ requis du Struct manquant | `InvalidArgumentException` | Message dépendant du `Struct` ciblé |
| Valeur invalide d'un Value Object | `InvalidArgumentException` | Message dépendant du VO |
| FQCN service introuvable | `BindingResolutionException` | `Target class [X] does not exist.` |
| FQCN handler introuvable | `BindingResolutionException` | `Target class [X] does not exist.` |
| Service non conforme | `TypeError` | Si la classe n'implémente pas `PawapayInterface` |
| Handler non conforme | `TypeError` | Si la classe n'implémente pas `HandlesCallbacksInterface` |

**Note :** Aucune de ces exceptions n'est interceptée par l'action. Laravel les convertit en `500` par défaut. Si tu veux un `400` pour les payloads malformés, ajouter un handler d'exceptions dédié ou envelopper l'appel dans un `try/catch` dans une sous-classe.

## Intégration

- **`EmptyRequest`** : Request vide du package `laravel-actions`, remplace une `CallbackRequest` dédiée.
- **`Illuminate\Http\Request`** : injectée pour lire le payload brut, contourne le cycle `AbstractRequest::getRecord()` qui consomme le body.
- **`CallbackOperationType`** : expose `fromPayload()` (détection) et `structClass()` (résolution du Struct).
- **`HandlesCallbacksInterface`** : contrat du handler ; l'action ne connaît que l'interface.
- **`PawapayInterface`** : contrat du service ; l'action ne fait que déléguer.
- **`PawapayConfigInterface`** : expose `service_fqcn` et `handle_callback_fqcn`.
- **`ResponseFactory`** : produit un `204 No Content` standardisé.

## Performance

- **Résolution du service et du handler** : `container->make()` en O(1) sur des singletons.
- **Détection d'opération** : `array_key_exists` sur au plus 4 clés — O(1).
- **Hydratation du Struct** : une seule instance créée, les 3 autres ne sont jamais instanciées.
- **Pas de requête sortante** dans cette action — le service reçoit le callback, mais ne fait pas d'appel HTTP vers Pawapay.
- **Pas de cache** : à ajouter côté handler si la vérification de signature implique une I/O.

## Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.1+ | ✅ Complet (`readonly`, enums, union types) |
| Laravel 10+ | ✅ Complet (`action_route`, conteneur) |
| Laravel 9 | ⚠️ Dépend de `andydefer/laravel-actions` |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelPawapay\Http\Actions\CallbackAction;
use AndyDefer\DomainStructures\Utils\EmptyRecord;

$action = app(CallbackAction::class);

$response = $action->run(new EmptyRecord);

// Le payload est lu depuis la Request injectée.
// Le handler configuré (handle_callback_fqcn) reçoit le Struct typé.
// Réponse : 204 No Content.

// Deux issues côté handler :
// 1. Le handler traite le callback (persistance, notification, etc.)
// 2. Le handler peut lever une exception si la vérification de signature échoue
```

## Voir aussi

- `PawapayConfigInterface` — contrat de configuration (`service_fqcn`, `handle_callback_fqcn`)
- `PawapayInterface` — contrat du service PawaPay
- `HandlesCallbacksInterface` — contrat du handler de callbacks
- `CallbackOperationType` — détection du type d'opération
- `DepositCallbackStruct`, `PayoutCallbackStruct`, `RefundCallbackStruct`, `CheckoutCallbackStruct` — Structures de callback
- `EmptyRequest` — Request vide du package `laravel-actions`
- `ResponseFactory` — construction des réponses HTTP typées