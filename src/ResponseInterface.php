<?php

namespace Scaleplan\Http;

/**
 * Interface ResponseInterface
 *
 * @package Scaleplan\Http
 */
interface ResponseInterface
{
    /**
     * @param \Throwable $e
     */
    public function buildError(\Throwable $e) : void;
}