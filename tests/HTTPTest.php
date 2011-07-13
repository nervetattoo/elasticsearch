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

        $del = $this->search->deleteByQuery(array(
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
    
    /**
     * Test a midly complex search
     */
    public function testSlightlyComplexSearch() {
        $this->addDocuments();
        $doc = array(
            'title' => 'One cool document',
            'body' => 'Lorem ipsum dolor sit amet',
            'tag' => array('cool', "stuff", "2k")
        );
        $resp = $this->search->index($doc, 1);
        sleep(1); // Indexing is only near real time

        $hits = $this->search->search(array(
            'query' => array(
                'bool' => array(
                    'must' => array(
                        'term' => array('title' => 'cool')
                    ),
                    'should' => array(
                        'field' => array(
                            'tag' => 'stuff'
                        )
                    )
                )
            )
        ));
        $this->assertEquals(3, $hits['hits']['total']);
    }

    
    /**
     * @expectedException ElasticSearchTransportHTTPException
     */
    public function testSearchThrowExceptionWhenServerDown() {
        $transport = new ElasticSearchTransportHTTP("localhost", 9300);
        $search = new ElasticSearchClient($transport, "test-index", "test-type");
        $search->search("title:cool");
    }

    /**
     * Test highlighting
     */
    public function testHighlightedSearch() {
        $query = array(
            'query' => array(
                'term' => array(
                    'title' => 'cool'
                )
            ), 
            'highlight' => array(
                'fields' => array(
                    'title' => new stdClass()
                )
            )
        );
        $doc = array(
            'title' => 'One cool document',
            'body' => 'Lorem ipsum dolor sit amet',
            'tag' => array('cool', "stuff", "2k")
        );
        $refresh = true;
        $resp = $this->search->index($doc, 1, compact('refresh'));
        $results = $this->search->search($query);
        $hit = $results['hits']['hits'][0];
        $this->assertTrue(array_key_exists('highlight', $hit));
        $this->assertRegexp('/<em>/', $hit['highlight']['title'][0]);
    }
}
