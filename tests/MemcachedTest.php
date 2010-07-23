<?php // vim:set ts=4 sw=4 et:
require_once 'helper.php';
class ElasticSearchMemcachedTest extends ElasticSearchParent {
    
    protected $search = null;
    public function setUp() {
        $transport = new ElasticSearchTransportMemcached;
        $this->search = new ElasticSearchClient($transport, "test-index", "test-type");
        $this->search->delete();
    }
    public function tearDown() {
        $this->search->delete();
        $this->search = null;
    }

    public function testFoo() {
        $this->assertTrue(true);
    }
}
