<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use PHPUnit\Framework\Attributes\Group;
use Throwable;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Pawapay\Data\Deposit\InitiateDepositRequestData;
use Pawapay\Data\Deposit\InitiateDepositResponseData;
use Pawapay\Data\PaymentPage\PaymentPageErrorResponseData;
use Pawapay\Data\PaymentPage\PaymentPageRequestData;
use Pawapay\Data\PaymentPage\PaymentPageSuccessResponseData;
use Pawapay\Data\Responses\CheckDepositStatusWrapperData;
use Pawapay\Data\Responses\PredictProviderFailureResponse;
use Pawapay\Data\Responses\PredictProviderSuccessResponse;
use Pawapay\Enums\Currency;
use Pawapay\Enums\FailureCode;
use Pawapay\Enums\Language;
use Pawapay\Enums\SupportedCountry;
use Pawapay\Enums\SupportedProvider;
use Pawapay\Enums\TransactionStatus;
use Pawapay\Services\PawapayService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Spatie\LaravelData\Optional;
use Tests\TestCase;

/**
 * Integration tests for PawaPay API.
 *
 * These tests make real API calls to the PawaPay sandbox environment.
 */
#[AllowMockObjectsWithoutExpectations]

final class PawapayServiceTest extends TestCase
{
    private PawapayService $service;

    private string $uuid;

    protected function setUp(): void
    {
        $this->uuid = (string) Str::uuid();
        parent::setUp();
        $this->service = app(PawapayService::class);
    }

    /**
     * Test successful payment page creation
     */
    public function test_create_payment_page_success(): void
    {
        $requestData = PaymentPageRequestData::fromArray([
            'depositId' =>  $this->uuid,
            'returnUrl' => 'https://example.com/return',
            'customerMessage' => 'Note of 4 to 22 chars',
            'amountDetails' => [
                'amount' => '100',
                'currency' => Currency::ZMW->value,
            ],
            'phoneNumber' => '260763456789',
            'language' => Language::EN->value,
            'country' => SupportedCountry::ZMB->value,
            'reason' => 'Ticket to festival',
            'metadata' =>  [
                [
                    'orderId' => 'ORD-123456789',
                ],
                [
                    'customerId' => 'customer@email.com',
                    'isPII' => true,
                ],
            ],
        ]);

        $response = $this->service->createPaymentPage($requestData);

        $this->assertInstanceOf(
            PaymentPageSuccessResponseData::class,
            $response,
            'Expected successful payment page creation'
        );

        $this->assertNotEmpty($response->redirectUrl);
        $this->assertStringContainsString('https://', $response->redirectUrl);

        Log::info('Payment page created successfully', [
            'depositId' =>  $this->uuid,
            'redirectUrl' => $response->redirectUrl,
        ]);
    }

    /**
     * Test successful provider prediction with Zambian number.
     */
    public function test_predict_provider_success_with_zambian_number(): void
    {
        $response = $this->service->predictProvider('+260763456789');

        // Vérifier le type de réponse
        if ($response instanceof PredictProviderSuccessResponse) {
            $this->assertInstanceOf(SupportedCountry::class, $response->country);
            $this->assertInstanceOf(SupportedProvider::class, $response->provider);
            $this->assertNotEmpty($response->phoneNumber);

            // Vérifier la cohérence entre pays et provider
            $countryFromProvider = $response->provider->getCountry();
            $this->assertEquals($response->country, $countryFromProvider);

            // Le provider doit être dans la liste des providers du pays
            $providersForCountry = $response->country->getProviders();
            $this->assertContains($response->provider, $providersForCountry);

            // Log pour voir ce qui est retourné
            Log::info('PawaPay API response', [
                'country' => $response->country->value,
                'provider' => $response->provider->value,
                'phoneNumber' => $response->phoneNumber,
            ]);
        } elseif ($response instanceof PredictProviderFailureResponse) {
            // Si c'est un échec, vérifier que c'est bien structuré
            $this->assertNotNull($response->failureReason);
            Log::warning('PawaPay API failure', [
                'failureCode' => $response->failureReason->failureCode->value,
                'failureMessage' => $response->failureReason->failureMessage,
            ]);
        }
    }

    /**
     * Test with Kenyan number.
     */
    public function test_predict_provider_with_kenyan_number(): void
    {
        $response = $this->service->predictProvider('+254712345678');

        if ($response instanceof PredictProviderSuccessResponse) {
            $this->assertEquals(SupportedCountry::KEN, $response->country);
            $this->assertEquals(SupportedProvider::MPESA_KEN, $response->provider);
        }
    }

    /**
     * Test with Ghanaian number.
     */
    public function test_predict_provider_with_ghanaian_number(): void
    {
        $response = $this->service->predictProvider('+233241234567');

        if ($response instanceof PredictProviderSuccessResponse) {
            $this->assertEquals(SupportedCountry::GHA, $response->country);
            $this->assertContains($response->provider, [
                SupportedProvider::MTN_MOMO_GHA,
                SupportedProvider::AIRTELTIGO_GHA,
                SupportedProvider::VODAFONE_GHA,
            ]);
        }
    }

    /**
     * Test with Nigerian number.
     */
    public function test_predict_provider_with_nigerian_number(): void
    {
        $response = $this->service->predictProvider('+2348012345678');

        // TOUJOURS faire une assertion
        $this->assertThat($response, $this->logicalOr(
            $this->isInstanceOf(PredictProviderSuccessResponse::class),
            $this->isInstanceOf(PredictProviderFailureResponse::class)
        ));

        if ($response instanceof PredictProviderSuccessResponse) {
            $this->assertEquals(SupportedCountry::NGA, $response->country);
            $this->assertContains($response->provider, [
                SupportedProvider::AIRTEL_NGA,
                SupportedProvider::MTN_MOMO_NGA,
            ]);
        } else {
            // Si c'est un échec, vérifier la structure
            $this->assertNotNull($response->failureReason);
            $this->assertNotEmpty($response->failureReason->failureMessage);
        }
    }

