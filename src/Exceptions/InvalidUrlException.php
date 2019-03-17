<?php

namespace Scaleplan\Http\Exceptions;

use Scaleplan\HttpStatus\HttpStatusCodes;

/**
 * Class HttpException
 *
 * @package Scaleplan\Http\Exceptions
 */
class InvalidUrlException extends HttpException
{
    public const MESSAGE = 'Invalid url received.';
    public const CODE = HttpStatusCodes::HTTP_NOT_ACCEPTABLE;
}
