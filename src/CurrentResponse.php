<?php

namespace Scaleplan\Http;

use Lmc\HttpConstants\Header;
use function Scaleplan\DependencyInjection\get_required_static_container;
use function Scaleplan\Event\dispatch_async;
use function Scaleplan\Helpers\get_required_env;
use Scaleplan\Http\Constants\ContentTypes;
use Scaleplan\Http\Exceptions\HttpException;
use Scaleplan\Http\Exceptions\NotFoundException;
use Scaleplan\Http\Hooks\SendError;
use Scaleplan\Http\Hooks\SendFile;
use Scaleplan\Http\Hooks\SendRedirect;
use Scaleplan\Http\Hooks\SendResponse;
use Scaleplan\Http\Hooks\SendUnauthUserError;
use Scaleplan\Http\Interfaces\CurrentRequestInterface;
use Scaleplan\Http\Interfaces\CurrentResponseInterface;
use Scaleplan\HttpStatus\HttpStatusCodes;
use Scaleplan\Main\Interfaces\UserInterface;
use Scaleplan\Main\Interfaces\ViewInterface;
use Scaleplan\Result\DbResult;

/**
 * Ответ от сервера
 *
 * Class CurrentResponse
 *
 * @package Scaleplan\Http
 */
class CurrentResponse implements CurrentResponseInterface
{
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
    protected $code = HttpStatusCodes::HTTP_OK;

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
        $this->setContentType();
    }

    /**
     * Редирект на страницу авторизации, если еще не авторизован
     *
     * @param UserInterface $user
     *
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     */
    public function redirectUnauthorizedUser(UserInterface $user) : void
    {
        if (!$user->isGuest()) {
            return;
        }

        $this->buildRedirect(get_required_env('AUTH_PATH'));

        dispatch_async(SendUnauthUserError::class, ['response' => $this]);
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
     * Редирект в зависимости от типа запроса
     *
     * @param string $url - на какой урл редиректить
     */
    public function buildRedirect(string $url) : void
    {
        if ($this->request->isAjax()) {
            $this->setContentType(ContentTypes::JSON);
            $this->payload = \json_encode(['redirect' => $url], JSON_UNESCAPED_UNICODE);
        } else {
            $this->addHeader(Header::LOCATION, $url);
        }
        $this->setCode(HttpStatusCodes::HTTP_TEMPORARY_REDIRECT);

        $this->send();

        dispatch_async(SendRedirect::class, ['response' => $this]);
    }

    /**
     * Редирект на nginx
     *
     * @param string $url - на какой урл редиректить
     */
    public function XRedirect(string $url) : void
    {
        $this->setContentType('');
        $this->addHeader('X-Accel-Redirect', $url);

        $this->send();

        dispatch_async(SendRedirect::class, ['response' => $this]);
    }

    /**
     * @param \Throwable $e
     *
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerNotFoundException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     * @throws \Scaleplan\Result\Exceptions\ResultException
     */
    public function buildError(\Throwable $e) : void
    {
        if ($this->request->isAjax()) {
            $this->setContentType(ContentTypes::JSON);
            $errorResult = new DbResult(
                [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'errors' => method_exists($e, 'getErrors') ? $e->getErrors() : [],
                ]
            );
        } else {
            /** @var ViewInterface $view */
            $view = get_required_static_container(ViewInterface::class);
            $errorResult = $view::renderError($e);
        }

        $code = $e->getCode();
        if (\in_array($code, HttpStatusCodes::ERROR_CODES, true)) {
            $this->setCode($code);
        } else {
            $this->setCode(HttpException::CODE);
        }

        $this->setPayload($errorResult);
        $this->send();

        dispatch_async(SendError::class, ['response' => $this]);
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
            $name && header("$name: $value");
        }

        foreach ($this->cookie as $key => $value) {
            setcookie($key, $value);
        }

        http_response_code($this->code);

        echo (string) $this->payload;
        //fastcgi_finish_request();

        dispatch_async(SendResponse::class, ['response' => $this]);
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
        fastcgi_finish_request();

        dispatch_async(SendFile::class, ['response' => $this]);
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
