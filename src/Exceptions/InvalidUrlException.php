<?php

namespace Scaleplan\Http\Exceptions;

/**
 * Class HttpException
 *
 * @package Scaleplan\Http\Exceptions
 */
class InvalidUrlException extends HttpException
{
    public const MESSAGE = 'Получен неверный URL.';
}
