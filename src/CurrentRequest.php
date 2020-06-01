<?php
declare(strict_types=1);

namespace Scaleplan\Http;

use Lmc\HttpConstants\Header;
use Scaleplan\Helpers\FileHelper;
use Scaleplan\Http\Constants\ContentTypes;
use Scaleplan\Http\Interfaces\CurrentRequestInterface;
use Scaleplan\Http\Interfaces\CurrentResponseInterface;
use function Scaleplan\Helpers\get_env;

/**
 * Class CurrentRequest
 *
 * @package Scaleplan\Http
 */
class CurrentRequest extends AbstractRequest implements CurrentRequestInterface
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
     * CurrentRequest constructor.
     *
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     * @throws \Scaleplan\Helpers\Exceptions\HelperException
     * @throws \Throwable
     */
    public function __construct()
    {
        $this->response = new CurrentResponse($this);

        if (!isset($_SERVER['REQUEST_URI'])) {
            return;
        }

        try {
            $this->url = explode('?', $_SERVER['REQUEST_URI'])[0];
            $this->headers = getallheaders();

            if (($this->headers[Header::CONTENT_TYPE] ?? '') === ContentTypes::JSON) {
                $this->setParams(json_decode(file_get_contents('php://input'), true));
            } else {
                $this->setParams($_REQUEST);
            }

            $this->isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === static::X_REQUESTED_WITH_VALUE;

            $this->method = $_SERVER['REQUEST_METHOD'];
            $this->session = $_SESSION;
            $this->cookie = $_COOKIE;

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
        } catch (\Throwable $e) {
            $this->response->buildError($e);
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
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     * @throws \Scaleplan\Helpers\Exceptions\FileSaveException
     * @throws \Scaleplan\Helpers\Exceptions\FileUploadException
     * @throws \Scaleplan\Helpers\Exceptions\HelperException
     * @throws \Throwable
     */
    protected function setParams(array $params) : void
    {
        $this->params = array_map(static function ($item) {
            if ($item === 'null') {
                $item = null;
            }

            return \is_array($item) ? array_filter($item) : $item;
        }, array_merge_recursive($params, FileHelper::saveFiles($_FILES)));
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
}
