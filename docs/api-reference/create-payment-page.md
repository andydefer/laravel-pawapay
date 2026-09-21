# CreatePaymentPageAction - Référence Technique

## Description

Action HTTP qui expose l'opération `createPaymentPage` du service PawaPay. Elle traduit une `CreatePaymentPageRequest` validée en appel de service, puis renvoie la `CreatePaymentPageData` en JSON.

## Hiérarchie / Implémentations

```
AbstractAction
    └── CreatePaymentPageAction
```

Dépendances :
- `Illuminate\Contracts\Container\Container`
- `AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface`
- `AndyDefer\PhpPawapay\Records\CreatePaymentPageRecord` (via `AbstractRecord`)

## Rôle principal

Servir de point d'entrée HTTP pour générer une page de paiement PawaPay et récupérer l'URL de redirection. L'action résout dynamiquement le service PawaPay via le conteneur à partir du FQCN déclaré dans la config (`pawapay.service_fqcn`), ce qui permet de substituer l'implémentation sans modifier l'action.

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
| `$request` | `AbstractRecord` | Doit être une instance de `CreatePaymentPageRecord` (cast documenté en PHPDoc) |

**Retourne :** `ResponseFactory` - Réponse JSON contenant la `CreatePaymentPageData` sérialisée.

**Exceptions :**
- Toute exception remontée par le service PawaPay (validation VO, erreur réseau, etc.).
- `Illuminate\Contracts\Container\BindingResolutionException` si le FQCN configuré n'est pas résolvable par le conteneur.

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
        'amountDetails' => [
            'amount' => 25.50,
            'currency' => 'USD',
        ],
        'phoneNumber' => '243812345678',
        'language' => 'EN',
        'country' => 'COD',
        'customerMessage' => 'Payment order',
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

Route::post('/payment-page', action_route(
    CreatePaymentPageRequest::class,
    CreatePaymentPageAction::class,
))->name('payment-page');
```

```bash
curl -X POST https://app.test/api/pawapay/payment-page \
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

La réponse contient `redirectUrl` vers la page hébergée par PawaPay.

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

L'action appelle alors `CustomPawapayService::createPaymentPage()` au lieu de `PawapayService::createPaymentPage()`.

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
CreatePaymentPageData
    ↓
ResponseFactory::json($data)
    ↓
Réponse HTTP JSON (avec redirectUrl)
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Champ obligatoire manquant | `Illuminate\Validation\ValidationException` | Renvoyée par `CreatePaymentPageRequest` → 422 |
| `currency` / `language` / `country` non autorisé | `Illuminate\Validation\ValidationException` | `The selected X is invalid.` |
| FQCN service introuvable | `Illuminate\Contracts\Container\BindingResolutionException` | `Target class [X] does not exist.` |
| Service ne respecte pas le contrat | `TypeError` | Levée si la classe configurée n'implémente pas `PawapayInterface` |
| Erreur PawaPay | Aucune exception | Renvoyée dans la `Data` avec `hasFailureReason = true` |

## Intégration

- **Avec `CreatePaymentPageRequest`** : valide les entrées (URL, enums autorisés depuis la config).
- **Avec `PawapayConfigInterface`** : lit `service_fqcn` pour résoudre l'implémentation.
- **Avec le conteneur Laravel** : permet la substitution d'implémentation par binding ou par config.
- **Avec `ResponseFactory`** : sérialise la `Data` en JSON, y compris `redirectUrl` et `failureReason`.

## Performance

- **Résolution du service** : `container->make()` est O(1) si le service est un singleton enregistré.
- **Aucune transformation lourde** : le `Record` est transmis tel quel.
- **Appel HTTP bloquant** : une requête vers PawaPay par appel.
- **Pas de cache** : chaque appel produit une nouvelle page côté PawaPay.

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

use AndyDefer\LaravelPawapay\Http\Actions\CreatePaymentPageAction;
use AndyDefer\PhpPawapay\Records\CreatePaymentPageRecord;

$action = app(CreatePaymentPageAction::class);

$response = $action->run(
    CreatePaymentPageRecord::from([
        'depositId' => '9b724dbf-32a7-4e63-96bb-59a4747e43ca',
        'returnUrl' => 'https://merchant.example.com/checkout-result',
        'amountDetails' => [
            'amount' => 25.50,
            'currency' => 'USD',
        ],
        'phoneNumber' => '243812345678',
        'language' => 'EN',
        'country' => 'COD',
        'customerMessage' => 'Payment order',
    ]),
);

// $response contient la CreatePaymentPageData sérialisée :
// - redirectUrl : URL vers la page PawaPay
// - failureReason : null si succès
// - hasFailureReason : false si succès
```

## Voir aussi

- `CreatePaymentPageRequest` — validation et construction du `Record`
- `PawapayConfigInterface` — contrat de configuration (`service_fqcn`)
- `PawapayInterface` — contrat du service PawaPay
- `ResponseFactory` — construction des réponses HTTP typées