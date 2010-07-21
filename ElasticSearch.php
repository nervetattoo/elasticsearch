<?php // vim:set ts=4 sw=4 et:
require_once 'lib/transport/ElasticSearchTransport.php';
require_once 'lib/transport/ElasticSearchTransportHTTP.php';

class ElasticSearch {

    private $transport, $index, $type;
    
    /**
     * Construct search client
     *
     * @return ElasticSearch
     * @param ElasticSearchTransport $transport
     * @param string $index
     * @param string $type
     */
    public function __construct($transport, $index, $type) {
        $this->index = $index;
        $this->type = $type;
        $this->transport = $transport;
        $this->transport->setIndex($index);
        $this->transport->setType($type);
    }
    
    /**
     * Fetch a document by its id
     *
     * @return array
     * @param mixed $id Optional
     */
    public function get($id, $verbose=false) {
        $response = $this->transport->request(array($this->type, $id), "GET");
        return ($verbose)
            ? $response
            : $response['_source'];
    }

    /**
     * Index a new document or update it if existing
     *
     * @return array
     * @param array $document
     * @param mixed $id Optional
     */
    public function index($document, $id=false) {
        return $this->transport->index($document, $id);
    }

    /**
     * Perform search, this is the sweet spot
     *
     * @return array
     * @param array $document
     * @param mixed $id Optional
     */
    public function search($query) {
        return $this->transport->search($query);
    }
    
    /**
     * Flush this index/type combination
     *
     * @return array
     */
    public function delete($id=false) {
        return $this->transport->delete($id);
    }
}
