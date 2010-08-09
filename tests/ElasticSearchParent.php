<?php // vim:set ts=4 sw=4 et:
require_once 'helper.php';
/**
 * These tests cover the union of every transports api
 */
abstract class ElasticSearchParent extends PHPUnit_Framework_TestCase {
    
    protected $search = null;

    protected function generateDocument($words, $len=4) {
        $sentence = "";
        while ($len > 0) {
            shuffle($words);
            $sentence .= $words[0] . " ";
            $len--;
        }
        return array('title' => $sentence, 'rank' => rand(1, 10));
    }
    protected function addDocuments($indexes=array("test-index"), $num=3, $rand=false) {
        $words = array("cool", "dog", "lorem", "ipsum", "dolor", "sit", "amet");
        // Generate documents
        foreach ($indexes as $ind) {
            $this->search->setIndex($ind);
            $tmpNum = $num;

            // Index documents
            while ($tmpNum > 0) {
                $tmpNum--;
                if ($rand)
                    $doc = $this->generateDocument($words, 5);
                else
                    $doc = array('title' => 'One cool document', 'rank' => rand(1,10));
                $this->search->index($doc, $tmpNum + 1);
            }
        }
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
     * Test regular string search
     */
    public function testStringSearch() {
        $this->addDocuments();
        sleep(2); // Indexing is only near real time
        $hits = $this->search->search("title:cool");
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
        $hits = $this->search->search('title:cool');
        $this->assertEquals(count($indexes) * 3, $hits['hits']['total']);

        foreach ($indexes as $ind) {
            $this->search->setIndex($ind);
            $this->search->delete();
        }
    }
    
    /**
     * Try searching using the dsl
     */
    public function testSearch() {
        $this->addDocuments();
        sleep(2); // To make sure the documents will be ready

        $hits = $this->search->search(array(
            'query' => array(
                'term' => array('title' => 'cool')
           )
        ));
        $this->assertEquals(3, $hits['hits']['total']);
    }
    
    /**
     * Test sort
     */
    public function testSort() {
        $this->addDocuments();
        sleep(2); // To make sure the documents will be ready

        $arr = array(
            'sort' => array(
                array('rank' => array('reverse'=>true)),
                array('rank' => 'asc'),
                'rank'
            ),
            'query' => array(
                'term' => array('title' => 'cool')
            )
        );
        $hits = $this->search->search($arr);
        $this->assertEquals(3, $hits['hits']['total']);
    }
}
