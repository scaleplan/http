<?php

namespace Scaleplan\Http;

use function Scaleplan\Helpers\getenv;
use Scaleplan\Http\Exceptions\RemoteServiceNotAvailableException;

/**
 * Class Request
 *
 * @package Scaleplan\Http
 */
class Request extends AbstractRequest implements RequestInterface
{
    public const SERVICES_HTTP_VERSION_ENV_NAME = 'SERVICES_HTTP_VERSION';

    public const SERVICES_HTTP_TIMEOUT_ENV_NAME = 'SERVICES_HTTP_TIMEOUT';

    public const DEFAULT_HTTP_VERSION = 1.1;

    public const DEFAULT_TIMEOUT = 30;

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers) : void
    {
        $this->headers = $headers;
    }

    /**
     * @param bool $isAjax
     */
    public function setIsAjax(bool $isAjax) : void
    {
        $this->isAjax = $isAjax;
    }

    /**
     * @param bool $method
     */
    public function setMethod(bool $method) : void
    {
        $this->method = $method;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url) : void
    {
        $this->url = $url;
    }

    /**
     * @param array $params
     */
    public function setParams(array $params) : void
    {
        $this->params = $params;
    }

    /**
     * @param array $cacheAdditionalParams
     */
    public function setCacheAdditionalParams(array $cacheAdditionalParams) : void
    {
        $this->cacheAdditionalParams = $cacheAdditionalParams;
    }

    /**
     * @param array $session
     */
    public function setSession(array $session) : void
    {
        $this->session = $session;
    }

    /**
     * @param array $cookie
     */
    public function setCookie(array $cookie) : void
    {
        $this->cookie = $cookie;
    }

    /**
     * @param string $name
     * @param $value
     */
    public function addHeader(string $name, $value) : void
    {
        $this->headers[$name] = $value;
    }

    /**
     * @param string $name
     */
    public function removeHeader(string $name) : void
    {
        unset($this->headers[$name]);
    }

    /**
     * Очистить заголовки
     */
    public function clearHeaders() : string
    {
        $this->headers = [];
    }

    /**
     * @return string
     *
     * @throws RemoteServiceNotAvailableException
     */
    public function send() : string
    {
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => $this->headers,
                'content' => json_encode(
                    $this->params,
                    JSON_OBJECT_AS_ARRAY | JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
                ),
                'protocol_version' => getenv(static::SERVICES_HTTP_VERSION_ENV_NAME)
                    ?? static::DEFAULT_HTTP_VERSION,
                'timeout' => getenv(static::SERVICES_HTTP_TIMEOUT_ENV_NAME) ?? static::DEFAULT_TIMEOUT
            ],
        ];

        $context = stream_context_create($opts);

        try {
            return file_get_contents($this->url, false, $context);
        } catch (\Exception $e) {
            throw new RemoteServiceNotAvailableException();
        }
    }
}