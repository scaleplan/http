<?php

namespace Scaleplan\Http;

use Lmc\HttpConstants\Header;
use function Scaleplan\Event\dispatch;
use Scaleplan\Http\Constants\Codes;
use Scaleplan\Http\Exceptions\EnvVarNotFoundOrInvalidException;
use Scaleplan\Http\Exceptions\NotFoundException;
use function Scaleplan\Helpers\getenv;

/**
 * Ответ от сервера
 *
 * Class CurrentResponse
 *
 * @package Scaleplan\Http
 */
class CurrentResponse implements CurrentResponseInterface
{
    public const DEFAULT_ERROR_CODE = Codes::HTTP_BAD_REQUEST;

    public const SEND_EVENT_NAME = 'response_send';

    public const SEND_FILE_EVENT_NAME = 'file_send';

    /**
     * @var AbstractRequestInterface
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
     * @param AbstractRequestInterface $request
     */
    public function __construct(AbstractRequestInterface $request)
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
        if (!($authPath = getenv('AUTH_PATH'))) {
            throw new EnvVarNotFoundOrInvalidException();
        }

        if (!$this->request->getUser()) {
            if ($this->request->isAjax()) {
                $this->payload = json_encode(['redirect' => $authPath], JSON_UNESCAPED_UNICODE);
            } else {
                $this->addHeader('Location', $authPath);
            }

            $this->setCode(Codes::HTTP_UNAUTHORIZED);
        }
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
        if (!($viewClass = getenv('VIEW_CLASS')) || !($viewClass instanceof ViewInterface)) {
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
        if (\in_array($e->getCode(), Codes::statusTexts)) {
            $this->setCode($e->getCode());
        } else {
            $this->setCode(getenv('DEFAULT_ERROR_CODE') ?? static::DEFAULT_ERROR_CODE);
        }

        if ($this->request->isAjax()) {
            $this->payload = json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->payload = static::buildErrorPage($e);
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
            $this->addHeader('Location', $url);
        }
    }

    /**
     * Редирект на nginx
     *
     * @param string $url - на какой урл редиректить
     */
    public function XRedirect(string $url) : void
    {
        $this->addHeader('Content-Type', '');
        $this->addHeader('X-Accel-Redirect', $url);
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
     *
     * @throws \Scaleplan\Event\Exceptions\ClassIsNotEventException
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
            header($name . $value ? ": $value" : '');
        }

        session_start($this->session);
        foreach ($this->cookie as $key => $value) {
            setcookie($key, $value);
        }

        echo (string) $this->payload;
        fastcgi_finish_request();
        dispatch(getenv('SEND_EVENT_NAME') ?? static::SEND_EVENT_NAME);
    }

    /**
     * @param string $filePath
     *
     * @throws NotFoundException
     * @throws \Scaleplan\Event\Exceptions\ClassIsNotEventException
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
        fastcgi_finish_request();
        dispatch(getenv('SEND_FILE_EVENT_NAME') ?? static::SEND_FILE_EVENT_NAME);
    }

    /**
     * @return AbstractRequestInterface
     */
    public function getRequest() : AbstractRequestInterface
    {
        return $this->request;
    }

    /**
     * @param AbstractRequestInterface $request
     */
    public function setRequest(AbstractRequestInterface $request) : void
    {
        $this->request = $request;
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