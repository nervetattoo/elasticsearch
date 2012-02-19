<?php

class ElasticSearchTransportHTTPException extends ElasticSearchException {
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
