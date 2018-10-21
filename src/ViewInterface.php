<?php

namespace Scaleplan\Http;

/**
 * Interface ViewInterface
 *
 * @package Scaleplan\Http
 */
interface ViewInterface
{
    /**
     * @param \Throwable $e
     */
    public static function renderError(\Throwable $e);
}