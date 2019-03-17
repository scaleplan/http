<?php

namespace Scaleplan\Http\Exceptions;

use Scaleplan\HttpStatus\HttpStatusCodes;

/**
 * Class NotFoundException
 *
 * @package Scaleplan\Http\Exceptions
 */
class EnvVarNotFoundOrInvalidException extends HttpException
{
    public const MESSAGE = 'Environment variable not found or invalid.';

    public const CODE = HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR;
}
