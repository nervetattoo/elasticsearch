<?php // vim:set ts=4 sw=4 et:
require_once 'lib/ElasticSearchException.php';
require_once 'lib/ElasticSearchDSLStringify.php';

require_once 'lib/builder/ElasticSearchDSLBuilder.php';

require_once 'lib/transport/ElasticSearchTransport.php';
require_once 'lib/transport/ElasticSearchTransportHTTP.php';
require_once 'lib/transport/ElasticSearchTransportMemcached.php';

class ElasticSearchClient {

    private $transport, $index, $type;

    /**
     * Construct search client.
     *
     * If no index and type is given, try to get its values from a URL passed
     * as $transport or from the ELASTICSEARCH_URL environmental variable.
     *
     * @return ElasticSearchClient
     * @param false|string|ElasticSearchTransport $transport
     * @param false|string|array $index
     * @param false|string|array $type
     */
    public function __construct($transport=false, $index=false, $type=false) {

        if (! $transport) {
            if (! $transport)
                $transport = getenv('ELASTICSEARCH_URL');
            if (! $transport)
                throw new Exception('ELASTICSEARCH_URL must be set for autodetection to work.');
            if (! preg_match('/^(\w+:\/\/)?\w(\w|\.\w)*:\d+($|\/$|\/[\w_\.]+)($|\/$|\/[\w_\.]+$)/', $transport))
                throw new Exception("A setup URL needs to be the of the form http://host:port/index/type.");

            // strip protocol, we only support http anyway
            $transport = preg_replace('/^[\w\d]+:\/\//', '', $transport);
            $components = explode('/', $transport);

            list($host, $port) = explode(':', $components[0]);
            $transport = new ElasticSearchTransportHTTP($host, $port);

            if (count($components) > 1) {
                if (! $index)
                    $index = $components[1];
                if (count($components) > 2 && ! $type)
                    $type = $components[2];
            }
            echo "host=$host,port=$port,index=$index,type=$type\n";
        }

        $this->transport = $transport;
        if ($index) {
            $this->index = $index;
            $this->transport->setIndex($index);
        }
        if ($type) {
            $this->type = $type;
            $this->transport->setType($type);
        }
    }

    /**
     * Change what index to go against
     * @return void
     * @param mixed $index
     */
    public function setIndex($index) {
        if (is_array($index))
            $index = implode(",", array_filter($index));
        $this->index = $index;
        $this->transport->setIndex($index);
    }

    /**
     * Change what types to act against
     * @return void
     * @param mixed $type
     */
    public function setType($type) {
        if (is_array($type))
            $type = implode(",", array_filter($type));
        $this->type = $type;
        $this->transport->setType($type);
    }

    /**
     * Fetch a document by its id
     *
     * @return array
     * @param mixed $id Optional
     */
    public function get($id, $verbose=false) {
        if (! $this->type || ! $this->index)
            throw new Exception("ElasticsearchClient: You need to setType and setIndex before.");
        $response = $this->transport->request(array($this->type, $id), "GET");
        return ($verbose)
            ? $response
            : $response['_source'];
    }

    /**
     * Perform a request
     *
     * @return array
     * @param mixed $id Optional
     */
    public function request($path, $method='GET', $payload=false, $verbose=false) {
        if (! $this->type || ! $this->index)
            throw new Exception("ElasticsearchClient: You need to setType and setIndex before.");

        $path = array_merge((array) $this->type, (array) $path);

        $response = $this->transport->request($path, $method, $payload);
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
     * @param array $options Allow sending query parameters to control indexing further
     *        _refresh_ *bool* If set to true, immediately refresh the shard after indexing
     */
    public function index($document, $id=false, array $options = array()) {
        if (! $this->type || ! $this->index)
            throw new Exception("ElasticsearchClient: You need to setType and setIndex before.");
        return $this->transport->index($document, $id, $options);
    }

    /**
     * Perform search, this is the sweet spot
     *
     * @return array
     * @param array $document
     */
    public function search($query) {
        if (! $this->type || ! $this->index)
            throw new Exception("ElasticsearchClient: You need to setType and setIndex before.");
        $start = $this->getMicroTime();
        $result = $this->transport->search($query);
        $result['time'] = $this->getMicroTime() - $start;
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
        if (! $this->type || ! $this->index)
            throw new Exception("ElasticsearchClient: You need to setType and setIndex before.");
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
        if (! $this->type || ! $this->index)
            throw new Exception("ElasticsearchClient: You need to setType and setIndex before.");
        return $this->transport->deleteByQuery($query, $options);
    }

    private function getMicroTime() {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

}
