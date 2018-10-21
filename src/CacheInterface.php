<?php

namespace Scaleplan\Http;

use Scaleplan\Result\HTMLResult;

/**
 * Class CacheInterface
 *
 * @package Scaleplan\Http
 */
interface CacheInterface
{
    /**
     * @param array $params
     */
    public function setParams(array $params) : void;

    /**
     * @param string $verifyingFilePath
     *
     * @return HTMLResult
     */
    public function getHtml(string $verifyingFilePath = '') : HTMLResult;

    /**
     * @param HTMLResult $html
     * @param array $tags
     *
     * @return mixed
     */
    public function setHtml(HTMLResult $html, array $tags = []);
}