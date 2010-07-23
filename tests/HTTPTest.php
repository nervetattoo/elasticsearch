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
     * Test indexing a new document and having an auto id
     * This means dupes will occur
     */
    public function testStringSearch() {
        $this->addDocuments();
        sleep(1); // To make sure there will be documents. Sucks
        $hits = $this->search->search("title:cool");
        $this->assertEquals(3, $hits['hits']['total']);
    }
    
    /**
     * Try searching using the dsl
     */
    public function testSearch() {
        $this->addDocuments();
        sleep(1); // To make sure the documents will be ready

        $hits = $this->search->search(array(
            'query' => array(
                'term' => array('title' => 'cool')
           )
        ));
        $this->assertEquals(3, $hits['hits']['total']);

        $hits = $this->search->search(array(
            'query' => array(
                'term' => array('title' => 'cool')
            ),
        ));
        $this->assertEquals(3, $hits['hits']['total']);
    }

    /**
     * Test multi index search
     */
    public function testSearchMultipleIndexes() {
        $indexes = array("test-index", "test2");
        $this->addDocuments($indexes);
        sleep(1); // To make sure the documents will be ready

        // Use both indexes when searching
        $this->search->setIndex($indexes);
        $hits = $this->search->search(array(
            'query' => array(
                'term' => array('title' => 'cool')
            )
        ));
        $this->assertEquals(count($indexes) * 3, $hits['hits']['total']);

        foreach ($indexes as $ind) {
            $this->search->setIndex($ind);
            $this->search->delete();
        }
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
