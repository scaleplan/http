<?php

namespace Scaleplan\Http;

use Scaleplan\Http\Interfaces\AbstractRequestInterface;

/**
 * Class AbstractRequest
 *
 * @package Scaleplan\Http
 */
abstract class AbstractRequest implements AbstractRequestInterface
{
    /**
     * @var array
     */
    protected $headers = [];

    /**
     * Является ли текущий запрос AJAX-запросом
     *
     * @var bool
     */
    protected $isAjax = false;

    /**
     * Метод запроса
     *
     * @var string
     */
    protected $method = 'GET';

    /**
     * URL запроса
     *
     * @var string
     */
    protected $url = '';

    /**
     * Параметры запроса
     *
     * @var array
     */
    protected $params = [];

    /**
     * @var array
     */
    protected $cookie = [];

    /**
     * @return array
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getHeader(string $name)
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * @return array
     */
    public function getCookie() : array
    {
        return $this->cookie;
    }

    /**
     * @param $key
     *
     * @return mixed|null
     */
    public function getCookieVar($key)
    {
        return $this->cookie[$key] ?? null;
    }

    /**
     * Вернуть URL запроса
     *
     * @return string
     */
    public function getURL() : string
    {
        return $this->url;
    }

    /**
     * Вернуть параметры запросы
     *
     * @return array
     */
    public function getParams() : array
    {
        return $this->params;
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getParam(string $name)
    {
        return $this->params[$name] ?? null;
    }

    /**
     * Запрос был отправлен через Ajax?
     *
     * @return bool
     */
    public function isAjax() : bool
    {
        return $this->isAjax;
    }

    /**
     * @return string
     */
    public function getMethod() : string
    {
        return $this->method;
    }
}
