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

if (!defined('CURLE_OPERATION_TIMEDOUT'))
    define('CURLE_OPERATION_TIMEDOUT', 28);


class HTTPTransport extends AbstractTransport {
    
    /**
     * How long before timing out CURL call
     */
    const TIMEOUT = 5;
	
    /**
     * curl handler which is needed for reusing existing http connection to the server
     * @var resource
     */
    protected $ch;
	
	
    public function __construct($host='localhost', $port=9200) {
        parent::__construct($host, $port);
        $this->ch = curl_init();
    }
    
    /**
     * Index a new document or update it if existing
     *
     * @return array
     * @param array $document
     * @param mixed $id Optional
     */
    public function index($document, $id=false, array $options = array()) {
        $url = $this->buildUrl(array($this->type, $id), $options);
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
     * @param array $options Parameters to pass to delete action
     */
    public function deleteByQuery($query, array $options = array()) {
        $options += array(
            'refresh' => true
        );
        if (is_array($query)) {
            /**
             * Array implies using the JSON query DSL
             */
            $url = $this->buildUrl(array($this->type, "_query"));
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
            $url = $this->buildUrl(array($this->type, "_query"), array('q' => $query));
            try {
                $result = $this->call($url, "DELETE");
            }
            catch (Exception $e) {
                throw $e;
            }
        }
        if ($options['refresh']) {
            $this->request('_refresh', "POST");
        }
        return !isset($result['error']) && $result['ok'];
    }
    
    /**
     * Perform a request against the given path/method/payload combination
     * Example:
     * $es->request('/_status');
     *
     * @param string|array $path
     * @param string $method
     * @param array|false $payload
     * @return array
     */
    public function request($path, $method="GET", $payload=false) {
        $url = $this->buildUrl($path);
        try {
            $result = $this->call($url, $method, $payload);
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
     * @param mixed $id Id of document to delete
     * @param array $options Parameters to pass to delete action
     */
    public function delete($id=false, array $options = array()) {
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
        $conn = $this->ch;
        $protocol = "http";
        $requestURL = $protocol . "://" . $this->host . $url;
        curl_setopt($conn, CURLOPT_URL, $requestURL);
        curl_setopt($conn, CURLOPT_TIMEOUT, self::TIMEOUT);
        curl_setopt($conn, CURLOPT_PORT, $this->port);
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1) ;
        curl_setopt($conn, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($conn, CURLOPT_FORBID_REUSE , 0) ;

        if (is_array($payload) && count($payload) > 0)
            curl_setopt($conn, CURLOPT_POSTFIELDS, json_encode($payload)) ;
        else
        	curl_setopt($conn, CURLOPT_POSTFIELDS, null);

        $response = curl_exec($conn);
        if ($response !== false) {
            $data = json_decode($response, true);
            if (!$data) {
                $data = array('error' => $response);
            }
        }
        else {
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
            $exception = new HTTPTransportException($error);
            $exception->payload = $payload;
            $exception->port = $this->port;
            $exception->protocol = $protocol;
            $exception->host = $this->host;
            $exception->method = $method;
            throw $exception;
        }

        return $data;
    }
}
