<?php

namespace Scaleplan\Http\Exceptions;

use Scaleplan\Http\Constants\Codes;

/**
 * Class NotFoundException
 *
 * @package Scaleplan\Http\Exceptions
 */
class EnvVarNotFoundOrInvalidException extends HttpException
{
    public const MESSAGE = 'Environment variable not found or invalid.';

    public const CODE = Codes::HTTP_INTERNAL_SERVER_ERROR;
}