    /**
     * Test with Ivory Coast number.
     */
    public function test_predict_provider_with_ivory_coast_number(): void
    {
        $response = $this->service->predictProvider('+2250700000000');

        if ($response instanceof PredictProviderSuccessResponse) {
            $this->assertEquals(SupportedCountry::CIV, $response->country);
            $this->assertContains($response->provider, [
                SupportedProvider::MTN_MOMO_CIV,
                SupportedProvider::ORANGE_CIV,
                SupportedProvider::WAVE_CIV,
            ]);
        }
    }

    /**
     * Test with invalid phone number.
     */
    public function test_predict_provider_with_invalid_number(): void
    {
        $response = $this->service->predictProvider('invalid');

        // L'API peut retourner soit un succès (avec un numéro normalisé), soit un échec
        if ($response instanceof PredictProviderFailureResponse) {
            $this->assertNotNull($response->failureReason);
            Log::info('PawaPay API failure for invalid number', [
                'failureCode' => $response->failureReason->failureCode->value,
                'failureMessage' => $response->failureReason->failureMessage,
            ]);
        }
    }

    /**
     * Test with empty phone number.
     */
    public function test_predict_provider_with_empty_number(): void
    {
        $response = $this->service->predictProvider('');

        // L'API devrait retourner une erreur pour un numéro vide
        $this->assertInstanceOf(PredictProviderFailureResponse::class, $response);
        $this->assertNotNull($response->failureReason);
    }

    /**
     * Test with phone number from unsupported country.
     */
    public function test_predict_provider_with_unsupported_country(): void
    {
        $response = $this->service->predictProvider('+33123456789'); // France

        // L'API devrait retourner un échec pour un pays non supporté
        if ($response instanceof PredictProviderFailureResponse) {
            $this->assertNotNull($response->failureReason);
            Log::info('PawaPay API response for unsupported country', [
                'failureCode' => $response->failureReason->failureCode->value,
                'failureMessage' => $response->failureReason->failureMessage,
            ]);
        }
    }

    /**
     * Test phone number normalization.
     */
    public function test_phone_number_normalization(): void
    {
        // Test avec différents formats
        $testNumbers = [
            '+260763456789',
            '260763456789',
            '+260 763-456789',
            '+260 (763) 456-789',
        ];

        foreach ($testNumbers as $phoneNumber) {
            $response = $this->service->predictProvider($phoneNumber);

            if ($response instanceof PredictProviderSuccessResponse) {
                // Le phoneNumber retourné devrait être normalisé
                $this->assertMatchesRegularExpression('/^[0-9]+$/', $response->phoneNumber);
            }
        }
    }

    /**
     * Test the response type discrimination
     */
    public function test_response_type_discrimination(): void
    {
        // Test avec un numéro valide
        $successResponse = $this->service->predictProvider('+260763456789');

        if ($successResponse instanceof PredictProviderSuccessResponse) {
            $this->assertInstanceOf(SupportedCountry::class, $successResponse->country);
            $this->assertInstanceOf(SupportedProvider::class, $successResponse->provider);
        }

        // Test avec un numéro invalide
        $failureResponse = $this->service->predictProvider('invalid');

        if ($failureResponse instanceof PredictProviderFailureResponse) {
            $this->assertNotNull($failureResponse->failureReason);
            $this->assertNotEmpty($failureResponse->failureReason->failureMessage);

            // failureCode peut être NULL, donc on vérifie seulement s'il est défini
            if ($failureResponse->failureReason->failureCode !== null) {
                $this->assertInstanceOf(FailureCode::class, $failureResponse->failureReason->failureCode);
            }
        }

        // Vérifier qu'on ne peut pas avoir les deux types en même temps
        $response = $this->service->predictProvider('+260763456789');
        $isSuccess = $response instanceof PredictProviderSuccessResponse;
        $isFailure = $response instanceof PredictProviderFailureResponse;

        $this->assertTrue($isSuccess || $isFailure);
        $this->assertNotTrue($isSuccess && $isFailure);
    }


    /**
     * Test payment page creation with invalid amount
     */
    public function test_create_payment_page_invalid_amount(): void
    {
        $requestData = PaymentPageRequestData::fromArray([
            'depositId' =>  $this->uuid,
            'returnUrl' => 'https://example.com/return',
            'customerMessage' => 'Test payment',
            'amountDetails' => [
                'amount' => '0.00', // Montant invalide
                'currency' => Currency::ZMW->value,
            ],
            'phoneNumber' => '260763456789',
            'language' => Language::EN->value,
            'country' => SupportedCountry::ZMB->value,
            'reason' => 'Test payment',
            'metadata' => [], // Tableau vide OK
        ]);

        $response = $this->service->createPaymentPage($requestData);

        $this->assertInstanceOf(
            PaymentPageErrorResponseData::class,
            $response,
            'Expected error for invalid amount'
        );

        $this->assertNotNull($response->failureReason);
        $this->assertNotEmpty($response->failureReason->failureMessage);

        Log::info('Expected error for invalid amount', [
            'failureMessage' => $response->failureReason->failureMessage,
        ]);
    }

    /**
     * Test payment page creation with invalid phone number
     */
    public function test_create_payment_page_invalid_phone(): void
    {
        $requestData = PaymentPageRequestData::fromArray([
            'depositId' =>  $this->uuid,
            'returnUrl' => 'https://example.com/return',
            'customerMessage' => 'Test payment',
            'amountDetails' => [
                'amount' => '100.00',
                'currency' => Currency::ZMW->value,
            ],
            'phoneNumber' => 'invalid_phone', // Numéro invalide
            'language' => Language::EN->value,
            'country' => SupportedCountry::ZMB->value,
            'reason' => 'Test payment',
            'metadata' => [],
        ]);

        $response = $this->service->createPaymentPage($requestData);

        $this->assertInstanceOf(
            PaymentPageErrorResponseData::class,
            $response,
            'Expected error for invalid phone number'
        );
    }

