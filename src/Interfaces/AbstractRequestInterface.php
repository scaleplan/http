<?php

namespace Scaleplan\Http\Interfaces;

/**
 * Class AbstractRequestInterface
 *
 * @package Scaleplan\Http
 */
interface AbstractRequestInterface
{
    public const X_REQUESTED_WITH_VALUE = 'xmlhttprequest';

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
}
