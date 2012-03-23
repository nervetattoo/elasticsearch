<?php // vim:set ts=4 sw=4 et:

namespace ElasticSearch\Transport;

/**
 * This file is part of the ElasticSearch PHP client
 *
 * (c) Raymond Julin <raymond.julin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class HTTPTransportException extends \Exception {
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
     * @param mixed $key
     * @param mixed $value
     */
    public function __set($key, $value) {
        if (array_key_exists($key, $this->data))
            $this->data[$key] = $value;
    }

    /**
     * Getter
     * @param mixed $key
     * @return mixed
     */
    public function __get($key) {
        if (array_key_exists($key, $this->data))
            return $this->data[$key];
        else
            return false;
    }

    /**
     * Rebuild CLI command using curl to further investigate the failure
     * @return string
     */
    public function getCLICommand() {
        $postData = json_encode($this->payload);
        $curlCall = "curl -X{$method} 'http://{$this->host}:{$this->port}$this->url' -d '$postData'";
        return $curlCall;
    }
}
