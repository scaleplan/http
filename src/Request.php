<?php
declare(strict_types=1);

namespace Scaleplan\Http;

use GuzzleHttp\Psr7\Uri;
use Lmc\HttpConstants\Header;
use Psr\Http\Message\UriInterface;
use Scaleplan\DTO\DTO;
use Scaleplan\DTO\Exceptions\ValidationException;
use Scaleplan\Http\Constants\ContentTypes;
use Scaleplan\Http\Constants\Methods;
use Scaleplan\Http\Constants\Schemes;
use Scaleplan\Http\Exceptions\ClassMustBeDTOException;
use Scaleplan\Http\Exceptions\HttpException;
use Scaleplan\Http\Exceptions\RemoteServiceNotAvailableException;
use Scaleplan\Http\Interfaces\RequestInterface;
use Scaleplan\HttpStatus\HttpStatusCodes;
use function Scaleplan\Helpers\get_env;

/**
 * Class Request
 *
 * @package Scaleplan\Http
 */
class Request extends AbstractRequest implements RequestInterface
{
    public const SERVICES_HTTP_TIMEOUT_ENV_NAME = 'SERVICES_HTTP_TIMEOUT';

    public const DEFAULT_CONNECTION_TIMEOUT = 500;
    public const DEFAULT_TIMEOUT            = 10000;
    public const RETRY_COUNT                = 1;
    public const RETRY_TIMEOUT              = 10000;
    public const ALLOW_REDIRECTS            = true;

    public const RESPONSE_RESULT_SECTION_NAME = 'result';

    public const RESPONSE_ERROR_CODE_SECTION_NAME           = 'result';
    public const RESPONSE_ERROR_MESSAGE_SECTION_NAME_FIRST  = 'message';
    public const RESPONSE_ERROR_MESSAGE_SECTION_NAME_SECOND = 'error';
    public const RESPONSE_ERRORS_SECTION_NAME               = 'errors';

    /**
     * @var string|null
     */
    protected $dtoClass;

    /**
     * @var bool
     */
    protected $validationEnable = false;

    /**
     * @var bool
     */
    protected $isKeepAuthHeader = false;

    /**
     * @var UriInterface
     */
    protected $uri;

    /**
     * @var string
     */
    protected $user;

    /**
     * @var string
     */
    protected $password;

    /**
     * Метод запроса
     *
     * @var string
     */
    protected $method = 'GET';

    /**
     * Request constructor.
     *
     * @param string $url
     * @param array $params
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $url, $params = [])
    {
        $this->setUri(new Uri($url));
        $this->setParams($params);
        $this->user = get_env('BASIC_USER');
        $this->password = get_env('BASIC_PASSWORD');
    }

    /**
     * @param string $user
     */
    public function setUser(string $user) : void
    {
        $this->user = $user;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password) : void
    {
        $this->password = $password;
    }

    /**
     * @return bool
     */
    public function isKeepAuthHeader() : bool
    {
        return $this->isKeepAuthHeader;
    }

