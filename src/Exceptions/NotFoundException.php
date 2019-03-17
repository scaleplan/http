<?php

namespace Scaleplan\Http\Exceptions;

use Scaleplan\HttpStatus\HttpStatusCodes;

/**
 * Class NotFoundException
 *
 * @package Scaleplan\Http\Exceptions
 */
class NotFoundException extends HttpException
{
    public const MESSAGE = 'Not found.';

    public const CODE = HttpStatusCodes::HTTP_NOT_FOUND;
}