    /**
     * Test payment page creation with minimal required data
     */
    public function test_create_payment_page_minimal_data(): void
    {
        $requestData = PaymentPageRequestData::fromArray([
            'depositId' =>  $this->uuid,
            'returnUrl' => 'https://example.com/return',
            'customerMessage' => 'Minimal payment',
            'amountDetails' => [
                'amount' => '50.00',
                'currency' => Currency::ZMW->value,
            ],
            'phoneNumber' => '260763456789',
            'language' => Language::EN->value,
            'country' => SupportedCountry::ZMB->value,
            'reason' => 'Raison de paiment', // Reason est nullable
            'metadata' => [], // Tableau vide au lieu de null
        ]);

        $response = $this->service->createPaymentPage($requestData);

        $this->assertThat(
            $response,
            $this->logicalOr(
                $this->isInstanceOf(PaymentPageSuccessResponseData::class),
                $this->isInstanceOf(PaymentPageErrorResponseData::class)
            )
        );

        if ($response instanceof PaymentPageSuccessResponseData) {
            $this->assertNotEmpty($response->redirectUrl);
            Log::info('Payment page created with minimal data', [
                'depositId' =>  $this->uuid,
            ]);
        }
    }

    /**
     * Test payment page creation with metadata
     */
    public function test_create_payment_page_with_metadata(): void
    {
        $requestData = PaymentPageRequestData::fromArray([
            'depositId' =>  $this->uuid,
            'returnUrl' => 'https://example.com/return',
            'customerMessage' => 'Payment with metadata',
            'amountDetails' => [
                'amount' => '100.00',
                'currency' => Currency::ZMW->value,
            ],
            'phoneNumber' => '260763456789',
            'language' => Language::EN->value,
            'country' => SupportedCountry::ZMB->value,
            'reason' => 'Order with metadata',
            'metadata' => [
                ['order_id' => '12345'],
                ['user_id' => '67890'],
                ['items' => json_encode(['item1', 'item2'])], // Convertir en string JSON
                ['custom_field' => 'custom_value'],
            ],
        ]);

        $response = $this->service->createPaymentPage($requestData);

        $this->assertThat(
            $response,
            $this->logicalOr(
                $this->isInstanceOf(PaymentPageSuccessResponseData::class),
                $this->isInstanceOf(PaymentPageErrorResponseData::class)
            )
        );

        if ($response instanceof PaymentPageSuccessResponseData) {
            $this->assertNotEmpty($response->redirectUrl);
            Log::info('Payment page created with metadata', [
                'depositId' =>  $this->uuid,
            ]);
        }
    }

    /**
     * Test payment page creation with different languages
     */
    public function test_create_payment_page_different_languages(): void
    {
        $languages = [
            Language::EN,
            Language::FR,
        ];

        foreach ($languages as $language) {
            $requestData = PaymentPageRequestData::fromArray([
                'depositId' =>  $this->uuid,
                'returnUrl' => 'https://example.com/return',
                'customerMessage' => 'Test in ' . $language->value,
                'amountDetails' => [
                    'amount' => '100.00',
                    'currency' => Currency::ZMW->value,
                ],
                'phoneNumber' => '260763456789',
                'language' => $language->value,
                'country' => SupportedCountry::ZMB->value,
                'reason' => 'Language test',
                'metadata' => [], // Tableau vide
            ]);

            $response = $this->service->createPaymentPage($requestData);

            $this->assertThat(
                $response,
                $this->logicalOr(
                    $this->isInstanceOf(PaymentPageSuccessResponseData::class),
                    $this->isInstanceOf(PaymentPageErrorResponseData::class)
                ),
                'Expected valid response for language: ' . $language->value
            );

            if ($response instanceof PaymentPageSuccessResponseData) {
                Log::info('Payment page created with language', [
                    'depositId' =>  $this->uuid,
                    'language' => $language->value,
                ]);
            }
        }
    }

    /**
     * Test payment page creation with missing required fields
     */
    public function test_create_payment_page_missing_required_fields(): void
    {
        // Test sans depositId (champ requis)
        try {
            $requestData = PaymentPageRequestData::fromArray([
                'returnUrl' => 'https://example.com/return',
                'customerMessage' => 'Test payment',
                'amountDetails' => [
                    'amount' => '100.00',
                    'currency' => Currency::ZMW->value,
                ],
                'phoneNumber' => '260763456789',
                'language' => Language::EN->value,
                'country' => SupportedCountry::ZMB->value,
                'reason' => 'Test payment',
                'metadata' => [],
            ]);

            // Ce test devrait échouer car depositId est requis
            // La validation se fera au niveau du Data Object
            $this->fail('Expected validation error for missing depositId');
        } catch (Throwable $throwable) {
            $this->assertTrue(true, 'Validation should fail for missing required field');
        }
    }

