<?php // vim:set ts=4 sw=4 et:

namespace ElasticSearch;

use \stdClass;
use ElasticSearch\Client;
use ElasticSearch\Transport\HTTPTransport;

/**
 * This file is part of the ElasticSearch PHP client
 *
 * (c) Raymond Julin <raymond.julin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class HTTPTest extends TestBase {

    public function setUp() {
        $this->search = Client::connection(array(
            'index' => 'test-index',
            'type' => 'test-type'
        ));
        $this->search->delete();
    }
    public function tearDown() {
        if ($this->search) {
            $this->search->delete();
            $this->search = null;
        }
    }

    public function testDsnIsCorrectlyParsed() {
        $search = Client::connection('http://test.com:9100/index/type');
        $config = array(
            'protocol' => 'http',
            'servers' => 'test.com:9100',
            'index' => 'index',
            'type' => 'type'
        );
        $this->assertEquals($config, $search->config());
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
        $refresh = true;
        $doc = array('title' => 'not cool yo');
        $this->search->setIndex("test-index");
        $this->search->index($doc, 1, compact('refresh'));

        $del = $this->search->deleteByQuery(array(
            'term' => array('title' => 'cool')
        ), compact('refresh'));
        sleep(1);

        $this->assertTrue($del);

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
        $resp = $this->search->index($doc, 1, array('refresh' => true));

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
     * @expectedException ElasticSearch\Transport\HTTPTransportException
     */
    public function testSearchThrowExceptionWhenServerDown() {
        //$search = Client::connection('http://127.0.0.1:9300'));
        $search = Client::connection(array(
            'servers' => array(
                '127.0.0.1:9300'
            )
        ));
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
