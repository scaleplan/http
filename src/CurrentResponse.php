<?php

namespace Scaleplan\Http;

use Lmc\HttpConstants\Header;
use function Scaleplan\Event\dispatch_async;
use Scaleplan\Http\Exceptions\EnvVarNotFoundOrInvalidException;
use Scaleplan\Http\Exceptions\NotFoundException;
use function Scaleplan\Helpers\get_env;
use Scaleplan\Http\Hooks\SendError;
use Scaleplan\Http\Hooks\SendFile;
use Scaleplan\Http\Hooks\SendRedirect;
use Scaleplan\Http\Hooks\SendResponse;
use Scaleplan\Http\Hooks\SendUnauthUserError;
use Scaleplan\Http\Interfaces\CurrentRequestInterface;
use Scaleplan\Http\Interfaces\CurrentResponseInterface;
use Scaleplan\Http\Interfaces\ViewInterface;
use Scaleplan\HttpStatus\HttpStatusCodes;
use Scaleplan\HttpStatus\HttpStatusPhrases;

/**
 * Ответ от сервера
 *
 * Class CurrentResponse
 *
 * @package Scaleplan\Http
 */
class CurrentResponse implements CurrentResponseInterface
{
    public const DEFAULT_ERROR_CODE = HttpStatusCodes::HTTP_BAD_REQUEST;

    /**
     * @var CurrentRequestInterface
     */
    protected $request;

    /**
     * @var mixed|null
     */
    protected $payload;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var int
     */
    protected $code;

    /**
     * @var array
     */
    protected $session = [];

    /**
     * @var array
     */
    protected $cookie = [];

    /**
     * Response constructor.
     *
     * @param CurrentRequestInterface $request
     */
    public function __construct(CurrentRequestInterface $request)
    {
        $this->request = $request;
        $this->session = $request->getSession();
        $this->cookie = $request->getCookie();
    }

    /**
     * Редирект на страницу авторизации, если еще не авторизован
     *
     * @throws EnvVarNotFoundOrInvalidException
     */
    public function redirectUnauthorizedUser() : void
    {
        if (!($authPath = get_env('AUTH_PATH'))) {
            throw new EnvVarNotFoundOrInvalidException();
        }

        if ($this->request->isAjax()) {
            $this->payload = json_encode(['redirect' => $authPath], JSON_UNESCAPED_UNICODE);
        } else {
            $this->addHeader(Header::LOCATION, $authPath);
        }

        $this->setCode(HttpStatusCodes::HTTP_UNAUTHORIZED);
        $this->send();

        dispatch_async(SendUnauthUserError::class, $this);
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
     * @param int $code
     */
    public function setCode(int $code) : void
    {
        $this->code = $code;
    }

    /**
     * Возвращает страницу ошибки
     *
     * @param \Throwable $e - объект пойманной ошибки
     *
     * @return mixed
     *
     * @throws EnvVarNotFoundOrInvalidException
     */
    protected static function buildErrorPage(\Throwable $e)
    {
        if (!($viewClass = get_env('VIEW_CLASS')) || !($viewClass instanceof ViewInterface)) {
            throw new EnvVarNotFoundOrInvalidException();
        }

        return $viewClass::renderError($e);
    }

    /**
     * Формирует либо json-ошибку (если зарос AJAX), либо страницу ошибки
     *
     * @param \Throwable $e - объект пойманной ошибки
     *
     * @throws EnvVarNotFoundOrInvalidException
     */
    public function buildError(\Throwable $e) : void
    {
        if (\array_key_exists($e->getCode(), HttpStatusPhrases::HTTP_STATUSES)) {
            $this->setCode($e->getCode());
        } else {
            $this->setCode(get_env('DEFAULT_ERROR_CODE') ?? static::DEFAULT_ERROR_CODE);
        }

        if ($this->request->isAjax()) {
            $this->payload = json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->payload = static::buildErrorPage($e);
        $this->send();

        dispatch_async(SendError::class, $this);
    }

    /**
     * Редирект в зависимости от типа запроса
     *
     * @param string $url - на какой урл редиректить
     */
    public function buildRedirect(string $url) : void
    {
        if ($this->request->isAjax()) {
            $this->payload = json_encode(['redirect' => $url], JSON_UNESCAPED_UNICODE);
        } else {
            $this->addHeader(Header::LOCATION, $url);
        }

        $this->send();

        dispatch_async(SendRedirect::class, $this);
    }

    /**
     * Редирект на nginx
     *
     * @param string $url - на какой урл редиректить
     */
    public function XRedirect(string $url) : void
    {
        $this->addHeader(Header::CONTENT_TYPE, '');
        $this->addHeader('X-Accel-Redirect', $url);

        dispatch_async(SendRedirect::class, $this);
    }

    /**
     * @param string $contentType
     */
    public function setContentType($contentType = ContentTypes::HTML) : void
    {
        $this->addHeader(Header::CONTENT_TYPE, $contentType);
    }

    /**
     * Отправить ответ
     */
    public function send() : void
    {
        $i = 0;
        while ($i < ob_get_level()) {
            ob_end_clean();
            $i++;
        }

        header_remove();

        foreach ($this->headers as $name => $value) {
            header(($name . $value) ? ": $value" : '');
        }

        session_start($this->session);
        foreach ($this->cookie as $key => $value) {
            setcookie($key, $value);
        }

        echo (string) $this->payload;

        dispatch_async(SendResponse::class, $this);
    }

    /**
     * @param string $filePath
     *
     * @throws NotFoundException
     */
    public function sendFile(string $filePath) : void
    {
        if (!file_exists($filePath)) {
            throw new NotFoundException("File $filePath not found.");
        }

        $filePathArray = explode('/', $filePath);
        $fileName = end($filePathArray);
        http_send_content_disposition($fileName, true);
        http_send_content_type(mime_content_type($filePath));
        http_throttle(0.1, 2048);
        http_send_file($filePath);

        dispatch_async(SendFile::class, $this);
    }

    /**
     * @return CurrentRequestInterface
     */
    public function getRequest() : CurrentRequestInterface
    {
        return $this->request;
    }

    /**
     * @return null|mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param null|mixed $payload
     */
    public function setPayload($payload) : void
    {
        $this->payload = $payload;
    }

    /**
     * @return array
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers) : void
    {
        $this->headers = $headers;
    }

    /**
     * @return array
     */
    public function getSession() : array
    {
        return $this->session;
    }

    /**
     * @param array $session
     */
    public function setSession(array $session) : void
    {
        $this->session = $session;
    }

    /**
     * @param string $key
     * @param $value
     */
    public function addSessionVar(string $key, $value) : void
    {
        $this->session[$key] = $value;
    }

    /**
     * @param string $key
     */
    public function removeSessionVar(string $key) : void
    {
        unset($this->session[$key]);
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
     * @return array
     */
    public function getCookie() : array
    {
        return $this->cookie;
    }

    /**
     * @param array $cookie
     */
    public function setCookie(array $cookie) : void
    {
        $this->cookie = $cookie;
    }
}
