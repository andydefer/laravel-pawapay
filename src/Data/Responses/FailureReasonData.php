<?php

declare(strict_types=1);

namespace Pawapay\Data\Responses;

use Pawapay\Enums\FailureCode;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;

class FailureReasonData extends Data
{
    public function __construct(
        #[WithCast(EnumCast::class)]
        public FailureCode $failureCode,

        public string $failureMessage,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        // Si failureCode n'existe pas, utiliser une valeur par défaut
        $failureCodeValue = $data['failureCode'] ?? 'UNKNOWN_ERROR';

        return new self(
            failureCode: FailureCode::tryFrom($failureCodeValue) ?? FailureCode::UNKNOWN_ERROR,
            failureMessage: $data['failureMessage'] ?? 'Unknown error',
        );
    }
}
