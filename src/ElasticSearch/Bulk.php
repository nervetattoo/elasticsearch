<?php // vim:set ts=4 sw=4 et:

namespace ElasticSearch;

class Bulk {

	private $client;
	private $operations = array();

    /**
     * Construct a bulk operation
     *
     * @param \ElasticSearch\Client
     */

	public function __construct(Client $client) {
		$this->client = $client;
	}

    /**
     * commit this operation
     */
	public function commit() {
		return $this->client->request('/_bulk', 'POST', $this->createPayload());
	}
	
    /**
     * reset this operation
     */
	public function reset() {
		$this->operations = array();
	}
    
    /**
     * Index a new document or update it if existing
     *
     * @param array $document
     * @param mixed $id Optional
     * @param string $index Index
     * @param string $type Type
     * @param array $options Allow sending query parameters to control indexing further
     *        _refresh_ *bool* If set to true, immediately refresh the shard after indexing
     * @return \Elasticsearch\Bulk
     */
	public function index($document, $id=false, $index, $type, array $options = array()) {
		$params = array( '_id' => $id, 
   					     '_index' => $index, 
					     '_type' => $type);

		foreach ($options as $key => $value) {
			$params['_' . $key] = $value;
		}

		$operation = array(
			array('index' => $params),
			$document 
		);
		$this->operations[] = $operation;
		return $this;
	}

	 /**
     * delete a document
     *
     * @param mixed $id
     * @param string $index Index
     * @param string $type Type
     * @param array $options Parameters to pass to delete action
     * @return \Elasticsearch\Bulk
     */
	public function delete($id=false, $index, $type, array $options = array()) {
		$params = array( '_id' => $id, 
   					     '_index' => $index, 
					     '_type' => $type);

		foreach ($options as $key => $value) {
			$params['_' . $key] = $value;
		}

		$operation = array(
			array('delete' => $params)
		);
		$this->operations[] = $operation;
		return $this;

	}

	 /**
	 * get all pending operations
	 * @return array
     */
	public function getOperations() {
		return $this->operations;		
	}

	 /**
	 * count all pending operations
	 * @return int
     */
	public function count() {
		return count($this->operations);
	}

	 /**
	 * create a request payload with all pending operations
	 * @return string
     */
	public function createPayload() 
	{
		$payloads = array();
		foreach ($this->operations as $operation) {
			foreach ($operation as $partial) {
				$payloads[] = json_encode($partial);
			}
		}
		return join("\n", $payloads);
	}
}
