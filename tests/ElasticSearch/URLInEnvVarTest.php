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
class URLInEnvVarTest extends \PHPUnit_Framework_TestCase {

    protected $esURL = 'http://127.0.0.1:9200/index/type';

    public function setUp() {
        putenv("ELASTICSEARCH_URL={$this->esURL}");
    }

    public function tearDown() {
        putenv("ELASTICSEARCH_URL");
        if ($this->search) {
            $this->search->delete();
        }
    }

    public function testConnect() {
        $this->search = Client::connection();
        $doc = array(
            'title' => 'One cool document',
            'tag' => 'cool'
        );
        $resp = $this->search->index($doc);
        $this->assertTrue($resp['ok'] == 1);
    }
}
