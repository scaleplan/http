<?php

namespace Scaleplan\Http\Exceptions;

use Scaleplan\DTO\DTO;

/**
 * Class ClassMustBeDTOException
 *
 * @package Scaleplan\Http\Exceptions
 */
class ClassMustBeDTOException extends \Exception
{
    public const MESSAGE = 'Class must be subclass of ' . DTO::class . '.';

    /**
     * ClassMustBeDTOException constructor.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message ?? static::MESSAGE, $code, $previous);
    }
}
