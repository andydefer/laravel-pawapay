# CheckDepositStatusAction - Référence Technique

## Description

Action HTTP qui expose l'opération `checkDepositStatus` du service PawaPay. Elle traduit une `CheckDepositStatusRequest` validée en appel de service, puis renvoie la `CheckDepositStatusData` en JSON.

## Hiérarchie / Implémentations

```
AbstractAction
    └── CheckDepositStatusAction
```

Dépendances :
- `Illuminate\Contracts\Container\Container`
- `AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface`
- `AndyDefer\PhpPawapay\Records\CheckDepositStatusRecord` (via `AbstractRecord`)

## Rôle principal

Servir de point d'entrée HTTP pour interroger le statut d'un dépôt PawaPay. L'action résout dynamiquement le service PawaPay via le conteneur à partir du FQCN déclaré dans la config (`pawapay.service_fqcn`), ce qui permet à l'utilisateur du package de substituer sa propre implémentation du service sans modifier l'action.

## Installation

Aucune installation spécifique. L'action est chargée par le routeur Laravel via `action_route()`.

## API / Méthodes publiques

### `__construct(Container $container, PawapayConfigInterface $config)`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$container` | `Container` | Conteneur IoC pour résoudre le service PawPay |
| `$config` | `PawapayConfigInterface` | Configuration du package (accès au FQCN du service) |

**Retourne :** rien (constructeur).

### `handle(AbstractRecord $request): ResponseFactory`

Méthode protégée invoquée par `AbstractAction::run()`. Résout le service et exécute l'appel PawaPay.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$request` | `AbstractRecord` | Doit être une instance de `CheckDepositStatusRecord` (cast documenté en PHPDoc) |

**Retourne :** `ResponseFactory` - Réponse JSON contenant la `CheckDepositStatusData` sérialisée.

**Exceptions :**
- Toute exception remontée par le service PawaPay (validation VO, erreur réseau, etc.).
- `Illuminate\Contracts\Container\BindingResolutionException` si le FQCN configuré n'est pas résolvable par le conteneur.

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
    ]),
);
```

## Cas d'utilisation

### Cas 1 : Route HTTP standard

L'action est branchée sur une route POST. La `CheckDepositStatusRequest` valide l'entrée et produit le `Record`, l'action appelle le service.

```php
<?php

use AndyDefer\LaravelPawapay\Http\Actions\CheckDepositStatusAction;
use AndyDefer\LaravelPawapay\Http\Requests\CheckDepositStatusRequest;
use Illuminate\Support\Facades\Route;

Route::post('/deposits/status', action_route(
    CheckDepositStatusRequest::class,
    CheckDepositStatusAction::class,
))->name('deposits.status');
```

```bash
curl -X POST https://app.test/api/pawapay/deposits/status \
  -H "Content-Type: application/json" \
  -d '{"deposit_id": "f4401bd2-1568-4140-bf2d-eb77d2b2b639"}'
```

### Cas 2 : Substitution du service via la config

L'utilisateur fournit sa propre classe de service dans `config/pawapay.php`. L'action résout cette classe au runtime, sans redéploiement.

```php
<?php

// config/pawapay.php
return [
    'service_fqcn' => \App\Payments\CustomPawapayService::class,
    // ...
];
```

L'action appelle alors `CustomPawapayService::checkDepositStatus()` au lieu de `PawapayService::checkDepositStatus()`.

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
CheckDepositStatusData
    ↓
ResponseFactory::json($data)
    ↓
Réponse HTTP JSON
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| `deposit_id` manquant ou invalide | `Illuminate\Validation\ValidationException` | Renvoyée par `CheckDepositStatusRequest` → 422 |
| FQCN service introuvable | `Illuminate\Contracts\Container\BindingResolutionException` | `Target class [X] does not exist.` |
| Service ne respecte pas le contrat | `TypeError` | Levée si la classe configurée n'implémente pas `PawapayInterface` |
| Dépôt inconnu côté PawaPay | Aucune | Renvoyée dans la `Data` avec `isNotFound = true` |

## Intégration

- **Avec `CheckDepositStatusRequest`** : la Request valide et produit le `Record`, l'action consomme le `Record`.
- **Avec `PawapayConfigInterface`** : l'action lit `service_fqcn` pour résoudre l'implémentation.
- **Avec le conteneur Laravel** : permet la substitution d'implémentation par binding ou par config.
- **Avec `ResponseFactory`** : sérialise la `Data` en JSON avec les enums convertis en valeurs scalaires.

## Performance

- **Résolution du service** : `container->make()` est O(1) pour un singleton déjà résolu.
- **Aucune transformation lourde** : le `Record` est transmis tel quel au service.
- **Pas de cache** : chaque requête déclenche un appel HTTP vers PawaPay.
- **Conseil** : si tu veux mutualiser le polling, mets un cache côté client ou expose une route batch, pas ici.

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

use AndyDefer\LaravelPawapay\Http\Actions\CheckDepositStatusAction;
use AndyDefer\PhpPawapay\Records\CheckDepositStatusRecord;

$action = app(CheckDepositStatusAction::class);

$response = $action->run(
    CheckDepositStatusRecord::from([
        'depositId' => 'f4401bd2-1568-4140-bf2d-eb77d2b2b639',
    ]),
);

// $response est une ResponseFactory prête à être retournée par le routeur.
// Le payload contient la CheckDepositStatusData sérialisée.
```

## Voir aussi

- `CheckDepositStatusRequest` — validation et construction du `Record`
- `PawapayConfigInterface` — contrat de configuration (`service_fqcn`)
- `PawapayInterface` — contrat du service PawaPay
- `ResponseFactory` — construction des réponses HTTP typées