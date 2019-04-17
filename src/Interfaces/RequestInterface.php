<?php

namespace Scaleplan\Http\Interfaces;

use Scaleplan\Http\Exceptions\ClassMustBeDTOException;
use Scaleplan\Http\Exceptions\HttpException;
use Scaleplan\Http\Exceptions\RemoteServiceNotAvailableException;
use Scaleplan\Http\RemoteResponse;

/**
 * Class Request
 *
 * @package Scaleplan\Http
 */
interface RequestInterface
{
    /**
     * @return bool
     */
    public function isValidationEnable() : bool;

    /**
     * @param bool $validationEnable
     */
    public function setValidationEnable(bool $validationEnable) : void;

    /**
     * @return string|null
     */
    public function getDtoClass() : ?string;

    /**
     * @param string|null $dtoClass
     *
     * @throws ClassMustBeDTOException
     */
    public function setDtoClass(?string $dtoClass) : void;

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers) : void;

    /**
     * @param bool $isAjax
     */
    public function setIsAjax(bool $isAjax) : void;

    /**
     * @param string $method
     */
    public function setMethod(string $method) : void;

    /**
     * @param string $url
     */
    public function setUrl(string $url) : void;

    /**
     * @param array $params
     */
    public function setParams(array $params) : void;

    /**
     * @param array $cookie
     */
    public function setCookie(array $cookie) : void;

    /**
     * @param string $key
     * @param string $value
     */
    public function addCookie(string $key, string $value) : void;

    /**
     * @param string $key
     */
    public function removeCookie(string $key) : void;

    /**
     * @param string $name
     * @param $value
     */
    public function addHeader(string $name, $value) : void;

    /**
     * @param string $name
     */
    public function removeHeader(string $name) : void;

    /**
     * Очистить заголовки
     */
    public function clearHeaders() : void;

    /**
     * @return RemoteResponse
     *
     * @throws HttpException
     * @throws RemoteServiceNotAvailableException
     * @throws \Scaleplan\DTO\Exceptions\ValidationException
     */
    public function send() : RemoteResponse;
}
