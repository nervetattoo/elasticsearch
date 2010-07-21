<?php // vim:set ts=4 sw=4 et:
require_once 'helper.php';
class ElasticSearchTest extends PHPUnit_Framework_TestCase {
    
    protected $search = null;
    public function setUp() {
        if ($this->search == null) {
            $transport = new ElasticSearchTransportHTTP("localhost", 9200);
            $this->search = new ElasticSearch($transport, "test-index", "test-type");
        }
    }

    public function tearDown() {
        $this->search->delete();
        $this->search = null;
    }

    /**
     * Test indexing a new document
     */
    public function testIndexingDocument() {
        $doc = array(
            'title' => 'One cool document',
            'tag' => 'cool'
        );
        $resp = $this->search->index($doc, 1);

        $this->assertTrue($resp['ok'] == 1);
    }
    
    /**
     * Test indexing a new document and having an auto id
     * This means dupes will occur
     */
    public function testIndexingDocumentWithoutId() {
        $doc = array(
            'title' => 'One cool document',
            'tag' => 'cool'
        );
        $resp = $this->search->index($doc);
        $this->assertTrue($resp['ok'] == 1);
    }
}
