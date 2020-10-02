<?php
declare(strict_types=1);

namespace Scaleplan\Http;

use GuzzleHttp\Psr7\Uri;
use Lmc\HttpConstants\Header;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use Scaleplan\File\FileHelper;
use Scaleplan\File\Stream;
use Scaleplan\File\UploadedFile;
use Scaleplan\Http\Constants\ContentTypes;
use Scaleplan\Http\Interfaces\CurrentRequestInterface;
use Scaleplan\Http\Interfaces\CurrentResponseInterface;
use function Scaleplan\Helpers\get_env;

/**
 * Class CurrentRequest
 *
 * @package Scaleplan\Http
 */
class CurrentRequest extends AbstractRequest implements CurrentRequestInterface, ServerRequestInterface
{
    /**
     * URL запроса
     *
     * @var string
     */
    protected $url = '';

    /**
     * Какие заголовки запроса учесть во время кэширования
     *
     * @var array
     */
    protected $cacheAdditionalParams = [];

    /**
     * @var array
     */
    protected $session = [];

    /**
     * @var CurrentResponseInterface
     */
    protected $response;

    /**
     * @var string
     */
    protected $accept = ContentTypes::HTML;

    /**
     * @var UploadedFileInterface[]
     */
    protected $files = [];

    /**
     * @var string|null
     */
    protected $protocolVersion;

    /**
     * @var Stream
     */
    protected $body;

    /**
     * @var null|array|object
     */
    protected $parsedBody;

    /**
     * CurrentRequest constructor.
     *
     * @throws \Scaleplan\File\Exceptions\FileSaveException
     * @throws \Scaleplan\File\Exceptions\FileUploadException
     * @throws \Throwable
     */
    public function __construct()
    {
        $this->response = new CurrentResponse($this);

        if (!isset($_SERVER['REQUEST_URI'])) {
            return;
        }

        $this->protocolVersion = explode('/', $_SERVER['SERVER_PROTOCOL'] ?? '')[1] ?? null;
        $this->url = explode('?', $_SERVER['REQUEST_URI'])[0];
        $this->headers = getallheaders();
        $this->body = new Stream('php://input');
        $this->parsedBody = json_decode((string)$this->body, true);

        if (($this->headers[Header::CONTENT_TYPE] ?? '') === ContentTypes::JSON) {
            $this->setParams($this->parsedBody);
        } else {
            $this->setParams($_REQUEST);
        }

        $this->isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === static::X_REQUESTED_WITH_VALUE;

        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->session = $_SESSION ?? [];
        $this->cookie = $_COOKIE ?? [];

        if (!empty($this->headers[Header::ACCEPT])) {
            $this->accept = preg_split('/[,;]/', $this->headers[Header::ACCEPT])[0];
        }

        $denyParams = array_map('trim', explode(',', get_env('DENY_PARAMS') ?? ''));
        foreach ($denyParams as $denyParam) {
            unset($this->params[$denyParam]);
        }

        $additionalHeaders = array_map('trim', explode(',', get_env('CACHE_ADDITIONAL_HEADERS') ?? ''));
        foreach ($additionalHeaders as $header) {
            $this->cacheAdditionalParams[$header] = $_SERVER[$header] ?? null;
        }
    }

    /**
     * @return string
     */
    public function getAccept() : string
    {
        return $this->accept;
    }

    /**
     * @param array $params
     *
     * @throws \Scaleplan\File\Exceptions\FileSaveException
     * @throws \Scaleplan\File\Exceptions\FileUploadException
     * @throws \Throwable
     */
    protected function setParams(array $params) : void
    {
        $this->files = FileHelper::saveFiles($_FILES);
        $this->params = array_map(static function ($item) {
            if ($item === 'null') {
                $item = null;
            }

            return \is_array($item) ? array_filter($item) : $item;
        }, array_merge_recursive($params, static::filesUnpack($this->files)));
    }

    /**
     * @param array $files
     *
     * @return array
     */
    protected static function filesUnpack(array $files) : array
    {
        return array_filter(array_map(static function ($item) {
            if ($item instanceof UploadedFile) {
                return $item->toArray();
            }

            return is_array($item) ? static::filesUnpack($item) : null;
        }, $files));
    }

    /**
     * @return array
     */
    public function getSession() : array
    {
        return $this->session;
    }

    /**
     * @param $key
     *
     * @return mixed|null
     */
    public function getSessionVar($key)
    {
        return $this->session[$key] ?? null;
    }

