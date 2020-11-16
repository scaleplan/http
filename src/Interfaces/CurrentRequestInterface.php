<?php

namespace Scaleplan\Http\Interfaces;

/**
 * Class CurrentRequest
 *
 * @package Scaleplan\Http
 */
interface CurrentRequestInterface extends AbstractRequestInterface
{
    /**
     * @return string
     */
    public function getAccept() : string;

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
     * @return CurrentResponseInterface
     */
    public function getResponse() : CurrentResponseInterface;

    /**
     * @return array
     */
    public function getCacheAdditionalParams() : array;

    /**
     * Вернуть URL запроса
     *
     * @return string
     */
    public function getURL() : string;
}
