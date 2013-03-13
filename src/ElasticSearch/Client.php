<?php // vim:set ts=4 sw=4 et:

namespace ElasticSearch;

/**
 * This file is part of the ElasticSearch PHP client
 *
 * (c) Raymond Julin <raymond.julin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Client {
    const DEFAULT_PROTOCOL = 'http';
    const DEFAULT_SERVER = '127.0.0.1:9200';
    const DEFAULT_INDEX = 'default-index';
    const DEFAULT_TYPE = 'default-type';

    protected $_config = array();

    protected static $_defaults = array(
        'protocol' => Client::DEFAULT_PROTOCOL,
        'servers' => Client::DEFAULT_SERVER,
        'index' => Client::DEFAULT_INDEX,
        'type' => Client::DEFAULT_TYPE
    );

    protected static $_protocols = array(
        'http' => 'ElasticSearch\\Transport\\HTTP',
        'memcached' => 'ElasticSearch\\Transport\\Memcached',
    );

    private $transport, $index, $type;

    /**
     * Construct search client
     *
     * @return \ElasticSearch\Client
     * @param \ElasticSearch\Transport\Base $transport
     * @param string $index
     * @param string $type
     */
    public function __construct($transport, $index = null, $type = null) {
        $this->transport = $transport;
        $this->setIndex($index)->setType($type);
    }

    /**
     * Get a client instance
     * Defaults to opening a http transport connection to 127.0.0.1:9200
     *
     * @param string|array $config Allow overriding only the configuration bits you desire
     *   - _transport_
     *   - _host_
     *   - _port_
     *   - _index_
     *   - _type_
     * @throws \Exception
     * @return \ElasticSearch\Client
     */
    public static function connection($config = array()) {
        if (!$config && ($url = getenv('ELASTICSEARCH_URL'))) {
            $config = $url;
        }
        if (is_string($config)) {
            $config = self::parseDsn($config);
        }
        $config += self::$_defaults;

        $protocol = $config['protocol'];
        if (!isset(self::$_protocols[$protocol])) {
            throw new \Exception("Tried to use unknown protocol: $protocol");
        }
        $class = self::$_protocols[$protocol];

        $server = is_array($config['servers']) ? $config['servers'][0] : $config['servers'];
        list($host, $port) = explode(':', $server);
        $transport = new $class($host, $port);
        $client = new self($transport, $config['index'], $config['type']);
        $client->config($config);
        return $client;
    }

    /**
     * @param array|null $config
     * @return array|void
     */
    public function config($config = null) {
        if (!$config)
            return $this->_config;
        if (is_array($config))
            $this->_config = $config + $this->_config;
    }

    /**
     * Change what index to go against
     * @return \ElasticSearch\Client
     * @param mixed $index
     */
    public function setIndex($index) {
        if (is_array($index))
            $index = implode(",", array_filter($index));
        $this->index = $index;
        $this->transport->setIndex($index);
        return $this;
    }

    /**
     * Change what types to act against
     * @return \ElasticSearch\Client
     * @param mixed $type
     */
    public function setType($type) {
        if (is_array($type))
            $type = implode(",", array_filter($type));
        $this->type = $type;
        $this->transport->setType($type);
        return $this;
    }

    /**
     * Fetch a document by its id
     *
     * @return array
     * @param mixed $id Optional
     * @param bool $verbose
     */
    public function get($id, $verbose=false) {
        return $this->request($id, "GET");
    }

    /**
     * Puts a mapping on index
     *
     * @param array|object $mapping
     * @param array $config
     * @throws Exception
     * @return array
     */
    public function map($mapping, array $config = array()) {
        if (is_array($mapping)) $mapping = new Mapping($mapping);
        $mapping->config($config);

        try {
            $type = $mapping->config('type');
        }
        catch (\Exception $e) {} // No type is cool
        if (isset($type) && !$this->passesTypeConstraint($type)) {
            throw new Exception("Cant create mapping due to type constraint mismatch");
        }

        return $this->request('_mapping', 'PUT', $mapping->export(), true);
    }

    protected function passesTypeConstraint($constraint) {
        if (is_string($constraint)) $constraint = array($constraint);
        $currentType = explode(',', $this->type);
        $includeTypes = array_intersect($constraint, $currentType);
        return ($constraint && count($includeTypes) === count($constraint));
    }

    /**
     * Perform a raw request
     *
     * Usage example
     *
     *     $response = $client->request('_status', 'GET');
     *
     * @return array
     * @param mixed $path Request path to use.
     *     `type` is prepended to this path inside request
     * @param string $method HTTP verb to use
     * @param mixed $payload Array of data to be json-encoded
     * @param bool $verbose Controls response data, if `false`
     *     only `_source` of response is returned
     */
    public function request($path, $method = 'GET', $payload = false, $verbose=false) {
        $response = $this->transport->request($this->expandPath($path), $method, $payload);
        return ($verbose || !isset($response['_source']))
            ? $response
            : $response['_source'];
    }

    /**
     * Index a new document or update it if existing
     *
     * @return array
     * @param array $document
     * @param mixed $id Optional
     * @param array $options Allow sending query parameters to control indexing further
     *        _refresh_ *bool* If set to true, immediately refresh the shard after indexing
     */
    public function index($document, $id=false, array $options = array()) {
        return $this->transport->index($document, $id, $options);
    }

    /**
     * Perform search, this is the sweet spot
     *
     * @return array
     * @param $query
     * @param array $options
     */
    public function search($query, array $options = array()) {
        $start = microtime(true);
        $result = $this->transport->search($query, $options);
        $result['time'] = microtime(true) - $start;
        return $result;
    }
    
    /**
     * Flush this index/type combination
     *
     * @return array
     * @param mixed $id If id is supplied, delete that id for this index
     *                  if not wipe the entire index
     * @param array $options Parameters to pass to delete action
     */
    public function delete($id=false, array $options = array()) {
        return $this->transport->delete($id, $options);
    }

    /**
     * Flush this index/type combination
     *
     * @return array
     * @param mixed $query Text or array based query to delete everything that matches
     * @param array $options Parameters to pass to delete action
     */
    public function deleteByQuery($query, array $options = array()) {
        return $this->transport->deleteByQuery($query, $options);
    }

    /**
     * Perform refresh of current indexes
     *
     * @return array
     */
    public function refresh() {
        return $this->request('_refresh', "POST");
    }

    /**
     * Expand a given path (array or string)
     * If this is not an absolute path index + type will be prepended
     * If it is an absolute path it will be used as is
     *
     * @param mixed $path
     * @return array
     */
    protected function expandPath($path) {
        $path = (array) $path;
        $isAbsolute = $path[0][0] === '/';

        return $isAbsolute
            ? $path
            : array_merge((array) $this->type, $path);
    }

    /**
     * Parse a DSN string into an associative array
     *
     * @param string $dsn
     * @return array
     */
    protected static function parseDsn($dsn) {
        $parts = parse_url($dsn);
        $protocol = $parts['scheme'];
        $servers = $parts['host'] . ':' . $parts['port'];
        if (isset($parts['path'])) {
            $path = explode('/', $parts['path']);
            list($index, $type) = array_values(array_filter($path));
        }
        return compact('protocol', 'servers', 'index', 'type');
    }
}
