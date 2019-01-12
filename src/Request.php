<?php

namespace Scaleplan\Http;

use Lmc\HttpConstants\Header;
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

    public const RETRY_COUNT = 1;

    public const RETRY_TIMEOUT = 10000;

    /**
     * @var bool
     */
    protected $updated;

    /**
     * Request constructor.
     *
     * @param string $url
     * @param array $params
     */
    public function __construct(string $url, array $params)
    {
        $this->url = $url;

        $this->params = array_map(function($item) {
            return \is_array($item) ? array_filter($item) : $item;
        }, $params);
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers) : void
    {
        $this->headers = $headers;
        $this->updated = true;
    }

    /**
     * @param bool $isAjax
     */
    public function setIsAjax(bool $isAjax) : void
    {
        $this->isAjax = $isAjax;
        $this->updated = true;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method) : void
    {
        $this->method = $method;
        $this->updated = true;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url) : void
    {
        $this->url = $url;
        $this->updated = true;
    }

    /**
     * @param array $params
     */
    public function setParams(array $params) : void
    {
        $this->params = $params;
        $this->updated = true;
    }

    /**
     * @param array $cookie
     */
    public function setCookie(array $cookie) : void
    {
        $this->cookie = $cookie;
        $this->updated = true;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function addCookie(string $key, string $value) : void
    {
        $this->cookie[$key] = $value;
        $this->updated = true;
    }

    /**
     * @param string $key
     */
    public function removeCookie(string $key) : void
    {
        unset($this->cookie[$key]);
        $this->updated = true;
    }

    /**
     * @param string $name
     * @param $value
     */
    public function addHeader(string $name, $value) : void
    {
        $this->headers[$name] = $value;
        $this->updated = true;
    }

    /**
     * @param string $name
     */
    public function removeHeader(string $name) : void
    {
        unset($this->headers[$name]);
        $this->updated = true;
    }

    /**
     * Очистить заголовки
     */
    public function clearHeaders() : void
    {
        $this->headers = [];
        $this->updated = true;
    }

    /**
     * @return string
     */
    protected function serializeCookie() : string
    {
        $cookie = $this->cookie;
        array_walk($cookie, function(&$value, $key) {
            $value = "$key=$value";
        });

        return implode('; ', $cookie);
    }

    /**
     * @param bool $allowCacheValue
     *
     * @return string
     *
     * @throws RemoteServiceNotAvailableException
     */
    public function send(bool $allowCacheValue = true) : string
    {
        static $result;
        if ($this->updated || !$allowCacheValue) {
            $result = null;
        }

        if ($result === null) {
            $this->addHeader(Header::COOKIE, $this->serializeCookie());
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

            $attempts = 0;
            do {
                try {
                    $result = file_get_contents($this->url, false, $context);
                    $this->updated = false;
                } catch (\PDOException $e) {
                    $attempts++;
                    if ($attempts <= static::RETRY_COUNT) {
                        usleep(static::RETRY_TIMEOUT);
                        continue;
                    }
                }

                throw new RemoteServiceNotAvailableException();
            } while(true);
        }

        return $result;
    }
}