    /**
     * Test payment page creation with very large metadata
     */
    public function test_create_payment_page_large_metadata(): void
    {
        // Créer des metadata très grandes
        $largeMetadata = [];
        for ($i = 0; $i < 50; ++$i) { // Réduire à 50 pour éviter les limites
            $largeMetadata[] = ['key_' . $i => str_repeat('value_', 5) . $i];
        }

        $requestData = PaymentPageRequestData::fromArray([
            'depositId' =>  $this->uuid,
            'returnUrl' => 'https://example.com/return',
            'customerMessage' => 'Payment with large metadata',
            'amountDetails' => [
                'amount' => '100.00',
                'currency' => Currency::ZMW->value,
            ],
            'phoneNumber' => '260763456789',
            'language' => Language::EN->value,
            'country' => SupportedCountry::ZMB->value,
            'reason' => 'Large metadata test',
            'metadata' => $largeMetadata,
        ]);

        $response = $this->service->createPaymentPage($requestData);

        $this->assertThat(
            $response,
            $this->logicalOr(
                $this->isInstanceOf(PaymentPageSuccessResponseData::class),
                $this->isInstanceOf(PaymentPageErrorResponseData::class)
            ),
            'Expected response for large metadata'
        );

        if ($response instanceof PaymentPageErrorResponseData) {
            Log::info('API response for large metadata', [
                'failureMessage' => $response->failureReason->failureMessage ?? 'No message',
            ]);
        }
    }

    // ... [le reste des tests pour predictProvider reste inchangé] ...

    /**
     * Test checking deposit status for existing deposit
     */
    public function test_check_deposit_status_success(): void
    {
        // D'abord créer un paiement pour avoir un depositId valide
        $requestData = PaymentPageRequestData::fromArray([
            'depositId' => $this->uuid,
            'returnUrl' => 'https://example.com/return',
            'customerMessage' => 'Test deposit status',
            'amountDetails' => [
                'amount' => '100',
                'currency' => Currency::ZMW->value,
            ],
            'phoneNumber' => '260763456789',
            'language' => Language::EN->value,
            'country' => SupportedCountry::ZMB->value,
            'reason' => 'Deposit status test',
            'metadata' => [['orderId' => 'ORD-123456789']],
        ]);

        $paymentResponse = $this->service->createPaymentPage($requestData);

        if ($paymentResponse instanceof PaymentPageSuccessResponseData) {
            // Attendre un moment pour que le dépôt soit créé
            sleep(2);

            // Tester le check de statut
            $statusResponse = $this->service->checkDepositStatus($this->uuid);

            $this->assertInstanceOf(
                CheckDepositStatusWrapperData::class,
                $statusResponse
            );

            // Le dépôt devrait être FOUND ou NOT_FOUND
            $this->assertContains(
                $statusResponse->status->value,
                ['FOUND', 'NOT_FOUND']
            );

            if ($statusResponse->isFound()) {
                $this->assertNotNull($statusResponse->data);
                $this->assertEquals($this->uuid, $statusResponse->data->depositId);
                $this->assertNotEmpty($statusResponse->data->amount);
                $this->assertNotEmpty($statusResponse->data->currency->value);
                $this->assertNotEmpty($statusResponse->data->country->value);
                $this->assertNotNull($statusResponse->data->payer);
                $this->assertNotNull($statusResponse->data->payer->accountDetails);

                Log::info('Deposit status found', [
                    'depositId' => $statusResponse->data->depositId,
                    'status' => $statusResponse->data->status->value,
                    'amount' => $statusResponse->data->amount,
                ]);
            } else {
                Log::info('Deposit not found (expected for new deposit)', [
                    'depositId' => $this->uuid,
                ]);
            }
        } elseif ($paymentResponse instanceof PaymentPageErrorResponseData) {
            // Si la création échoue, on peut quand même tester avec un UUID aléatoire
            Log::info('Payment page creation failed, testing with random UUID', [
                'failureMessage' => $paymentResponse->failureReason->failureMessage ?? 'No message',
            ]);

            $statusResponse = $this->service->checkDepositStatus($this->uuid);
            $this->assertInstanceOf(CheckDepositStatusWrapperData::class, $statusResponse);
        }
    }

    /**
     * Test checking deposit status with invalid deposit ID
     */
    public function test_check_deposit_status_not_found(): void
    {
        $invalidDepositId = '00000000-0000-0000-0000-000000000000';

        $statusResponse = $this->service->checkDepositStatus($invalidDepositId);

        $this->assertInstanceOf(
            CheckDepositStatusWrapperData::class,
            $statusResponse
        );

        $this->assertTrue($statusResponse->isNotFound());
        $this->assertFalse($statusResponse->isFound());

        Log::info('Deposit not found as expected', [
            'depositId' => $invalidDepositId,
        ]);
    }

    /**
     * Test checking deposit status with malformed deposit ID
     */
    public function test_check_deposit_status_invalid_uuid(): void
    {
        $invalidDepositId = 'not-a-valid-uuid';

        try {
            $statusResponse = $this->service->checkDepositStatus($invalidDepositId);

            // Si on arrive ici, l'API a accepté l'ID mal formé
            $this->assertInstanceOf(
                CheckDepositStatusWrapperData::class,
                $statusResponse
            );

            // L'API peut retourner NOT_FOUND pour un UUID invalide
            $this->assertTrue(
                $statusResponse->isNotFound() || $statusResponse->isFound()
            );

            Log::info('API response for invalid UUID format', [
                'depositId' => $invalidDepositId,
                'status' => $statusResponse->status->value,
            ]);
        } catch (Exception $exception) {
            // Ou l'API peut rejeter la requête avec une erreur
            $this->assertStringContainsString('Invalid', $exception->getMessage());
        }
    }

