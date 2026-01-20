<?php

declare(strict_types=1);

namespace Pawapay\Data\Responses;

use InvalidArgumentException;
use Spatie\LaravelData\Data;

class PredictProviderFailureResponse extends Data
{
    public function __construct(
        public FailureReasonData $failureReason,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        // Assurez-vous que $data a au moins une clé de message d'erreur
        if (!isset($data['failureMessage']) && !isset($data['failureReason'])) {
            throw new InvalidArgumentException('Missing failure message in response');
        }

        return new self(
            failureReason: FailureReasonData::fromArray($data)
        );
    }
}
