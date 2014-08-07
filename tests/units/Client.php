<?php
namespace ElasticSearch\tests\units;

require_once __DIR__ . '/../Base.php';

use ElasticSearch\tests\Helper;

class Client extends \ElasticSearch\tests\Base
{
    public function tearDown() {
        \ElasticSearch\Client::connection()->setIndex('index')->delete();
        \ElasticSearch\Client::connection()->setIndex('index2')->delete();
        \ElasticSearch\Client::connection()->setIndex('test-index')->delete();
        \ElasticSearch\Client::connection()->delete();
    }

    public function testDsnIsCorrectlyParsed() {
        $search = \ElasticSearch\Client::connection('http://test.com:9100/index/type');
        $config = array(
            'protocol' => 'http',
            'servers' => 'test.com:9100',
            'index' => 'index',
            'type' => 'type',
            'timeout' => null,
        );
        $this->assert->array($search->config())
            ->isEqualTo($config);
    }

    public function testAbsoluteRequest() {
        $client = \ElasticSearch\Client::connection();
        $resp = $client->request('/');
        $this->assert->array($resp)
            ->integer($resp['status'])->isEqualTo(200)
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
        $resp = $client->index($doc, $tag, array('refresh' => true));
        $this->assert->array($resp)->boolean($resp['created'])->isTrue(1);

        $fetchedDoc = $client->get($tag);
        $this->assert->array($fetchedDoc)->isEqualTo($doc);
    }

    /**
     * Test regular string search
     */
    public function testStringSearch() {
        $client = \ElasticSearch\Client::connection();
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
        $client = \ElasticSearch\Client::connection();
        $resp = $client->index($doc, false, array('refresh' => true));
        $this->assert->array($resp)
            ->boolean($resp['created'])->isTrue(1);
    }

    /**
     * Test delete by query
     */
    public function testDeleteByQuery() {
        $options = array('refresh' => true);
        $client = \ElasticSearch\Client::connection();
        $word = $this->getTag();
        $resp = $client->index(array('title' => $word), 1, $options);

        $client->refresh();

        $del = $client->deleteByQuery(array(
            'query' => array(
                             'term' => array('title' => $word)
                             )
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
        $client = \ElasticSearch\Client::connection();

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
                        'term' => array(
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
        $client = \ElasticSearch\Client::connection();
        $tag = $this->getTag();

        $primaryIndex = 'test-index';
        $secondaryIndex = 'test-index2';
        $doc = array('title' => $tag);
        $options = array('refresh' => true);
        $client->setIndex($secondaryIndex)->index($doc, false, $options);
        $client->setIndex($primaryIndex)->index($doc, false, $options);

        $indexes = array($primaryIndex, $secondaryIndex);

        // Use both indexes when searching
        $resp = $client->setIndex($indexes)->search("title:$tag");

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
                '127.0.0.1:9201'
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
        $client = \ElasticSearch\Client::connection();
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

    public function testBulk() {
        $esURL = 'http://127.0.0.1:9200/index/type';
        putenv("ELASTICSEARCH_URL={$esURL}");

        $client = \ElasticSearch\Client::connection();
        $bulk = $client->beginBulk();

        $doc = array(
            'title' => 'First in Line' 
        );

        $client->index($doc, false, array('refresh' => true));

        $doc2 = array(
            'title' => 'Second in Line' 
        );
        $client->setType('type2');
        $client->index($doc2, false);

        $client->setIndex('index2');

        $client->delete(55);

        $operations = $bulk-> getOperations();
        $this->assert->integer($bulk->count())->isEqualTo(3)
                     ->array($operations[1])
                     ->hasSize(2)
                     ->array($operations[0])
                     ->hasSize(2)
                     ->array($operations[0][0])->hasKey('index')
                     ->array($operations[0][0]['index'])->hasKey('_refresh')
                     ->boolean($operations[0][0]['index']['_refresh'])->isEqualTo(true)
                     ->array($operations[1][1])->isEqualTo($doc2)
                     ->array($operations[2][0])->hasKey('delete')
                     ->array($operations[2][0]['delete'])->hasKey('_id')
                     ->integer($operations[2][0]['delete']['_id'])->isEqualTo(55)
;

        $payload = '{"index":{"_id":false,"_index":"index","_type":"type","_refresh":true}}'
        ."\n".'{"title":"First in Line"}'
        ."\n".'{"index":{"_id":false,"_index":"index","_type":"type2"}}'
        ."\n".'{"title":"Second in Line"}'
        ."\n".'{"delete":{"_id":55,"_index":"index2","_type":"type2"}}'."\n"
;

        $this->assert->string($bulk->createPayload())->isEqualTo($payload);

        // Run multiple bulks and make sure all documents are stored
        $client->beginBulk();
        $client->index(array('title' => 'Bulk1'), 1);
        $client->index(array('title' => 'Bulk2'), 2);
        $client->commitBulk();
        $client->beginBulk();
        $client->index(array('title' => 'Bulk3'), 3);
        $client->index(array('title' => 'Bulk4'), 4);
        $client->commitBulk();
        sleep(1);
        $resp = $client->search('title:Bulk*');
        $this->assert->array($resp)->hasKey('hits')
            ->array($resp['hits'])->hasKey('total')
            ->integer($resp['hits']['total'])->isEqualTo(4);

        putenv("ELASTICSEARCH_URL");
    }
}
