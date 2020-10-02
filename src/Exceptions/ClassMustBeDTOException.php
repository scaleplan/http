<?php

namespace Scaleplan\Http\Exceptions;

use Scaleplan\HttpStatus\HttpStatusCodes;
use function Scaleplan\Translator\translate;

/**
 * Class ClassMustBeDTOException
 *
 * @package Scaleplan\Http\Exceptions
 */
class ClassMustBeDTOException extends \Exception
{
    public const MESSAGE = 'http.class-must-inherits-dto';
    public const CODE    = HttpStatusCodes::HTTP_UNPROCESSABLE_ENTITY;

    /**
     * ClassMustBeDTOException constructor.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     *
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(
            $message ?: translate(static::MESSAGE) ?: static::MESSAGE,
            $code ?: static::CODE,
            $previous
        );
    }
}
