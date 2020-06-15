<?php

namespace Scaleplan\Http\Exceptions;

use Scaleplan\HttpStatus\HttpStatusCodes;

/**
 * Class HttpException
 *
 * @package Scaleplan\Http\Exceptions
 */
class HttpException extends \Exception
{
    public const MESSAGE = 'Ошибка HTTP-транспорта.';
    public const CODE = HttpStatusCodes::HTTP_BAD_REQUEST;

    /**
     * @var string[]
     */
    public $errors;

    /**
     * HttpException constructor.
     *
     * @param string $message
     * @param int|null $code
     * @param array|null $errors
     */
    public function __construct(string $message = '', int $code = 0, array $errors = null)
    {
        $this->errors = $errors;
        parent::__construct($message ?: static::MESSAGE, $code ?: static::CODE);
    }
}
