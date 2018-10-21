<?php

namespace Scaleplan\Http\Exceptions;

use Scaleplan\Http\Constants\Codes;

/**
 * Class HttpException
 *
 * @package Scaleplan\Http\Exceptions
 */
class HttpException extends \Exception
{
    public const MESSAGE = 'Http transport error.';

    public const CODE = Codes::HTTP_BAD_REQUEST;

    /**
     * HttpException constructor.
     *
     * @param string|null $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = null, int $code = null, \Throwable $previous = null)
    {
        parent::__construct($message ?? static::MESSAGE, $code ?? static::CODE, $previous);
    }
}