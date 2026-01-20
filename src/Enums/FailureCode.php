<?php

declare(strict_types=1);

namespace Pawapay\Enums;

enum FailureCode: string
{
    // Technical failure codes
    case NO_AUTHENTICATION = 'NO_AUTHENTICATION';                           // 401
    case AUTHENTICATION_ERROR = 'AUTHENTICATION_ERROR';                     // 403
    case AUTHORISATION_ERROR = 'AUTHORISATION_ERROR';                       // 403
    case HTTP_SIGNATURE_ERROR = 'HTTP_SIGNATURE_ERROR';                     // 403
    case INVALID_INPUT = 'INVALID_INPUT';                                   // 400
    case MISSING_PARAMETER = 'MISSING_PARAMETER';                           // 400
    case UNSUPPORTED_PARAMETER = 'UNSUPPORTED_PARAMETER';                   // 400
    case INVALID_PARAMETER = 'INVALID_PARAMETER';                           // 400
    case AMOUNT_OUT_OF_BOUNDS = 'AMOUNT_OUT_OF_BOUNDS';                     // 200
    case INVALID_AMOUNT = 'INVALID_AMOUNT';                                 // 200
    case INVALID_PHONE_NUMBER = 'INVALID_PHONE_NUMBER';                     // 200
    case INVALID_CURRENCY = 'INVALID_CURRENCY';                             // 200
    case INVALID_PROVIDER = 'INVALID_PROVIDER';                             // 200
    case DUPLICATE_METADATA_FIELD = 'DUPLICATE_METADATA_FIELD';             // 400
    case DEPOSITS_NOT_ALLOWED = 'DEPOSITS_NOT_ALLOWED';                     // 403
    case PAYOUTS_NOT_ALLOWED = 'PAYOUTS_NOT_ALLOWED';                       // 403
    case REFUNDS_NOT_ALLOWED = 'REFUNDS_NOT_ALLOWED';                       // 403
    case PROVIDER_TEMPORARILY_UNAVAILABLE = 'PROVIDER_TEMPORARILY_UNAVAILABLE';
    case UNKNOWN_ERROR = 'UNKNOWN_ERROR';                                   // 500

        // Transaction failure codes
    case PAYMENT_NOT_APPROVED = 'PAYMENT_NOT_APPROVED';
    case INSUFFICIENT_BALANCE = 'INSUFFICIENT_BALANCE';
    case PAYMENT_IN_PROGRESS = 'PAYMENT_IN_PROGRESS';
    case PAYER_NOT_FOUND = 'PAYER_NOT_FOUND';
    case RECIPIENT_NOT_FOUND = 'RECIPIENT_NOT_FOUND';
    case MANUALLY_CANCELLED = 'MANUALLY_CANCELLED';
    case PAWAPAY_WALLET_OUT_OF_FUNDS = 'PAWAPAY_WALLET_OUT_OF_FUNDS';
    case DEPOSIT_ALREADY_REFUNDED = 'DEPOSIT_ALREADY_REFUNDED';
    case AMOUNT_TOO_LARGE = 'AMOUNT_TOO_LARGE';
    case REFUND_IN_PROGRESS = 'REFUND_IN_PROGRESS';
    case WALLET_LIMIT_REACHED = 'WALLET_LIMIT_REACHED';
    case UNSPECIFIED_FAILURE = 'UNSPECIFIED_FAILURE';

    /**
     * Get the HTTP status code associated with the failure
     */
    public function httpStatusCode(): int
    {
        return match ($this) {
            // Technical failure codes with specific HTTP status codes
            self::NO_AUTHENTICATION => 401,
            self::AUTHENTICATION_ERROR => 403,
            self::AUTHORISATION_ERROR => 403,
            self::HTTP_SIGNATURE_ERROR => 403,
            self::INVALID_INPUT => 400,
            self::MISSING_PARAMETER => 400,
            self::UNSUPPORTED_PARAMETER => 400,
            self::INVALID_PARAMETER => 400,
            self::AMOUNT_OUT_OF_BOUNDS => 200,
            self::INVALID_AMOUNT => 200,
            self::INVALID_PHONE_NUMBER => 200,
            self::INVALID_CURRENCY => 200,
            self::INVALID_PROVIDER => 200,
            self::DUPLICATE_METADATA_FIELD => 400,
            self::DEPOSITS_NOT_ALLOWED => 403,
            self::PAYOUTS_NOT_ALLOWED => 403,
            self::REFUNDS_NOT_ALLOWED => 403,
            self::PROVIDER_TEMPORARILY_UNAVAILABLE => 503,
            self::UNKNOWN_ERROR => 500,

            // Transaction failure codes
            self::PAYMENT_NOT_APPROVED => 200,
            self::INSUFFICIENT_BALANCE => 200,
            self::PAYMENT_IN_PROGRESS => 200,
            self::PAYER_NOT_FOUND => 200,
            self::RECIPIENT_NOT_FOUND => 200,
            self::MANUALLY_CANCELLED => 200,
            self::PAWAPAY_WALLET_OUT_OF_FUNDS => 200,
            self::DEPOSIT_ALREADY_REFUNDED => 200,
            self::AMOUNT_TOO_LARGE => 200,
            self::REFUND_IN_PROGRESS => 200,
            self::WALLET_LIMIT_REACHED => 200,
            self::UNSPECIFIED_FAILURE => 500,
        };
    }
}