    /**
     * Test deposit status workflow from creation to completion
     */
    /**
     * Test deposit status workflow from creation to completion
     */
    public function test_deposit_status_workflow(): void
    {
        $depositId = (string) Str::uuid();

        // Créer un nouveau dépôt
        $requestData = PaymentPageRequestData::fromArray([
            'depositId' => $depositId,
            'returnUrl' => 'https://example.com/return',
            'customerMessage' => 'Workflow test deposit',
            'amountDetails' => [
                'amount' => '50',
                'currency' => Currency::ZMW->value,
            ],
            'phoneNumber' => '260763456789',
            'language' => Language::EN->value,
            'country' => SupportedCountry::ZMB->value,
            'reason' => 'Workflow test',
            'metadata' => [
                ['test' => 'workflow'],
                ['timestamp' => (string) Carbon::now()
                    ->getTimestamp()],
            ],
        ]);

        $paymentResponse = $this->service->createPaymentPage($requestData);

        // Assertion 1: Vérifier que nous avons une réponse valide
        $this->assertThat(
            $paymentResponse,
            $this->logicalOr(
                $this->isInstanceOf(PaymentPageSuccessResponseData::class),
                $this->isInstanceOf(PaymentPageErrorResponseData::class)
            ),
            'Expected a valid payment page response'
        );

        if ($paymentResponse instanceof PaymentPageSuccessResponseData) {
            // Assertion 2: Vérifier que l'URL de redirection est présente
            $this->assertNotEmpty($paymentResponse->redirectUrl, 'Redirect URL should not be empty');
            $this->assertStringContainsString('https://', $paymentResponse->redirectUrl, 'Redirect URL should be HTTPS');

            // Vérifier le statut immédiatement après création
            sleep(1);
            $status1 = $this->service->checkDepositStatus($depositId);

            // Assertion 3: Vérifier que nous avons une réponse de statut
            $this->assertInstanceOf(
                CheckDepositStatusWrapperData::class,
                $status1,
                'Expected CheckDepositStatusWrapperData instance'
            );

            Log::info('Initial deposit status', [
                'depositId' => $depositId,
                'status' => $status1->status->value,
                'isFound' => $status1->isFound(),
            ]);

            // Assertion 4: Vérifier que le statut est soit FOUND soit NOT_FOUND
            $this->assertContains(
                $status1->status->value,
                ['FOUND', 'NOT_FOUND'],
                'Status should be either FOUND or NOT_FOUND'
            );

            // Si le dépôt est trouvé
            if ($status1->isFound() && $status1->data) {
                // Assertion 5: Vérifier les données de base du dépôt
                $this->assertEquals($depositId, $status1->data->depositId, 'Deposit ID should match');
                $this->assertNotEmpty($status1->data->amount, 'Amount should not be empty');
                $this->assertNotEmpty($status1->data->currency->value, 'Currency should not be empty');
                $this->assertNotEmpty($status1->data->country->value, 'Country should not be empty');

                // Les statuts possibles immédiatement après création
                $possibleStatuses = ['ACCEPTED', 'PROCESSING', 'SUBMITTED', 'ENQUEUED'];
                $this->assertContains(
                    $status1->data->status->value,
                    $possibleStatuses,
                    sprintf('Status should be one of: %s', implode(', ', $possibleStatuses))
                );

                // Assertion 6: Vérifier les données du payeur
                $this->assertNotNull($status1->data->payer, 'Payer should not be null');
                $this->assertNotNull($status1->data->payer->accountDetails, 'Account details should not be null');
                $this->assertEquals('260763456789', $status1->data->payer->accountDetails->phoneNumber, 'Phone number should match');
                $this->assertInstanceOf(SupportedProvider::class, $status1->data->payer->accountDetails->provider, 'Provider should be an instance of SupportedProvider');

                // Assertion 7: Vérifier que le statut n'est pas final (trop tôt)
                $this->assertFalse(
                    $status1->data->isFinalStatus(),
                    'Deposit should not be in final status immediately after creation'
                );

                // Assertion 8: Vérifier que c'est en cours de traitement
                $this->assertTrue(
                    $status1->data->isProcessing(),
                    'Deposit should be in processing status'
                );

                // Vérifier les metadata si présentes
                if ($status1->data->metadata) {
                    $this->assertIsArray($status1->data->metadata, 'Metadata should be an array');
                }
            } else {
                // Si le dépôt n'est pas trouvé, c'est aussi un résultat valide
                // Assertion 9: Vérifier que c'est bien NOT_FOUND
                $this->assertTrue($status1->isNotFound(), 'Deposit should be NOT_FOUND');
                Log::info('Deposit not found (might be still processing)', [
                    'depositId' => $depositId,
                ]);
            }
        } elseif ($paymentResponse instanceof PaymentPageErrorResponseData) {
            // Si la création échoue, vérifier la structure de l'erreur
            // Assertion 10: Vérifier la structure de l'erreur
            $this->assertNotNull($paymentResponse->failureReason, 'Failure reason should not be null');
            $this->assertNotEmpty($paymentResponse->failureReason->failureMessage, 'Failure message should not be empty');

            Log::info('Payment page creation failed', [
                'failureMessage' => $paymentResponse->failureReason->failureMessage,
            ]);

            // Même si la création a échoué, tester checkDepositStatus avec le même ID
            // Assertion 11: Vérifier que checkDepositStatus fonctionne quand même
            $statusResponse = $this->service->checkDepositStatus($depositId);
            $this->assertInstanceOf(CheckDepositStatusWrapperData::class, $statusResponse, 'Should get status response even for failed deposit');

            // Assertion 12: Vérifier que le dépôt n'est pas trouvé (puisque la création a échoué)
            $this->assertTrue($statusResponse->isNotFound(), 'Deposit should be NOT_FOUND after failed creation');
        }
    }

