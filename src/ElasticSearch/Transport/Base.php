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
abstract class Base
{

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

    /** @var bool */
    protected $fixUnicode = true;

    /**
     * Default constructor, just set host and port
     *
     * @param string $host
     * @param int    $port
     */
    public function __construct(string $host, int $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Method for indexing a new document
     *
     * @param array       $document
     * @param string|null $id
     * @param array       $options
     *
     * @return array
     */
    abstract public function index(array $document, ?string $id = null, array $options = []): array;

    /**
     * Method for updating a document
     *
     * @param array  $partialDocument
     * @param string $id
     * @param array  $options
     *
     * @return array
     */
    abstract public function update(array $partialDocument, string $id, array $options = []): array;

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
     */
    abstract public function request($path, string $method = 'GET', $payload = false, array $options = []): array;

    /**
     * Delete a document by its id
     *
     * @param mixed $id
     */
    abstract public function delete($id = false): array;

    /**
     * Perform a search based on query
     *
     * @param array|string $query
     */
    abstract public function search($query);

    /**
     * Set timeout
     *
     * @param int $timeout
     */
    abstract public function setTimeout(int $timeout);

    /**
     * Get timeout
     * @return int
     */
    abstract public function getTimeout(): int;

    /**
     * @param bool $fixUnicode
     */
    public function setFixUnicode(bool $fixUnicode): void
    {
        $this->fixUnicode = $fixUnicode;
    }

    /**
     * Search
     *
     * @param mixed $query   String or array to use as criteria for delete
     * @param array $options Parameters to pass to delete action
     *
     * @return bool
     * @throws \Elasticsearch\Exception
     */
    public function deleteByQuery($query, array $options = []): bool
    {
        throw new \Elasticsearch\Exception(__FUNCTION__ . ' not implemented for ' . __CLASS__);
    }

    /**
     * Set what index to act against
     *
     * @param string|null $index
     */
    public function setIndex(?string $index): void
    {
        $this->index = $index;
    }

    /**
     * Set what document types to act against
     *
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * Build a callable url
     *
     * @param array|string $path
     * @param array        $options Query parameter options to pass
     *
     * @return string
     */
    protected function buildUrl($path, array $options = []): string
    {
        $isAbsolute = (is_array($path) ? $path[0][0] : $path[0]) === '/';
        $url = $isAbsolute || null === $this->index ? '' : "/{$this->index}";

        if ($path && is_array($path) && count($path) > 0) {
            $path = implode('/', array_filter($path));
            $url .= '/' . ltrim($path, '/');
        }
        $url = rtrim($url, '/');

        if (count($options) > 0) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($options, '', '&');
        }

        return $url;
    }
}
