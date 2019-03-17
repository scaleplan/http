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
     * @return int|null
     */
    public function getLimit() : ?int;

    /**
     * @return int|null
     */
    public function getPage() : ?int;

    /**
     * @return int
     */
    public function getHttpCode() : int;
}
