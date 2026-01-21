# Laravel PawaPay SDK

![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue)
![Laravel Version](https://img.shields.io/badge/Laravel-12%2B-orange)
![License](https://img.shields.io/badge/license-MIT-green)
![Tests](https://img.shields.io/badge/tests-integration%20ready-brightgreen)
![Coverage](https://img.shields.io/badge/coverage-comprehensive-blue)
![Mobile Money](https://img.shields.io/badge/Mobile%20Money-Africa-brightgreen)
![API Routes](https://img.shields.io/badge/API%20Routes-automatic-blue)

**Laravel PawaPay SDK** est un package Laravel complet et type-safe pour intégrer les paiements mobile money PawaPay dans 21 marchés africains. Construit avec des pratiques PHP modernes, il fournit une interface fluide pour les pay-ins, pay-outs, la prédiction de fournisseurs, la gestion des webhooks et inclut une API REST complète prête à l'emploi.

## 🚀 Installation

### 1. Installation via Composer

```bash
composer require andydefer/laravel-pawapay
```

### 2. Installation Rapide (Recommandée)

Utilisez la commande d'installation pour publier toutes les ressources en une fois :

```bash
# Installez tout en une seule commande
php artisan pawapay:install

# Installation forcée (écrase les fichiers existants)
php artisan pawapay:install --force
```

### 3. Installation Manuelle (Optionnel)

Si vous préférez un contrôle manuel, publiez des composants spécifiques :

```bash
# Publier uniquement la configuration
php artisan vendor:publish --provider="Pawapay\\PawapayServiceProvider" --tag="pawapay-config"

# Publier les définitions de types TypeScript
php artisan vendor:publish --provider="Pawapay\\PawapayServiceProvider" --tag="pawapay-types"

# Publier le contrôleur API
php artisan vendor:publish --provider="Pawapay\\PawapayServiceProvider" --tag="pawapay-controller"

# Publier les routes personnalisées (optionnel - les routes fonctionnent automatiquement)
php artisan vendor:publish --provider="Pawapay\\PawapayServiceProvider" --tag="pawapay-routes"

# Générer les définitions TypeScript
php artisan pawapay:generate-types
```

### 4. Configuration des Variables d'Environnement

Ajoutez à votre fichier `.env` :

```env
# Environnement (sandbox/production)
PAWAPAY_ENVIRONMENT=sandbox

# Token API de PawaPay
PAWAPAY_API_TOKEN=votre_token_api_ici

# Optionnel : Personnaliser les timeouts et tentatives
PAWAPAY_TIMEOUT=30
PAWAPAY_RETRY_TIMES=3
PAWAPAY_RETRY_SLEEP=100
```

## 📡 Deux Manières d'Utiliser le Package

### Option 1 : Utilisation Directe du SDK (Recommandé pour les Intégrations Personnalisées)

Utilisez le SDK directement dans vos contrôleurs ou services :

```php
use Pawapay\Facades\Pawapay;

// Prédire le fournisseur mobile money
$response = Pawapay::predictProvider('+260763456789');
// Retourne: PredictProviderSuccessResponse ou PredictProviderFailureResponse

// Créer une page de paiement
$requestData = PaymentPageRequestData::fromArray([...]);
$response = Pawapay::createPaymentPage($requestData);
// Retourne: PaymentPageSuccessResponseData ou PaymentPageErrorResponseData

// Initier un dépôt direct
$requestData = InitiateDepositRequestData::fromArray([...]);
$response = Pawapay::initiateDeposit($requestData);
// Retourne: InitiateDepositResponseData

// Vérifier le statut du dépôt
$response = Pawapay::checkDepositStatus('deposit_uuid');
// Retourne: CheckDepositStatusWrapperData
```

### Option 2 : API REST Intégrée (Prête à l'Emploi)

Le package inclut une API REST complète disponible automatiquement via les routes :

```
POST   /api/pawapay/predict-provider
POST   /api/pawapay/payment-page
POST   /api/pawapay/deposits
GET    /api/pawapay/deposits/{depositId}
```

#### Points de Terminaison API Disponibles :

| Méthode | Endpoint | Type de Retour | Description |
|--------|----------|----------------|-------------|
| `POST` | `/api/pawapay/predict-provider` | `PredictProviderSuccessResponse` ou `PredictProviderFailureResponse` | Prédire le fournisseur mobile money depuis un numéro de téléphone |
| `POST` | `/api/pawapay/payment-page` | `PaymentPageSuccessResponseData` ou `PaymentPageErrorResponseData` | Créer une page de paiement hébergée |
| `POST` | `/api/pawapay/deposits` | `InitiateDepositResponseData` | Initier un dépôt direct (sans redirection) |
| `GET` | `/api/pawapay/deposits/{depositId}` | `CheckDepositStatusWrapperData` | Vérifier le statut d'un dépôt |

#### Structure des Réponses API :

L'API retourne directement les objets DTO sans wrapper supplémentaire :

**Réponse de succès (exemple pour predict-provider) :**
```json
{
  "country": "ZMB",
  "provider": "MTN_MOMO_ZMB",
  "phoneNumber": "260763456789"
}
```

**Réponse d'erreur (exemple pour predict-provider) :**
```json
{
  "failureReason": {
    "failureCode": "INVALID_PHONE_NUMBER",
    "failureMessage": "Invalid phone number"
  }
}
```

#### Exemples d'Utilisation de l'API :

```javascript
// Utilisation de l'API fetch en JavaScript - Prédiction de fournisseur
const response = await fetch('/api/pawapay/predict-provider', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify({
        phoneNumber: '+260763456789'
    })
});

const data = await response.json();

// Vérifiez si c'est une réponse de succès
if (data.country && data.provider) {
    console.log('Pays:', data.country); // "ZMB"
    console.log('Fournisseur:', data.provider); // "MTN_MOMO_ZMB"
    console.log('Téléphone:', data.phoneNumber); // "260763456789"
} else if (data.failureReason) {
    console.log('Erreur:', data.failureReason.failureMessage);
    console.log('Code:', data.failureReason.failureCode);
}
```

```bash
# Utilisation de cURL - Création de page de paiement
curl -X POST "http://votre-app.test/api/pawapay/payment-page" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "depositId": "order_123",
    "returnUrl": "https://votre-boutique.com/payment/callback",
    "customerMessage": "Paiement pour la Commande #12345",
    "amountDetails": {
      "amount": "150.00",
      "currency": "ZMW"
    },
    "phoneNumber": "260763456789",
    "language": "EN",
    "country": "ZMB",
    "reason": "Achat en Ligne",
    "metadata": [
      {"orderId": "ORD-123"},
      {"customerId": "cust-456"}
    ]
  }'
```

#### Format des Requêtes/Réponses de l'API :

**1. Prédiction de Fournisseur :**
```json
// Requête
{
  "phoneNumber": "+260763456789"
}

// Réponse de succès
{
  "country": "ZMB",
  "provider": "MTN_MOMO_ZMB",
  "phoneNumber": "260763456789"
}

// Réponse d'erreur
{
  "failureReason": {
    "failureCode": "INVALID_PHONE_NUMBER",
    "failureMessage": "Invalid phone number"
  }
}
```

**2. Création de Page de Paiement :**
```json
// Requête
{
  "depositId": "order_123",
  "returnUrl": "https://votre-boutique.com/payment/callback",
  "customerMessage": "Paiement pour la Commande #12345",
  "amountDetails": {
    "amount": "150.00",
    "currency": "ZMW"
  },
  "phoneNumber": "260763456789",
  "language": "EN",
  "country": "ZMB",
  "reason": "Achat en Ligne",
  "metadata": [
    {"orderId": "ORD-123"},
    {"customerId": "cust-456"}
  ]
}

// Réponse de succès
{
  "redirectUrl": "https://sandbox.pawapay.io/payment/abc123"
}

// Réponse d'erreur
{
  "depositId": "order_123",
  "status": "REJECTED",
  "failureReason": {
    "failureCode": "INVALID_AMOUNT",
    "failureMessage": "Amount is invalid"
  }
}
```

**3. Initiation de Dépôt Direct :**
```json
// Requête
{
  "depositId": "deposit_123",
  "payer": {
    "type": "MMO",
    "accountDetails": {
      "phoneNumber": "+260763456789",
      "provider": "MTN_MOMO_ZMB"
    }
  },
  "amount": "100.00",
  "currency": "ZMW",
  "clientReferenceId": "INV-123456",
  "customerMessage": "Paiement pour services",
  "metadata": [
    {"orderId": "ORD-123"},
    {"customerId": "cust-456"}
  ]
}

// Réponse
{
  "depositId": "deposit_123",
  "status": "ACCEPTED",
  "created": "2024-01-15T10:30:00Z",
  "failureReason": null
}
```

**4. Vérification de Statut de Dépôt :**
```json
// Requête (via paramètre de route)
GET /api/pawapay/deposits/deposit_123

// Réponse (dépôt trouvé)
{
  "status": "FOUND",
  "data": {
    "depositId": "deposit_123",
    "status": "COMPLETED",
    "amount": "100.00",
    "currency": "ZMW",
    "country": "ZMB",
    "payer": {
      "type": "MMO",
      "accountDetails": {
        "phoneNumber": "260763456789",
        "provider": "MTN_MOMO_ZMB"
      }
    },
    "customerMessage": "Paiement pour services",
    "clientReferenceId": "INV-123456",
    "providerTransactionId": "txn_789",
    "created": "2024-01-15T10:30:00Z"
  }
}

// Réponse (dépôt non trouvé)
{
  "status": "NOT_FOUND",
  "data": null
}
```

## 🌍 Pays et Fournisseurs Supportés

PawaPay supporte **21 pays africains** avec leurs fournisseurs mobile money respectifs :

### Couverture Complète des Pays

| Pays | Code | Fournisseurs Supportés | Devise |
|---------|------|-------------------|----------|
| **Bénin** | `BEN` | MTN_MOMO_BEN, MOOV_BEN | XOF |
| **Burkina Faso** | `BFA` | MOOV_BFA, ORANGE_BFA | XOF |
| **Cameroun** | `CMR` | MTN_MOMO_CMR, ORANGE_CMR | XAF |
| **Côte d'Ivoire** | `CIV` | MTN_MOMO_CIV, ORANGE_CIV, WAVE_CIV | XOF |
| **RDC** | `COD` | VODACOM_MPESA_COD, AIRTEL_COD, ORANGE_COD | CDF, USD |
| **Éthiopie** | `ETH` | MPESA_ETH | ETB |
| **Gabon** | `GAB` | AIRTEL_GAB | XAF |
| **Ghana** | `GHA` | MTN_MOMO_GHA, AIRTELTIGO_GHA, VODAFONE_GHA | GHS |
| **Kenya** | `KEN` | MPESA_KEN | KES |
| **Lesotho** | `LSO` | MPESA_LSO | LSL |
| **Malawi** | `MWI` | AIRTEL_MWI, TNM_MWI | MWK |
| **Mozambique** | `MOZ` | MOVITEL_MOZ, VODACOM_MOZ | MZN |
| **Nigeria** | `NGA` | AIRTEL_NGA, MTN_MOMO_NGA | NGN |
| **République du Congo** | `COG` | AIRTEL_COG, MTN_MOMO_COG | XAF |
| **Rwanda** | `RWA` | AIRTEL_RWA, MTN_MOMO_RWA | RWF |
| **Sénégal** | `SEN` | FREE_SEN, ORANGE_SEN, WAVE_SEN | XOF |
| **Sierra Leone** | `SLE` | ORANGE_SLE | SLE |
| **Tanzanie** | `TZA` | AIRTEL_TZA, VODACOM_TZA, TIGO_TZA, HALOTEL_TZA | TZS |
| **Ouganda** | `UGA` | AIRTEL_OAPI_UGA, MTN_MOMO_UGA | UGX |
| **Zambie** | `ZMB` | AIRTEL_OAPI_ZMB, MTN_MOMO_ZMB, ZAMTEL_ZMB | ZMW |

## 💰 Fonctionnalités Principales

### 1. Prédiction de Fournisseur Mobile Money

Détectez automatiquement le fournisseur mobile money à partir d'un numéro de téléphone :

```php
use Pawapay\Facades\Pawapay;

$response = Pawapay::predictProvider('+260763456789');

if ($response instanceof \Pawapay\Data\Responses\PredictProviderSuccessResponse) {
    echo "Pays : " . $response->country->value; // ZMB
    echo "Fournisseur : " . $response->provider->value; // MTN_MOMO_ZMB
    echo "Téléphone : " . $response->phoneNumber; // 260763456789
} else {
    echo "Erreur : " . $response->failureReason->failureMessage;
}
```

### 2. Création de Page de Paiement

Créez des pages de paiement hébergées pour les clients :

```php
use Pawapay\Data\PaymentPage\PaymentPageRequestData;
use Pawapay\Enums\Currency;
use Pawapay\Enums\Language;
use Pawapay\Enums\SupportedCountry;
use Pawapay\Facades\Pawapay;

$requestData = PaymentPageRequestData::fromArray([
    'depositId' => (string) \Illuminate\Support\Str::uuid(),
    'returnUrl' => 'https://votre-boutique.com/payment/complete',
    'customerMessage' => 'Paiement pour la Commande #12345',
    'amountDetails' => [
        'amount' => '150.00',
        'currency' => Currency::ZMW->value,
    ],
    'phoneNumber' => '260763456789',
    'language' => Language::EN->value,
    'country' => SupportedCountry::ZMB->value,
    'reason' => 'Achat en ligne - Électronique',
    'metadata' => [
        ['orderId' => 'ORD-123456789'],
        ['customerId' => 'cust-789012'],
        ['productId' => 'PROD-345678'],
    ],
]);

$response = Pawapay::createPaymentPage($requestData);

if ($response instanceof \Pawapay\Data\PaymentPage\PaymentPageSuccessResponseData) {
    // Redirigez le client vers la page de paiement
    return redirect($response->redirectUrl);
} else {
    // Gérez l'erreur
    return back()->withErrors([
        'payment' => $response->failureReason->failureMessage
    ]);
}
```

### 3. Initiation de Dépôt Direct

Initiez des dépôts de manière programmatique sans rediriger les utilisateurs :

```php
use Pawapay\Data\Deposit\InitiateDepositRequestData;
use Pawapay\Enums\Currency;
use Pawapay\Enums\SupportedProvider;
use Pawapay\Facades\Pawapay;

$requestData = InitiateDepositRequestData::fromArray([
    'depositId' => (string) \Illuminate\Support\Str::uuid(),
    'payer' => [
        'type' => 'MMO',
        'accountDetails' => [
            'phoneNumber' => '260763456789',
            'provider' => SupportedProvider::MTN_MOMO_ZMB->value,
        ],
    ],
    'amount' => '100.00',
    'currency' => Currency::ZMW->value,
    'clientReferenceId' => 'INV-123456',
    'customerMessage' => 'Paiement pour services rendus',
    'metadata' => [
        ['orderId' => 'ORD-123456'],
        ['customerId' => 'customer@email.com'],
        ['isPII' => true],
    ],
]);

$response = Pawapay::initiateDeposit($requestData);

if ($response->isAccepted()) {
    // Dépôt accepté pour traitement
    echo "ID de dépôt : " . $response->depositId;
    echo "Statut : " . $response->status->value; // ACCEPTED
    echo "Créé le : " . $response->created;
} elseif ($response->isRejected()) {
    // Dépôt rejeté
    echo "Rejeté : " . $response->failureReason->failureMessage;
    echo "Code d'erreur : " . $response->failureReason->failureCode->value;
} elseif ($response->isDuplicateIgnored()) {
    // Requête dupliquée (idempotent)
    echo "Duplication ignorée pour : " . $response->depositId;
}
```

### 4. Vérification du Statut des Dépôts

Surveillez le statut des transactions en temps réel :

```php
use Pawapay\Facades\Pawapay;

$depositId = 'votre_depot_uuid';
$response = Pawapay::checkDepositStatus($depositId);

if ($response->isFound()) {
    $deposit = $response->data;

    echo "ID de dépôt : " . $deposit->depositId;
    echo "Montant : " . $deposit->amount . " " . $deposit->currency->value;
    echo "Statut : " . $deposit->status->value;
    echo "Pays : " . $deposit->country->value;
    echo "Téléphone : " . $deposit->payer->accountDetails->phoneNumber;
    echo "Fournisseur : " . $deposit->payer->accountDetails->provider->value;

    // Vérifiez si la transaction est terminée
    if ($deposit->isFinalStatus()) {
        echo "Transaction terminée";
    } elseif ($deposit->isProcessing()) {
        echo "Transaction en cours";
    }

    // Accédez aux métadonnées
    if ($deposit->metadata) {
        foreach ($deposit->metadata as $meta) {
            print_r($meta);
        }
    }

    // Vérifiez la raison de l'échec
    if ($deposit->failureReason) {
        echo "Échec : " . $deposit->failureReason->failureMessage;
        echo "Code : " . $deposit->failureReason->failureCode->value;
    }
} else {
    echo "Dépôt non trouvé";
}
```

## 📊 Référence API Complète

### Énumérations (Enums)

Le package fournit des énumérations complètes pour la sécurité des types :

#### `SupportedCountry` (21 pays)
```php
use Pawapay\Enums\SupportedCountry;

$country = SupportedCountry::ZMB;
echo $country->value; // "ZMB"
echo $country->name; // "Zambie"

// Obtenez tous les fournisseurs pour un pays
$providers = SupportedCountry::ZMB->getProviders();
// Retourne : [SupportedProvider::MTN_MOMO_ZMB, ...]
```

#### `SupportedProvider` (40+ fournisseurs)
```php
use Pawapay\Enums\SupportedProvider;

$provider = SupportedProvider::MTN_MOMO_ZMB;
echo $provider->value; // "MTN_MOMO_ZMB"

// Obtenez le pays du fournisseur
$country = $provider->getCountry();
echo $country->value; // "ZMB"
```

#### `Currency` (17 devises)
```php
use Pawapay\Enums\Currency;

$currency = Currency::ZMW;
echo $currency->value; // "ZMW"

// Communément utilisées :
Currency::ZMW; // Kwacha zambien
Currency::KES; // Shilling kényan
Currency::GHS; // Cédi ghanéen
Currency::NGN; // Naira nigérian
Currency::USD; // Dollar US (RDC)
```

#### `TransactionStatus`
```php
use Pawapay\Enums\TransactionStatus;

// Statuts d'initiation
TransactionStatus::ACCEPTED
TransactionStatus::REJECTED
TransactionStatus::DUPLICATE_IGNORED

// Statuts finaux
TransactionStatus::COMPLETED
TransactionStatus::FAILED

// Statuts intermédiaires
TransactionStatus::SUBMITTED
TransactionStatus::ENQUEUED
TransactionStatus::PROCESSING
TransactionStatus::IN_RECONCILIATION

// Statuts de recherche
TransactionStatus::FOUND
TransactionStatus::NOT_FOUND
```

#### `FailureCode` (27 codes détaillés)
```php
use Pawapay\Enums\FailureCode;

// Erreurs techniques
FailureCode::NO_AUTHENTICATION
FailureCode::INVALID_INPUT
FailureCode::MISSING_PARAMETER
FailureCode::INVALID_AMOUNT
FailureCode::INVALID_PHONE_NUMBER

// Erreurs de transaction
FailureCode::PAYMENT_NOT_APPROVED
FailureCode::INSUFFICIENT_BALANCE
FailureCode::PAYER_NOT_FOUND
FailureCode::MANUALLY_CANCELLED

// Obtenez le code de statut HTTP
$code = FailureCode::INVALID_INPUT;
echo $code->httpStatusCode(); // 400
```

#### `Language`
```php
use Pawapay\Enums\Language;

Language::EN; // Anglais
Language::FR; // Français
```

### Data Transfer Objects (DTOs)

Toutes les interactions API utilisent des DTOs fortement typés :

#### DTOs de Requête
```php
use Pawapay\Data\PaymentPage\PaymentPageRequestData;
use Pawapay\Data\Deposit\InitiateDepositRequestData;

// Depuis un tableau
$paymentRequest = PaymentPageRequestData::fromArray($data);

// Depuis le constructeur (type-safe)
$depositRequest = new InitiateDepositRequestData(
    depositId: 'uuid',
    payer: $payerData,
    amount: '100.00',
    currency: Currency::ZMW,
    // ... autres paramètres
);
```

#### DTOs de Réponse
```php
use Pawapay\Data\PaymentPage\PaymentPageSuccessResponseData;
use Pawapay\Data\PaymentPage\PaymentPageErrorResponseData;
use Pawapay\Data\Deposit\InitiateDepositResponseData;
use Pawapay\Data\Responses\CheckDepositStatusWrapperData;
use Pawapay\Data\Responses\PredictProviderSuccessResponse;
use Pawapay\Data\Responses\PredictProviderFailureResponse;

// Toutes les réponses ont des méthodes d'aide
$response->isAccepted();
$response->isRejected();
$response->isFound();
$response->isNotFound();
$response->isSuccess(); // Pour PredictProviderResponseData
```

### Méthodes du Service

#### Classe `PawapayService`

```php
// 1. Prédiction de Fournisseur
predictProvider(string $phoneNumber): PredictProviderSuccessResponse|PredictProviderFailureResponse

// 2. Pages de Paiement
createPaymentPage(PaymentPageRequestData $request): PaymentPageSuccessResponseData|PaymentPageErrorResponseData

// 3. Dépôts Directs
initiateDeposit(InitiateDepositRequestData $request): InitiateDepositResponseData

// 4. Vérification de Statut
checkDepositStatus(string $depositId): CheckDepositStatusWrapperData
```

## 🎨 Génération de Types TypeScript

### Générer les Définitions TypeScript

```bash
php artisan pawapay:generate-types
```

#### Ce Que Cela Fait
Crée des fichiers TypeScript dans `resources/js/pawapay/` :
- `enums.ts` - Toutes les énumérations Pawapay
- `types.ts` - Toutes les interfaces
- `index.ts` - Exports principaux avec fonctions utilitaires

#### Exemple d'Utilisation
```typescript
import {
  SupportedProvider,
  Currency,
  TransactionStatus,
  isTransactionFinal
} from '@/js/pawapay';

const provider: SupportedProvider = SupportedProvider.MTN_MOMO_ZMB;
const currency: Currency = Currency.ZMW;
const status: TransactionStatus = TransactionStatus.COMPLETED;

if (isTransactionFinal(status)) {
  console.log('Paiement terminé');
}
```

#### Régénération Forcée
```bash
php artisan pawapay:generate-types --force
```

## 🔧 Utilisation Avancée

### Idempotence

Toutes les opérations de dépôt sont idempotentes. Utiliser le même `depositId` plusieurs fois donnera un statut `DUPLICATE_IGNORED` :

```php
// Première requête
$response1 = Pawapay::initiateDeposit($requestData);
// Statut : ACCEPTED

// Deuxième requête identique
$response2 = Pawapay::initiateDeposit($requestData);
// Statut : DUPLICATE_IGNORED (pas de transaction dupliquée)
```

### Support des Métadonnées

Attachez des métadonnées personnalisées aux paiements pour le suivi :

```php
$requestData = PaymentPageRequestData::fromArray([
    // ... autres champs
    'metadata' => [
        ['orderId' => 'ORD-123'],
        ['userId' => 456],
        ['cartId' => 'CART-789'],
        ['channel' => 'web'],
        ['version' => '2.0'],
        ['items' => json_encode(['item1', 'item2'])],
        ['custom_field' => 'custom_value'],
    ],
]);
```

Les métadonnées sont conservées tout au long du cycle de paiement et peuvent être récupérées lors de la vérification du statut du dépôt.

### Normalisation des Numéros de Téléphone

Le package normalise automatiquement les numéros de téléphone :

```php
$response = Pawapay::predictProvider('+260 763-456-789');
echo $response->phoneNumber; // "260763456789" (normalisé)
```

### Bonnes Pratiques de Gestion des Erreurs

```php
use Illuminate\Http\Client\RequestException;
use Pawapay\Exceptions\PawapayApiException;

try {
    $response = Pawapay::predictProvider($phoneNumber);

    if ($response instanceof \Pawapay\Data\Responses\PredictProviderFailureResponse) {
        // L'API a retourné une erreur métier
        $errorCode = $response->failureReason->failureCode;
        $errorMessage = $response->failureReason->failureMessage;

        // Gérez des codes d'erreur spécifiques
        if ($errorCode === FailureCode::INVALID_PHONE_NUMBER) {
            return back()->withErrors(['phone' => 'Numéro de téléphone invalide']);
        }

        if ($errorCode === FailureCode::INSUFFICIENT_BALANCE) {
            return back()->withErrors(['payment' => 'Solde insuffisant']);
        }
    }

    // Traitez la réponse réussie
    return redirect($response->redirectUrl);

} catch (RequestException $e) {
    // Erreur réseau ou HTTP
    Log::error('Requête API PawaPay échouée', [
        'message' => $e->getMessage(),
        'status' => $e->response->status(),
        'body' => $e->response->body(),
    ]);

    return back()->withErrors([
        'payment' => 'Service de paiement temporairement indisponible'
    ]);

} catch (PawapayApiException $e) {
    // Exception spécifique au package
    Log::error('Erreur SDK PawaPay', [
        'message' => $e->getMessage(),
        'data' => $e->getErrorData(),
    ]);

    return back()->withErrors([
        'payment' => 'Erreur de traitement du paiement'
    ]);
}
```

## ⚙️ Détails de Configuration

### Timeouts et Tentatives

Configurez dans `.env` :

```env
# Timeout de requête en secondes
PAWAPAY_TIMEOUT=30

# Nombre de tentatives pour les requêtes échouées
PAWAPAY_RETRY_TIMES=3

# Délai entre les tentatives en millisecondes
PAWAPAY_RETRY_SLEEP=100
```

### En-têtes Personnalisés

Étendez les en-têtes par défaut dans la configuration :

```php
// config/pawapay.php
'defaults' => [
    'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'X-Custom-Header' => 'Votre-Valeur',
    ],
],
```

### Basculement d'Environnement

```php
// Basculer vers la production
config()->set('pawapay.environment', 'production');

// Ou utilisez .env
PAWAPAY_ENVIRONMENT=production
```

## 🔄 Exemples de Workflow Complet

### Flux de Paiement E-commerce (Utilisant l'API Intégrée)

```javascript
// Frontend JavaScript (React/Vue/etc)
async function processPayment(phoneNumber, amount, orderId) {
    try {
        // Étape 1 : Prédire le fournisseur
        const providerResponse = await fetch('/api/pawapay/predict-provider', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phoneNumber })
        });

        const providerData = await providerResponse.json();

        // Vérifiez si c'est une réponse de succès
        if (!providerData.country || !providerData.provider) {
            throw new Error(providerData.failureReason?.failureMessage || 'Impossible de détecter le fournisseur');
        }

        // Étape 2 : Créer la page de paiement
        const paymentResponse = await fetch('/api/pawapay/payment-page', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                depositId: orderId,
                returnUrl: `${window.location.origin}/payment/callback`,
                customerMessage: `Paiement pour la Commande #${orderId}`,
                amountDetails: {
                    amount: amount.toString(),
                    currency: providerData.country === 'ZMB' ? 'ZMW' : 'XOF'
                },
                phoneNumber: providerData.phoneNumber,
                language: navigator.language.startsWith('fr') ? 'FR' : 'EN',
                country: providerData.country,
                reason: 'Achat en ligne',
                metadata: [
                    { orderId },
                    { customerId: 'id-utilisateur-actuel' }
                ]
            })
        });

        const paymentData = await paymentResponse.json();

        // Vérifiez si c'est une réponse de succès
        if (paymentData.redirectUrl) {
            // Redirigez vers la page de paiement PawaPay
            window.location.href = paymentData.redirectUrl;
        } else {
            throw new Error(paymentData.failureReason?.failureMessage || 'Échec de la création du paiement');
        }

    } catch (error) {
        console.error('Erreur de paiement :', error);
        alert('Échec du paiement : ' + error.message);
    }
}
```

### Service d'Abonnement avec Dépôts Directs (Utilisant le SDK)

```php
class SubscriptionController
{
    public function renewSubscription(Subscription $subscription)
    {
        // Obtenez le téléphone de l'utilisateur depuis son profil
        $user = $subscription->user;

        // Créez une requête de dépôt en utilisant le SDK
        $depositId = (string) Str::uuid();

        $response = Pawapay::initiateDeposit([
            'depositId' => $depositId,
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => $user->phone_number,
                    'provider' => $user->mobile_money_provider,
                ],
            ],
            'amount' => $subscription->amount,
            'currency' => 'ZMW',
            'clientReferenceId' => 'SUB-' . $subscription->id,
            'customerMessage' => 'Renouvellement d\'abonnement mensuel',
            'metadata' => [
                ['subscriptionId' => $subscription->id],
                ['userId' => $user->id],
                ['plan' => $subscription->plan],
            ],
        ]);

        // Gérez la réponse
        if ($response->isAccepted()) {
            // Mettez en file d'attente la vérification du statut
            CheckDepositStatus::dispatch($depositId)
                ->delay(now()->addMinutes(5));

            return response()->json([
                'message' => 'Paiement initié',
                'depositId' => $depositId,
            ]);
        } else {
            return response()->json([
                'error' => $response->failureReason->failureMessage,
            ], 422);
        }
    }

    public function webhook(Request $request)
    {
        // Vérifiez la signature du webhook
        $payload = $request->all();
        $depositId = $payload['depositId'];

        // Mettez à jour l'abonnement basé sur le statut
        $status = Pawapay::checkDepositStatus($depositId);

        if ($status->isFound() && $status->data->status === TransactionStatus::COMPLETED) {
            // Mettez à jour l'abonnement
            $metadata = collect($status->data->metadata);
            $subscriptionId = $metadata->firstWhere('subscriptionId');

            Subscription::find($subscriptionId)->update([
                'status' => 'active',
                'renewed_at' => now(),
            ]);
        }

        return response()->json(['status' => 'traité']);
    }
}
```

## 🔐 Bonnes Pratiques de Sécurité

### 1. Stockez les Tokens API de Manière Sécurisée

```env
# Ne committez jamais les tokens dans le contrôle de version
PAWAPAY_API_TOKEN=${PAWAPAY_API_TOKEN}
```

### 2. Validez les Données d'Entrée

```php
use Illuminate\Validation\Rule;

