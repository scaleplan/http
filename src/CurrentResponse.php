<?php
declare(strict_types=1);

namespace Scaleplan\Http;

use Lmc\HttpConstants\Header;
use Scaleplan\Http\Constants\ContentTypes;
use Scaleplan\Http\Exceptions\HttpException;
use Scaleplan\Http\Exceptions\NotFoundException;
use Scaleplan\Http\Hooks\SendError;
use Scaleplan\Http\Hooks\SendFile;
use Scaleplan\Http\Hooks\SendRedirect;
use Scaleplan\Http\Hooks\SendResponse;
use Scaleplan\Http\Hooks\SendUnauthUserError;
use Scaleplan\Http\Interfaces\AbstractRequestInterface;
use Scaleplan\Http\Interfaces\CurrentRequestInterface;
use Scaleplan\Http\Interfaces\CurrentResponseInterface;
use Scaleplan\HttpStatus\HttpStatusCodes;
use Scaleplan\Main\Interfaces\UserInterface;
use Scaleplan\Main\Interfaces\ViewInterface;
use Scaleplan\Main\View;
use Scaleplan\Result\DbResult;
use function Scaleplan\DependencyInjection\get_required_static_container;
use function Scaleplan\Event\dispatch;
use function Scaleplan\Helpers\get_required_env;
use function Scaleplan\Translator\translate;

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
        //$this->cookie = $_COOKIE;
    }

    /**
     * Редирект на страницу авторизации, если еще не авторизован
     *
     * @param UserInterface $user
     *
     * @throws \Scaleplan\Event\Exceptions\ClassNotImplementsEventInterfaceException
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     */
    public function redirectUnauthorizedUser(UserInterface $user) : void
    {
        if ($user->getId()) {
            return;
        }

        $this->buildRedirect(get_required_env('AUTH_PATH'));

        dispatch(SendUnauthUserError::class, ['response' => $this]);
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
     * @param string $url
     *
     * @throws \Scaleplan\Event\Exceptions\ClassNotImplementsEventInterfaceException
     */
    public function buildRedirect(string $url) : void
    {
        if ($this->request->isAjax() || $this->request->getAccept() === ContentTypes::JSON) {
            $this->payload = \json_encode(['redirect' => $url], JSON_UNESCAPED_UNICODE);
            $this->setContentType(ContentTypes::JSON);
        } else {
            $this->addHeader(Header::LOCATION, $url);
            $this->setCode(HttpStatusCodes::HTTP_TEMPORARY_REDIRECT);
        }

        $this->send();

        dispatch(SendRedirect::class, ['response' => $this]);
    }

    /**
     * Редирект на nginx
     *
     * @param string $url
     *
     * @throws \Scaleplan\Event\Exceptions\ClassNotImplementsEventInterfaceException
     */
    public function XRedirect(string $url) : void
    {
        $this->setContentType('');
        $this->addHeader('X-Accel-Redirect', $url);

        $this->send();

        dispatch(SendRedirect::class, ['response' => $this]);
    }

    /**
     * @param \Throwable $e
     *
     * @throws \PhpQuery\Exceptions\PhpQueryException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerNotFoundException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     * @throws \Scaleplan\Event\Exceptions\ClassNotImplementsEventInterfaceException
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     * @throws \Scaleplan\Result\Exceptions\ResultException
     * @throws \Scaleplan\Templater\Exceptions\DomElementNotFoundException
     */
    public static function sendError(\Throwable $e) : void
    {
        $headers = getallheaders();
        $accept = preg_split('/[,;]/', $headers[Header::ACCEPT] ?? '')[0] ?? ContentTypes::HTML;
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === AbstractRequestInterface::X_REQUESTED_WITH_VALUE;

        if ($accept === ContentTypes::JSON || $isAjax) {
            $errorResult = new DbResult(
                [
                    'code'    => $e->getCode(),
                    'message' => @iconv('UTF-8', 'UTF-8//IGNORE', $e->getMessage()),
                    'errors'  => method_exists($e, 'getErrors') ? $e->getErrors() : [],
                ]
            );
            $contentType = ContentTypes::JSON;
        } else {
            /** @var View $view */
            $view = get_required_static_container(ViewInterface::class);
            $errorResult = $view::renderError($e);
            $contentType = ContentTypes::HTML;
        }

        $i = 0;
        while ($i < ob_get_level()) {
            ob_end_clean();
            $i++;
        }

        $code = $e->getCode();
        if (\in_array($code, HttpStatusCodes::ERROR_CODES, true)) {
            http_response_code($code);
        } else {
            http_response_code(HttpException::CODE);
        }

        \header(Header::CONTENT_TYPE . ": $contentType");
        echo (string)$errorResult;

        dispatch(SendError::class, ['exception' => $e,]);
    }

    /**
     * @param \Throwable $e
     *
     * @throws \PhpQuery\Exceptions\PhpQueryException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerNotFoundException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     * @throws \Scaleplan\Event\Exceptions\ClassNotImplementsEventInterfaceException
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     * @throws \Scaleplan\Result\Exceptions\ResultException
     * @throws \Scaleplan\Templater\Exceptions\DomElementNotFoundException
     */
    public function buildError(\Throwable $e) : void
    {
        if ($this->request->getAccept() === ContentTypes::JSON || $this->request->isAjax()) {
            $errorResult = new DbResult(
                [
                    'code'    => $e->getCode(),
                    'message' => @iconv('UTF-8', 'UTF-8//IGNORE', $e->getMessage()),
                    'errors'  => method_exists($e, 'getErrors') ? $e->getErrors() : [],
                ]
            );
            $this->setContentType(ContentTypes::JSON);
        } else {
            /** @var View $view */
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

        dispatch(SendError::class, ['exception' => $e,]);
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
     * @throws \Scaleplan\Event\Exceptions\ClassNotImplementsEventInterfaceException
     */
    public function send() : void
    {
        static $send;
        if ($send) {
            return;
        }

        $i = 0;
        while ($i < ob_get_level()) {
            ob_end_clean();
            $i++;
        }

        header_remove();

        foreach ($this->headers as $name => $value) {
            $name && $value && header("$name: $value");
        }

        $domain = '';
        if (!empty($_SERVER['HTTP_HOST'])) {
            $hostParts = array_reverse(explode('.', $_SERVER['HTTP_HOST']));
            $domain = ".{$hostParts[1]}.{$hostParts[0]}";
        }

        foreach ($this->cookie as $key => $value) {
            setcookie($key, (string)$value, 0, '/', $domain);
        }

        http_response_code($this->code);

        echo (string)$this->payload;
        fastcgi_finish_request();
        dispatch(SendResponse::class, ['response' => $this]);
        $send = 1;
    }

    /**
     * @param string $filePath
     *
     * @throws NotFoundException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     * @throws \Scaleplan\Event\Exceptions\ClassNotImplementsEventInterfaceException
     */
    public function sendFile(string $filePath) : void
    {
        if (!file_exists($filePath)) {
            throw new NotFoundException(translate('http.file-not-found', ['file-path' => $filePath,]));
        }

        $this->setContentType(mime_content_type($filePath));
        $filePathArray = explode('/', $filePath);
        $fileName = end($filePathArray);
        http_send_content_disposition($fileName, true);
        http_send_content_type(mime_content_type($filePath));
        http_throttle(0.1, 2048);
        http_send_file($filePath);
        dispatch(SendFile::class, ['response' => $this]);
        exit;
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

    /**
     * @param string $name
     * @param $value
     */
    public function addCookie(string $name, $value) : void
    {
        $this->cookie[$name] = $value;
    }

    /**
     * @param string $name
     */
    public function removeCookie(string $name) : void
    {
        unset($this->cookie[$name]);
    }
}
