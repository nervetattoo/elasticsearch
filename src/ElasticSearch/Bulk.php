<?php // vim:set ts=4 sw=4 et:

namespace ElasticSearch;

class Bulk
{

    private $client;
    private $operations = [];

    /**
     * Construct a bulk operation
     *
     * @param \ElasticSearch\Client
     */

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * commit this operation
     */
    public function commit(): array
    {
        return $this->client->request('/_bulk', 'POST', $this->createPayload());
    }

    /**
     * reset this operation
     */
    public function reset()
    {
        $this->operations = [];
    }

    /**
     * Index a new document or update it if existing
     *
     * @param array       $document
     * @param string|null $id      Optional
     * @param string      $index   Index
     * @param string      $type    Type
     * @param array       $options Allow sending query parameters to control indexing further
     *                             _refresh_ *bool* If set to true, immediately refresh the shard after indexing
     *
     * @return \Elasticsearch\Bulk
     */
    public function index(array $document, ?string $id, string $index, string $type, array $options = []): self
    {
        $params = [
            '_id' => $id,
            '_index' => $index,
            '_type' => $type
        ];

        foreach ($options as $key => $value) {
            $params['_' . $key] = $value;
        }

        $operation = [
            [ 'index' => $params ],
            $document,
        ];
        $this->operations[] = $operation;

        return $this;
    }

    /**
     * Update a part of a document
     *
     * @param array  $partialDocument
     * @param mixed  $id
     * @param string $index    Index
     * @param string $type     Type
     * @param array  $options  Allow sending query parameters to control indexing further
     *                         _refresh_ *bool* If set to true, immediately refresh the shard after indexing
     *
     * @return \Elasticsearch\Bulk
     */
    public function update(array $partialDocument, string $id, string $index, string $type, array $options = []): self
    {
        $params = [
            '_id' => $id,
            '_index' => $index,
            '_type' => $type,
        ];

        foreach ($options as $key => $value) {
            $params['_' . $key] = $value;
        }

        $operation = [
            [ 'update' => $params ],
            [ 'doc' => $partialDocument ],
        ];
        $this->operations[] = $operation;

        return $this;
    }

    /**
     * delete a document
     *
     * @param string $id
     * @param string $index   Index
     * @param string $type    Type
     * @param array  $options Parameters to pass to delete action
     *
     * @return \Elasticsearch\Bulk
     */
    public function delete(string $id, string $index, string $type, array $options = []): Bulk
    {
        $params = [
            '_id' => $id,
            '_index' => $index,
            '_type' => $type
        ];

        foreach ($options as $key => $value) {
            $params['_' . $key] = $value;
        }

        $operation = [
            [ 'delete' => $params ],
        ];
        $this->operations[] = $operation;

        return $this;
    }

    /**
     * get all pending operations
     * @return array
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * count all pending operations
     * @return int
     */
    public function count(): int
    {
        return count($this->operations);
    }

    /**
     * create a request payload with all pending operations
     * @return string
     */
    public function createPayload(): string
    {
        $payloads = [];
        foreach ($this->operations as $operation) {
            foreach ($operation as $partial) {
                $payloads[] = json_encode($partial);
            }
        }

        return implode("\n", $payloads) . "\n";
    }
}
