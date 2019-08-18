<?php

namespace Scaleplan\Http;

use Scaleplan\Helpers\FileHelper;
use function Scaleplan\Helpers\get_env;
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
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     * @throws \Scaleplan\Helpers\Exceptions\FileSaveException
     * @throws \Scaleplan\Helpers\Exceptions\FileUploadException
     * @throws \Scaleplan\Helpers\Exceptions\HelperException
     * @throws \Throwable
     */
    public function __construct()
    {
        $this->url = $_SERVER['REQUEST_URI'];
        $this->params = array_map(static function ($item) {
            if ($item === 'null') {
                $item = null;
            }

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
}
