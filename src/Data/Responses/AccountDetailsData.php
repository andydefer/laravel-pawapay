<?php


declare(strict_types=1);

namespace Pawapay\Data\Responses;

use Pawapay\Enums\SupportedProvider;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;


class AccountDetailsData extends Data
{
    public function __construct(
        public string $phoneNumber,

        #[WithCast(EnumCast::class)]
        public SupportedProvider $provider,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (isset($data['phoneNumber'])) {
            // Supprime le + au début si présent
            $data['phoneNumber'] = ltrim($data['phoneNumber'], '+');
        }

        return new self(
            phoneNumber: $data['phoneNumber'] ?? $data['phoneNUmber'] ?? '', // Correction pour l'orthographe dans l'API
            provider: SupportedProvider::from($data['provider']),
        );
    }
}
