<?php
if (!defined('CURLE_OPERATION_TIMEDOUT'))
    define('CURLE_OPERATION_TIMEDOUT', 28);

class ElasticSearchTransportHTTPException extends ElasticSearchException {
    protected $data = array(
        'payload' => null,
        'protocol' => null,
        'port' => null,
        'host' => null,
        'url' => null,
        'method' => null,
    );
    public function __set($key, $value) {
        if (array_key_exists($key, $this->data))
            $this->data[$key] = $value;
    }
    public function __get($key) {
        if (array_key_exists($key, $this->data))
            return $this->data[$key];
        else
            return false;
    }

    public function getCLICommand() {
        $postData = json_encode($this->payload);
        $curlCall = "curl -X{$method} 'http://{$this->host}:{$this->port}$this->url' -d '$postData'";
        return $curlCall;
    }
}

class ElasticSearchTransportHTTP extends ElasticSearchTransport {
    
    /**
     * How long before timing out CURL call
     */
    const TIMEOUT = 5;

    /**
     * What host to connect to for server
     * @var string
     */
    protected $host = "";
    
    /**
     * Port to connect on
     * @var int
     */
    protected $port = 9200;
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
        try {
            $response = $this->call($url, $method, $document);
        }
        catch (Exception $e) {
            throw $e;
        }

        return $response;
    }
    
    /**
     * Search
     *
     * @return array
     * @param mixed $id Optional
     */
    public function search($query) {
        if (is_array($query)) {
            /**
             * Array implies using the JSON query DSL
             */
            $url = $this->buildUrl(array(
                $this->type, "_search"
            ));
            try {
                $result = $this->call($url, "GET", $query);
            }
            catch (Exception $e) {
                throw $e;
            }
        }
        elseif (is_string($query)) {
            /**
             * String based search means http query string search
             */
            $url = $this->buildUrl(array(
                $this->type, "_search?q=" . $query
            ));
            $result = $this->call($url, "GET");
            try {
                $result = $this->call($url, "GET");
            }
            catch (Exception $e) {
                throw $e;
            }
        }
        return $result;
    }
    
    /**
     * Search
     *
     * @return array
     * @param mixed $id Optional
     */
    public function deleteByQuery($query) {
        if (is_array($query)) {
            /**
             * Array implies using the JSON query DSL
             */
            $url = $this->buildUrl(array(
                $this->type, "_query"
            ));
            try {
                $result = $this->call($url, "DELETE", $query);
            }
            catch (Exception $e) {
                throw $e;
            }
        }
        elseif (is_string($query)) {
            /**
             * String based search means http query string search
             */
            $url = $this->buildUrl(array(
                $this->type, "_query?q=" . $query
            ));
            try {
                $result = $this->call($url, "DELETE");
            }
            catch (Exception $e) {
                throw $e;
            }
        }
        return $result['ok'];
    }
    
    /**
     * Basic http call
     *
     * @return array
     * @param mixed $id Optional
     */
    public function request($path, $method="GET") {
        $url = $this->buildUrl($path);
        try {
            $result = $this->call($url, $method);
        }
        catch (Exception $e) {
            throw $e;
        }
        return $result;
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
    protected function call($url, $method="GET", $payload=false) {
        $conn = curl_init();
        $protocol = "http";
        $requestURL = $protocol . "://" . $this->host . $url;
        curl_setopt($conn, CURLOPT_URL, $requestURL);
        curl_setopt($conn, CURLOPT_TIMEOUT, self::TIMEOUT);
        curl_setopt($conn, CURLOPT_PORT, $this->port);
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1) ;
        curl_setopt($conn, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        if (is_array($payload) && count($payload) > 0)
            curl_setopt($conn, CURLOPT_POSTFIELDS, json_encode($payload)) ;

        $data = curl_exec($conn);
        if ($data !== false)
            $data = json_decode($data, true);
        else
        {
            /**
             * cUrl error code reference can be found here:
             * http://curl.haxx.se/libcurl/c/libcurl-errors.html
             */
            $errno = curl_errno($conn);
            switch ($errno)
            {
                case CURLE_UNSUPPORTED_PROTOCOL:
                    $error = "Unsupported protocol [$protocol]";
                    break;
                case CURLE_FAILED_INIT:
                    $error = "Internal cUrl error?";
                    break;
                case CURLE_URL_MALFORMAT:
                    $error = "Malformed URL [$requestURL] -d " . json_encode($payload);
                    break;
                case CURLE_COULDNT_RESOLVE_PROXY:
                    $error = "Couldnt resolve proxy";
                    break;
                case CURLE_COULDNT_RESOLVE_HOST:
                    $error = "Couldnt resolve host";
                    break;
                case CURLE_COULDNT_CONNECT:
                    $error = "Couldnt connect to host [{$this->host}], ElasticSearch down?";
                    break;
                case CURLE_OPERATION_TIMEDOUT:
                    $error = "Operation timed out on [$requestURL]";
                    break;
                default:
                    $error = "Unknown error";
                    if ($errno == 0)
                        $error .= ". Non-cUrl error";
                    break;
            }
            $exception = new ElasticSearchTransportHTTPException($error);
            $exception->payload = $payload;
            $exception->port = $this->port;
            $exception->protocol = $protocol;
            $exception->host = $this->host;
            $exception->method = $method;
            throw $exception;
        }

        if (array_key_exists('error', $data))
            $this->handleError($url, $method, $payload, $data);

        return $data;
    }

    protected function handleError($url, $method, $payload, $response) {
        $err = "Request: \n";
        $err .= "curl -X$method http://{$this->host}:{$this->port}$url";
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
    protected function buildUrl($path=false) {
        $url = "/" . $this->index;
        if ($path && count($path) > 0)
            $url .= "/" . implode("/", array_filter($path));
        if (substr($url, -1) == "/")
            $url = substr($url, 0, -1);
        return $url;
    }
}
