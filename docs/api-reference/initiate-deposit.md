# InitiateDepositAction - Référence Technique

## Description

Action HTTP qui expose l'opération `initiateDeposit` du service PawaPay. Elle traduit un `InitiateDepositRequest` validé en appel de service, puis renvoie l'`InitiateDepositData` en JSON.

## Hiérarchie / Implémentations

```
AbstractAction
    └── InitiateDepositAction
```

Dépendances :
- `Illuminate\Contracts\Container\Container`
- `AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface`
- `AndyDefer\PhpPawapay\Records\InitiateDepositRecord` (via `AbstractRecord`)

## Rôle principal

Point d'entrée HTTP pour déclencher un dépôt Mobile Money. L'action résout dynamiquement le service PawaPay via le conteneur à partir du FQCN déclaré dans la config (`pawapay.service_fqcn`), ce qui permet de substituer l'implémentation sans modifier l'action.

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
| `$request` | `AbstractRecord` | Doit être une instance de `InitiateDepositRecord` (cast documenté en PHPDoc) |

**Retourne :** `ResponseFactory` - Réponse JSON contenant l'`InitiateDepositData` sérialisée.

**Exceptions :**
- Toute exception remontée par le service PawaPay (validation VO, erreur réseau, etc.).
- `Illuminate\Contracts\Container\BindingResolutionException` si le FQCN configuré n'est pas résolvable par le conteneur.

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
                'phoneNumber' => '260763456789',
                'provider' => 'MTN_MOMO_ZMB',
            ],
        ],
        'amount' => 15.00,
        'currency' => 'ZMW',
        'clientReferenceId' => 'INV-123456',
        'customerMessage' => 'Payment order',
    ]),
);
```

## Cas d'utilisation

### Cas 1 : Route HTTP standard

L'action est branchée sur une route POST. L'`InitiateDepositRequest` valide l'entrée et produit le `Record`.

```php
<?php

use AndyDefer\LaravelPawapay\Http\Actions\InitiateDepositAction;
use AndyDefer\LaravelPawapay\Http\Requests\InitiateDepositRequest;
use Illuminate\Support\Facades\Route;

Route::post('/deposits', action_route(
    InitiateDepositRequest::class,
    InitiateDepositAction::class,
))->name('deposits.initiate');
```

```bash
curl -X POST https://app.test/api/pawapay/deposits \
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

La réponse contient `depositId`, `status` (`ACCEPTED`, `REJECTED`, `DUPLICATE_IGNORED`) et éventuellement `failureReason`.

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

L'action appelle alors `CustomPawapayService::initiateDeposit()` au lieu de `PawapayService::initiateDeposit()`.

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
InitiateDepositData
    ↓
ResponseFactory::json($data)
    ↓
Réponse HTTP JSON
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Champ obligatoire manquant | `Illuminate\Validation\ValidationException` | Renvoyée par `InitiateDepositRequest` → 422 |
| `provider` inconnu | `Illuminate\Validation\ValidationException` | `The selected provider is invalid.` |
| `currency` non autorisée | `Illuminate\Validation\ValidationException` | `The selected currency is invalid.` |
| `phone_number` invalide au sens PawaPay | Aucune | Renvoyée dans la `Data` avec `failureReason.failureCode = INVALID_PHONE_NUMBER` |
| FQCN service introuvable | `Illuminate\Contracts\Container\BindingResolutionException` | `Target class [X] does not exist.` |
| Service ne respecte pas le contrat | `TypeError` | Levée si la classe configurée n'implémente pas `PawapayInterface` |

## Intégration

- **Avec `InitiateDepositRequest`** : valide les entrées et produit le `InitiateDepositRecord`.
- **Avec `PawapayConfigInterface`** : lit `service_fqcn` et `currencies` pour la résolution et la validation.
- **Avec le conteneur Laravel** : permet la substitution d'implémentation par binding ou par config.
- **Avec `ResponseFactory`** : sérialise la `Data` en JSON, y compris `failureReason`.

## Performance

- **Résolution du service** : `container->make()` est O(1) si le service est un singleton enregistré.
- **Aucune transformation lourde** : le `Record` est transmis tel quel.
- **Appel HTTP bloquant** : une requête vers PawaPay par appel.
- **Pas de cache** : chaque appel déclenche un nouveau dépôt. Pour éviter les doublons, s'appuyer sur `depositId` unique + `DUPLICATE_IGNORED` côté PawaPay.

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

use AndyDefer\LaravelPawapay\Http\Actions\InitiateDepositAction;
use AndyDefer\PhpPawapay\Records\InitiateDepositRecord;

$action = app(InitiateDepositAction::class);

$response = $action->run(
    InitiateDepositRecord::from([
        'depositId' => 'f4401bd2-1568-4140-bf2d-eb77d2b2b639',
        'payer' => [
            'type' => 'MMO',
            'accountDetails' => [
                'phoneNumber' => '260763456789',
                'provider' => 'MTN_MOMO_ZMB',
            ],
        ],
        'amount' => 15.00,
        'currency' => 'ZMW',
        'clientReferenceId' => 'INV-123456',
        'customerMessage' => 'Payment order',
    ]),
);

// $response contient l'InitiateDepositData sérialisée :
// - depositId : identifiant PawaPay du dépôt
// - status : ACCEPTED, REJECTED ou DUPLICATE_IGNORED
// - created : timestamp de création
// - failureReason : null si succès, sinon code + message
// - isAccepted / isRejected / isDuplicateIgnored : booléens pratiques
```

## Voir aussi

- `InitiateDepositRequest` — validation et construction du `Record`
- `PawapayConfigInterface` — contrat de configuration (`service_fqcn`, `currencies`)
- `PawapayInterface` — contrat du service PawaPay
- `ResponseFactory` — construction des réponses HTTP typées