<?php

namespace Scaleplan\Http;

use Scaleplan\Http\Exceptions\EnvVarNotFoundOrInvalidException;
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
     *
     * @throws EnvVarNotFoundOrInvalidException
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
     * Формирует либо json-ошибку (если зарос AJAX), либо страницу ошибки
     *
     * @param \Throwable $e - объект пойманной ошибки
     *
     * @throws EnvVarNotFoundOrInvalidException
     */
    public function buildError(\Throwable $e) : void;

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
     *
     * @throws \Scaleplan\Event\Exceptions\ClassIsNotEventException
     */
    public function send() : void;

    /**
     * @param string $filePath
     *
     * @throws NotFoundException
     * @throws \Scaleplan\Event\Exceptions\ClassIsNotEventException
     */
    public function sendFile(string $filePath) : void;

    /**
     * @return AbstractRequestInterface
     */
    public function getRequest() : AbstractRequestInterface;

    /**
     * @param AbstractRequestInterface $request
     */
    public function setRequest(AbstractRequestInterface $request) : void;

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
    public function getSession() : array;

    /**
     * @param array $session
     */
    public function setSession(array $session) : void;

    /**
     * @param string $key
     * @param $value
     */
    public function addSessionVar(string $key, $value) : void;

    /**
     * @param string $key
     */
    public function removeSessionVar(string $key) : void;

    /**
     * @param $key
     *
     * @return mixed|null
     */
    public function getSessionVar($key);

    /**
     * @return array
     */
    public function getCookie() : array;

    /**
     * @param array $cookie
     */
    public function setCookie(array $cookie) : void;
}