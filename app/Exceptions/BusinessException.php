<?php

namespace App\Exceptions;

use Exception;

/**
 * Lightweight exception for business rule violations that may be shown to end users.
 * Examples: attempting to delete a category that is still in use, submitting an invalid form, etc.
 * System level failures (DB errors, filesystem issues, etc.) should throw regular exceptions instead.
 */
class BusinessException extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
