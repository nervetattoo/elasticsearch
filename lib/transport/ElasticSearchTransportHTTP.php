<?php

class ElasticSearchTransportHTTP extends ElasticSearchTransport {
    private $host = "", $port = 9200;
    public function __construct($host, $port) {
        $this->host = $host;
        $this->port = $port;
    }
    
    /**
     * Index a new document or update it if existing
     *
     * @return array
     * @param array $document
     * @param mixed $id Optional
     */
    public function index($document, $id=false) {
        $url = $this->buildUrl(array($this->type, $id));
        $method = ($id == false) ? "POST" : "PUT";
        $response = $this->call($url, $method, $document );

        return $response;
    }
    
    /**
     * Basic http call
     *
     * @return array
     * @param mixed $id Optional
     */
    public function request($path, $method="GET") {
        $url = $this->buildUrl($path);
        return $this->call($url, $method);
    }
    
    /**
     * Flush this index/type combination
     *
     * @return array
     */
    public function delete($id=false) {
        if ($id)
            return $this->request(array($this->type, $id), "DELETE");
        else
            return $this->request(false, "DELETE");
    }
    
    /**
     * Perform a http call against an url with an optional payload
     *
     * @return array
     * @param string $url
     * @param string $method (GET/POST/PUT/DELETE)
     * @param array $payload The document/instructions to pass along
     */
    private function call($url, $method="GET", $payload=false) {
        $conn = curl_init();
        curl_setopt($conn, CURLOPT_URL, "http://" . $this->host . $url);
        curl_setopt($conn, CURLOPT_PORT, $this->port);
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1) ;
        curl_setopt($conn, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        if (is_array($payload) && count($payload) > 0)
            curl_setopt($conn, CURLOPT_POSTFIELDS, json_encode($payload)) ;

        $data = curl_exec($conn);
        if ($data !== false)
            $data = json_decode($data, true);
        else
            throw new Exception("Transport call to API failed");

        if (array_key_exists('error', $data))
            $this->handleError($url, $method, $payload, $data);

        return $data;
    }

    private function handleError($url, $method, $payload, $response) {
        $err = "Request: \n";
        $err .= "curl -X$method $url";
        if ($payload) $err .=  " -d '" . json_encode($payload) . "'";
        $err .= "\n";
        $err .= "Triggered some error: \n";
        $err .= $response['error'] . "\n";
        //echo $err;
    }

    /**
     * Build a callable url
     *
     * @return string
     * @param array $path
     */
    private function buildUrl($path=false) {
        $url = "/" . $this->index;
        if ($path && count($path) > 0)
            $url .= "/" . implode("/", array_filter($path));
        if (substr($url, -1) == "/")
            $url = substr($url, 0, -1);
        return $url;
    }
}
