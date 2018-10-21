<?php

namespace Scaleplan\Http\Exceptions;

use Scaleplan\Http\Constants\Codes;

/**
 * Class NotFoundException
 *
 * @package Scaleplan\Http\Exceptions
 */
class NotFoundException extends HttpException
{
    public const MESSAGE = 'Not found.';

    public const CODE = Codes::HTTP_NOT_FOUND;
}