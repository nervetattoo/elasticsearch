<?php // vim:set ts=4 sw=4 et:
require_once 'helper.php';
class ElasticSearchHTTPTest extends ElasticSearchParent {

    public function setUp() {
        $transport = new ElasticSearchTransportHTTP("localhost", 9200);
        $this->search = new ElasticSearchClient($transport, "test-index", "test-type");
        $this->search->delete();
    }
    public function tearDown() {
        $this->search->delete();
        $this->search = null;
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


    /**
     * Test delete by query
     */
    public function testDeleteByQuery() {
        $doc = array('title' => 'not cool yo');
        $this->search->setIndex("test-index");
        $this->search->index($doc, 1);

        sleep(1); // To make sure the documents will be ready

        $del = $this->search->delete(array(
            'term' => array('title' => 'cool')
        ));

        $this->assertTrue($del);

        sleep(1); // To make sure the documents will be ready

        // Use both indexes when searching
        $hits = $this->search->search(array(
            'query' => array(
                'term' => array('title' => 'cool')
            )
        ));
        $this->assertEquals(0, $hits['hits']['total']);
    }
}
