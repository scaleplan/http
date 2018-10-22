<?php

namespace Scaleplan\Http;

use Scaleplan\Helpers\FileHelper;
use Scaleplan\Http\Exceptions\InvalidUrlException;
use Scaleplan\Http\Exceptions\NotFoundException;

/**
 * Class Request
 *
 * @package Scaleplan\Http
 */
class Request implements RequestInterface
{
    /**
     * Шаблон проверки правильности формата URL
     */
    public const PAGE_URL_TEMPLATE = '/^.+?\/[^\/]+$/';

    /**
     * Является ли текущий запрос AJAX-запросом
     *
     * @var bool
     */
    protected $isAjax = false;

    /**
     * Метод запроса
     *
     * @var bool
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
     * Какие заголовки запроса учесть во время кэширования
     *
     * @var array
     */
    protected $cacheAdditionalParams = [];

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var mixed
     */
    protected $user;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var array
     */
    protected $session = [];

    /**
     * @var array
     */
    protected $cookie = [];

    /**
     * @var Request
     */
    protected static $currentRequest;

    /**
     * Вернуть объект текущего запроса к серверу
     *
     * @return Request
     *
     * @throws InvalidUrlException
     * @throws \Scaleplan\Helpers\Exceptions\FileUploadException
     * @throws \Scaleplan\Helpers\Exceptions\HelperException
     */
    public static function getCurrentRequest() : Request
    {
        if (!static::$currentRequest) {
            if (empty($_SERVER['REQUEST_URI']) || !self::checkUrl($_SERVER['REQUEST_URI'])) {
                throw new InvalidUrlException();
            }

            $request = new Request(
                $_SERVER['REQUEST_URI'],
                array_merge_recursive($_REQUEST, FileHelper::saveFiles($_FILES))
            );
            $request->setIsAjax(!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            $request->method = $_SERVER['REQUEST_METHOD'];
            $request->setSession($_SESSION);
            $request->setCookie($_COOKIE);

            static::$currentRequest = $request;
        }

        return static::$currentRequest;
    }

    /**
     * Request constructor.
     *
     * @param string $url
     * @param array $params
     * @param CacheInterface|null $cache
     * @param null|mixed $user
     */
    public function __construct(
        string $url,
        array $params,
        CacheInterface $cache = null,
        UserInterface $user = null)
    {
        $this->user = $user;
        $this->url = $url;
        $this->cache = $cache;
        $this->response = new Response($this);

        $this->params = array_map(function($item) {
            return \is_array($item) ? array_filter($item) : $item;
        }, $params);

        foreach (getenv('DENY_PARAMS') ?? [] as $denyParam) {
            unset($this->params[$denyParam]);
        }

        foreach (getenv('CACHE_ADDITIONAL_HEADERS') ?? [] as $header) {
            $this->cacheAdditionalParams[$header] = $_SERVER[$header] ?? null;
        }
    }

    /**
     * @param array $session
     */
    public function setSession(array $session) : void
    {
        $this->session = $session;
    }

    /**
     * @return array
     */
    public function getSession() : array
    {
        return $this->session;
    }

    public function addSessionVariable(string $key, $value) : void
    {
        $this->session[$key] = $value;
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
     * @param string $key
     * @param $value
     */
    public function addCookieVariable(string $key, $value) : void
    {
        $this->cookie[$key] = $value;
    }

    /**
     * Является ли текущий запрос к серверу Ajax-запросом
     *
     * @param bool $isAjax
     */
    public function setIsAjax(bool $isAjax) : void
    {
        $this->isAjax = $isAjax;
    }

    /**
     * Проверить URL на соответствие маске по умолчанию
     *
     * @param string $url - URL
     *
     * @return bool
     */
    public static function checkUrl(string $url) : bool
    {
        if (!($template = getenv('PAGE_URL_TEMPLATE') ?? self::PAGE_URL_TEMPLATE)) {
            return true;
        }

        return !empty($url) && preg_match($template, $url);
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
     * Запрос был отправлен через Ajax?
     *
     * @return bool
     */
    public function isAjax() : bool
    {
        return $this->isAjax;
    }

    /**
     * @return mixed|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Выполнить метод контроллера
     *
     * @param \ReflectionClass $refClass - класс контроллера
     * @param \ReflectionMethod $method - метод контроллера
     * @param array $args - аргументы метода
     *
     * @return mixed
     */
    protected static function executeControllerMethod(
        \ReflectionClass $refClass,
        \ReflectionMethod $method,
        array &$args
    )
    {
        $object = (!$method->isStatic() && $refClass->isInstantiable()) ? $refClass->newInstance() : null;
        $params = $method->getParameters();
        if (!empty($params[0]) && $params[0]->isVariadic()) {
            return $method->invoke($object, $args);
        }

        return $method->invokeArgs($object, $args);
    }

    /**
     * Сконвертить урл в пару ИмяКонтроллера, ИмяМетода
     *
     * @return array
     */
    protected function convertURLToControllerMethod() : array
    {
        $path = explode('/', $this->getURL());
        $controllerName = (string) getenv('CONTROLLER_NAMESPACE')
            . str_replace(' ', '', ucwords(str_replace('-', ' ', $path[0])))
            . (string) getenv('CONTROLLER_POSTFIX');
        $methodName = (string) getenv('CONTROLLER_METHOD_PREFIX')
            . str_replace(' ', '', ucwords(str_replace('-', ' ', $path[1])));

        return [$controllerName, $methodName];
    }

    /**
     * @return null|string
     */
    protected function getCacheValue() : ?string
    {
        if (!$this->cache) {
            return null;
        }

        $this->cache->setParams($this->params + $this->cacheAdditionalParams);
        return $this->cache->getHtml($this->user->getId());
    }

    /**
     * @return Response
     *
     * @throws Exceptions\EnvVarNotFoundOrInvalidException
     * @throws \ReflectionException
     */
    public function execute() : Response
    {
        $cacheValue = $this->getCacheValue();
        if ($cacheValue !== null) {
            $this->response->setPayload($cacheValue);
            return $this->response;
        }

        [$controllerName, $methodName] = $this->convertURLToControllerMethod();

        $refClass = new \ReflectionClass($controllerName);
        if (!$refClass->hasMethod($methodName)) {
            $this->response->buildError(new NotFoundException());
            return $this->response;
        }

        $method = $refClass->getMethod($methodName);

        try {
            $this->response->setPayload(self::executeControllerMethod($refClass, $method, $this->params));
        } catch (\InvalidArgumentException $e) {
            $this->response->buildError($e);
        } finally {
            return $this->response;
        }
    }

    /**
     * @return bool
     */
    public function isMethod() : bool
    {
        return $this->method;
    }

    /**
     * @param bool $method
     */
    public function setMethod(bool $method) : void
    {
        $this->method = $method;
    }

    /**
     * @return array
     */
    public function getCacheAdditionalParams() : array
    {
        return $this->cacheAdditionalParams;
    }

    /**
     * @param array $cacheAdditionalParams
     */
    public function setCacheAdditionalParams(array $cacheAdditionalParams) : void
    {
        $this->cacheAdditionalParams = $cacheAdditionalParams;
    }

    /**
     * @return CacheInterface
     */
    public function getCache() : CacheInterface
    {
        return $this->cache;
    }

    /**
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache) : void
    {
        $this->cache = $cache;
    }

    /**
     * @return Response
     */
    public function getResponse() : Response
    {
        return $this->response;
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response) : void
    {
        $this->response = $response;
    }
}