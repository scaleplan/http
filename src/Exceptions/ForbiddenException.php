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
    public const MESSAGE = 'Доступ запрещен.';
    public const CODE = HttpStatusCodes::HTTP_FORBIDDEN;
}
