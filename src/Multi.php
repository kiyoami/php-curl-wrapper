<?php

namespace Kiyoami\Curl;

use Kiyoami\Curl\MultiStatus;

class Multi implements \ArrayAccess, \Iterator
{
    public const ERROR_INIT = 11001;
    public const ERROR_ERRNO = 11002;
    public const ERROR_UNSUPPORTED = 11003;

    /** @var resource cURL multi resource handle */
    private $curl_multi;

    /**
     * Create a new cURL multi handle
     * @throws \Kiyoami\Curl\CurlException
     */
    public function __construct()
    {
        $this->curl_multi = curl_multi_init();
        if ($this->curl_multi === false) {
            throw new CurlException('cURL multi initialize failed ' . $url, self::ERROR_INIT);
        }
    }

    /**
     * clean up instance
     */
    public function __destruct()
    {
        $this->close();
    }

    /** @var \Kiyoami\Curl\Core[] */
    private array $curl_list;

    /**
     * Add a normal cURL handle to a cURL multi handle
     * @param Core $curl
     * @return self
     */
    public function add(Core $curl): self
    {
        $errno = curl_multi_add_handle($this->curl_multi, $curl->getHandle());
        if ($errno !== CURLM_OK) {
            throw new CurlException(curl_strerror($errno), $errno);
        }
        $this->curl_list[] = $curl;

        return $this;
    }

    /**
     * cURL multi resource closes
     * @return void
     */
    public function close(): void
    {
        curl_multi_close($this->curl_multi);
    }

    /**
     * Return the last multi curl error number
     * @return integer
     * @throws \Kiyoami\Curl\CurlException
     */
    public function error(): int
    {
        $errno = curl_multi_errno($this->curl_multi);
        if ($errno === false) {
            throw new CurlException('curl_multi_errno function failed');
        }
        return $errno;
    }

    /**
     * Run the sub-connections of the current cURL handle
     * @return \Kiyoami\Curl\MultiStatus
     */
    public function exec(): MultiStatus
    {
        $still_running = null;
        $status = curl_multi_exec($this->curl_multi, $still_running);

        return new MultiStatus($status, $still_running);
    }

    /**
     * Wait for activity on any curl_multi connection
     * @param float $timeout
     * @return integer
     */
    public function select(float $timeout = 1.0): int
    {
        return curl_multi_select($this->curl_multi, $timeout);
    }

    /**
     * Undocumented function
     *
     * @param int $offset
     * @return boolean
     */
    public function offsetExists($offset): bool
    {
        return isset($this->curl_list[$offset]);
    }

    /**
     * Undocumented function
     *
     * @param integer $offset
     * @return \Kiyoami\Curl\Core
     */
    public function offsetGet($offset): Core
    {
        return $this->curl_list[$offset];
    }

    /**
     * Undocumented function
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     * @throws \Kiyoami\Curl\CurlException
     */
    public function offsetSet($offset, $value): void
    {
        throw new CurlException('Direct modify is unsupported', self::ERROR_UNSUPPORTED);
    }

    /**
     * Undocumented function
     *
     * @param mixed $offset
     * @return void
     * @throws \Kiyoami\Curl\CurlException
     */
    public function offsetUnset($offset): void
    {
        throw new CurlException('Unset is unsupported', self::ERROR_UNSUPPORTED);
    }

    private int $cursol;

    /**
     * Undocumented function
     *
     * @return \Kiyoami\Curl\Core
     */
    public function current(): Core
    {
        return $this->curl_list[$this->cursol];
    }

    /**
     * Undocumented function
     *
     * @return int
     */
    public function key(): int
    {
        return $this->cursol;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function next(): void
    {
        $this->cursol++;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->cursol = 0;
    }

    /**
     * Undocumented function
     *
     * @return boolean
     */
    public function valid(): bool
    {
        return isset($this->curl_list[$this->cursol]);
    }
}
