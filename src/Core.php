<?php

namespace Kiyoami\Curl;

use CurlHandle;
use Throwable;

/**
 * Simple cURL function wapper
 * This makes being useable PHP cURL function as an object.
 */
class Core
{
    public const ERROR_INIT = 10001;
    public const ERROR_TEMPORARY = 10002;

    /**
     * cURL version
     * @return array
     */
    public static function version(): array
    {
        return curl_version();
    }

    /** @var CurlHandle cURL resource handle */
    private CurlHandle|false|null $curl;

    /** @var array cURL option list */
    private $options = [];

    /** @var boolean|string cURL execution results */
    private $results;

    /**
     * Prepare instance
     * @param string|null $url
     * @throws \Kiyoami\Curl\CurlException
     */
    public function __construct(?string $url = null)
    {
        $this->curl = curl_init($url);
        if ($this->curl === false) {
            throw new CurlException('cURL initialize failed ' . $url, self::ERROR_INIT);
        }
        $this->options[CURLOPT_URL] = $url;
    }

    /**
     * cURL resource closes
     * @return void
     */
    public function close()
    {
        if ($this->curl) {
            curl_close($this->curl);
            $this->curl = null;
        }
    }

    /**
     * The procedure before destroy instance
     */
    public function __destruct()
    {
        $this->close();
        $this->closeTemporary();
    }

    /** @var resource Temporary files resource */
    private $temporary_file;

    /**
     * Initialize temporary file
     * @return void
     */
    private function initTemporary()
    {
        $this->temporary_file = \tmpfile();
        $this->setopt(CURLOPT_FILE, $this->temporary_file);
    }

    /**
     * Close temporary file
     * @return void
     */
    private function closeTemporary()
    {
        if (is_resource($this->temporary_file) && get_resource_type($this->temporary_file) === 'stream') {
            fclose($this->temporary_file);
            $this->temporary_file = null;
        }
    }

    /**
     * The procedure for clone instance
     * @return void
     */
    public function __clone()
    {
        $this->curl = curl_copy_handle($this->curl);
        if (is_resource($this->temporary_file) && get_resource_type($this->temporary_file) === 'stream') {
            $this->temporary_file = \tmpfile();
        }
    }

    /**
     * Error description
     * @return array|null
     */
    public function error(): ?array
    {
        $errno = curl_errno($this->curl);
        return $errno ? [
            'errno' => $errno,
            'error' => curl_error($this->curl),
            'strerror' => curl_strerror($errno),
        ] : null;
    }

    /**
     * url encode
     * @param string $url
     * @return string
     */
    public function escape(string $url): string
    {
        return curl_escape($this->curl, $url);
    }

    /**
     * url decode
     * @param string $url
     * @return string
     */
    public function unescape(string $url): string
    {
        return curl_unescape($this->curl, $url);
    }

    /**
     * cURL execute
     * @return self
     * @throws \Kiyoami\Curl\CurlException
     */
    public function exec(): self
    {
        $this->closeTemporary();
        if (empty($this->options[CURLOPT_RETURNTRANSFER]) && empty($this->options[CURLOPT_FILE])) {
            // CURLOPT_RETURNTRANSFER != true and CURLOPT_FILE does not set
            $this->initTemporary();
        }
        $this->results = curl_exec($this->curl);
        if ($this->results === false) {
            throw new CurlException(curl_error($this->curl), curl_errno($this->curl));
        }
        return $this;
    }

    /**
     * Get cURL information
     * @param integer|null $opt
     * @return mixed
     */
    public function getInfo(?int $opt)
    {
        return is_null($opt) ? curl_getinfo($this->curl) : curl_getinfo($this->curl, $opt);
    }

    /**
     * Reset options for cURL
     * @return self
     */
    public function reset(): self
    {
        curl_reset($this->curl);
        $this->options = [];
        return $this;
    }

    /**
     * Set a option for cURL
     * @param integer $option
     * @param mixed $value
     * @return self
     * @throws \Kiyoami\Curl\CurlException
     */
    public function setopt(int $option, $value): self
    {
        if (curl_setopt($this->curl, $option, $value) === false) {
            throw new CurlException(curl_error($this->curl), curl_errno($this->curl));
        }
        $this->options[$option] = $value;
        return $this;
    }

    /**
     * Set options for cURL
     * @param array $options
     * @param boolean|null $use_temp
     * @return self
     * @throws \Kiyoami\Curl\CurlException
     */
    public function setoptArray(array $options, ?bool $use_temp = false): self
    {
        if (curl_setopt_array($this->curl, $options) === false) {
            throw new CurlException(curl_error($this->curl), curl_errno($this->curl));
        }
        foreach ($options as $option => $value) {
            $this->options[$option] = $value;
        }

        if ($use_temp) {
            $this->closeTemporary();
            if (empty($this->options[CURLOPT_RETURNTRANSFER]) && empty($this->options[CURLOPT_FILE])) {
                // CURLOPT_RETURNTRANSFER != true and CURLOPT_FILE does not set
                $this->initTemporary();
            }
        }

        return $this;
    }

    /**
     * cURL execute with options
     * @param array|null $options
     * @return self
     * @throws \Kiyoami\Curl\CurlException
     */
    public function request(?array $options): self
    {
        if ($options) {
            $this->reset()->setoptArray($options);
        }
        $this->exec();
        return $this;
    }

    /**
     * Results cURL execution
     * @return boolean|string
     */
    public function results()
    {
        return $this->results;
    }

    /**
     * Results file of cURL execution
     * @return resource
     * @throws \Kiyoami\Curl\CurlException
     */
    public function temporaryFile()
    {
        try {
            \fseek($this->temporary_file, 0);
            return $this->temporary_file;
        } catch (Throwable $e) {
            throw new CurlException('Temporary file access failed', self::ERROR_TEMPORARY, $e);
        }
    }

    /**
     * Return cURL handle
     * @return resource
     */
    public function getHandle()
    {
        return $this->curl;
    }
}
