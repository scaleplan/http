<?php

namespace Scaleplan\Http;

/**
 * Class RequestInterface
 *
 * @package Scaleplan\Http
 */
interface RequestInterface
{
    /**
     * Вернуть URL запроса
     *
     * @return string
     */
    public function getURL();

    /**
     * Вернуть параметры запросы
     *
     * @return array
     */
    public function getParams() : array;

    /**
     * Запрос был отправлен через Ajax?
     *
     * @return bool
     */
    public function isAjax() : bool;

    /**
     * @return mixed
     */
    public function getUser();

    /**
     * @return array
     */
    public function getSession() : array;

    /**
     * @return array
     */
    public function getCookie() : array;

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getParam(string $name);
}