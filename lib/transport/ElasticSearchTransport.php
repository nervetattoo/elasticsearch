<?php

abstract class ElasticSearchTransport {
    protected $index, $type;

    abstract public function index($document, $id=false, array $options = array());
    abstract public function request($path, $method="GET", $payload=false);
    abstract public function delete($id=false);
    abstract public function search($query);

    public function setIndex($index) {
        $this->index = $index;
    }
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * Build a callable url
     *
     * @return string
     * @param array $path
     * @param array $options Query parameter options to pass
     */
    protected function buildUrl($path=false, array $options = array()) {
        $url = "/" . $this->index;
        if ($path && count($path) > 0)
            $url .= "/" . implode("/", array_filter($path));
        if (substr($url, -1) == "/")
            $url = substr($url, 0, -1);
        if (count($options) > 0)
            $url .= "?" . http_build_query($options);
        return $url;
    }
}
