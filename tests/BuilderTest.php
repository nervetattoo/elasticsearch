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
class ElasticSearchBuilderTest extends PHPUnit_Framework_TestCase {

    public function testTermQuery() {
        $dsl = new ElasticSearchDSLBuilder;
        $query = $dsl->query();
        $query->term("cool", "title");

        $arr = array(
            'query' => array(
                'term' => array('title' => 'cool')
            )
        );
        $built = $dsl->build();
        $this->assertEquals($arr, $built);
        $query->wildcard("cool*", "title");
        $built = $dsl->build();
        $this->assertEquals($arr, $built);
    }

    public function testRangeQuery() {
        $dsl = new ElasticSearchDSLBuilder;
        $query = $dsl->query();
        $query->range(array(
            'age' => array(
                'from' => 18,
                'to' => 100,
                'includeLower' => true,
                'includeUpper' => false,
                'boost' => 2.0
            )
        ));

        // This is how it should turn out
        $arr = array(
            'query' => array(
                'range' => array(
                    'age' => array(
                        'from' => 18,
                        'to' => 100,
                        'includeLower' => true,
                        'includeUpper' => false,
                        'boost' => 2.0
                    )
                )
            )
        );

        $built = $dsl->build();
        $this->assertEquals($arr, $built);
    }

    public function testRangeQueryAlternativeSyntax() {
        $dsl = new ElasticSearchDSLBuilder;
        $query = $dsl->query();
        $range = $query->range();
        $range->fieldname('age')
            ->from(18)
            ->to(100)
            ->includeUpper(false)
            ->includeLower(false)
            ->boost(2.0);

        // This is how it should turn out
        $arr = array(
            'query' => array(
                'range' => array(
                    'age' => array(
                        'from' => 18,
                        'to' => 100,
                        'includeLower' => false,
                        'includeUpper' => false,
                        'boost' => 2.0
                    )
                )
            )
        );

        $built = $dsl->build();
        $this->assertEquals($arr, $built);
    }
}
