# ResendDepositCallbackAction - Référence Technique

## Description

Action HTTP qui expose l'opération `resendDepositCallback` du service PawaPay. Elle traduit un `ResendDepositCallbackRecord` en appel de service et renvoie la `ResendDepositCallbackData` en JSON, ou une `ErrorResponseData` avec son code HTTP d'origine.

## Hiérarchie / Implémentations

```
AbstractAction
    └── ResendDepositCallbackAction
```

Dépendances :
- `Illuminate\Contracts\Container\Container`
- `AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface`
- `AndyDefer\PhpPawapay\Contracts\PawapayInterface`
- `AndyDefer\PhpPawapay\Records\ResendDepositCallbackRecord`
- `AndyDefer\PhpPawapay\Datas\ErrorResponseData`

## Rôle principal

Point d'entrée HTTP pour demander à PawaPay de renvoyer le webhook d'un dépôt (utile lorsqu'un webhook n'a pas été reçu côté serveur). L'action résout dynamiquement le service via `PawapayConfigInterface::getServiceFqcn()`, ce qui permet à l'application hôte de substituer sa propre implémentation sans modifier l'action. Elle distingue deux issues :

- succès → `ResendDepositCallbackData` sérialisée en JSON avec un statut HTTP `200` ;
- erreur métier → `ErrorResponseData` sérialisée en JSON avec le code HTTP qu'elle porte (`400`, `401`, `403`, `500`, etc.).

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
| `$request` | `AbstractRecord` | Doit être une instance de `ResendDepositCallbackRecord` |

**Retourne :** `ResponseFactory` - Réponse JSON contenant soit la `ResendDepositCallbackData`, soit l'`ErrorResponseData`.

**Exceptions :**
- `Illuminate\Contracts\Container\BindingResolutionException` si le FQCN configuré n'est pas résolvable.
- Toute exception levée par le service en amont du retour typé.

**Exemple :**

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelPawapay\Http\Actions\ResendDepositCallbackAction;
use AndyDefer\PhpPawapay\Records\ResendDepositCallbackRecord;

$action = app(ResendDepositCallbackAction::class);

$response = $action->run(
    ResendDepositCallbackRecord::from([
        'depositId' => '9b724dbf-32a7-4e63-96bb-59a4747e43ca',
        'data' => null,
    ]),
);
```

## Cas d'utilisation

### Cas 1 : Route HTTP standard

L'action est branchée sur une route POST. La `ResendDepositCallbackRequest` valide l'entrée et produit le `Record`.

```php
<?php

use AndyDefer\LaravelPawapay\Http\Actions\ResendDepositCallbackAction;
use AndyDefer\LaravelPawapay\Http\Requests\ResendDepositCallbackRequest;
use Illuminate\Support\Facades\Route;

Route::post('/resend-deposit-callback', action_route(
    ResendDepositCallbackRequest::class,
    ResendDepositCallbackAction::class,
))->name('resend-deposit-callback');
```

```bash
curl -X POST https://app.test/pawapay/resend-deposit-callback \
  -H "Content-Type: application/json" \
  -d '{"deposit_id": "9b724dbf-32a7-4e63-96bb-59a4747e43ca"}'
```

Succès (`200`) :

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

Erreur métier (`500`) :

```json
{
  "message": "Unable to process request due to an unknown problem.",
  "status": 500,
  "errorCode": "UNKNOWN_ERROR"
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
ResendDepositCallbackRequest (validation + getRecord)
    ↓
ResendDepositCallbackRecord
    ↓
ResendDepositCallbackAction::handle()
    ↓
container->make(config->getServiceFqcn())
    ↓
Service->resendDepositCallback($record)
    ↓
├── ResendDepositCallbackData → ResponseFactory::json($data)        → 200
└── ErrorResponseData          → ResponseFactory::json($error, $status) → $status
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| `deposit_id` manquant ou invalide | `Illuminate\Validation\ValidationException` | Renvoyée par `ResendDepositCallbackRequest` → `422` |
| Dépôt introuvable côté PawaPay | Aucune exception | `ResendDepositCallbackData` avec `status = REJECTED` et `failureReason.failureCode = NOT_FOUND` |
| Dépôt en cours de traitement | Aucune exception | `ResendDepositCallbackData` avec `status = REJECTED` et `failureReason.failureCode = PAYMENT_IN_PROGRESS` |
| FQCN service introuvable | `Illuminate\Contracts\Container\BindingResolutionException` | `Target class [X] does not exist.` |
| Service ne respecte pas le contrat | `TypeError` | Si la classe configurée n'implémente pas `PawapayInterface` |
| Erreur PawaPay (`ErrorResponseData`) | Aucune exception | Renvoyée en JSON avec `ErrorResponseData::$status` |

## Intégration

- **`ResendDepositCallbackRequest`** : valide le `deposit_id` (UUID) et construit le `Record` avec un `data` bag optionnel pour les champs non réservés.
- **`PawapayConfigInterface`** : expose le `service_fqcn` utilisé pour la résolution.
- **`PawapayInterface`** : contrat attendu du service.
- **`ErrorResponseData`** : permet à l'action de propager le code HTTP d'origine.
- **`ResponseFactory`** : sérialise le retour en JSON en respectant le type.

## Performance

- **Résolution du service** : `container->make()` en O(1) sur un singleton enregistré.
- **Pas de transformation lourde** : le `Record` est transmis tel quel.
- **Appel HTTP bloquant** : une requête sortante par invocation, coût dominé par la latence réseau.
- **Usage** : à réserver aux cas où le webhook initial n'est pas arrivé ; ne pas utiliser en polling régulier.

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

use AndyDefer\LaravelPawapay\Http\Actions\ResendDepositCallbackAction;
use AndyDefer\PhpPawapay\Records\ResendDepositCallbackRecord;

$action = app(ResendDepositCallbackAction::class);

$response = $action->run(
    ResendDepositCallbackRecord::from([
        'depositId' => '9b724dbf-32a7-4e63-96bb-59a4747e43ca',
        'data' => null,
    ]),
);

// Deux issues possibles :
// 1. ResendDepositCallbackData sérialisée → 200 avec `status` (ACCEPTED, REJECTED)
// 2. ErrorResponseData sérialisée → code porté par ErrorResponseData::$status
```

## Voir aussi

- `ResendDepositCallbackRequest` — validation et construction du `Record`
- `PawapayConfigInterface` — contrat de configuration (`service_fqcn`)
- `PawapayInterface` — contrat du service PawaPay
- `ErrorResponseData` — représentation typée des erreurs du SDK
- `ResponseFactory` — construction des réponses HTTP typées