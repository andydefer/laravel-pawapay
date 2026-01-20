<?php

declare(strict_types=1);

namespace Pawapay\Enums;

enum CheckDepositStatus: string
{
    case FOUND = 'FOUND';
    case NOT_FOUND = 'NOT_FOUND';
}
