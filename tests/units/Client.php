<?php
namespace ElasticSearch\tests\units;

require_once __DIR__ . '/../Base.php';

use ElasticSearch\tests\Helper;

class Client extends \ElasticSearch\tests\Base
{
	protected static $CONFIG = array
	(
		'index'	=> 'default-index',
		'type'	=> 'default-type'
	);
	
    public function tearDown() {
        \ElasticSearch\Client::connection()->delete();
    }

    public function testDsnIsCorrectlyParsed() {
        $search = \ElasticSearch\Client::connection('http://test.com:9100/index/type');
        $config = array(
            'protocol' => 'http',
            'servers' => 'test.com:9100',
            'index' => 'index',
            'type' => 'type'
        );
        $this->assert->array($search->config())
            ->isEqualTo($config);
    }

    /**
     * Test indexing a new document
     */
    public function testIndexingDocument() {
        $doc = array(
            'title' => 'One cool ' . $this->getTag()
        );
        $resp = \ElasticSearch\Client::connection(static::$CONFIG)
        	->index($doc, 1, array('refresh' => true));

        $this->assert->array($resp)->hasKey('ok')
            ->boolean($resp['ok'])->isTrue(1);
    }

    /**
     * Test regular string search
     */
    public function testStringSearch() {
        $client = \ElasticSearch\Client::connection(static::$CONFIG);
        $tag = $this->getTag();
        Helper::addDocuments($client, 3, $tag);
        $resp = $client->search("title:$tag");
        $this->assert->array($resp)->hasKey('hits')
            ->array($resp['hits'])->hasKey('total')
            ->integer($resp['hits']['total'])->isEqualTo(3);
    }
    
    /**
     * Test indexing a new document and having an auto id
     * This means dupes will occur
     */
    public function testIndexingDocumentWithoutId() {
        $doc = array(
            'title' => 'One cool ' . $this->getTag()
        );
        $client = \ElasticSearch\Client::connection(static::$CONFIG);
        $resp = $client->index($doc, false, array('refresh' => true));
        $this->assert->array($resp)->hasKey('ok')
            ->boolean($resp['ok'])->isTrue(1);
    }

    /**
     * Test delete by query
     */
    public function testDeleteByQuery() {
        $options = array('refresh' => true);
        $client = \ElasticSearch\Client::connection(static::$CONFIG);
        $word = $this->getTag();
        $resp = $client->index(array('title' => $word), 1, $options);

        $client->refresh();

        $del = $client->deleteByQuery(array(
            'term' => array('title' => $word)
        ));

        $hits = $client->search(array(
            'query' => array(
                'term' => array('title' => $word)
            )
        ));
        $this->assert->array($hits)->hasKey('hits')
            ->array($hits['hits'])->hasKey('total')
            ->integer($hits['hits']['total'])->isEqualTo(0);
    }
    
    /**
     * Test a midly complex search
     */
    public function testSlightlyComplexSearch() {
        $client = \ElasticSearch\Client::connection(static::$CONFIG);

        $uniqueWord = $this->getTag();
        $docs = 3;
        $doc = array(
            'title' => "One cool document $uniqueWord",
            'tag' => array('cool', "stuff", "2k")
        );
        while ($docs-- > 0) {
            $resp = $client->index($doc, false, array('refresh' => true));
        }

        $hits = $client->search(array(
            'query' => array(
                'bool' => array(
                    'must' => array(
                        'term' => array('title' => $uniqueWord)
                    ),
                    'should' => array(
                        'field' => array(
                            'tag' => 'stuff'
                        )
                    )
                )
            )
        ));

        $this->assert->array($hits)->hasKey('hits')
            ->array($hits['hits'])->hasKey('total')
            ->integer($hits['hits']['total'])->isEqualTo(3);
    }

    /**
     * Test multi index search
     */
    public function testSearchMultipleIndexes()
    {
        $client = \ElasticSearch\Client::connection(static::$CONFIG);
        $tag = $this->getTag();

        $primaryIndex = 'test-index';
        $secondaryIndex = 'test-index2';
        $doc = array('title' => $tag);
        $options = array('refresh' => true);
        $client->setIndex($secondaryIndex, true)->index($doc, false, $options);
        $client->setIndex($primaryIndex, true)->index($doc, false, $options);

        $indexes = array($primaryIndex, $secondaryIndex);

        // Use both indexes when searching
        $resp = $client->setIndex($indexes, true)->search("title:$tag");

        $this->assert->array($resp)->hasKey('hits')
            ->array($resp['hits'])->hasKey('total')
            ->integer($resp['hits']['total'])->isEqualTo(2);

        $client->delete();
    }

    /**
     * @expectedException ElasticSearch\Transport\HTTPTransportException
     */
    public function testSearchThrowExceptionWhenServerDown() {
        $client = \ElasticSearch\Client::connection(array(
            'servers' => array(
                '127.0.0.1:9300'
            )
        ));

        $this->assert->exception(function()use($client) {
                $client->search("title:cool");
            })->isInstanceOf('ElasticSearch\\Transport\\HTTPTransportException');
    }

    /**
     * Test highlighting
     */
    public function testHighlightedSearch() {
        $client = \ElasticSearch\Client::connection(static::$CONFIG);
        $ind = $client->index(array( 
            'title' => 'One cool document',
            'body' => 'Lorem ipsum dolor sit amet',
            'tag' => array('cool', "stuff", "2k")
        ), 1, array('refresh' => true));
        $client->refresh();

        $results = $client->search(array(
            'query' => array(
                'term' => array(
                    'title' => 'cool'
                )
            ), 
            'highlight' => array(
                'fields' => array(
                    'title' => new \stdClass()
                )
            )
        ));

        $this->assert->array($results)->hasKey('hits')
            ->array($results['hits'])->hasKey('hits')
            ->array($results['hits']['hits'])->isNotEmpty()
            ->array($results['hits']['hits'][0])
            ->hasKey('highlight')
            ->array($results['hits']['hits'][0]['highlight'])
            ->hasKey('title');
    }

    public function testConfigIsReadFromEnv() {
        $esURL = 'http://127.0.0.1:9200/index/type';
        putenv("ELASTICSEARCH_URL={$esURL}");

        $client = \ElasticSearch\Client::connection();
        $config = $client->config();
        $this->assert->array($config)
            ->hasKeys(array('index', 'type'));
        $this->assert->string($config['index'])->isEqualTo('index');
        $this->assert->string($config['type'])->isEqualTo('type');
        putenv("ELASTICSEARCH_URL");
    }
}