$validated = $request->validate([
    'phone' => [
        'required',
        'string',
        'regex:/^(?:\+?\d{1,3}[- ]?)?\d{6,14}$/'
    ],
    'amount' => [
        'required',
        'numeric',
        'min:1',
        'max:100000' // Définissez des limites raisonnables
    ],
    'currency' => [
        'required',
        Rule::in(array_column(Currency::cases(), 'value'))
    ],
]);
```

### 3. Implémentez la Vérification de Signature des Webhooks

```php
public function handleWebhook(Request $request)
{
    $signature = $request->header('X-PawaPay-Signature');
    $payload = $request->getContent();
    $secret = config('services.pawapay.webhook_secret');

    $expectedSignature = hash_hmac('sha256', $payload, $secret);

    if (!hash_equals($expectedSignature, $signature)) {
        abort(401, 'Signature de webhook invalide');
    }

    // Traitez le webhook
}
```

### 4. Surveillez et Journalisez les Transactions

```php
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function initiatePayment($data)
    {
        try {
            $response = Pawapay::createPaymentPage($data);

            Log::info('Paiement initié', [
                'depositId' => $data['depositId'],
                'amount' => $data['amountDetails']['amount'],
                'currency' => $data['amountDetails']['currency'],
                'response_status' => $response->status ?? 'inconnu',
            ]);

            return $response;

        } catch (Exception $e) {
            Log::error('Échec d\'initiation de paiement', [
                'depositId' => $data['depositId'] ?? 'inconnu',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
```

## 🔄 Guide de Migration

### Des Appels API Bruts au Package

**Avant :**

```php
public function makePayment($data)
{
    $response = Http::withToken(config('pawapay.token'))
        ->post('https://api.sandbox.pawapay.io/v2/paymentpage', $data);

    if ($response->failed()) {
        throw new Exception('Échec du paiement : ' . $response->body());
    }

    return $response->json();
}
```

**Après :**

```php
use Pawapay\Facades\Pawapay;
use Pawapay\Data\PaymentPage\PaymentPageRequestData;

public function makePayment($data)
{
    $requestData = PaymentPageRequestData::fromArray($data);
    $response = Pawapay::createPaymentPage($requestData);

    if ($response instanceof \Pawapay\Data\PaymentPage\PaymentPageErrorResponseData) {
        throw new Exception('Échec du paiement : ' . $response->failureReason->failureMessage);
    }

    return $response;
}
```

### API Intégrée vs Implémentation Personnalisée

| Approche | Idéal Pour | Avantages |
|----------|----------|------|
| **API Intégrée** | Configuration rapide, SPAs, applications mobiles | Configuration zéro, validation automatique, prêt à l'emploi |
| **SDK Direct** | Logique métier personnalisée, workflows complexes | Contrôle total, intégration directe, gestion d'erreurs personnalisée |
| **Routes Personnalisées** | Personnalisation API avancée | Contrôle complet sur les routes et middleware |

## 🤝 Contribution

Nous accueillons les contributions ! Voici comment commencer :

### 1. Forkez le Dépôt

```bash
git clone https://github.com/andydefer/laravel-pawapay.git
cd laravel-pawapay
composer install
```

### 2. Exécutez les Tests

```bash
# Tests unitaires
composer test

# Tests d'intégration (nécessite un token sandbox)
PAWAPAY_API_TOKEN=votre_token composer test --group=integration

# Style de code
composer lint

# Analyse statique
composer analyse
```

### 3. Workflow de Développement

```bash
# 1. Créez une branche de fonctionnalité
git checkout -b feature/nouveau-support-fournisseur

# 2. Faites vos modifications
# 3. Ajoutez des tests
# 4. Exécutez les tests
composer test

# 5. Vérifiez le style de code
composer lint

# 6. Committez avec un message descriptif
git commit -m "feat: ajouter le support pour un nouveau fournisseur mobile money"

# 7. Poussez et créez une PR
git push origin feature/nouveau-support-fournisseur
```

### 4. Standards de Codage

- Suivez les standards de codage PSR-12
- Écrivez du code compatible PHPStan niveau 9
- Ajoutez des indications de type pour toutes les méthodes
- Incluez des tests complets
- Mettez à jour la documentation pour les nouvelles fonctionnalités

## 📚 Ressources Supplémentaires

### Documentation Officielle
- [Documentation API PawaPay](https://docs.pawapay.io)
- [Documentation Laravel](https://laravel.com/docs)

### Communauté
- [Problèmes GitHub](https://github.com/andydefer/laravel-pawapay/issues)
- [Communauté Discord](https://discord.gg/votre-lien)
- [Mises à jour Twitter](https://twitter.com/votre-handle)

### Packages Similaires
- [Laravel Cashier](https://laravel.com/docs/billing) - Pour l'intégration Stripe
- [Laravel Flutterwave](https://github.com/kingflamez/laravelrave) - Pour les paiements Flutterwave
- [Laravel Paystack](https://github.com/unicodeveloper/laravel-paystack) - Pour l'intégration Paystack

## 📄 Licence

Ce package est un logiciel open-source sous licence [MIT](LICENSE).

## 🏆 Support

Si ce package vous a été utile, pensez à :

- ⭐ Étoiler le dépôt sur GitHub
- 📢 Partager avec votre réseau
- 💼 L'utiliser dans vos projets commerciaux
- 🐛 Signaler des problèmes et suggérer des fonctionnalités

## 📞 Besoin d'Aide ?

- **Documentation** : Consultez le [Wiki GitHub](https://github.com/andydefer/laravel-pawapay/wiki)
- **Problèmes** : [Problèmes GitHub](https://github.com/andydefer/laravel-pawapay/issues)
- **Email** : andykanidimbu@gmail.com

---

**Laravel PawaPay SDK** - Autonomiser le commerce africain avec des paiements mobile money fluides. Construit avec ❤️ pour la communauté Laravel en Afrique et au-delà.