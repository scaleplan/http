<?php

namespace Scaleplan\Http\Interfaces;

use Scaleplan\DTO\DTO;

/**
 * Class RemoteResponse
 *
 * @package Scaleplan\Http
 */
interface RemoteResponseInterface
{
    /**
     * @return DTO|mixed
     */
    public function getResult();

    /**
     * @return int
     */
    public function getHttpCode() : int;
}
