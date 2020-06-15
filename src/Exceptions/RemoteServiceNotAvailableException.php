<?php

namespace Scaleplan\Http\Exceptions;

use Scaleplan\HttpStatus\HttpStatusCodes;

/**
 * Class RemoteServiceNotAvailableException
 *
 * @package Scaleplan\DependencyInjection\Exceptions
 */
class RemoteServiceNotAvailableException extends HttpException
{
    public const MESSAGE = 'Удаленный сервис не доступен.';
    public const CODE = HttpStatusCodes::ORIGIN_IS_UNREACHABLE;
}