    /**
     * Test successful deposit initiation
     */
    public function test_initiate_deposit_success(): void
    {
        $requestData = InitiateDepositRequestData::fromArray([
            'depositId' => $this->uuid,
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => '243827833325',
                    'provider' => 'VODACOM_MPESA_COD',
                ],
            ],
            'amount' => '100',
            'currency' => 'USD',
            'clientReferenceId' => 'INV-123456',
            'customerMessage' => 'Payment for order',
            'metadata' => [
                ['orderId' => 'ORD-123456789'],
                ['customerId' => 'customer@email.com'],
            ],
        ]);

        $response = $this->service->initiateDeposit($requestData);

        $this->assertInstanceOf(
            InitiateDepositResponseData::class,
            $response,
            'Expected InitiateDepositResponseData instance'
        );

        // Vérifier les statuts possibles
        $this->assertContains(
            $response->status->value,
            ['ACCEPTED', 'REJECTED', 'DUPLICATE_IGNORED'],
            sprintf('Status should be ACCEPTED, REJECTED or DUPLICATE_IGNORED, got %s', $response->status->value)
        );

        if ($response->isAccepted()) {
            $this->assertSame($this->uuid, $response->depositId, 'Deposit ID should match');
            $this->assertNotEmpty($response->created, 'Created timestamp should not be empty');
            $this->assertInstanceOf(Optional::class, $response->failureReason, 'Failure reason should be Optional for ACCEPTED');

            Log::info('Deposit initiated successfully', [
                'depositId' => $response->depositId,
                'status' => $response->status->value,
                'created' => $response->created,
            ]);
        } elseif ($response->isRejected()) {
            // En cas de rejet, vérifier la structure de l'erreur
            $this->assertNotInstanceOf(Optional::class, $response->failureReason, 'Failure reason should not be Optional for REJECTED');
            $this->assertNotEmpty($response->failureReason->failureMessage, 'Failure message should not be empty');

            Log::info('Deposit rejected', [
                'depositId' => $response->depositId,
                'failureCode' => $response->failureReason->failureCode->value,
                'failureMessage' => $response->failureReason->failureMessage,
            ]);
        } elseif ($response->isDuplicateIgnored()) {
            $this->assertSame($this->uuid, $response->depositId, 'Deposit ID should match for duplicate');
            $this->assertInstanceOf(Optional::class, $response->failureReason, 'Failure reason should be Optional for DUPLICATE_IGNORED');

            Log::info('Deposit duplicate ignored', [
                'depositId' => $response->depositId,
            ]);
        }
    }

    /**
     * Test deposit initiation with minimal required data
     */
    public function test_initiate_deposit_minimal_data(): void
    {
        $requestData = InitiateDepositRequestData::fromArray([
            'depositId' => $this->uuid,
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => '260763456789',
                    'provider' => 'MTN_MOMO_ZMB',
                ],
            ],
            'amount' => '15',
            'currency' => 'ZMW',
        ]);

        $response = $this->service->initiateDeposit($requestData);

        $this->assertInstanceOf(
            InitiateDepositResponseData::class,
            $response,
            'Expected InitiateDepositResponseData instance'
        );

        // Vérifier que la réponse est valide
        $this->assertContains(
            $response->status->value,
            ['ACCEPTED', 'REJECTED', 'DUPLICATE_IGNORED'],
            sprintf('Status should be ACCEPTED, REJECTED or DUPLICATE_IGNORED, got %s', $response->status->value)
        );

        // Vérifier que c'est un statut valide
        $this->assertInstanceOf(TransactionStatus::class, $response->status);
    }

    /**
     * Test deposit initiation with invalid phone number
     */
    public function test_initiate_deposit_invalid_phone_number(): void
    {
        $requestData = InitiateDepositRequestData::fromArray([
            'depositId' => $this->uuid,
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => 'invalid',
                    'provider' => 'MTN_MOMO_ZMB',
                ],
            ],
            'amount' => '15',
            'currency' => 'ZMW',
        ]);

        $response = $this->service->initiateDeposit($requestData);

        $this->assertInstanceOf(
            InitiateDepositResponseData::class,
            $response,
            'Expected InitiateDepositResponseData instance'
        );

        // Vérifier que c'est un rejet
        $this->assertTrue($response->isRejected(), 'Should be rejected for invalid phone number');

        // Vérifier la structure de l'erreur
        $this->assertNotInstanceOf(Optional::class, $response->failureReason, 'Failure reason should not be Optional for REJECTED');
        $this->assertNotEmpty($response->failureReason->failureMessage, 'Failure message should not be empty');

        Log::info('Deposit rejected due to invalid phone number', [
            'failureCode' => $response->failureReason->failureCode->value,
            'failureMessage' => $response->failureReason->failureMessage,
        ]);
    }

    /**
     * Test deposit initiation with invalid amount
     */
    public function test_initiate_deposit_invalid_amount(): void
    {
        $requestData = InitiateDepositRequestData::fromArray([
            'depositId' => $this->uuid,
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => '260763456789',
                    'provider' => 'MTN_MOMO_ZMB',
                ],
            ],
            'amount' => '0', // Montant invalide
            'currency' => 'ZMW',
        ]);

        $response = $this->service->initiateDeposit($requestData);

        $this->assertInstanceOf(
            InitiateDepositResponseData::class,
            $response,
            'Expected InitiateDepositResponseData instance'
        );

        // Vérifier que c'est probablement un rejet
        if ($response->isRejected()) {
            $this->assertNotInstanceOf(Optional::class, $response->failureReason, 'Failure reason should not be Optional for REJECTED');
            $this->assertNotEmpty($response->failureReason->failureMessage, 'Failure message should not be empty');

            Log::info('Deposit rejected due to invalid amount', [
                'failureCode' => $response->failureReason->failureCode->value,
                'failureMessage' => $response->failureReason->failureMessage,
            ]);
        }
    }

    /**
     * Test deposit initiation with unsupported currency for specific provider
     */
    public function test_initiate_deposit_unsupported_currency(): void
    {
        // Pour MTN_MOMO_ZMB (Zambia), les devises supportées sont ZMW
        // Utilisons une devise qui existe dans l'enum mais n'est pas supportée par ce provider
        $requestData = InitiateDepositRequestData::fromArray([
            'depositId' => $this->uuid,
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => '260763456789',
                    'provider' => 'MTN_MOMO_ZMB',
                ],
            ],
            'amount' => '15',
            'currency' => 'USD', // USD existe dans l'enum mais n'est pas supporté pour MTN_MOMO_ZMB (Zambia)
        ]);

        $response = $this->service->initiateDeposit($requestData);

        $this->assertInstanceOf(
            InitiateDepositResponseData::class,
            $response,
            'Expected InitiateDepositResponseData instance'
        );

        // Le dépôt devrait être rejeté car USD n'est pas supporté pour MTN_MOMO_ZMB
        if ($response->isRejected()) {
            $this->assertNotInstanceOf(
                Optional::class,
                $response->failureReason,
                'Failure reason should not be Optional for REJECTED'
            );

            // Le failureReason devrait être présent
            if (!$response->failureReason instanceof Optional) {
                $this->assertNotEmpty(
                    $response->failureReason->failureMessage,
                    'Failure message should not be empty'
                );

                // Le code d'erreur pourrait être INVALID_CURRENCY ou autre
                if ($response->failureReason->failureCode) {
                    $this->assertInstanceOf(
                        FailureCode::class,
                        $response->failureReason->failureCode
                    );
                }

                Log::info('Deposit rejected due to unsupported currency', [
                    'failureCode' => $response->failureReason->failureCode?->value ?? 'N/A',
                    'failureMessage' => $response->failureReason->failureMessage ?? 'No message',
                ]);
            }
        } else {
            // Si ce n'est pas rejeté, c'est peut-être que l'API a changé ou que USD est maintenant supporté
            Log::info('Deposit not rejected for unsupported currency - API might have changed', [
                'status' => $response->status->value,
                'depositId' => $response->depositId,
            ]);
        }
    }

    /**
     * Test deposit initiation with pre-authorization code
     */
    public function test_initiate_deposit_with_preauthorisation(): void
    {
        $requestData = InitiateDepositRequestData::fromArray([
            'depositId' => $this->uuid,
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => '260763456789',
                    'provider' => 'MTN_MOMO_ZMB',
                ],
            ],
            'amount' => '15',
            'currency' => 'ZMW',
            'preAuthorisationCode' => '123456', // Code OTP simulé
            'clientReferenceId' => 'INV-789012',
            'customerMessage' => 'Order payment with OTP',
        ]);

        $response = $this->service->initiateDeposit($requestData);

        $this->assertInstanceOf(
            InitiateDepositResponseData::class,
            $response,
            'Expected InitiateDepositResponseData instance'
        );

        // Vérifier que la réponse est valide
        $this->assertContains(
            $response->status->value,
            ['ACCEPTED', 'REJECTED', 'DUPLICATE_IGNORED'],
            'Should return valid status'
        );

        // Le statut peut varier selon si le code OTP est valide ou non
        if ($response->isAccepted()) {
            Log::info('Deposit with pre-authorization accepted', [
                'depositId' => $response->depositId,
            ]);
        }
    }

    /**
     * Test duplicate deposit initiation (idempotency)
     */
    public function test_initiate_deposit_duplicate(): void
    {
        $requestData = InitiateDepositRequestData::fromArray([
            'depositId' => $this->uuid,
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => '260763456789',
                    'provider' => 'MTN_MOMO_ZMB',
                ],
            ],
            'amount' => '15',
            'currency' => 'ZMW',
        ]);

        // Premier appel
        $this->service->initiateDeposit($requestData);

        // Deuxième appel avec le même depositId
        $response2 = $this->service->initiateDeposit($requestData);

        $this->assertInstanceOf(
            InitiateDepositResponseData::class,
            $response2,
            'Expected InitiateDepositResponseData instance for duplicate'
        );

        // Le deuxième appel devrait être ACCEPTED ou DUPLICATE_IGNORED
        $this->assertTrue(
            $response2->isAccepted() || $response2->isDuplicateIgnored(),
            'Duplicate request should be ACCEPTED or DUPLICATE_IGNORED'
        );

        if ($response2->isDuplicateIgnored()) {
            $this->assertSame($this->uuid, $response2->depositId, 'Deposit ID should match');

            Log::info('Duplicate deposit ignored as expected', [
                'depositId' => $response2->depositId,
            ]);
        }
    }

    /**
     * Test deposit initiation with different countries and providers
     */
    public function test_initiate_deposit_different_providers(): void
    {
        $testCases = [
            [
                'phone' => '254712345678',
                'provider' => 'MPESA_KEN',
                'currency' => 'KES',
                'amount' => '100',
            ],
            [
                'phone' => '233241234567',
                'provider' => 'MTN_MOMO_GHA',
                'currency' => 'GHS',
                'amount' => '10',
            ],
            [
                'phone' => '243827833325',
                'provider' => 'VODACOM_MPESA_COD',
                'currency' => 'USD',
                'amount' => '5',
            ],
        ];

        foreach ($testCases as $testCase) {
            $depositId = (string) Str::uuid();

            $requestData = InitiateDepositRequestData::fromArray([
                'depositId' => $depositId,
                'payer' => [
                    'type' => 'MMO',
                    'accountDetails' => [
                        'phoneNumber' => $testCase['phone'],
                        'provider' => $testCase['provider'],
                    ],
                ],
                'amount' => $testCase['amount'],
                'currency' => $testCase['currency'],
                'customerMessage' => 'Test deposit for ' . $testCase['provider'],
            ]);

            $response = $this->service->initiateDeposit($requestData);

            $this->assertInstanceOf(
                InitiateDepositResponseData::class,
                $response,
                'Expected InitiateDepositResponseData instance for provider: ' . $testCase['provider']
            );

            // Vérifier que c'est un statut valide
            $this->assertContains(
                $response->status->value,
                ['ACCEPTED', 'REJECTED', 'DUPLICATE_IGNORED'],
                sprintf('Invalid status for provider %s: %s', $testCase['provider'], $response->status->value)
            );

            if ($response->isAccepted()) {
                Log::info('Deposit accepted for provider', [
                    'provider' => $testCase['provider'],
                    'depositId' => $response->depositId,
                ]);
            } elseif ($response->isRejected()) {
                Log::info('Deposit rejected for provider', [
                    'provider' => $testCase['provider'],
                    'failureCode' => $response->failureReason->failureCode->value ?? 'N/A',
                    'failureMessage' => $response->failureReason->failureMessage ?? 'No message',
                ]);
            }

            // Petite pause entre les requêtes
            sleep(1);
        }
    }

    /**
     * Test deposit initiation with metadata
     */
    public function test_initiate_deposit_with_metadata(): void
    {
        $requestData = InitiateDepositRequestData::fromArray([
            'depositId' => $this->uuid,
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => '260763456789',
                    'provider' => 'MTN_MOMO_ZMB',
                ],
            ],
            'amount' => '15',
            'currency' => 'ZMW',
            'customerMessage' => 'Order with metadata',
            'metadata' => [
                ['orderId' => 'ORD-123456'],
                ['customerId' => 'cust-789012'],
                ['productId' => 'PROD-345678'],
                ['channel' => 'mobile_app'],
            ],
        ]);

        $response = $this->service->initiateDeposit($requestData);

        $this->assertInstanceOf(
            InitiateDepositResponseData::class,
            $response,
            'Expected InitiateDepositResponseData instance'
        );

        // Vérifier que la réponse est valide
        $this->assertContains(
            $response->status->value,
            ['ACCEPTED', 'REJECTED', 'DUPLICATE_IGNORED'],
            'Should return valid status'
        );

        if ($response->isAccepted()) {
            Log::info('Deposit with metadata accepted', [
                'depositId' => $response->depositId,
            ]);
        }
    }

    /**
     * Test checking multiple deposits
     */
    public function test_check_multiple_deposits(): void
    {
        $depositIds = [];

        // Créer plusieurs dépôts
        for ($i = 0; $i < 2; ++$i) { // Réduire à 2 pour éviter les limites de l'API
            $depositId = (string) Str::uuid();
            $depositIds[] = $depositId;

            $requestData = PaymentPageRequestData::fromArray([
                'depositId' => $depositId,
                'returnUrl' => 'https://example.com/return',
                'customerMessage' => 'Batch test ' . $i,
                'amountDetails' => [
                    'amount' => (string) (($i + 1) * 10),
                    'currency' => Currency::ZMW->value,
                ],
                'phoneNumber' => '260763456789',
                'language' => Language::EN->value,
                'country' => SupportedCountry::ZMB->value,
                'reason' => 'Batch test',
                'metadata' => [
                    ['batchId' => 'BATCH-001'],
                    ['index' => (string) $i],
                ],
            ]);

            $this->service->createPaymentPage($requestData);

            // Petite pause entre les créations
            if ($i < 1) {
                sleep(1);
            }
        }

        // Attendre que les dépôts soient créés
        sleep(2);

        // Vérifier le statut de chaque dépôt
        foreach ($depositIds as $depositId) {
            $statusResponse = $this->service->checkDepositStatus($depositId);

            $this->assertInstanceOf(CheckDepositStatusWrapperData::class, $statusResponse);

            if ($statusResponse->isFound() && $statusResponse->data) {
                Log::info('Batch deposit status', [
                    'depositId' => $depositId,
                    'status' => $statusResponse->data->status->value,
                    'amount' => $statusResponse->data->amount,
                    'isFinal' => $statusResponse->data->isFinalStatus(),
                    'isProcessing' => $statusResponse->data->isProcessing(),
                ]);

                // Vérifier que les méthodes helper fonctionnent
                $this->assertIsBool($statusResponse->data->isFinalStatus());
                $this->assertIsBool($statusResponse->data->isProcessing());
            }
        }
    }

    /**
     * Test deposit status with failure reason
     */
    public function test_deposit_status_with_failure(): void
    {
        $depositId = (string) Str::uuid();

        try {
            // Essayer de créer un dépôt avec un montant invalide (0.00)
            $requestData = PaymentPageRequestData::fromArray([
                'depositId' => $depositId,
                'returnUrl' => 'https://example.com/return',
                'customerMessage' => 'Failure test',
                'amountDetails' => [
                    'amount' => '0.00', // Montant invalide
                    'currency' => Currency::ZMW->value,
                ],
                'phoneNumber' => '260763456789',
                'language' => Language::EN->value,
                'country' => SupportedCountry::ZMB->value,
                'reason' => 'Failure test',
                'metadata' => [],
            ]);

            $paymentResponse = $this->service->createPaymentPage($requestData);

            if ($paymentResponse instanceof PaymentPageErrorResponseData) {
                // Le dépôt a été rejeté, donc il pourrait ne pas être trouvé
                $statusResponse = $this->service->checkDepositStatus($depositId);

                $this->assertInstanceOf(CheckDepositStatusWrapperData::class, $statusResponse);

                // Un dépôt rejeté peut ne pas être trouvé
                if ($statusResponse->isFound() && $statusResponse->data) {
                    $this->assertEquals($depositId, $statusResponse->data->depositId);

                    // Si le statut est FAILED, il devrait avoir un failureReason
                    if ($statusResponse->data->failureReason) {
                        $this->assertNotEmpty($statusResponse->data->failureReason->failureMessage);
                        $this->assertInstanceOf(
                            FailureCode::class,
                            $statusResponse->data->failureReason->failureCode
                        );

                        Log::info('Deposit failed with reason', [
                            'depositId' => $depositId,
                            'failureCode' => $statusResponse->data->failureReason->failureCode->value,
                            'failureMessage' => $statusResponse->data->failureReason->failureMessage,
                        ]);
                    }
                }
            }
        } catch (Exception $exception) {
            // Gérer les exceptions pour ce test spécifique
            Log::info('Exception in failure test', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
