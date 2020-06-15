<?php

namespace Scaleplan\Http\Exceptions;

use Scaleplan\DTO\DTO;
use Scaleplan\HttpStatus\HttpStatusCodes;

/**
 * Class ClassMustBeDTOException
 *
 * @package Scaleplan\Http\Exceptions
 */
class ClassMustBeDTOException extends \Exception
{
    public const MESSAGE = 'Класс должен быть подклассом ' . DTO::class . '.';
    public const CODE = HttpStatusCodes::HTTP_UNPROCESSABLE_ENTITY;

    /**
     * ClassMustBeDTOException constructor.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message ?: static::MESSAGE, $code, $previous);
    }
}
