<?php // vim:set ts=4 sw=4 et:

namespace ElasticSearch;

use ElasticSearch\Transport\Base;

/**
 * This file is part of the ElasticSearch PHP client
 *
 * (c) Raymond Julin <raymond.julin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Client
{
    const DEFAULT_PROTOCOL = 'http';
    const DEFAULT_SERVER   = '127.0.0.1:9200';
    const DEFAULT_INDEX    = 'default-index';
    const DEFAULT_TYPE     = 'default-type';

    protected $_config = [];

    protected static $_defaults = [
        'protocol' => Client::DEFAULT_PROTOCOL,
        'servers' => Client::DEFAULT_SERVER,
        'index' => Client::DEFAULT_INDEX,
        'type' => Client::DEFAULT_TYPE,
        'timeout' => null,
    ];

    protected static $_protocols = [
        'http' => 'ElasticSearch\\Transport\\HTTP',
        'memcached' => 'ElasticSearch\\Transport\\Memcached',
    ];

    /** @var Base */
    private $transport;

    /** @var string|null */
    private $index;

    /** @var string|null */
    private $type;

    /** @var Bulk|null */
    private $bulk;

    /**
     * Construct search client
     *
     * @param Base        $transport
     * @param string|null $index
     * @param string|null $type
     */
    public function __construct(Base $transport, ?string $index = null, ?string $type = null)
    {
        $this->transport = $transport;
        $this->setIndex($index)->setType($type);
    }

    /**
     * Get a client instance
     * Defaults to opening a http transport connection to 127.0.0.1:9200
     *
     * @param string|array $config Allow overriding only the configuration bits you desire
     *                             - _transport_
     *                             - _host_
     *                             - _port_
     *                             - _index_
     *                             - _type_
     *
     * @return Client
     * @throws \Exception
     */
    public static function connection(array $config = []): Client
    {
        if (!$config && ($url = getenv('ELASTICSEARCH_URL'))) {
            $config = $url;
        }
        if (is_string($config)) {
            $config = self::parseDsn($config);
        }

        $config = array_merge(self::$_defaults, $config);

        $protocol = $config['protocol'];
        if (!isset(self::$_protocols[$protocol])) {
            throw new \Exception("Tried to use unknown protocol: {$protocol}");
        }
        $class = self::$_protocols[$protocol];

        if (null !== $config['timeout'] && !is_numeric($config['timeout'])) {
            throw new \Exception('HTTP timeout should have a numeric value when specified.');
        }

        $server = is_array($config['servers']) ? $config['servers'][0] : $config['servers'];
        [ $host, $port ] = explode(':', $server);

        $transport = new $class($host, $port, $config['timeout']);

        $client = new self($transport, $config['index'], $config['type']);
        $client->config($config);

        return $client;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout): void
    {
        $this->transport->setTimeout($timeout);
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->transport->getTimeout();
    }

    /**
     * @return Base
     */
    public function getTransport(): Base
    {
        return $this->transport;
    }

    /**
     * Set fix unicode option in transport
     */
    public function setFixUnicode(bool $fixUnicode): void
    {
        $this->transport->setFixUnicode($fixUnicode);
    }

    /**
     * @param array|null $config
     *
     * @return array|void
     */
    public function config(?array $config = null): array
    {
        if (is_array($config)) {
            $this->_config = array_merge($this->_config, $config);
        }

        return $this->_config;
    }

    /**
     * Change what index to go against
     *
     * @param mixed $index
     *
     * @return Client
     */
    public function setIndex($index): self
    {
        if (is_array($index)) {
            $index = implode(',', array_filter($index));
        }

        $this->index = $index;
        $this->transport->setIndex($index);

        return $this;
    }

    /**
     * Change what types to act against
     *
     * @param mixed $type
     *
     * @return Client
     */
    public function setType($type): self
    {
        if (is_array($type)) {
            $type = implode(',', array_filter($type));
        }
        $this->type = $type;
        $this->transport->setType($type);

        return $this;
    }

    /**
     * Fetch a document by its id
     *
     * @param mixed $id Optional
     * @param bool  $verbose
     *
     * @return array
     */
    public function get($id, bool $verbose = false): array
    {
        return $this->request($id, 'GET');
    }

    /**
     * Puts a mapping on index
     *
     * @param array|object $mapping
     * @param array        $config
     *
     * @return array
     * @throws Exception
     * @throws \Exception
     */
    public function map($mapping, array $config = [])
    {
        if (is_array($mapping)) {
            $mapping = new Mapping($mapping);
        }
        $mapping->config($config);

        try {
            $type = $mapping->config('type');
        } catch(\Exception $e) {
        } // No type is cool
        if (isset($type) && !$this->passesTypeConstraint($type)) {
            throw new Exception("Cant create mapping due to type constraint mismatch");
        }

        return $this->request('_mapping', 'PUT', $mapping->export(), true);
    }

    protected function passesTypeConstraint($constraint): bool
    {
        if (is_string($constraint)) {
            $constraint = [ $constraint ];
        }
        $currentType = explode(',', $this->type);
        $includeTypes = array_intersect($constraint, $currentType);

        return ($constraint && count($includeTypes) === count($constraint));
    }

    /**
     * Perform a request
     *
     * Usage example
     *
     *     $response = $client->request('_status', 'GET');
     *
     * @param mixed  $path    Request path to use.
     *                        `type` is prepended to this path inside request
     * @param string $method  HTTP verb to use
     * @param mixed  $payload Array of data to be json-encoded
     * @param bool   $verbose Controls response data, if `false`
     *                        only `_source` of response is returned
     *
     * @return array
     */
    public function request($path, string $method = 'GET', $payload = false, bool $verbose = false): array
    {
        $response = $this->transport->request($this->expandPath($path), $method, $payload);

        return ($verbose || !isset($response['_source']))
            ? $response
            : $response['_source'];
    }

    /**
     * Perform a raw request
     *
     * Usage example
     *
     *     $response = $client->rawRequest('_cluster/health/indexName', 'GET', false, ['level' => 'indices]);
     *
     * @param mixed  $path    Request path to use.
     *                        `type` is prepended to this path inside request
     * @param string $method  HTTP verb to use
     * @param mixed  $payload Array of data to be json-encoded
     * @param array  $options
     *
     * @return array
     */
    public function rawRequest($path, string $method = 'GET', $payload = false, array $options = []): array
    {
        return $this->transport->request($this->expandPath($path), $method, $payload, $options);
    }

    /**
     * Index a new document or update it if existing
     *
     * @param array $document
     * @param mixed $id      Optional
     * @param array $options Allow sending query parameters to control indexing further
     *                       _refresh_ *bool* If set to true, immediately refresh the shard after indexing
     *
     * @return array|Bulk
     */
    public function index(array $document, $id = false, array $options = [])
    {
        if ($this->bulk) {
            return $this->bulk->index($document, $id, $this->index, $this->type, $options);
        }

        return $this->transport->index($document, $id, $options);
    }

    /**
     * Update a part of a document
     *
     * @param array $partialDocument
     * @param mixed $id
     * @param array $options  Allow sending query parameters to control indexing further
     *                        _refresh_ *bool* If set to true, immediately refresh the shard after indexing
     *
     * @return array|Bulk
     *
     */
    public function update(array $partialDocument, $id, array $options = [])
    {
        if ($this->bulk) {
            return $this->bulk->update($partialDocument, $id, $this->index, $this->type, $options);
        }

        return $this->transport->update($partialDocument, $id, $options);
    }

    /**
     * Perform search, this is the sweet spot
     *
     * @param       $query
     * @param array $options
     *
     * @return array
     */
    public function search($query, array $options = []): array
    {
        $start = microtime(true);
        $result = $this->transport->search($query, $options);
        $result['time'] = microtime(true) - $start;

        return $result;
    }

    /**
     * Continue scroll
     *
     * @param string $scrollId
     * @param string $scroll
     *
     * @return array
     */
    public function scroll(string $scrollId, string $scroll): array
    {
        return $this->transport->request('/_search/scroll', 'POST', [
            'scroll_id' => $scrollId,
            'scroll' => $scroll
        ]);
    }

    /**
     * Flush this index/type combination
     *
     * @param mixed $id      If id is supplied, delete that id for this index
     *                       if not wipe the entire index
     * @param array $options Parameters to pass to delete action
     *
     * @return array|Bulk
     */
    public function delete($id = false, array $options = [])
    {
        if ($this->bulk) {
            return $this->bulk->delete($id, $this->index, $this->type, $options);
        }

        return $this->transport->delete($id, $options);
    }

    /**
     * Flush this index/type combination
     *
     * @param mixed $query   Text or array based query to delete everything that matches
     * @param array $options Parameters to pass to delete action
     *
     * @return bool
     * @throws Exception
     */
    public function deleteByQuery($query, array $options = []): bool
    {
        return $this->transport->deleteByQuery($query, $options);
    }

    /**
     * Perform refresh of current indexes
     *
     * @return array
     */
    public function refresh(): array
    {
        return $this->transport->request([ '_refresh' ], 'GET');
    }

    /**
     * Expand a given path (array or string)
     * If this is not an absolute path index + type will be prepended
     * If it is an absolute path it will be used as is
     *
     * @param mixed $path
     *
     * @return array
     */
    protected function expandPath($path): array
    {
        $path = (array)$path;

        return $path[0][0] === '/'
            ? $path
            : array_merge((array) $this->type, $path);
    }

    /**
     * Parse a DSN string into an associative array
     *
     * @param string $dsn
     *
     * @return array
     */
    protected static function parseDsn(string $dsn): array
    {
        $parts = parse_url($dsn);
        $protocol = $parts['scheme'];
        $servers = $parts['host'] . ':' . $parts['port'];
        if (isset($parts['path'])) {
            $path = explode('/', $parts['path']);
            [ $index, $type ] = array_values(array_filter($path));
        }

        return compact('protocol', 'servers', 'index', 'type');
    }

    /**
     * Create a bulk-transaction
     *
     * @return \Elasticsearch\Bulk
     */

    public function createBulk(): Bulk
    {
        return new Bulk($this);
    }

    /**
     * Begin a transparent bulk-transaction
     * if one is already running, return its handle
     * @note Maybe deprecated in next version prior to createBulk method
     *
     * @return \Elasticsearch\Bulk
     */
    public function beginBulk(): Bulk
    {
        if (!$this->bulk) {
            $this->bulk = $this->createBulk();
        }

        return $this->bulk;
    }

    /**
     * commit a bulk-transaction
     * @return array|null
     */
    public function commitBulk(): ?array
    {
        if ($this->bulk && $this->bulk->count()) {
            $result = $this->bulk->commit();
            $this->bulk = null;

            return $result;
        }

        return null;
    }

    /**
     * Stop bulk without commit
     */
    public function stopBulk(): void
    {
        if ($this->bulk) {
            $this->bulk = null;
        }
    }

    /**
     * @see beginBulk
     */
    public function begin(): Bulk
    {
        return $this->beginBulk();
    }

    /**
     * @see commitBulk
     */
    public function commit(): array
    {
        return $this->commitBulk();
    }
}
