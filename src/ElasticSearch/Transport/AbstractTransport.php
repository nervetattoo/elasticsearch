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

abstract class AbstractTransport {

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
     * @param string $host
     * @param int $port
     */
    public function __construct($host, $port) {
        $this->host = $host;
        $this->port = $port;
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
     * @param array|false $payload
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
     */
    public function deleteByQuery($query, array $options = array()) {
        throw new Exception(__FUNCTION__ . ' not implemented for ' . __CLASS__);
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
     * @param array $path
     * @param array $options Query parameter options to pass
     */
    protected function buildUrl($path=false, array $options = array()) {
        $url = "/" . $this->index;
        if ($path && is_array($path) && count($path) > 0)
            $url .= "/" . implode("/", array_filter($path));
        if (substr($url, -1) == "/")
            $url = substr($url, 0, -1);
        if (count($options) > 0)
            $url .= "?" . http_build_query($options);
        return $url;
    }
}
