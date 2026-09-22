# CheckDepositStatusAction - Référence Technique

## Description

Action HTTP qui expose l'opération `checkDepositStatus` du service PawaPay. Elle traduit un `CheckDepositStatusRecord` en appel de service et renvoie la `CheckDepositStatusData` en JSON, ou une `ErrorResponseData` avec son code HTTP d'origine.

## Hiérarchie / Implémentations

```
AbstractAction
    └── CheckDepositStatusAction
```

Dépendances :
- `Illuminate\Contracts\Container\Container`
- `AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface`
- `AndyDefer\PhpPawapay\Contracts\PawapayInterface`
- `AndyDefer\PhpPawapay\Records\CheckDepositStatusRecord`
- `AndyDefer\PhpPawapay\Datas\ErrorResponseData`

## Rôle principal

Servir de point d'entrée HTTP pour interroger le statut d'un dépôt PawaPay. L'action résout dynamiquement le service via `PawapayConfigInterface::getServiceFqcn()`, ce qui permet à l'application hôte de substituer sa propre implémentation sans modifier l'action. Elle distingue deux issues :

- succès → `CheckDepositStatusData` sérialisée en JSON avec un statut HTTP `200` ;
- erreur métier → `ErrorResponseData` sérialisée en JSON avec le code HTTP qu'elle porte (`401`, `403`, `500`, etc.).

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
| `$request` | `AbstractRecord` | Doit être une instance de `CheckDepositStatusRecord` |

**Retourne :** `ResponseFactory` - Réponse JSON contenant soit la `CheckDepositStatusData`, soit l'`ErrorResponseData`.

**Exceptions :**
- `Illuminate\Contracts\Container\BindingResolutionException` si le FQCN configuré n'est pas résolvable.
- Toute exception levée par le service en amont du retour typé.

**Exemple :**

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelPawapay\Http\Actions\CheckDepositStatusAction;
use AndyDefer\PhpPawapay\Records\CheckDepositStatusRecord;

$action = app(CheckDepositStatusAction::class);

$response = $action->run(
    CheckDepositStatusRecord::from([
        'depositId' => 'f4401bd2-1568-4140-bf2d-eb77d2b2b639',
        'data' => null,
    ]),
);
```

## Cas d'utilisation

### Cas 1 : Route HTTP standard

L'action est branchée sur une route POST. La `CheckDepositStatusRequest` valide l'entrée et produit le `Record`.

```php
<?php

use AndyDefer\LaravelPawapay\Http\Actions\CheckDepositStatusAction;
use AndyDefer\LaravelPawapay\Http\Requests\CheckDepositStatusRequest;
use Illuminate\Support\Facades\Route;

Route::post('/check-deposit-status', action_route(
    CheckDepositStatusRequest::class,
    CheckDepositStatusAction::class,
))->name('check-deposit-status');
```

```bash
curl -X POST https://app.test/pawapay/check-deposit-status \
  -H "Content-Type: application/json" \
  -d '{"deposit_id": "f4401bd2-1568-4140-bf2d-eb77d2b2b639"}'
```

Succès (`200`) :

```json
{
  "searchStatus": "FOUND",
  "depositData": {
    "depositId": "f4401bd2-1568-4140-bf2d-eb77d2b2b639",
    "status": "COMPLETED",
    "amount": "15.00",
    "currency": "ZMW"
  },
  "isFound": true,
  "isNotFound": false,
  "hasFailureReason": false
}
```

Erreur métier (`401`) :

```json
{
  "message": "The API token in the request is invalid.",
  "status": 401,
  "errorCode": "AUTHENTICATION_ERROR"
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
CheckDepositStatusRequest (validation + getRecord)
    ↓
CheckDepositStatusRecord
    ↓
CheckDepositStatusAction::handle()
    ↓
container->make(config->getServiceFqcn())
    ↓
Service->checkDepositStatus($record)
    ↓
├── CheckDepositStatusData → ResponseFactory::json($data)          → 200
└── ErrorResponseData       → ResponseFactory::json($error, $status) → $status
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| `deposit_id` manquant ou invalide | `Illuminate\Validation\ValidationException` | Renvoyée par `CheckDepositStatusRequest` → `422` |
| FQCN service introuvable | `Illuminate\Contracts\Container\BindingResolutionException` | `Target class [X] does not exist.` |
| Service ne respecte pas le contrat | `TypeError` | Si la classe configurée n'implémente pas `PawapayInterface` |
| Erreur PawaPay (`ErrorResponseData`) | Aucune exception | Renvoyée en JSON avec `ErrorResponseData::$status` |
| Dépôt inconnu côté PawaPay | Aucune exception | `CheckDepositStatusData` avec `isNotFound = true` |

## Intégration

- **`CheckDepositStatusRequest`** : valide le payload et construit le `Record`, y compris un `data` bag optionnel.
- **`PawapayConfigInterface`** : expose le `service_fqcn` utilisé pour la résolution.
- **`PawapayInterface`** : contrat attendu du service, jamais instancié directement.
- **`ErrorResponseData`** : permet à l'action de propager le code HTTP d'origine sans deviner la sémantique de l'erreur.
- **`ResponseFactory`** : sérialise le retour en JSON en respectant le type.

## Performance

- **Résolution du service** : `container->make()` en O(1) sur un singleton enregistré.
- **Pas de transformation lourde** : le `Record` est transmis tel quel.
- **Appel HTTP bloquant** : une requête sortante par invocation, coût dominé par la latence réseau.
- **Pas de cache** : à ajouter côté application hôte si un polling régulier est nécessaire.

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

use AndyDefer\LaravelPawapay\Http\Actions\CheckDepositStatusAction;
use AndyDefer\PhpPawapay\Datas\CheckDepositStatusData;
use AndyDefer\PhpPawapay\Datas\ErrorResponseData;
use AndyDefer\PhpPawapay\Records\CheckDepositStatusRecord;

$action = app(CheckDepositStatusAction::class);

$response = $action->run(
    CheckDepositStatusRecord::from([
        'depositId' => 'f4401bd2-1568-4140-bf2d-eb77d2b2b639',
        'data' => null,
    ]),
);

// Deux issues possibles :
// 1. CheckDepositStatusData sérialisée → 200
// 2. ErrorResponseData sérialisée → code porté par ErrorResponseData::$status
```

## Voir aussi

- `CheckDepositStatusRequest` — validation et construction du `Record`
- `PawapayConfigInterface` — contrat de configuration (`service_fqcn`)
- `PawapayInterface` — contrat du service PawaPay
- `ErrorResponseData` — représentation typée des erreurs du SDK
- `ResponseFactory` — construction des réponses HTTP typées