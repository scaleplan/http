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
}