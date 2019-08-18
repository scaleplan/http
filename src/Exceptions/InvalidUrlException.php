<?php

namespace Scaleplan\Http\Exceptions;

/**
 * Class HttpException
 *
 * @package Scaleplan\Http\Exceptions
 */
class InvalidUrlException extends HttpException
{
    public const MESSAGE = 'Invalid url received.';
}
