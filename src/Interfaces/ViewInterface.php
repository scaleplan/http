<?php

namespace Scaleplan\Http\Interfaces;

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
