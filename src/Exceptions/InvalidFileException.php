<?php

namespace Scaleplan\Http\Exceptions;

/**
 * Class InvalidFileException
 *
 * @package Scaleplan\Http\Exceptions
 */
class InvalidFileException extends HttpException
{
    public const MESSAGE = 'http.corrupted-file';
}
