<?php // vim:set ts=4 sw=4 et:

namespace ElasticSearch;

use \PHPUnit_Framework_TestCase;
use ElasticSearch\DSL\Stringify;

/**
 * This file is part of the ElasticSearch PHP client
 *
 * (c) Raymond Julin <raymond.julin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class DSLTest extends PHPUnit_Framework_TestCase {
    
    public function testNamedTerm() {
        $arr = array(
            'query' => array(
                'term' => array('title' => 'cool')
            )
        );
        $dsl = new Stringify($arr);
        $strDsl = (string)$dsl;
        $this->assertEquals("title:cool", $strDsl);
    }

    public function testTerm() {
        $arr = array(
            'query' => array(
                'term' => 'cool'
            )
        );
        $dsl = new Stringify($arr);
        $strDsl = (string)$dsl;
        $this->assertEquals("cool", $strDsl);
    }

    public function testGroupedTerms() {
        $arr = array(
            'query' => array(
                'term' => 'cool stuff'
            )
        );
        $dsl = new Stringify($arr);
        $strDsl = (string)$dsl;
        $this->assertEquals('"cool stuff"', $strDsl);
    }

    public function testNamedGroupedTerms() {
        $arr = array(
            'query' => array(
                'term' => array('title' => 'cool stuff')
            )
        );
        $dsl = new Stringify($arr);
        $strDsl = (string)$dsl;
        $this->assertEquals('title:"cool stuff"', $strDsl);
    }

    public function testSort() {
        $arr = array(
            'sort' => array(
                array('title' => 'desc')
            ),
            'query' => array(
                'term' => array('title' => 'cool stuff')
            )
        );
        $dsl = new Stringify($arr);
        $this->assertEquals('title:"cool stuff"&sort=title:reverse', (string)$dsl);

        $arr['sort'] = array('title');
        $dsl = new Stringify($arr);
        $this->assertEquals('title:"cool stuff"&sort=title', (string)$dsl);

        $arr['sort'] = array(array('title' => array('reverse' => true)));
        $dsl = new Stringify($arr);
        $this->assertEquals('title:"cool stuff"&sort=title:reverse', (string)$dsl);
    }

    public function testLimitReturnFields() {
        $arr = array(
            'fields' => array('title','body'),
            'query' => array(
                'term' => array('title' => 'cool')
            )
        );
        $dsl = new Stringify($arr);
        $this->assertEquals('title:cool&fields=title,body', (string)$dsl);
    }
}
