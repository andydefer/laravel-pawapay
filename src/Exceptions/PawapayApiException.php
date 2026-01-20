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
     * @var array
     */
    private array $errorData;

    /**
     * Create a new API exception.
     *
     * @param string $message
     * @param int $code
     * @param array $errorData
     * @param Throwable|null $previous
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
     * @return array
     */
    public function getErrorData(): array
    {
        return $this->errorData;
    }

    /**
     * Convert exception to array.
     *
     * @return array
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
