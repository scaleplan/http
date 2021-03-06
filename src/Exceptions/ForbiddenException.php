<?php

namespace Scaleplan\Http\Exceptions;

use Scaleplan\HttpStatus\HttpStatusCodes;

/**
 * Class ForbiddenException
 *
 * @package Scaleplan\Http\Exceptions
 */
class ForbiddenException extends HttpException
{
    public const MESSAGE = 'http.access-denied';
    public const CODE = HttpStatusCodes::HTTP_FORBIDDEN;
}
