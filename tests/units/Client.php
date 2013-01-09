<?php
namespace ElasticSearch\tests\units;

require_once __DIR__ . '/../Base.php';

use ElasticSearch\tests\Helper;

class Client extends \ElasticSearch\tests\Base
{
    const TYPE = 'test-type';

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

    public function testAbsoluteRequest() {
        $client = \ElasticSearch\Client::connection();
        $resp = $client->request('/');
        $this->assert->array($resp)->hasKey('ok')
            ->string($resp['tagline'])->isEqualTo('You Know, for Search');
    }

    /**
     * Test indexing a new document
     */
    public function testIndexingDocument() {
        $tag = $this->getTag();
        $doc = array(
            'title' => 'One cool ' . $tag
        );
        $client = \ElasticSearch\Client::connection();
        $client->setType(self::TYPE);
        $resp = $client->index($doc, $tag, array('refresh' => true));

        $this->assert->array($resp)->hasKey('ok')
            ->boolean($resp['ok'])->isTrue(1);

        $fetchedDoc = $client->get($tag);
        $this->assert->array($fetchedDoc)->isEqualTo($doc);
    }

    /**
     * Test regular string search
     */
    public function testStringSearch() {
        $client = \ElasticSearch\Client::connection();
        $tag = $this->getTag();
        $options = array('type' => self::TYPE);
        Helper::addDocuments($client, 3, $tag, $options);
        $resp = $client->search("title:$tag", $options);

        $client->refresh();

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
        $client = \ElasticSearch\Client::connection();
        $resp = $client->index($doc, false, array('refresh' => true, 'type' => self::TYPE));
        $this->assert->array($resp)->hasKey('ok')
            ->boolean($resp['ok'])->isTrue(1);
    }

    /**
     * Test delete by query
     */
    public function testDeleteByQuery() {
        $options = array('refresh' => true, 'type' => self::TYPE);
        $client = \ElasticSearch\Client::connection();
        $client->setType(self::TYPE);
        $word = $this->getTag();
        $resp = $client->index(array('title' => $word), 1, $options);

        $client->refresh();

        $del = $client->deleteByQuery(array(
            'term' => array('title' => $word)
        ));

        $client->refresh();

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
        $client = \ElasticSearch\Client::connection();

        $uniqueWord = $this->getTag();
        $docs = 3;
        $doc = array(
            'title' => "One cool document $uniqueWord",
            'tag' => array('cool', "stuff", "2k")
        );
        while ($docs-- > 0) {
            $resp = $client->index($doc, false, array('refresh' => true, 'type' => self::TYPE));
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
        $client = \ElasticSearch\Client::connection(array(
            'type' => self::TYPE
        ));
        $tag = $this->getTag();

        $primaryIndex = 'first-index';
        $secondaryIndex = 'second-index';
        $doc = array('title' => $tag);
        $options = array('refresh' => true);

        $client->setIndex($secondaryIndex)->index($doc, false, $options);
        $client->setIndex($primaryIndex)->index($doc, false, $options);

        $client->refresh();

        // Use both indexes when searching
        $resp = $client
            ->setIndex(array($primaryIndex, $secondaryIndex))
            ->search("title:$tag");

        $this->assert->array($resp)->hasKey('hits')
            ->array($resp['hits'])->hasKey('total')
            ->integer($resp['hits']['total'])->isEqualTo(2);

        $client->delete();
    }

    /**
     * @expectedException ElasticSearch\Transport\HTTPException
     */
    public function testSearchThrowExceptionWhenServerDown() {
        $client = \ElasticSearch\Client::connection(array(
            'servers' => array(
                '127.0.0.1:9300'
            )
        ));

        $this->assert->exception(function()use($client) {
                $client->search("title:cool");
            })->isInstanceOf('ElasticSearch\\Transport\\HTTPException');
    }

    /**
     * Test highlighting
     */
    public function testHighlightedSearch() {
        $client = \ElasticSearch\Client::connection(array(
            'index' => 'highlight-search'
        ));
        $ind = $client->index(array( 
            'title' => 'One cool document',
            'body' => 'Lorem ipsum dolor sit amet',
            'tag' => array('cool', "stuff", "2k")
        ), 1, array(
            'refresh' => true,
            'type' => self::TYPE
        ));

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
