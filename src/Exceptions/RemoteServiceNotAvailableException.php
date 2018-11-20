<?php

namespace Scaleplan\Http\Exceptions;

/**
 * Class RemoteServiceNotAvailableException
 *
 * @package Scaleplan\DependencyInjection\Exceptions
 */
class RemoteServiceNotAvailableException extends HttpException
{
    public const MESSAGE = 'Remote service not available.';
}