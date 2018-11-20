<?php

namespace Scaleplan\Http;

use Scaleplan\Http\Exceptions\InvalidUrlException;

/**
 * Class CurrentRequest
 *
 * @package Scaleplan\Http
 */
interface CurrentRequestInterface extends AbstractRequestInterface
{
    /**
     * Вернуть объект текущего запроса к серверу
     *
     * @return CurrentRequest
     *
     * @throws InvalidUrlException
     * @throws \Scaleplan\Helpers\Exceptions\FileUploadException
     * @throws \Scaleplan\Helpers\Exceptions\HelperException
     */
    public static function getCurrentRequest() : CurrentRequest;
}