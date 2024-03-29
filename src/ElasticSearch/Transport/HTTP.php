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


class HTTP extends Base {
    
    /**
     * How long before timing out CURL call
     */
    private $timeout = 5;
	
    /**
     * curl handler which is needed for reusing existing http connection to the server
     * @var resource
     */
    protected $ch;
	
	
    public function __construct($host='localhost', $port=9200, $timeout=null) {
        parent::__construct($host, $port);
        if(null !== $timeout) {
            $this->setTimeout($timeout);
        }    
        $this->ch = curl_init();
    }

    /**
     * Index a new document or update it if existing
     *
     * @return array
     * @param array $document
     * @param mixed $id Optional
     * @param array $options
     */
    public function index($document, $id=false, array $options = array()) {
        $url = $this->buildUrl(array($this->type, $id), $options);
        $method = ($id == false) ? "POST" : "PUT";
        return $this->call($url, $method, $document);
    }

    /**
     * Update a part of a document
     *
     * @return array
     *
     * @param array $partialDocument
     * @param mixed $id
     * @param array $options
     */
    public function update($partialDocument, $id, array $options = array()) {
        $url = $this->buildUrl(array($this->type, $id, '_update'), $options);

        return $this->call($url, "POST", array('doc' => $partialDocument));
    }

    /**
     * Search
     *
     * @return array
     * @param array|string $query
     * @param array $options
     */
    public function search($query, array $options = array()) {
        $result = false;
        if (is_array($query)) {
            /**
             * Array implies using the JSON query DSL
             */
            $arg = "_search";
            /**
             * $options may contain values like:
             * $options['routing'] = 'user123'
             * or
             * $options['preference'] = 'xyzabc123'
             */
            $url = $this->buildUrl(array($this->type, $arg), $options);

            $result = $this->call($url, "GET", $query);
        }
        elseif (is_string($query)) {
            /**
             * String based search means http query string search
             */
            $url = $this->buildUrl(array(
                $this->type, "_search?q=" . $query
            ));
            $result = $this->call($url, "POST", $options);
        }
        else {
            /**
             * no http query string search
             */
            $url = $this->buildUrl(array(
                $this->type, "_search?"
            ));
            $result = $this->call($url, "POST", $options);
        }
        return $result;
    }

    /**
     * Search
     *
     * @return array
     * @param mixed $query
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
            $result = $this->call($url, "DELETE", $query);
        }
        elseif (is_string($query)) {
            /**
             * String based search means http query string search
             */
            $url = $this->buildUrl(array($this->type, "_query"), array('q' => $query));
            $result = $this->call($url, "DELETE");
        }
        if ($options['refresh']) {
            $this->request('_refresh', "POST");
        }
        return !isset($result['error']);
    }

    /**
     * Perform a request against the given path/method/payload combination
     * Example:
     * $es->request('/_status');
     *
     * @param string|array $path
     * @param string $method
     * @param array|bool $payload
     * @return array
     */
    public function request($path, $method="GET", $payload=false) {
        return $this->call($this->buildUrl($path), $method, $payload);
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
            return $this->call($this->buildUrl(array($this->type, $id), $options), "DELETE");
        else
            return $this->request(false, "DELETE");
    }

    /**
     * Perform a http call against an url with an optional payload
     *
     * @return array
     * @param string $url
     * @param string $method (GET/POST/PUT/DELETE)
     * @param array|bool $payload The document/instructions to pass along
     * @throws HTTPException
     */
    protected function call($url, $method="GET", $payload=null) {
        $conn = $this->ch;
        $protocol = "http";
        $requestURL = $protocol . "://" . $this->host . $url;
        curl_setopt($conn, CURLOPT_URL, $requestURL);
        curl_setopt($conn, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($conn, CURLOPT_PORT, $this->port);
        curl_setopt($conn, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($conn, CURLOPT_FORBID_REUSE , 0) ;
	
	$headers = array();
        $headers[] = 'Accept: application/json';
        $headers[] = 'Content-Type: application/json';

        curl_setopt($conn, CURLOPT_HTTPHEADER, $headers);

        if (is_array($payload) && count($payload) > 0)
            curl_setopt($conn, CURLOPT_POSTFIELDS, json_encode($payload)) ;
        else
	       	curl_setopt($conn, CURLOPT_POSTFIELDS, $payload);

        // cURL opt returntransfer leaks memory, therefore OB instead.
        ob_start();
        curl_exec($conn);
        $response = ob_get_clean();
        if ($response !== false) {
            $data = json_decode($response, true);
            if (!$data) {
                $data = array('error' => $response, "code" => curl_getinfo($conn, CURLINFO_HTTP_CODE));
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
                    if ($errno == 0) {
                        $error .= ". Non-cUrl error";
                    } else {
                        $errstr = curl_error($conn);
                        $error .= " ($errstr)";
                    }
                    break;
            }
            $exception = new HTTPException($error);
            $exception->payload = $payload;
            $exception->port = $this->port;
            $exception->protocol = $protocol;
            $exception->host = $this->host;
            $exception->method = $method;
            throw $exception;
        }

        return $data;
    }

    public function setTimeout($timeout) 
    {
        $this->timeout = $timeout;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }
}
