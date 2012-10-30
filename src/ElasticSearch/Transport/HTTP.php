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


class HTTP extends AbstractTransport {
    
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
        $conn = curl_init();
        curl_setopt_array($conn, array(
            CURLOPT_TIMEOUT			=> self::TIMEOUT,
            CURLOPT_PORT			=> $this->port,
            CURLOPT_RETURNTRANSFER	=> true,
            CURLOPT_FORBID_REUSE	=> true,
            CURLOPT_FRESH_CONNECT	=> true,
        ) );
        $this->ch = $conn;
    }
    
    /**
     * Index a new document or update it if existing
     *
     * @return array
     * @param array $document
     * @param mixed $id Optional
     */
    public function index($document, $id=false, array $options = array()) {
        $url = $this->buildUrl($id, $options);
        $method = ($id === false) ? "POST" : "PUT";
        $response = $this->call($url, $method, $document);

        return $response;
    }
    
    /**
     * requestWithQuery
     *
     * @return array
     * @param mixed $query Query string or DSL array
     * @param string $path action to perform (_search, _query, etc.)
     * @param string $method HTTP method
     * @param array $reqParams Parameters to pass in URI
     */
    protected function requestWithQuery($query, $path,
    	$method, $reqParams = array())
    {
        if (is_array( $query ))
        {
        	$post =& $query;
        } else
        {
        	$reqParams[ 'q' ] = $query;
        	$post = false;
        }
        $url = $this->buildUrl($path, $reqParams);
        $result = $this->call($url, $method, $post);
        return $result;
    }
    
    /**
     * Search
     *
     * @return array
     * @param mixed $query Query string or DSL array
     * @param array $reqParams Parameters to pass in URI
     */
    public function search($query, array $reqParams = array()) {
        return $this->requestWithQuery($query, '_search', 'GET', $reqParams);
    }
    
    /**
     * Search
     *
     * @return array
     * @param mixed $id Optional
     * @param array $reqParams Parameters to pass in URI
     * @param array $options Parameters to pass to delete action
     */
    public function deleteByQuery($query, array $reqParams = array())
    {
        $result =
        	$this->requestWithQuery($query, '_query', 'DELETE', $reqParams);
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
    public function request($path, $method="GET", array $reqParams = array(),
    	$payload=false)
    {
        $url = $this->buildUrl($path, $reqParams);
        $result = $this->call($url, $method, $payload);
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
        return $this->request($id, "DELETE", $options);
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
        curl_setopt($conn, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        if (false !== $payload)
            curl_setopt($conn, CURLOPT_POSTFIELDS, json_encode($payload)) ;
        else
        	curl_setopt($conn, CURLOPT_POSTFIELDS, '{}');

        $response = curl_exec($conn);
        if ($response !== false) {
            $data = json_decode($response, true);
            if (false === $data)
                throw new \ElasticSearch\Exception( 'ElasticSearch responded invalid JSON' );
            if ( isset( $data[ 'error' ] ) )
            	throw new \ElasticSearch\Exception( $data[ 'error' ], $data[ 'status' ] );
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
}
