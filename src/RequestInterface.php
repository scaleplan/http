<?php

namespace Scaleplan\Http;

/**
 * Class Request
 *
 * @package Scaleplan\Http
 */
interface RequestInterface extends AbstractRequestInterface
{
    /**
     * @param array $headers
     */
    public function setHeaders(array $headers) : void;

    /**
     * @param bool $isAjax
     */
    public function setIsAjax(bool $isAjax) : void;

    /**
     * @param bool $method
     */
    public function setMethod(bool $method) : void;

    /**
     * @param string $url
     */
    public function setUrl(string $url) : void;

    /**
     * @param array $params
     */
    public function setParams(array $params) : void;

    /**
     * @param array $cacheAdditionalParams
     */
    public function setCacheAdditionalParams(array $cacheAdditionalParams) : void;

    /**
     * @param array $session
     */
    public function setSession(array $session) : void;

    /**
     * @param array $cookie
     */
    public function setCookie(array $cookie) : void;
}