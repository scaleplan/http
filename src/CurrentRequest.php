<?php

namespace Scaleplan\Http;

use Scaleplan\Helpers\FileHelper;
use Scaleplan\Http\Exceptions\InvalidUrlException;

/**
 * Class CurrentRequest
 *
 * @package Scaleplan\Http
 */
class CurrentRequest extends AbstractRequest implements CurrentRequestInterface
{
    /**
     * @var CurrentRequest
     */
    protected static $currentRequest;

    /**
     * Вернуть объект текущего запроса к серверу
     *
     * @return CurrentRequest
     *
     * @throws InvalidUrlException
     * @throws \Scaleplan\Helpers\Exceptions\FileUploadException
     * @throws \Scaleplan\Helpers\Exceptions\HelperException
     */
    public static function getRequest() : CurrentRequest
    {
        if (!static::$currentRequest) {
            if (empty($_SERVER['REQUEST_URI']) || !static::checkUrl($_SERVER['REQUEST_URI'])) {
                throw new InvalidUrlException();
            }

            $request = new CurrentRequest(
                $_SERVER['REQUEST_URI'],
                array_merge_recursive($_REQUEST, FileHelper::saveFiles($_FILES))
            );
            $request->isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            $request->method = $_SERVER['REQUEST_METHOD'];
            $request->session = $_SESSION;
            $request->cookie = $_COOKIE;
            $request->headers = getallheaders();

            static::$currentRequest = $request;
        }

        return static::$currentRequest;
    }
}