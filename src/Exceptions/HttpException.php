<?php

namespace Scaleplan\Http\Exceptions;

use Scaleplan\HttpStatus\HttpStatusCodes;
use function Scaleplan\Translator\translate;

/**
 * Class HttpException
 *
 * @package Scaleplan\Http\Exceptions
 */
class HttpException extends \Exception
{
    public const MESSAGE = 'http.http-error';
    public const CODE = HttpStatusCodes::HTTP_BAD_REQUEST;

    /**
     * @var string[]
     */
    public $errors;

    /**
     * HttpException constructor.
     *
     * @param string $message
     * @param int $code
     * @param array|null $errors
     *
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function __construct(string $message = '', int $code = 0, array $errors = null)
    {
        $this->errors = $errors;
        parent::__construct(
            $message ?: translate(static::MESSAGE) ?: static::MESSAGE,
            $code ?: static::CODE
        );
    }
}
