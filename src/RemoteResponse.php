<?php
declare(strict_types=1);

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
     * @var int
     */
    protected $httpCode;

    /**
     * @var array
     */
    protected $headers;

    /**
     * RemoteResponse constructor.
     *
     * @param $result
     * @param int $httpCode
     * @param array $headers
     */
    public function __construct($result, int $httpCode, array $headers = [])
    {
        $this->result = $result;
        $this->httpCode = $httpCode;
        $this->headers = $headers;
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
     * @return array
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }

    /**
     * @param string $header
     *
     * @return string|null
     */
    public function getHeader(string $header) : ?string
    {
        return $this->headers[strtolower($header)] ?? null;
    }
}
