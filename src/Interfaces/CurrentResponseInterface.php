<?php

namespace Scaleplan\Http\Interfaces;

use Scaleplan\Http\ContentTypes;
use Scaleplan\Http\Exceptions\NotFoundException;

/**
 * Ответ от сервера
 *
 * Class CurrentResponseInterface
 *
 * @package Scaleplan\Http
 */
interface CurrentResponseInterface
{
    /**
     * Редирект на страницу авторизации, если еще не авторизован
     */
    public function redirectUnauthorizedUser() : void;

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
     * @param int $code
     */
    public function setCode(int $code) : void;

    /**
     * Редирект в зависимости от типа запроса
     *
     * @param string $url - на какой урл редиректить
     */
    public function buildRedirect(string $url) : void;

    /**
     * Редирект на nginx
     *
     * @param string $url - на какой урл редиректить
     */
    public function XRedirect(string $url) : void;

    /**
     * @param string $contentType
     */
    public function setContentType($contentType = ContentTypes::HTML) : void;

    /**
     * Отправить ответ
     */
    public function send() : void;

    /**
     * @param string $filePath
     *
     * @throws NotFoundException
     */
    public function sendFile(string $filePath) : void;

    /**
     * @return CurrentRequestInterface
     */
    public function getRequest() : CurrentRequestInterface;

    /**
     * @return null|mixed
     */
    public function getPayload();

    /**
     * @param null|mixed $payload
     */
    public function setPayload($payload) : void;

    /**
     * @return array
     */
    public function getHeaders() : array;

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers) : void;

    /**
     * @return array
     */
    public function getCookie() : array;

    /**
     * @param array $cookie
     */
    public function setCookie(array $cookie) : void;
}
