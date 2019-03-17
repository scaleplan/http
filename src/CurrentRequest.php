<?php

namespace Scaleplan\Http;

use Scaleplan\Helpers\FileHelper;
use function Scaleplan\Helpers\get_env;
use Scaleplan\Http\Exceptions\InvalidUrlException;
use Scaleplan\Http\Interfaces\CurrentRequestInterface;
use Scaleplan\Http\Interfaces\CurrentResponseInterface;

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
     * CurrentRequest constructor.
     *
     * @throws InvalidUrlException
     * @throws \Scaleplan\Helpers\Exceptions\FileUploadException
     * @throws \Scaleplan\Helpers\Exceptions\HelperException
     */
    public function __construct()
    {
        if (empty($_SERVER['REQUEST_URI']) || !static::checkUrl($_SERVER['REQUEST_URI'])) {
            throw new InvalidUrlException();
        }

        $this->url = $_SERVER['REQUEST_URI'];
        $this->params = array_map(function ($item) {
            return \is_array($item) ? array_filter($item) : $item;
        }, array_merge_recursive($_REQUEST, FileHelper::saveFiles($_FILES)));

        $this->isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->session = $_SESSION;
        $this->cookie = $_COOKIE;
        $this->headers = getallheaders();

        $this->response = new CurrentResponse($this);

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
        if (!($template = get_env('PAGE_URL_TEMPLATE') ?? static::PAGE_URL_TEMPLATE)) {
            return true;
        }

        return !empty($url) && preg_match($template, $url);
    }

    /**
     * @return CurrentResponseInterface
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
}
