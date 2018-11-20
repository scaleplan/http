<?php

namespace Scaleplan\Http;

/**
 * Class AbstractRequestInterface
 *
 * @package Scaleplan\Http
 */
interface AbstractRequestInterface
{
    /**
     * @return array
     */
    public function getHeaders() : array;

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getHeader(string $name);

    /**
     * @return array
     */
    public function getSession() : array;

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
     * @param $key
     *
     * @return mixed|null
     */
    public function getCookieVar($key);

    /**
     * Вернуть URL запроса
     *
     * @return string
     */
    public function getURL() : string;

    /**
     * Вернуть параметры запросы
     *
     * @return array
     */
    public function getParams() : array;

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getParam(string $name);

    /**
     * Запрос был отправлен через Ajax?
     *
     * @return bool
     */
    public function isAjax() : bool;

    /**
     * @return mixed|null
     */
    public function getUser();

    /**
     * @param UserInterface $user
     */
    public function setUser(UserInterface $user) : void;

    /**
     * @return CurrentResponseInterface
     *
     * @throws Exceptions\EnvVarNotFoundOrInvalidException
     * @throws \ReflectionException
     */
    public function execute() : CurrentResponseInterface;

    /**
     * @return bool
     */
    public function isMethod() : bool;

    /**
     * @return array
     */
    public function getCacheAdditionalParams() : array;

    /**
     * @return CacheInterface
     */
    public function getCache() : CacheInterface;

    /**
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache) : void;

    /**
     * @return CurrentResponseInterface
     */
    public function getResponse() : CurrentResponseInterface;

    /**
     * @param CurrentResponseInterface $response
     */
    public function setResponse(CurrentResponseInterface $response) : void;
}