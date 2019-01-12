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
    public function getCookie() : array;

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
     * @return string
     */
    public function getMethod() : string;

    /**
     * @return CurrentResponseInterface
     */
    public function getResponse() : CurrentResponseInterface;

    /**
     * @param CurrentResponseInterface $response
     */
    public function setResponse(CurrentResponseInterface $response) : void;
}