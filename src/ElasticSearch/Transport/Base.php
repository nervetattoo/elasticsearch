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

abstract class Base {

    /**
     * Which hosts to connect to for server (for failover)
     * @var string
     */
    protected $connections = [];

    /**
     * Which connection index is active
     * @var integer
     */
    protected $connectionIndex = 0;

    /**
     * ElasticSearch index
     * @var string
     */
    protected $index;

    /**
     * ElasticSearch document type
     * @var string
     */
    protected $type;

    /**
     * Default constructor, just set host and port
     * @param string $connections
     */
    public function __construct($connections) {
        if (isset($connections['host'])) {
            $connections = [$connections];
        }
        $this->connections = $connections;
        $this->selectActiveConnection();
    }

    /**
     * Selects a connection from the list of available connections to be the active connection
     */
    public function selectActiveConnection() {
        $this->activeConnection = rand(0, count($connections)-1);
    }

    /**
     * Returns the host and port of the active connection
     */
    public function getActiveConnection() {
        return $this->connections[$this->activeConnection];
    }

    /**
     * Returns a formatted string for the active connection that can be used in URL generation for cURL requests
     */
    public function getActiveConnectionString() {
        return $this->connections[$this->activeConnection]['host'].":".$this->connections[$this->activeConnection]['port'];
    }

    /**
     * Removes the active connection and selects a new connection
     */
    public function rolloverActiveConnection() {
        unset($this->connections[$this->activeConnection]);
        $this->selectActiveConnection();
    }

    /**
     * Checks to see if there are atleast x connections available
     */
    public function atleastConnectionsAvailable($neededConnections=1) {
        return (count($this->connections) >= $neededConnections);
    }


    /**
     * Method for indexing a new document
     *
     * @param array|object $document
     * @param mixed $id
     * @param array $options
     */
    abstract public function index($document, $id=false, array $options = array());

    /**
     * Perform a request against the given path/method/payload combination
     * Example:
     * $es->request('/_status');
     *
     * @param string|array $path
     * @param string $method
     * @param array|bool $payload
     */
    abstract public function request($path, $method="GET", $payload=false);

    /**
     * Delete a document by its id
     * @param mixed $id
     */
    abstract public function delete($id=false);

    /**
     * Perform a search based on query
     * @param array|string $query
     */
    abstract public function search($query);

    /**
     * Search
     *
     * @return array
     * @param mixed $query String or array to use as criteria for delete
     * @param array $options Parameters to pass to delete action
     * @throws \Elasticsearch\Exception
     */
    public function deleteByQuery($query, array $options = array()) {
        throw new \Elasticsearch\Exception(__FUNCTION__ . ' not implemented for ' . __CLASS__);
    }

    /**
     * Set what index to act against
     * @param string $index
     */
    public function setIndex($index) {
        $this->index = $index;
    }

    /**
     * Set what document types to act against
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * Build a callable url
     *
     * @return string
     * @param array|bool $path
     * @param array $options Query parameter options to pass
     */
    protected function buildUrl($path = false, array $options = array()) {
        $isAbsolute = (is_array($path) ? $path[0][0] : $path[0]) === '/';
        $url = $isAbsolute ? '' : "/" . $this->index;

        if ($path && is_array($path) && count($path) > 0)
            $url .= "/" . implode("/", array_filter($path));
        if (substr($url, -1) == "/")
            $url = substr($url, 0, -1);
        if (count($options) > 0)
          $url .= "?" . http_build_query($options, '', '&');

        return $url;
    }
}
