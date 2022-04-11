<?php // vim:set ts=4 sw=4 et:

namespace ElasticSearch\Transport;

/**
 * This file is part of the ElasticSearch PHP client
 *
 * (c) Raymond Julin <raymond.julin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * @property mixed $payload
 * @property mixed $port
 * @property mixed $protocol
 * @property mixed $host
 * @property mixed $method
 * @property mixed $url
 */

class HTTPException extends \Exception {
    /**
     * Exception data
     * @var array
     */
    protected $data = array(
        'payload' => null,
        'protocol' => null,
        'port' => null,
        'host' => null,
        'url' => null,
        'method' => null,
    );

    /**
     * Setter
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function __set($key, $value): void
    {
        if (array_key_exists($key, $this->data)) {
            $this->data[$key] = $value;
        }
    }

    /**
     * Getter
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return false;
    }

    /**
     * Rebuild CLI command using curl to further investigate the failure
     * @return string
     */
    public function getCLICommand(): string
    {
        $postData = json_encode($this->payload);

        return "curl -X{$this->method} '{$this->protocol}://{$this->host}:{$this->port}{$this->url}' -d '{$postData}'";
    }
}