    /**
     * @param bool $isKeepAuthHeader
     */
    public function setIsKeepAuthHeader(bool $isKeepAuthHeader) : void
    {
        $this->isKeepAuthHeader = $isKeepAuthHeader;
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
        if (!is_subclass_of($dtoClass, DTO::class)) {
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
    public function setIsAjax(bool $isAjax = true) : void
    {
        $this->isAjax = $isAjax;
        if ($isAjax) {
            $this->addHeader(Header::X_REQUESTED_WITH, static::X_REQUESTED_WITH_VALUE);
            return;
        }

        $this->removeHeader(Header::X_REQUESTED_WITH);
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method) : void
    {
        $this->method = $method;
    }

    /**
     * @param UriInterface $uri
     *
     * @throws \InvalidArgumentException
     */
    public function setUri(UriInterface $uri) : void
    {
        if (isset($_SERVER['HTTP_HOST']) && !$uri->getHost()) {
            $uri = $uri->withHost($_SERVER['HTTP_HOST']);
        }

        if (!$uri->getScheme()) {
            $uri = $uri->withScheme(empty($_SERVER['HTTPS']) ? Schemes::HTTP : Schemes::HTTPS);
        }

        $this->uri = $uri;
    }

    /**
     * @return UriInterface
     */
    public function getUri() : UriInterface
    {
        return $this->uri;
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
    public function addCookie(string $key, string $value) : void
    {
        $this->cookie[$key] = $value;
    }

    /**
     * @param string $key
     */
    public function removeCookie(string $key) : void
    {
        unset($this->cookie[$key]);
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
    public function clearHeaders() : void
    {
        $this->headers = [];
    }

    /**
     * @return string
     */
    protected function getSerializeCookie() : string
    {
        $cookie = $this->cookie;
        array_walk($cookie, static function (&$value, $key) {
            $value = "$key=$value";
        });

        return implode('; ', $cookie);
    }

    /**
     * @return string[]
     */
    protected function getSerializeHeaders() : array
    {
        $headers = $this->headers ?? [];
        array_walk($headers, static function (&$value, $key) {
            $value = "$key: $value";
        });

        return array_values($headers);
    }

    /**
     * @param $response
     *
     * @return DTO|null
     *
     * @throws ValidationException
     */
    protected function buildDTO($response) : ?DTO
    {
        if ($this->dtoClass && \is_array($response)) {
            /** @var DTO $dto */
            $dto = new $this->dtoClass($response);
            if ($this->isValidationEnable()) {
                $dto->validate(['type']);
                $dto->validate();
            }

            return $dto;
        }

        return null;
    }

    /**
     * @param int $code
     *
     * @return bool
     */
    public static function codeIsOk(int $code) : bool
    {
        return $code < HttpStatusCodes::HTTP_BAD_REQUEST && $code >= HttpStatusCodes::HTTP_OK;
    }

    /**
     * @return resource
     */
    protected function getCurlResource()
    {
        $resource = curl_init((string)$this->uri);
        curl_setopt($resource, CURLOPT_HTTPHEADER, $this->getSerializeHeaders());
        curl_setopt($resource, CURLOPT_UNRESTRICTED_AUTH, $this->isKeepAuthHeader);
        if ($this->method === Methods::POST) {
            curl_setopt($resource, CURLOPT_POST, true);
            if ($this->params) {
                $params = $this->params;
                if ($this->getHeader(Header::CONTENT_TYPE) === ContentTypes::JSON) {
                    $params = json_encode($params, JSON_OBJECT_AS_ARRAY | JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES
                        | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
                }

                curl_setopt($resource, CURLOPT_POSTFIELDS, $params);
            }
        } else {
            curl_setopt($resource, CURLOPT_CUSTOMREQUEST, $this->method);
            curl_setopt($resource, CURLOPT_URL, (string)$this->uri->withQuery(http_build_query($this->params)));
        }

        curl_setopt(
            $resource,
            CURLOPT_TIMEOUT_MS,
            get_env(static::SERVICES_HTTP_TIMEOUT_ENV_NAME) ?? static::DEFAULT_TIMEOUT
        );
        curl_setopt($resource, CURLOPT_CONNECTTIMEOUT_MS, static::DEFAULT_CONNECTION_TIMEOUT);
        curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($resource, CURLOPT_FOLLOWLOCATION, static::ALLOW_REDIRECTS);
        curl_setopt($resource, CURLOPT_FAILONERROR, false);
        if ($this->user && $this->password) {
            curl_setopt($resource, CURLOPT_USERPWD, "{$this->user}:{$this->password}");
        }

        return $resource;
    }

    /**
     * @param resource $resource
     *
     * @return array
     */
    protected static function setResponseHeadersBuilder($resource) : array
    {
        $responseHeaders = [];
        curl_setopt($resource, CURLOPT_HEADERFUNCTION, static function ($cURL, $header) use (&$responseHeaders) {
            $len = strlen($header);
            if (!$len) {
                return $len;
            }

            $headerArray = explode(':', $header, 2);
            if (count($headerArray) < 2) {// ignore invalid headers
                return $len;
            }

            $responseHeaders[strtolower(trim($headerArray[0]))][] = trim($headerArray[1]);

            return $len;
        });

        return $responseHeaders;
    }

    /**
     * @return RemoteResponse
     *
     * @throws \Throwable
     */
    public function send() : RemoteResponse
    {
        if ($this->getCookie()) {
            $this->addHeader(Header::COOKIE, $this->getSerializeCookie());
        }
        $resource = $this->getCurlResource();
        try {
            @$responseHeaders = &static::setResponseHeadersBuilder($resource);
            $attempts = 0;
            $responseData = null;
            do {
                $responseData = curl_exec($resource);
                $code = (int)curl_getinfo($resource, CURLINFO_HTTP_CODE);
                $attempts++;
            } while (
                ($responseData === false || $code >= HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR)
                && $attempts <= static::RETRY_COUNT
                && !usleep(static::RETRY_TIMEOUT)
            );

            $result = json_decode($responseData[static::RESPONSE_RESULT_SECTION_NAME] ?? $responseData ?: '', true);

            if (!static::codeIsOk($code)) {
                $message = $result['message'] ?: curl_error($resource);

                if ($code >= HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR) {
                    throw new RemoteServiceNotAvailableException($message);
                }

                if ($code === HttpStatusCodes::HTTP_UNPROCESSABLE_ENTITY) {
                    throw new ValidationException($result[static::RESPONSE_ERRORS_SECTION_NAME] ?? [], $message);
                }

                if ($code >= HttpStatusCodes::HTTP_BAD_REQUEST && $result) {
                    throw new HttpException(
                        $result[static::RESPONSE_ERROR_MESSAGE_SECTION_NAME_FIRST]
                        ?? $result[static::RESPONSE_ERROR_MESSAGE_SECTION_NAME_SECOND]
                        ?? $message,
                        $result[static::RESPONSE_ERROR_CODE_SECTION_NAME] ?? $code,
                        $result[static::RESPONSE_ERRORS_SECTION_NAME] ?? null
                    );
                }

                throw new HttpException($message, $code);
            }

            $dto = $this->buildDTO($result);

            return new RemoteResponse($dto ?? $result, $code, $responseHeaders);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            curl_close($resource);
        }
    }

    /**
     * @throws \Throwable
     */
    public function sendAsync() : void
    {
        $this->addHeader(Header::COOKIE, $this->getSerializeCookie());
        $resource = $this->getCurlResource();
        try {
            $mh = curl_multi_init();
            curl_multi_add_handle($mh, $resource);
            $attempts = 0;
            do {
                $status = curl_multi_exec($mh, $active);
                $attempts++;
            } while (
                $status !== CURLM_OK
                && $attempts <= static::RETRY_COUNT
                && !usleep(static::RETRY_TIMEOUT)
            );

            if ($status !== CURLM_OK) {
                throw new HttpException(curl_error($resource));
            }
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            curl_close($resource);
        }
    }
}
