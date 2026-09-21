# ResendDepositCallbackAction - Référence Technique

## Description

Action HTTP qui expose l'opération `resendDepositCallback` du service PawaPay. Elle traduit un `ResendDepositCallbackRequest` validé en appel de service, puis renvoie la `ResendDepositCallbackData` en JSON.

## Hiérarchie / Implémentations

```
AbstractAction
    └── ResendDepositCallbackAction
```

Dépendances :
- `Illuminate\Contracts\Container\Container`
- `AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface`
- `AndyDefer\PhpPawapay\Records\ResendDepositCallbackRecord` (via `AbstractRecord`)

## Rôle principal

Point d'entrée HTTP pour demander à PawaPay de renvoyer le webhook d'un dépôt (par exemple si ton serveur ne l'a pas reçu). L'action résout dynamiquement le service PawaPay via le conteneur à partir du FQCN déclaré dans la config (`pawapay.service_fqcn`).

## Installation

Aucune installation spécifique. L'action est branchée sur une route Laravel via `action_route()`.

## API / Méthodes publiques

### `__construct(Container $container, PawapayConfigInterface $config)`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$container` | `Container` | Conteneur IoC pour résoudre le service PawaPay |
| `$config` | `PawapayConfigInterface` | Configuration du package (accès au FQCN du service) |

**Retourne :** rien (constructeur).

### `handle(AbstractRecord $request): ResponseFactory`

Méthode protégée invoquée par `AbstractAction::run()`. Résout le service et exécute l'appel PawaPay.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$request` | `AbstractRecord` | Doit être une instance de `ResendDepositCallbackRecord` (cast documenté en PHPDoc) |

**Retourne :** `ResponseFactory` - Réponse JSON contenant la `ResendDepositCallbackData` sérialisée.

**Exceptions :**
- Toute exception remontée par le service PawaPay (validation VO, erreur réseau, etc.).
- `Illuminate\Contracts\Container\BindingResolutionException` si le FQCN configuré n'est pas résolvable par le conteneur.

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

Route::post('/deposits/resend-callback', action_route(
    ResendDepositCallbackRequest::class,
    ResendDepositCallbackAction::class,
))->name('deposits.resend-callback');
```

```bash
curl -X POST https://app.test/api/pawapay/deposits/resend-callback \
  -H "Content-Type: application/json" \
  -d '{"deposit_id": "9b724dbf-32a7-4e63-96bb-59a4747e43ca"}'
```

La réponse contient `depositId`, `status` (`ACCEPTED` ou `REJECTED`) et éventuellement `failureReason`.

### Cas 2 : Substitution du service via la config

L'utilisateur fournit sa propre classe de service dans `config/pawapay.php`. L'action résout cette classe au runtime.

```php
<?php

// config/pawapay.php
return [
    'service_fqcn' => \App\Payments\CustomPawapayService::class,
    // ...
];
```

L'action appelle alors `CustomPawapayService::resendDepositCallback()` au lieu de `PawapayService::resendDepositCallback()`.

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
ResendDepositCallbackData
    ↓
ResponseFactory::json($data)
    ↓
Réponse HTTP JSON
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| `deposit_id` manquant ou invalide | `Illuminate\Validation\ValidationException` | Renvoyée par `ResendDepositCallbackRequest` → 422 |
| Dépôt introuvable côté PawaPay | Aucune | Renvoyée dans la `Data` avec `isRejected = true` et `failureReason.failureCode = NOT_FOUND` |
| Dépôt en cours de traitement | Aucune | Renvoyée dans la `Data` avec `isRejected = true` et `failureReason.failureCode = PAYMENT_IN_PROGRESS` |
| FQCN service introuvable | `Illuminate\Contracts\Container\BindingResolutionException` | `Target class [X] does not exist.` |
| Service ne respecte pas le contrat | `TypeError` | Levée si la classe configurée n'implémente pas `PawapayInterface` |

## Intégration

- **Avec `ResendDepositCallbackRequest`** : valide le `deposit_id` (UUID).
- **Avec `PawapayConfigInterface`** : lit `service_fqcn` pour résoudre l'implémentation.
- **Avec le conteneur Laravel** : permet la substitution d'implémentation par binding ou par config.
- **Avec `ResponseFactory`** : sérialise la `Data` en JSON, y compris `failureReason`.

## Performance

- **Résolution du service** : `container->make()` est O(1) si le service est un singleton enregistré.
- **Aucune transformation lourde** : le `Record` est transmis tel quel.
- **Appel HTTP bloquant** : une requête vers PawaPay par appel.
- **Usage** : à n'utiliser qu'en cas de webhook manquant, pas en polling régulier.

## Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.1+ | ✅ Complet (`readonly` properties, enums) |
| Laravel 10+ | ✅ Complet (`action_route`, conteneur) |
| Laravel 9 | ⚠️ `action_route` dépend de `laravel-actions` — vérifier la compatibilité |

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
    ]),
);

// $response contient la ResendDepositCallbackData sérialisée :
// - depositId : identifiant PawaPay du dépôt
// - status : ACCEPTED ou REJECTED
// - failureReason : null si succès, sinon code + message
// - isAccepted / isRejected : booléens pratiques
// - hasFailureReason : bool
```

## Voir aussi

- `ResendDepositCallbackRequest` — validation et construction du `Record`
- `PawapayConfigInterface` — contrat de configuration (`service_fqcn`)
- `PawapayInterface` — contrat du service PawaPay
- `ResponseFactory` — construction des réponses HTTP typées