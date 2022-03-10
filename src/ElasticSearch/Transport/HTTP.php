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

if (!defined('CURLE_OPERATION_TIMEDOUT')) {
    define('CURLE_OPERATION_TIMEDOUT', 28);
}


class HTTP
    extends Base
{
    /**
     * @var int
     */
    protected $pid;

    /** @var forkManager */
    protected $forkManager;

    /**
     * How long before timing out CURL call
     * @var int
     */
    private $timeout = 5;

    /**
     * curl handler which is needed for reusing existing http connection to the server
     *
     * @var resource
     */
    protected $ch;

    public function __construct(string $host = 'localhost', int $port = 9200, int $timeout = null)
    {
        parent::__construct($host, $port);
        if (null !== $timeout) {
            $this->setTimeout($timeout);
        }
        $this->ch = curl_init();
        $this->pid = getmypid();
    }

    /**
     * @param $forkManager
     *
     * @return void
     */
    public function setForkManager($forkManager)
    {
        $this->forkManager = $forkManager;
    }

    /**
     * Index a new document or update it if existing
     *
     * @param array       $document
     * @param string|null $id Optional
     * @param array       $options
     *
     * @return array
     * @throws HTTPException
     */
    public function index(array $document, string $id = null, array $options = []): array
    {
        $url = $this->buildUrl([ $this->type, $id ], $options);
        $method = ($id == false) ? 'POST' : 'PUT';

        return $this->call($url, $method, $document);
    }

    /**
     * Update a part of a document
     *
     * @param array  $partialDocument
     * @param string $id
     * @param array  $options
     *
     * @return array
     * @throws HTTPException
     */
    public function update(array $partialDocument, string $id, array $options = []): array
    {
        $url = $this->buildUrl([ $this->type, $id, '_update' ], $options);

        return $this->call($url, "POST", [ 'doc' => $partialDocument ]);
    }

    /**
     * Search
     *
     * @param array|string $query
     * @param array        $options
     *
     * @return array
     * @throws HTTPException
     */
    public function search($query, array $options = []): array
    {
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
            $url = $this->buildUrl([ $this->type, $arg ], $options);

            $result = $this->call($url, 'GET', $query);
        } elseif (is_string($query)) {
            /**
             * String based search means http query string search
             */
            $url = $this->buildUrl([
                $this->type, "_search?q=" . $query,
            ]);
            $result = $this->call($url, 'POST', $options);
        } else {
            /**
             * no http query string search
             */
            $url = $this->buildUrl([
                $this->type, '_search?',
            ]);
            $result = $this->call($url, 'POST', $options);
        }

        return $result;
    }

    /**
     * Search
     *
     * @param mixed $query
     * @param array $options Parameters to pass to delete action
     *
     * @return bool
     * @throws HTTPException
     */
    public function deleteByQuery($query, array $options = []): bool
    {
        $options = array_merge($options, [
            'refresh' => true,
        ]);

        if (is_array($query)) {
            /**
             * Array implies using the JSON query DSL
             */
            $url = $this->buildUrl([ $this->type, '_query' ]);
            $result = $this->call($url, 'DELETE', $query);
        } elseif (is_string($query)) {
            /**
             * String based search means http query string search
             */
            $url = $this->buildUrl([ $this->type, '_query' ], [ 'q' => $query ]);
            $result = $this->call($url, 'DELETE');
        }

        if ($options['refresh']) {
            $this->request('_refresh', 'POST');
        }

        return !isset($result['error']);
    }

    /**
     * Perform a request against the given path/method/payload combination
     * Example:
     * $es->request('/_status');
     *
     * @param string|array $path
     * @param string       $method
     * @param mixed        $payload
     * @param array        $options
     *
     * @return array
     * @throws HTTPException
     */
    public function request($path, string $method = 'GET', $payload = false, array $options = []): array
    {
        return $this->call($this->buildUrl($path, $options), $method, $payload);
    }

    /**
     * Flush this index/type combination
     *
     * @param mixed $id      Id of document to delete
     * @param array $options Parameters to pass to delete action
     *
     * @return array
     * @throws HTTPException
     */
    public function delete($id = false, array $options = []): array
    {
        if ($id) {
            return $this->call($this->buildUrl([ $this->type, $id ], $options), 'DELETE');
        }

        return $this->request(false, 'DELETE');
    }

    /**
     * Perform a http call against an url with an optional payload
     *
     * @param string       $url
     * @param string       $method  (GET/POST/PUT/DELETE)
     * @param array|string $payload The document/instructions to pass along
     *
     * @return array
     * @throws HTTPException
     */
    protected function call(string $url, string $method = 'GET', $payload = null): array
    {
        $conn = $this->getConnection();

        $protocol = 'http';
        $requestURL = $protocol . "://" . $this->host . $url;
        curl_setopt($conn, CURLOPT_URL, $requestURL);
        curl_setopt($conn, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($conn, CURLOPT_PORT, $this->port);
        curl_setopt($conn, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($conn, CURLOPT_FORBID_REUSE, 0);
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($conn, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json'
        ]);

        if (is_array($payload) && count($payload) > 0) {
            curl_setopt($conn, CURLOPT_POSTFIELDS, json_encode($payload));
        } else {
            curl_setopt($conn, CURLOPT_POSTFIELDS, $payload);
        }

        $response = curl_exec($conn);

        if (!empty($response)) {
            $data = json_decode($response, true);
            if (!$data && $this->fixUnicode && json_last_error() === JSON_ERROR_UTF16) {
                $data = json_decode(preg_replace('/\\\\uD[89A-F][0-9A-F]{2}/i', '', $response), true);
            }
            if (!$data) {
                $data = [ 'error' => $response, 'code' => curl_getinfo($conn, CURLINFO_HTTP_CODE) ];
            }
        } else {
            /**
             * cUrl error code reference can be found here:
             * http://curl.haxx.se/libcurl/c/libcurl-errors.html
             */
            $errno = curl_errno($conn);
            switch ($errno) {
                case CURLE_UNSUPPORTED_PROTOCOL:
                    $error = "Unsupported protocol [{$protocol}]";
                    break;
                case CURLE_FAILED_INIT:
                    $error = "Internal cUrl error?";
                    break;
                case CURLE_URL_MALFORMAT:
                    $error = "Malformed URL [{$requestURL}] -d " . json_encode($payload);
                    break;
                case CURLE_COULDNT_RESOLVE_PROXY:
                    $error = "Couldn't resolve proxy";
                    break;
                case CURLE_COULDNT_RESOLVE_HOST:
                    $error = "Couldn't resolve host";
                    break;
                case CURLE_COULDNT_CONNECT:
                    $error = "Couldn't connect to host [{$this->host}], ElasticSearch down?";
                    break;
                case CURLE_OPERATION_TIMEDOUT:
                    $error = "Operation timed out on [{$requestURL}]";
                    break;
                default:
                    $error = "Unknown error";
                    if ($errno == 0) {
                        $error .= ". Non-cUrl error";
                    } else {
                        $errStr = curl_error($conn);
                        $error .= " ({$errStr})";
                    }
                    break;
            }

            $exception = new HTTPException($error);
            $exception->payload = $payload;
            $exception->port = $this->port;
            $exception->protocol = $protocol;
            $exception->host = $this->host;
            $exception->method = $method;
            $exception->url = $url;

            throw $exception;
        }

        return $data;
    }

    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Get connection
     *
     * @return resource
     */
    private function getConnection()
    {
        if ($this->forkManager) {
            if (!$this->forkManager->isContextAlive()) {
                $this->ch = curl_init();
            }

            return $this->ch;
        }

        if ($this->pid !== getmypid()) {
            $this->ch = curl_init();
            $this->pid = getmypid();
        }

        return $this->ch;
    }
}
