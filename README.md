# Laravel PawaPay SDK

![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue)
![Laravel Version](https://img.shields.io/badge/Laravel-12%2B-orange)
![License](https://img.shields.io/badge/license-MIT-green)
![Tests](https://img.shields.io/badge/tests-integration%20ready-brightgreen)
![Coverage](https://img.shields.io/badge/coverage-comprehensive-blue)
![Mobile Money](https://img.shields.io/badge/Mobile%20Money-Africa-brightgreen)
![API Routes](https://img.shields.io/badge/API%20Routes-automatic-blue)

**Laravel PawaPay SDK** is a comprehensive, type-safe Laravel package for integrating PawaPay Mobile Money payments across 21 African markets. Built with modern PHP practices, it provides a seamless interface for pay-ins, pay-outs, provider prediction, webhook handling, and includes a complete REST API out of the box.

## 🚀 Installation

### 1. Install via Composer

```bash
composer require andydefer/laravel-pawapay
```

### 2. Quick Installation (Recommended)

Use the installation command to publish all resources at once:

```bash
# Install everything in one command
php artisan pawapay:install

# Force installation (overwrites existing files)
php artisan pawapay:install --force
```

### 3. Manual Installation (Optional)

If you prefer manual control, publish specific components:

```bash
# Publish configuration only
php artisan vendor:publish --provider="Pawapay\\PawapayServiceProvider" --tag="pawapay-config"

# Publish TypeScript type definitions
php artisan vendor:publish --provider="Pawapay\\PawapayServiceProvider" --tag="pawapay-types"

# Publish API controller
php artisan vendor:publish --provider="Pawapay\\PawapayServiceProvider" --tag="pawapay-controller"

# Publish custom routes (optional - routes work automatically)
php artisan vendor:publish --provider="Pawapay\\PawapayServiceProvider" --tag="pawapay-routes"

# Generate TypeScript definitions
php artisan pawapay:generate-types
```

### 4. Configure Environment Variables

Add to your `.env` file:

```env
# Environment (sandbox/production)
PAWAPAY_ENVIRONMENT=sandbox

# API Token from PawaPay
PAWAPAY_API_TOKEN=your_api_token_here

# Optional: Customize timeouts and retries
PAWAPAY_TIMEOUT=30
PAWAPAY_RETRY_TIMES=3
PAWAPAY_RETRY_SLEEP=100
```

## 📡 Two Ways to Use the Package

### Option 1: Direct SDK Usage (Recommended for Custom Integrations)

Use the SDK directly in your controllers or services:

```php
use Pawapay\Facades\Pawapay;

// Predict mobile money provider
$provider = Pawapay::predictProvider('+260763456789');

// Create payment page
$paymentPage = Pawapay::createPaymentPage([
    'depositId' => 'order_123',
    'amount' => '100',
    'currency' => 'ZMW',
    'phoneNumber' => '+260763456789',
    'country' => 'ZMB'
]);

// Check deposit status
$status = Pawapay::checkDepositStatus('order_123');
```

### Option 2: Built-in REST API (Ready-to-Use)

The package includes a complete REST API that's automatically available:

#### Available API Endpoints:

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/pawapay/predict-provider` | Predict mobile money provider from phone number |
| `POST` | `/api/pawapay/payment-page` | Create a hosted payment page |
| `POST` | `/api/pawapay/deposits` | Initiate direct deposit (no redirect) |
| `GET` | `/api/pawapay/deposits/{depositId}` | Check deposit status |

#### API Usage Examples:

```javascript
// Using fetch API in JavaScript
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
console.log(data.success); // true or false
console.log(data.data); // Response data
```

```bash
# Using cURL
curl -X POST "http://your-app.test/api/pawapay/predict-provider" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"phoneNumber": "+260763456789"}'
```

#### API Request/Response Format:

**Predict Provider:**
```json
{
  "phoneNumber": "+260763456789"
}
```

**Create Payment Page:**
```json
{
  "depositId": "order_123",
  "returnUrl": "https://yourstore.com/payment/callback",
  "customerMessage": "Payment for Order #12345",
  "amountDetails": {
    "amount": "150.00",
    "currency": "ZMW"
  },
  "phoneNumber": "260763456789",
  "language": "EN",
  "country": "ZMB",
  "reason": "Online Purchase",
  "metadata": [
    {"orderId": "ORD-123"},
    {"customerId": "cust-456"}
  ]
}
```

**API Response Format (All Endpoints):**
```json
{
  "success": true,
  "data": {
    // Response data varies by endpoint
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Error description",
  "message": "Detailed error message"
}
```

## 🌍 Supported Countries & Providers

PawaPay supports **21 African countries** with their respective mobile money providers:

### Complete Country Coverage

| Country | Code | Supported Providers | Currency |
|---------|------|-------------------|----------|
| **Benin** | `BEN` | MTN_MOMO_BEN, MOOV_BEN | XOF |
| **Burkina Faso** | `BFA` | MOOV_BFA, ORANGE_BFA | XOF |
| **Cameroon** | `CMR` | MTN_MOMO_CMR, ORANGE_CMR | XAF |
| **Côte d'Ivoire** | `CIV` | MTN_MOMO_CIV, ORANGE_CIV, WAVE_CIV | XOF |
| **DR Congo** | `COD` | VODACOM_MPESA_COD, AIRTEL_COD, ORANGE_COD | CDF, USD |
| **Ethiopia** | `ETH` | MPESA_ETH | ETB |
| **Gabon** | `GAB` | AIRTEL_GAB | XAF |
| **Ghana** | `GHA` | MTN_MOMO_GHA, AIRTELTIGO_GHA, VODAFONE_GHA | GHS |
| **Kenya** | `KEN` | MPESA_KEN | KES |
| **Lesotho** | `LSO` | MPESA_LSO | LSL |
| **Malawi** | `MWI` | AIRTEL_MWI, TNM_MWI | MWK |
| **Mozambique** | `MOZ` | MOVITEL_MOZ, VODACOM_MOZ | MZN |
| **Nigeria** | `NGA` | AIRTEL_NGA, MTN_MOMO_NGA | NGN |
| **Republic of Congo** | `COG` | AIRTEL_COG, MTN_MOMO_COG | XAF |
| **Rwanda** | `RWA` | AIRTEL_RWA, MTN_MOMO_RWA | RWF |
| **Senegal** | `SEN` | FREE_SEN, ORANGE_SEN, WAVE_SEN | XOF |
| **Sierra Leone** | `SLE` | ORANGE_SLE | SLE |
| **Tanzania** | `TZA` | AIRTEL_TZA, VODACOM_TZA, TIGO_TZA, HALOTEL_TZA | TZS |
| **Uganda** | `UGA` | AIRTEL_OAPI_UGA, MTN_MOMO_UGA | UGX |
| **Zambia** | `ZMB` | AIRTEL_OAPI_ZMB, MTN_MOMO_ZMB, ZAMTEL_ZMB | ZMW |

## 💰 Core Features

### 1. Mobile Money Provider Prediction

Automatically detect the mobile money provider from a phone number:

```php
use Pawapay\Facades\Pawapay;

$response = Pawapay::predictProvider('+260763456789');

if ($response->isSuccess()) {
    echo "Country: " . $response->country->value; // ZMB
    echo "Provider: " . $response->provider->value; // MTN_MOMO_ZMB
    echo "Phone: " . $response->phoneNumber; // 260763456789
} else {
    echo "Error: " . $response->failureReason->failureMessage;
}
```

### 2. Payment Page Creation

Create hosted payment pages for customers:

```php
use Pawapay\Data\PaymentPage\PaymentPageRequestData;
use Pawapay\Enums\Currency;
use Pawapay\Enums\Language;
use Pawapay\Enums\SupportedCountry;
use Pawapay\Facades\Pawapay;

$requestData = PaymentPageRequestData::fromArray([
    'depositId' => (string) \Illuminate\Support\Str::uuid(),
    'returnUrl' => 'https://yourstore.com/payment/complete',
    'customerMessage' => 'Payment for Order #12345',
    'amountDetails' => [
        'amount' => '150.00',
        'currency' => Currency::ZMW->value,
    ],
    'phoneNumber' => '260763456789',
    'language' => Language::EN->value,
    'country' => SupportedCountry::ZMB->value,
    'reason' => 'Online Purchase - Electronics',
    'metadata' => [
        ['orderId' => 'ORD-123456789'],
        ['customerId' => 'cust-789012'],
        ['productId' => 'PROD-345678'],
    ],
]);

$response = Pawapay::createPaymentPage($requestData);

if ($response->isSuccess()) {
    // Redirect customer to payment page
    return redirect($response->redirectUrl);
} else {
    // Handle error
    return back()->withErrors([
        'payment' => $response->failureReason->failureMessage
    ]);
}
```

### 3. Direct Deposit Initiation

Initiate deposits programmatically without redirecting users:

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
    'customerMessage' => 'Payment for services rendered',
    'metadata' => [
        ['orderId' => 'ORD-123456'],
        ['customerId' => 'customer@email.com'],
        ['isPII' => true],
    ],
]);

$response = Pawapay::initiateDeposit($requestData);

if ($response->isAccepted()) {
    // Deposit accepted for processing
    echo "Deposit ID: " . $response->depositId;
    echo "Status: " . $response->status->value; // ACCEPTED
    echo "Created: " . $response->created;
} elseif ($response->isRejected()) {
    // Deposit rejected
    echo "Rejected: " . $response->failureReason->failureMessage;
    echo "Error Code: " . $response->failureReason->failureCode->value;
} elseif ($response->isDuplicateIgnored()) {
    // Duplicate request (idempotent)
    echo "Duplicate ignored for: " . $response->depositId;
}
```

### 4. Deposit Status Checking

Monitor transaction status in real-time:

```php
use Pawapay\Facades\Pawapay;

$depositId = 'your_deposit_uuid';
$status = Pawapay::checkDepositStatus($depositId);

if ($status->isFound()) {
    $deposit = $status->data;

    echo "Deposit ID: " . $deposit->depositId;
    echo "Amount: " . $deposit->amount . " " . $deposit->currency->value;
    echo "Status: " . $deposit->status->value;
    echo "Country: " . $deposit->country->value;
    echo "Phone: " . $deposit->payer->accountDetails->phoneNumber;
    echo "Provider: " . $deposit->payer->accountDetails->provider->value;

    // Check if transaction is complete
    if ($deposit->isFinalStatus()) {
        echo "Transaction completed";
    } elseif ($deposit->isProcessing()) {
        echo "Transaction in progress";
    }

    // Access metadata
    if ($deposit->metadata) {
        foreach ($deposit->metadata as $meta) {
            print_r($meta);
        }
    }

    // Check for failure reason
    if ($deposit->failureReason) {
        echo "Failure: " . $deposit->failureReason->failureMessage;
        echo "Code: " . $deposit->failureReason->failureCode->value;
    }
} else {
    echo "Deposit not found";
}
```

## 📊 Complete API Reference

### Enums

The package provides comprehensive enums for type safety:

#### `SupportedCountry` (21 countries)
```php
use Pawapay\Enums\SupportedCountry;

$country = SupportedCountry::ZMB;
echo $country->value; // "ZMB"
echo $country->name; // "Zambia"

// Get all providers for a country
$providers = SupportedCountry::ZMB->getProviders();
// Returns: [SupportedProvider::MTN_MOMO_ZMB, ...]
```

#### `SupportedProvider` (40+ providers)
```php
use Pawapay\Enums\SupportedProvider;

$provider = SupportedProvider::MTN_MOMO_ZMB;
echo $provider->value; // "MTN_MOMO_ZMB"

// Get country from provider
$country = $provider->getCountry();
echo $country->value; // "ZMB"
```

#### `Currency` (17 currencies)
```php
use Pawapay\Enums\Currency;

$currency = Currency::ZMW;
echo $currency->value; // "ZMW"

// Commonly used:
Currency::ZMW; // Zambian Kwacha
Currency::KES; // Kenyan Shilling
Currency::GHS; // Ghanaian Cedi
Currency::NGN; // Nigerian Naira
Currency::USD; // US Dollar (DR Congo)
```

#### `TransactionStatus`
```php
use Pawapay\Enums\TransactionStatus;

// Initiation statuses
TransactionStatus::ACCEPTED
TransactionStatus::REJECTED
TransactionStatus::DUPLICATE_IGNORED

// Final statuses
TransactionStatus::COMPLETED
TransactionStatus::FAILED

// Intermediate statuses
TransactionStatus::SUBMITTED
TransactionStatus::ENQUEUED
TransactionStatus::PROCESSING
TransactionStatus::IN_RECONCILIATION

// Search statuses
TransactionStatus::FOUND
TransactionStatus::NOT_FOUND
```

#### `FailureCode` (27 detailed codes)
```php
use Pawapay\Enums\FailureCode;

// Technical errors
FailureCode::NO_AUTHENTICATION
FailureCode::INVALID_INPUT
FailureCode::MISSING_PARAMETER
FailureCode::INVALID_AMOUNT
FailureCode::INVALID_PHONE_NUMBER

// Transaction errors
FailureCode::PAYMENT_NOT_APPROVED
FailureCode::INSUFFICIENT_BALANCE
FailureCode::PAYER_NOT_FOUND
FailureCode::MANUALLY_CANCELLED

// Get HTTP status code
$code = FailureCode::INVALID_INPUT;
echo $code->httpStatusCode(); // 400
```

#### `Language`
```php
use Pawapay\Enums\Language;

Language::EN; // English
Language::FR; // French
```

### Data Transfer Objects (DTOs)

All API interactions use strongly-typed DTOs:

#### Request DTOs
```php
use Pawapay\Data\PaymentPage\PaymentPageRequestData;
use Pawapay\Data\Deposit\InitiateDepositRequestData;

// From array
$paymentRequest = PaymentPageRequestData::fromArray($data);

// From constructor (type-safe)
$depositRequest = new InitiateDepositRequestData(
    depositId: 'uuid',
    payer: $payerData,
    amount: '100.00',
    currency: Currency::ZMW,
    // ... other parameters
);
```

#### Response DTOs
```php
use Pawapay\Data\PaymentPage\PaymentPageSuccessResponseData;
use Pawapay\Data\PaymentPage\PaymentPageErrorResponseData;
use Pawapay\Data\Deposit\InitiateDepositResponseData;
use Pawapay\Data\Responses\CheckDepositStatusWrapperData;

// All responses have helper methods
$response->isSuccess();
$response->isFailure();
$response->isAccepted();
$response->isRejected();
$response->isFound();
$response->isNotFound();
```

### Service Methods

#### `PawapayService` Class

```php
// 1. Provider Prediction
predictProvider(string $phoneNumber): PredictProviderSuccessResponse|PredictProviderFailureResponse

// 2. Payment Pages
createPaymentPage(PaymentPageRequestData $request): PaymentPageSuccessResponseData|PaymentPageErrorResponseData

// 3. Direct Deposits
initiateDeposit(InitiateDepositRequestData $request): InitiateDepositResponseData

// 4. Status Checking
checkDepositStatus(string $depositId): CheckDepositStatusWrapperData
```

## 🎨 TypeScript Type Generation

### Generate TypeScript Definitions

```bash
php artisan pawapay:generate-types
```

#### What It Does
Creates TypeScript files in `resources/js/pawapay/`:
- `enums.ts` - All Pawapay enums
- `types.ts` - All interfaces
- `index.ts` - Main exports with utility functions

#### Usage Example
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
  console.log('Payment completed');
}
```

#### Force Regeneration
```bash
php artisan pawapay:generate-types --force
```

## 🔧 Advanced Usage

### Idempotency

All deposit operations are idempotent. Using the same `depositId` multiple times will result in `DUPLICATE_IGNORED` status:

```php
// First request
$response1 = Pawapay::initiateDeposit($requestData);
// Status: ACCEPTED

// Second identical request
$response2 = Pawapay::initiateDeposit($requestData);
// Status: DUPLICATE_IGNORED (no duplicate transaction)
```

### Metadata Support

Attach custom metadata to payments for tracking:

```php
$requestData = PaymentPageRequestData::fromArray([
    // ... other fields
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

Metadata is preserved throughout the payment lifecycle and can be retrieved when checking deposit status.

### Phone Number Normalization

The package automatically normalizes phone numbers:

```php
$response = Pawapay::predictProvider('+260 763-456-789');
echo $response->phoneNumber; // "260763456789" (normalized)
```

### Error Handling Best Practices

```php
use Illuminate\Http\Client\RequestException;
use Pawapay\Exceptions\PawapayApiException;

try {
    $response = Pawapay::predictProvider($phoneNumber);

    if ($response->isFailure()) {
        // API returned a business error
        $errorCode = $response->failureReason->failureCode;
        $errorMessage = $response->failureReason->failureMessage;

        // Handle specific error codes
        if ($errorCode === FailureCode::INVALID_PHONE_NUMBER) {
            return back()->withErrors(['phone' => 'Invalid phone number']);
        }

        if ($errorCode === FailureCode::INSUFFICIENT_BALANCE) {
            return back()->withErrors(['payment' => 'Insufficient balance']);
        }
    }

    // Process successful response
    return redirect($response->redirectUrl);

} catch (RequestException $e) {
    // Network or HTTP error
    Log::error('PawaPay API request failed', [
        'message' => $e->getMessage(),
        'status' => $e->response->status(),
        'body' => $e->response->body(),
    ]);

    return back()->withErrors([
        'payment' => 'Payment service temporarily unavailable'
    ]);

} catch (PawapayApiException $e) {
    // Package-specific exception
    Log::error('PawaPay SDK error', [
        'message' => $e->getMessage(),
        'data' => $e->getErrorData(),
    ]);

    return back()->withErrors([
        'payment' => 'Payment processing error'
    ]);
}
```

## ⚙️ Configuration Details

### Timeouts and Retries

Configure in `.env`:

```env
# Request timeout in seconds
PAWAPAY_TIMEOUT=30

# Number of retry attempts for failed requests
PAWAPAY_RETRY_TIMES=3

# Delay between retries in milliseconds
PAWAPAY_RETRY_SLEEP=100
```

### Custom Headers

Extend default headers in configuration:

```php
// config/pawapay.php
'defaults' => [
    'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'X-Custom-Header' => 'Your-Value',
    ],
],
```

### Environment Switching

```php
// Switch to production
config()->set('pawapay.environment', 'production');

// Or use .env
PAWAPAY_ENVIRONMENT=production
```

## 🔄 Complete Workflow Examples

### E-commerce Checkout Flow (Using Built-in API)

```javascript
// Frontend JavaScript (React/Vue/etc)
async function processPayment(phoneNumber, amount, orderId) {
    try {
        // Step 1: Predict provider
        const providerResponse = await fetch('/api/pawapay/predict-provider', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phoneNumber })
        });

        const providerData = await providerResponse.json();

        if (!providerData.success) {
            throw new Error('Unable to detect mobile money provider');
        }

        // Step 2: Create payment page
        const paymentResponse = await fetch('/api/pawapay/payment-page', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                depositId: orderId,
                returnUrl: `${window.location.origin}/payment/callback`,
                customerMessage: `Payment for Order #${orderId}`,
                amountDetails: {
                    amount: amount.toString(),
                    currency: providerData.data.country === 'ZMB' ? 'ZMW' : 'XOF'
                },
                phoneNumber: providerData.data.phoneNumber,
                language: navigator.language.startsWith('fr') ? 'FR' : 'EN',
                country: providerData.data.country,
                reason: 'Online Store Purchase',
                metadata: [
                    { orderId },
                    { customerId: 'current-user-id' }
                ]
            })
        });

        const paymentData = await paymentResponse.json();

        if (paymentData.success) {
            // Redirect to PawaPay payment page
            window.location.href = paymentData.data.redirectUrl;
        } else {
            throw new Error(paymentData.error || 'Payment creation failed');
        }

    } catch (error) {
        console.error('Payment error:', error);
        alert('Payment failed: ' + error.message);
    }
}
```

### Subscription Service with Direct Deposits (Using SDK)

```php
class SubscriptionController
{
    public function renewSubscription(Subscription $subscription)
    {
        // Get user's phone from profile
        $user = $subscription->user;

        // Create deposit request using SDK
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
            'customerMessage' => 'Monthly subscription renewal',
            'metadata' => [
                ['subscriptionId' => $subscription->id],
                ['userId' => $user->id],
                ['plan' => $subscription->plan],
            ],
        ]);

        // Handle response
        if ($response->isAccepted()) {
            // Queue status check
            CheckDepositStatus::dispatch($depositId)
                ->delay(now()->addMinutes(5));

            return response()->json([
                'message' => 'Payment initiated',
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
        // Verify webhook signature
        $payload = $request->all();
        $depositId = $payload['depositId'];

        // Update subscription based on status
        $status = Pawapay::checkDepositStatus($depositId);

        if ($status->isFound() && $status->data->status === TransactionStatus::COMPLETED) {
            // Update subscription
            $metadata = collect($status->data->metadata);
            $subscriptionId = $metadata->firstWhere('subscriptionId');

            Subscription::find($subscriptionId)->update([
                'status' => 'active',
                'renewed_at' => now(),
            ]);
        }

        return response()->json(['status' => 'processed']);
    }
}
```

## 🔐 Security Best Practices

### 1. Store API Tokens Securely

```env
# Never commit tokens to version control
PAWAPAY_API_TOKEN=${PAWAPAY_API_TOKEN}
```

### 2. Validate Input Data

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
        'max:100000' // Set reasonable limits
    ],
    'currency' => [
        'required',
        Rule::in(array_column(Currency::cases(), 'value'))
    ],
]);
```

### 3. Implement Webhook Signature Verification

```php
public function handleWebhook(Request $request)
{
    $signature = $request->header('X-PawaPay-Signature');
    $payload = $request->getContent();
    $secret = config('services.pawapay.webhook_secret');

    $expectedSignature = hash_hmac('sha256', $payload, $secret);

    if (!hash_equals($expectedSignature, $signature)) {
        abort(401, 'Invalid webhook signature');
    }

    // Process webhook
}
```

### 4. Monitor and Log Transactions

```php
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function initiatePayment($data)
    {
        try {
            $response = Pawapay::createPaymentPage($data);

            Log::info('Payment initiated', [
                'depositId' => $data['depositId'],
                'amount' => $data['amountDetails']['amount'],
                'currency' => $data['amountDetails']['currency'],
                'response_status' => $response->status ?? 'unknown',
            ]);

            return $response;

        } catch (Exception $e) {
            Log::error('Payment initiation failed', [
                'depositId' => $data['depositId'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
```

## 🔄 Migration Guide

### From Raw API Calls to Package

**Before:**

```php
public function makePayment($data)
{
    $response = Http::withToken(config('pawapay.token'))
        ->post('https://api.sandbox.pawapay.io/v2/paymentpage', $data);

    if ($response->failed()) {
        throw new Exception('Payment failed: ' . $response->body());
    }

    return $response->json();
}
```

**After:**

```php
use Pawapay\Facades\Pawapay;
use Pawapay\Data\PaymentPage\PaymentPageRequestData;

public function makePayment($data)
{
    $requestData = PaymentPageRequestData::fromArray($data);
    $response = Pawapay::createPaymentPage($requestData);

    if ($response->isFailure()) {
        throw new Exception('Payment failed: ' . $response->failureReason->failureMessage);
    }

    return $response;
}
```

### Using the Built-in API vs Custom Implementation

| Approach | Best For | Pros |
|----------|----------|------|
| **Built-in API** | Quick setup, SPAs, mobile apps | Zero configuration, automatic validation, ready-to-use |
| **SDK Direct** | Custom business logic, complex workflows | Full control, direct integration, custom error handling |
| **Custom Routes** | Advanced API customization | Complete control over routes and middleware |

## 🤝 Contributing

We welcome contributions! Here's how to get started:

### 1. Fork the Repository

```bash
git clone https://github.com/andydefer/laravel-pawapay.git
cd laravel-pawapay
composer install
```

### 2. Run Tests

```bash
# Unit tests
composer test

# Integration tests (requires sandbox token)
PAWAPAY_API_TOKEN=your_token composer test --group=integration

# Code style
composer lint

# Static analysis
composer analyse
```

### 3. Development Workflow

```bash
# 1. Create a feature branch
git checkout -b feature/new-provider-support

# 2. Make your changes
# 3. Add tests
# 4. Run tests
composer test

# 5. Check code style
composer lint

# 6. Commit with descriptive message
git commit -m "feat: add support for new mobile money provider"

# 7. Push and create PR
git push origin feature/new-provider-support
```

### 4. Coding Standards

- Follow PSR-12 coding standards
- Write PHPStan level 9 compatible code
- Add type hints for all methods
- Include comprehensive tests
- Update documentation for new features

## 📚 Additional Resources

### Official Documentation
- [PawaPay API Documentation](https://docs.pawapay.io)
- [Laravel Documentation](https://laravel.com/docs)

### Community
- [GitHub Issues](https://github.com/andydefer/laravel-pawapay/issues)
- [Discord Community](https://discord.gg/your-link)
- [Twitter Updates](https://twitter.com/your-handle)

### Related Packages
- [Laravel Cashier](https://laravel.com/docs/billing) - For Stripe integration
- [Laravel Flutterwave](https://github.com/kingflamez/laravelrave) - For Flutterwave payments
- [Laravel Paystack](https://github.com/unicodeveloper/laravel-paystack) - For Paystack integration

## 📄 License

This package is open-source software licensed under the [MIT license](LICENSE).

## 🏆 Support

If this package has been helpful to you, consider:

- ⭐ Starring the repository on GitHub
- 📢 Sharing with your network
- 💼 Using it in your commercial projects
- 🐛 Reporting issues and suggesting features

## 📞 Need Help?

- **Documentation**: Check the [GitHub Wiki](https://github.com/andydefer/laravel-pawapay/wiki)
- **Issues**: [GitHub Issues](https://github.com/andydefer/laravel-pawapay/issues)
- **Email**: andykanidimbu@gmail.com

---

**Laravel PawaPay SDK** - Empowering African commerce with seamless mobile money payments. Built with ❤️ for the Laravel community in Africa and beyond.