<?php

namespace Scaleplan\Http;

use Scaleplan\Http\Exceptions\InvalidUrlException;

/**
 * Class CurrentRequest
 *
 * @package Scaleplan\Http
 */
interface CurrentRequestInterface extends AbstractRequestInterface
{
    /**
     * Вернуть объект текущего запроса к серверу
     *
     * @return CurrentRequest
     *
     * @throws InvalidUrlException
     * @throws \Scaleplan\Helpers\Exceptions\FileUploadException
     * @throws \Scaleplan\Helpers\Exceptions\HelperException
     */
    public static function getRequest() : CurrentRequest;

    /**
     * @return array
     */
    public function getSession() : array;

    /**
     * @param $key
     *
     * @return mixed|null
     */
    public function getSessionVar($key);

    /**
     * @return mixed|null
     */
    public function getUser();

    /**
     * @param UserInterface $user
     */
    public function setUser(UserInterface $user) : void;

    /**
     * @return CurrentResponseInterface
     *
     * @throws Exceptions\EnvVarNotFoundOrInvalidException
     * @throws \ReflectionException
     */
    public function execute() : CurrentResponseInterface;

    /**
     * @return array
     */
    public function getCacheAdditionalParams() : array;

    /**
     * @param array $cacheAdditionalParams
     */
    public function setCacheAdditionalParams(array $cacheAdditionalParams) : void;

    /**
     * @return CacheInterface
     */
    public function getCache() : CacheInterface;

    /**
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache) : void;
}