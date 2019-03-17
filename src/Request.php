<?php

namespace Scaleplan\Http;

use Lmc\HttpConstants\Header;
use Scaleplan\DTO\DTO;
use function Scaleplan\Helpers\get_env;
use Scaleplan\Http\Exceptions\ClassMustBeDTOException;
use Scaleplan\Http\Exceptions\HttpException;
use Scaleplan\Http\Exceptions\RemoteServiceNotAvailableException;
use Scaleplan\Http\Interfaces\RequestInterface;
use Scaleplan\HttpStatus\HttpStatusCodes;

/**
 * Class Request
 *
 * @package Scaleplan\Http
 */
class Request extends AbstractRequest implements RequestInterface
{
    public const SERVICES_HTTP_TIMEOUT_ENV_NAME = 'SERVICES_HTTP_TIMEOUT';

    public const DEFAULT_CONNECTION_TIMEOUT = 500;
    public const DEFAULT_TIMEOUT            = 2000;
    public const RETRY_COUNT                = 1;
    public const RETRY_TIMEOUT              = 10000;
    public const ALLOW_REDIRECTS            = false;

    public const RESPONSE_RESULT_SECTION_NAME        = 'result';
    public const RESPONSE_LIMIT_SECTION_NAME         = 'limit';
    public const RESPONSE_PAGE_SECTION_NAME          = 'page';
    public const RESPONSE_ERROR_MESSAGE_SECTION_NAME = 'error_message';
    public const RESPONSE_ERRORS_SECTION_NAME        = 'errors';

    /**
     * @var string|null
     */
    protected $dtoClass;

    /**
     * @var bool
     */
    protected $validationEnable = false;

    /**
     * Request constructor.
     *
     * @param string $url
     * @param array $params
     */
    public function __construct(string $url, array $params)
    {
        $this->url = $url;
        $this->params = $params;
    }

    /**
     * @return bool
     */
    public function isValidationEnable() : bool
    {
        return $this->validationEnable;
    }

    /**
     * @param bool $validationEnable
     */
    public function setValidationEnable(bool $validationEnable) : void
    {
        $this->validationEnable = $validationEnable;
    }

    /**
     * @return string|null
     */
    public function getDtoClass() : ?string
    {
        return $this->dtoClass;
    }

    /**
     * @param string|null $dtoClass
     *
     * @throws ClassMustBeDTOException
     */
    public function setDtoClass(?string $dtoClass) : void
    {
        if (!($dtoClass instanceof DTO)) {
            throw new ClassMustBeDTOException();
        }

        $this->dtoClass = $dtoClass;
    }

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
    public function setIsAjax(\bool $isAjax) : void
    {
        $this->isAjax = $isAjax;
    }

    /**
     * @param string $method
     */
    public function setMethod(\string $method) : void
    {
        $this->method = $method;
    }

    /**
     * @param string $url
     */
    public function setUrl(\string $url) : void
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
     * @param array $cookie
     */
    public function setCookie(array $cookie) : void
    {
        $this->cookie = $cookie;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function addCookie(\string $key, \string $value) : void
    {
        $this->cookie[$key] = $value;
    }

    /**
     * @param string $key
     */
    public function removeCookie(\string $key) : void
    {
        unset($this->cookie[$key]);
    }

    /**
     * @param string $name
     * @param $value
     */
    public function addHeader(\string $name, $value) : void
    {
        $this->headers[$name] = $value;
    }

    /**
     * @param string $name
     */
    public function removeHeader(\string $name) : void
    {
        unset($this->headers[$name]);
    }

    /**
     * Очистить заголовки
     */
    public function clearHeaders() : void
    {
        $this->headers = [];
    }

    /**
     * @return string
     */
    protected function getSerializeCookie() : \string
    {
        $cookie = $this->cookie;
        array_walk($cookie, function (&$value, $key) {
            $value = "$key=$value";
        });

        return implode('; ', $cookie);
    }

    /**
     * @return string[]
     */
    protected function getSerializeHeaders() : \string
    {
        $headers = $this->headers;
        array_walk($headers, function (&$value, $key) {
            $value = "$key: $value";
        });

        return $headers;
    }

    /**
     * @param $result
     *
     * @throws \Scaleplan\DTO\Exceptions\ValidationException
     */
    protected function buildDTO(&$result) : void
    {
        if ($this->dtoClass && \is_array($result)) {
            if ($result && !empty($result[0])) {
                /** @var DTO $item */
                foreach ($result as &$item) {
                    $item = new $this->dtoClass($item);
                    if ($this->isValidationEnable()) {
                        $item->validate(['type']);
                    }
                }
            } else {
                /** @var DTO $result */
                $result = new $this->dtoClass($result);
                if ($this->isValidationEnable()) {
                    $result->validate(['type']);
                }
            }
        }
    }


    /**
     * @return RemoteResponse
     *
     * @throws HttpException
     * @throws RemoteServiceNotAvailableException
     * @throws \Scaleplan\DTO\Exceptions\ValidationException
     */
    public function send() : RemoteResponse
    {
        $this->addHeader(Header::COOKIE, $this->getSerializeCookie());
        $resource = curl_init($this->url);
        curl_setopt($resource, CURLOPT_HTTPHEADER, $this->getSerializeHeaders());
        if ($this->params) {
            curl_setopt($resource, CURLOPT_POSTFIELDS, $this->params);
            curl_setopt($resource, CURLOPT_POST, true);
        }

        curl_setopt(
            $resource,
            CURLOPT_TIMEOUT_MS,
            get_env(static::SERVICES_HTTP_TIMEOUT_ENV_NAME) ?? static::DEFAULT_TIMEOUT
        );
        curl_setopt($resource, CURLOPT_CONNECTTIMEOUT_MS, static::DEFAULT_CONNECTION_TIMEOUT);
        curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($resource, CURLOPT_FOLLOWLOCATION, static::ALLOW_REDIRECTS);
        curl_setopt($resource, CURLOPT_FAILONERROR, true);

        $attempts = 0;
        $responseData = null;
        do {
            $responseData = curl_exec($resource);
            $code = curl_getinfo($resource, CURLINFO_HTTP_CODE);
            $attempts++;
        } while (
            ($responseData === false || $code >= HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR)
            && $attempts <= static::RETRY_COUNT
            && !usleep(static::RETRY_TIMEOUT)
        );

        if ($code >= HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR) {
            throw new RemoteServiceNotAvailableException();
        }

        $result = json_decode($responseData[static::RESPONSE_RESULT_SECTION_NAME] ?? null, true);

        if ($code >= HttpStatusCodes::HTTP_BAD_REQUEST) {
            throw new HttpException(
                $result[static::RESPONSE_ERROR_MESSAGE_SECTION_NAME] ?? null,
                $code,
                $result[static::RESPONSE_ERRORS_SECTION_NAME] ?? null
            );
        }

        $this->buildDTO($result);

        return new RemoteResponse(
            $result,
            $code,
            $responseData[static::RESPONSE_LIMIT_SECTION_NAME],
            $responseData[static::RESPONSE_PAGE_SECTION_NAME]
        );
    }
}
