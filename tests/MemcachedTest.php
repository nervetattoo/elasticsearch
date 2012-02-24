<?php // vim:set ts=4 sw=4 et:
/**
 * This file is part of the ElasticSearch PHP client
 *
 * (c) Raymond Julin <raymond.julin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
require_once 'helper.php';
class ElasticSearchMemcachedTest extends ElasticSearchParent {
    
    protected $search = null;
    public function setUp() {
        $transport = new ElasticSearchTransportMemcached;
        $this->search = new ElasticSearchClient($transport, "test-index", "test-type");
        $this->search->delete();
    }
    public function tearDown() {
        if (is_object($this->search))
            $this->search->delete();
        $this->search = null;
    }

    protected function addDocuments($indexes=array("test-index"), $num=3, $rand=false) {
        parent::addDocuments($indexes, $num, $rand);
        sleep(1);
    }
}
