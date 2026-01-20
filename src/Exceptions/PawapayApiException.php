<?php

declare(strict_types=1);

namespace Pawapay\Exceptions;

use Exception;
use Throwable;

/**
 * Exception for Pawapay API errors.
 */
class PawapayApiException extends Exception
{
    /**
     * Additional error data.
     *
     * @var mixed[]
     */
    private array $errorData;

    /**
     * Create a new API exception.
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        array $errorData = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorData = $errorData;
    }

    /**
     * Get the error data.
     *
     * @return mixed[]
     */
    public function getErrorData(): array
    {
        return $this->errorData;
    }

    /**
     * Convert exception to array.
     *
     * @return array<string, string|int|mixed[]>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'data' => $this->getErrorData(),
        ];
    }
}
