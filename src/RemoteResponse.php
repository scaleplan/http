<?php

namespace Scaleplan\Http;

use Scaleplan\DTO\DTO;
use Scaleplan\Http\Interfaces\RemoteResponseInterface;

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
     * @var int|null
     */
    protected $limit;

    /**
     * @var int|null
     */
    protected $page;

    /**
     * @var int
     */
    protected $httpCode;

    /**
     * RemoteResponse constructor.
     *
     * @param DTO|mixed $result
     * @param int $httpCode
     * @param int|null $limit
     * @param int|null $page
     */
    public function __construct($result, int $httpCode, int $limit = null, int $page = null)
    {
        $this->result = $result;
        $this->httpCode = $httpCode;
        $this->limit = $limit;
        $this->page = $page;
    }

    /**
     * @return DTO|mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return int|null
     */
    public function getLimit() : ?int
    {
        return $this->limit;
    }

    /**
     * @return int|null
     */
    public function getPage() : ?int
    {
        return $this->page;
    }

    /**
     * @return int
     */
    public function getHttpCode() : int
    {
        return $this->httpCode;
    }
}
