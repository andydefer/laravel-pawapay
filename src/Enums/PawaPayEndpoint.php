<?php

declare(strict_types=1);

namespace Pawapay\Enums;

enum PawaPayEndpoint: string
{
    case PREDICT_PROVIDER = '/predict-provider';
    case PAYMENT_PAGE = '/paymentpage';
    case CHECK_DEPOSIT_STATUS = '/deposits/{depositId}';
    case INITIATE_DEPOSIT = '/deposits';

    public function path(): string
    {
        return $this->value;
    }

    public function buildPath(array $parameters = []): string
    {
        $path = $this->value;

        foreach ($parameters as $key => $value) {
            $path = str_replace('{' . $key . '}', (string) $value, $path);
        }

        return $path;
    }
}
