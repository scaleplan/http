<?php

namespace Scaleplan\Http;

use Scaleplan\DTO\DTO;
use Scaleplan\Http\Interfaces\RemoteResponseInterface;
use Scaleplan\HttpStatus\HttpStatusCodes;

/**
 * Class RemoteResponse
 *
 * @package Scaleplan\Http
 */
class RemoteResponse implements RemoteResponseInterface
{
    /**
     * @var DTO|mixed
     */
    protected $result;

    /**
     * @var int
     */
    protected $httpCode;

    /**
     * RemoteResponse constructor.
     *
     * @param DTO|mixed $result
     * @param int $httpCode
     */
    public function __construct($result, int $httpCode)
    {
        $this->result = $result;
        $this->httpCode = $httpCode;
    }

    /**
     * @return DTO|mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return int
     */
    public function getHttpCode() : int
    {
        return $this->httpCode;
    }

    /**
     * @return bool
     */
    public function isOk() : bool
    {
        return $this->httpCode >= HttpStatusCodes::HTTP_OK && $this->httpCode < HttpStatusCodes::HTTP_BAD_REQUEST;
    }
}
