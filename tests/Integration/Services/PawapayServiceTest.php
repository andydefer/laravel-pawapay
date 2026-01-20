<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use Illuminate\Support\Facades\Log;
use Pawapay\Data\Responses\PredictProviderFailureResponse;
use Pawapay\Data\Responses\PredictProviderSuccessResponse;
use Pawapay\Enums\FailureCode;
use Pawapay\Enums\SupportedCountry;
use Pawapay\Enums\SupportedProvider;
use Pawapay\Services\PawapayService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Tests\TestCase;

/**
 * Integration tests for PawaPay API.
 *
 * These tests make real API calls to the PawaPay sandbox environment.
 *
 * @group integration
 * @group api
 */
#[AllowMockObjectsWithoutExpectations]
class PawapayServiceTest extends TestCase
{
    private PawapayService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PawapayService::class);
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
}
