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

    
    /**
     * Test indexing a new document and having an auto id
     * This means dupes will occur
     */
    public function testStringSearch() {
        $docs = array(
            array('title' => 'One cool document'),
            array('title' => 'The coolest'),
            array('title' => 'Not cool')
        );
        foreach ($docs as $doc)
            $this->search->index($doc);
        sleep(2); // To make sure there will be documents. Sucks
        $hits = $this->search->search("title:cool");
        $this->assertEquals(2, $hits['total']);
    }
    
    /**
     * Try searching using the dsl
     */
    public function testSearch() {
        $docs = array(
            array('title' => 'not cool yo'),
            array('title' => 'One cool document'),
            array('title' => 'The coolest'),
        );
        foreach ($docs as $doc)
            $this->search->index($doc);
        sleep(2); // To make sure the documents will be ready

        $hits = $this->search->search(array(
            'query' => array(
                'term' => array('title' => 'cool')
            )
        ));
        $this->assertEquals(2, $hits['hits']['total']);

        $hits = $this->search->search(array(
            'facets' => array(
                'stopword' => array(
                    'query' => array(
                        'term' => array('title' => 'not')
                    ),
                ),
                'one' => array(
                    'query' => array(
                        'term' => array('title' => 'one')
                    ),
                ),
            ),
            'query' => array(
                'term' => array('title' => 'cool')
            ),
        ));
        $this->assertEquals(2, $hits['hits']['total']);
        $this->assertEquals(1, $hits['facets']['one']);
        $this->assertEquals(0, $hits['facets']['stopword']);
    }

    /**
     * Test multi index search
     */
    public function testSearchMultipleIndexes() {
        $docs = array(
            array('title' => 'not cool yo'),
            array('title' => 'One cool document'),
            array('title' => 'The coolest'),
        );
        $indexes = array("test-index", "test2");
        foreach ($indexes as $ind) {
            $this->search->setIndex($ind);
            foreach ($docs as $doc)
                $this->search->index($doc);
        }

        sleep(2); // To make sure the documents will be ready

        // Use both indexes when searching
        $this->search->setIndex($indexes);
        $hits = $this->search->search(array(
            'query' => array(
                'term' => array('title' => 'cool')
            )
        ));
        $this->assertEquals(count($indexes) * 2, $hits['hits']['total']);

        foreach ($indexes as $ind) {
            $this->search->setIndex($ind);
            $this->search->delete();
        }
    }
}
