<?php // vim:set ts=4 sw=4 et:

namespace ElasticSearch\Transport;

use \Memcache;
use \ElasticSearch\DSL\Stringify;

/**
 * This file is part of the ElasticSearch PHP client
 *
 * (c) Raymond Julin <raymond.julin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Memcached
    extends Base
{
    /** @var Memcache */
    protected $conn;

    /** @var int */
    protected $timeout = 1;

    public function __construct($host = "127.0.0.1", $port = 11311, $timeout = 1)
    {
        parent::__construct($host, $port);
        $this->timeout = $timeout;

        $this->conn = new Memcache();
        $this->conn->connect($this->host, $this->port, $this->timeout);
    }

    /**
     * Index a new document or update it if existing
     *
     * @param array       $document
     * @param string|null $id Optional
     * @param array       $options
     *
     * @return array
     * @throws \ElasticSearch\Exception
     */
    public function index(array $document, string $id = null, array $options = []): array
    {
        if (!$id) {
            throw new \ElasticSearch\Exception("Memcached transport requires id when indexing");
        }

        $document = json_encode($document);
        $url = $this->buildUrl([ $this->type, $id ]);
        $response = $this->conn->set($url, $document);

        return [
            'ok' => $response,
        ];
    }

    /**
     * Update a part of a document
     *
     * @param array $partialDocument
     * @param string $id
     * @param array $options
     *
     * @return array
     */
    public function update(array $partialDocument, string $id, array $options = []): array
    {
        $document = json_encode([ 'doc' => $partialDocument ]);
        $url = $this->buildUrl([ $this->type, $id ]);
        $response = $this->conn->set($url, $document);

        return [
            'ok' => $response,
        ];
    }

    /**
     * Search
     *
     * @param array|string $query
     *
     * @return array
     * @throws \ElasticSearch\Exception
     */
    public function search($query): array
    {
        if (is_array($query)) {
            if (!array_key_exists('query', $query)) {
                throw new \ElasticSearch\Exception("Memcached protocol doesnt support the full DSL, only query");
            }

            $dsl = new Stringify($query);
            $q = (string)$dsl;
            $url = $this->buildUrl([
                $this->type, "_search?q=" . $q,
            ]);
        } else {
            /**
             * String based search means http query string search
             */
            $url = $this->buildUrl([
                $this->type, "_search?q={$query}",
            ]);
        }

        return json_decode($this->conn->get($url), true);
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
     * @throws \ElasticSearch\Exception
     */
    public function request($path, string $method = 'GET', $payload = false, array $options = []): array
    {
        $url = $this->buildUrl($path, $options);
        switch ($method) {
            case 'GET':
                $result = $this->conn->get($url);
                break;
            case 'DELETE':
                $result = $this->conn->delete($url);
                break;
            default:
                throw new \ElasticSearch\Exception("Memcached protocol support only GET and DELETE methods");
        }

        return json_decode($result, true);
    }

    /**
     * Flush this index/type combination
     *
     * @param mixed $id
     * @param array $options Parameters to pass to delete action
     *
     * @return array
     * @throws \ElasticSearch\Exception
     */
    public function delete($id = false, array $options = []): array
    {
        if ($id) {
            return $this->request([ $this->type, $id ], 'DELETE');
        } else {
            return $this->request(false, 'DELETE');
        }
    }

    public function setTimeout(int $timeout)
    {
        // TODO: Implement setTimeout() method.
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