    /**
     * @return CurrentResponse
     */
    public function getResponse() : CurrentResponseInterface
    {
        return $this->response;
    }

    /**
     * @return array
     */
    public function getCacheAdditionalParams() : array
    {
        return $this->cacheAdditionalParams;
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
     * @return string
     */
    public static function getScheme() : string
    {
        return !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    }

    /**
     * @return array|mixed
     */
    public function getServerParams() : array
    {
        return $_SERVER;
    }

    /**
     * @return array
     */
    public function getCookieParams() : array
    {
        return $this->getCookie();
    }

    /**
     * @param array $cookies
     *
     * @return self
     *
     * @throws \Throwable
     */
    public function withCookieParams(array $cookies) : self
    {
        $object = new static();
        $object->cookie = $cookies;

        return $object;
    }

    /**
     * @return array
     */
    public function getQueryParams() : array
    {
        return $this->getParams();
    }

    /**
     * @param array $query
     *
     * @return static
     *
     * @throws \Scaleplan\Helpers\Exceptions\FileSaveException
     * @throws \Scaleplan\Helpers\Exceptions\FileUploadException
     * @throws \Throwable
     */
    public function withQueryParams(array $query) : self
    {
        $object = new static();
        $object->setParams($query);

        return $object;
    }

    /**
     * @return array
     */
    public function getUploadedFiles() : array
    {
        return $this->files;
    }

    /**
     * @return string|null
     */
    public function getProtocolVersion() : ?string
    {
        return $this->protocolVersion;
    }

    /**
     * @param string $version
     *
     * @return $this
     */
    public function withProtocolVersion($version) : self
    {
        $request = clone $this;
        $request->protocolVersion = $version;

        return $request;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getHeaderLine($name) : string
    {
        return implode(',', (array)$this->getHeader($name));
    }

    /**
     * @param string $name
     * @param string|string[] $value
     *
     * @return $this
     */
    public function withHeader($name, $value) : self
    {
        $request = clone $this;
        $request->headers[$name] = $value;

        return $request;
    }

    /**
     * @param string $name
     * @param string|string[] $value
     *
     * @return $this|ServerRequestInterface
     */
    public function withAddedHeader($name, $value)
    {
        $request = clone $this;
        $request->headers[$name] = array_merge($request->headers[$name], (array)$value);

        return $request;
    }

    /**
     * @param string $name
     *
     * @return ServerRequestInterface|CurrentRequest
     */
    public function withoutHeader($name)
    {
        $request = clone $this;
        unset($request->headers[$name]);

        return $request;
    }

    /**
     * @return StreamInterface
     */
    public function getBody() : StreamInterface
    {
        return $this->body;
    }

    /**
     * @param StreamInterface $body
     *
     * @return $this
     */
    public function withBody(StreamInterface $body) : self
    {
        $request = clone $this;
        $request->body = $body;

        return $request;
    }

    /**
     * @return string
     */
    public function getRequestTarget() : string
    {
        return $this->url;
    }

    /**
     * @param mixed $requestTarget
     *
     * @return $this
     */
    public function withRequestTarget($requestTarget) : self
    {
        $request = clone $this;
        $request->url = $requestTarget;

        return $request;
    }

    /**
     * @param string $method
     *
     * @return $this
     */
    public function withMethod($method) : self
    {
        $request = clone $this;
        $request->method = $method;

        return $request;
    }

    /**
     * @return UriInterface
     */
    public function getUri() : UriInterface
    {
        return new Uri($this->url);
    }

    /**
     * @param UriInterface $uri
     * @param bool $preserveHost
     *
     * @return ServerRequestInterface|CurrentRequest
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $request = clone $this;
        $request->url = (string)$uri;

        return $request;
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        $request = clone $this;
        $request->files = $uploadedFiles;

        return $request;
    }

    /**
     * @return null|array|object
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data)
    {
        $request = clone $this;
        $request->parsedBody = $data;

        return $request;
    }

    /**
     * @return array
     */
    public function getAttributes() : array
    {
        return [];
    }

    /**
     * @param string $name
     * @param null $default
     *
     * @return mixed|null
     */
    public function getAttribute($name, $default = null)
    {
        return null;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return ServerRequestInterface|CurrentRequest
     */
    public function withAttribute($name, $value)
    {
        return clone $this;
    }

    /**
     * @param string $name
     *
     * @return ServerRequestInterface|CurrentRequest
     */
    public function withoutAttribute($name)
    {
        return clone $this;
    }
}
