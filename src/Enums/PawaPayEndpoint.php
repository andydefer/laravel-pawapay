<?php

declare(strict_types=1);

namespace Pawapay\Enums;

enum PawaPayEndpoint: string
{
    case PREDICT_PROVIDER = '/predict-provider';
    case PAYMENT_PAGE = '/paymentpage';

    public function path(): string
    {
        return $this->value;
    }
}
