<?php

namespace Scaleplan\Http;

use Scaleplan\Helpers\FileHelper;
use Scaleplan\Http\Exceptions\InvalidUrlException;
use Scaleplan\Http\Exceptions\NotFoundException;

/**
 * Class CurrentRequest
 *
 * @package Scaleplan\Http
 */
class CurrentRequest extends AbstractRequest implements CurrentRequestInterface
{
    /**
     * Шаблон проверки правильности формата URL
     */
    public const PAGE_URL_TEMPLATE = '/^.+?\/[^\/]+$/';

    /**
     * @var CurrentRequest
     */
    protected static $currentRequest;

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
     * @var array
     */
    protected $session = [];

    /**
     * CurrentRequest constructor.
     *
     * @param string $url
     * @param array $params
     * @param CacheInterface|null $cache
     * @param null|UserInterface $user
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
        $this->response = new CurrentResponse($this);

        $this->params = array_map(function($item) {
            return \is_array($item) ? array_filter($item) : $item;
        }, $params);

        $denyParams = explode(',', getenv('DENY_PARAMS') ?? '');
        foreach ($denyParams as $denyParam) {
            unset($this->params[$denyParam]);
        }

        $additionalHeaders = explode(',', getenv('CACHE_ADDITIONAL_HEADERS') ?? '');
        foreach ($additionalHeaders as $header) {
            $this->cacheAdditionalParams[$header] = $_SERVER[$header] ?? null;
        }
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
     * Проверить URL на соответствие маске по умолчанию
     *
     * @param string $url - URL
     *
     * @return bool
     */
    public static function checkUrl(string $url) : bool
    {
        if (!($template = getenv('PAGE_URL_TEMPLATE') ?? static::PAGE_URL_TEMPLATE)) {
            return true;
        }

        return !empty($url) && preg_match($template, $url);
    }

    /**
     * @return mixed|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param UserInterface $user
     */
    public function setUser(UserInterface $user) : void
    {
        $this->user = $user;
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
        if (!$this->cache || !$this->user) {
            return null;
        }

        $this->cache->setParams($this->params + $this->cacheAdditionalParams);
        return $this->cache->getHtml($this->user->getId());
    }

    /**
     * @return CurrentResponseInterface
     *
     * @throws Exceptions\EnvVarNotFoundOrInvalidException
     * @throws \ReflectionException
     */
    public function execute() : CurrentResponseInterface
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
            $this->response->setPayload(static::executeControllerMethod($refClass, $method, $this->params));
        } catch (\InvalidArgumentException $e) {
            $this->response->buildError($e);
        } finally {
            return $this->response;
        }
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
     * Вернуть объект текущего запроса к серверу
     *
     * @return CurrentRequest
     *
     * @throws InvalidUrlException
     * @throws \Scaleplan\Helpers\Exceptions\FileUploadException
     * @throws \Scaleplan\Helpers\Exceptions\HelperException
     */
    public static function getRequest() : CurrentRequest
    {
        if (!static::$currentRequest) {
            if (empty($_SERVER['REQUEST_URI']) || !static::checkUrl($_SERVER['REQUEST_URI'])) {
                throw new InvalidUrlException();
            }

            $request = new CurrentRequest(
                $_SERVER['REQUEST_URI'],
                array_merge_recursive($_REQUEST, FileHelper::saveFiles($_FILES))
            );
            $request->isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            $request->method = $_SERVER['REQUEST_METHOD'];
            $request->session = $_SESSION;
            $request->cookie = $_COOKIE;
            $request->headers = getallheaders();

            static::$currentRequest = $request;
        }

        return static::$currentRequest;
    }
}