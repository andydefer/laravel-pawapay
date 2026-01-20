<?php

declare(strict_types=1);

namespace Pawapay\Data\Responses;

use Spatie\LaravelData\Data;

class PredictProviderFailureResponse extends Data
{
    public function __construct(
        public FailureReasonData $failureReason,
    ) {}

    public static function fromArray($data)
    {
        // Assurez-vous que $data a au moins une clé de message d'erreur
        if (!isset($data['failureMessage']) && !isset($data['failureReason'])) {
            throw new \InvalidArgumentException('Missing failure message in response');
        }

        return new self(
            failureReason: FailureReasonData::fromArray($data)
        );
    }
}
