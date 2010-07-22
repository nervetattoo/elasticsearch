<?php // vim:set ts=4 sw=4 et:
require_once 'ElasticSearchTest.php';
class ElasticSearchMemcachedTest extends ElasticSearchTest {
    
    protected $search = null;
    public function setUp() {
        if ($this->search == null) {
            $transport = new ElasticSearchTransportMemcached;
            $this->search = new ElasticSearchClient($transport, "test-index", "test-type");
        }
        else
            $this->search->setIndex("test-index");
    }
}
