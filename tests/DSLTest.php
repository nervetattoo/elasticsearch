<?php // vim:set ts=4 sw=4 et:
require_once 'helper.php';
class ElasticSearchDSLTest extends PHPUnit_Framework_TestCase {
    
    public function testNamedTerm() {
        $arr = array(
            'query' => array(
                'term' => array('title' => 'cool')
            )
        );
        $dsl = new ElasticSearchDSL($arr);
        $strDsl = (string)$dsl;
        $this->assertEquals("title:cool", $strDsl);
    }

    public function testTerm() {
        $arr = array(
            'query' => array(
                'term' => 'cool'
            )
        );
        $dsl = new ElasticSearchDSL($arr);
        $strDsl = (string)$dsl;
        $this->assertEquals("cool", $strDsl);
    }
}
