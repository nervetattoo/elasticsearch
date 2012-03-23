<?php // vim:set ts=4 sw=4 et:

namespace ElasticSearch\DSL;

/**
 * This file is part of the ElasticSearch PHP client
 *
 * (c) Raymond Julin <raymond.julin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Handle the query sub dsl 
 *
 * @author Raymond Julin <raymond.julin@gmail.com>
 * @package ElasticSearchClient
 * @since 0.1
 * Created: 2010-07-24
 */
class Query {
    protected $term = null;
    protected $range = null;
    protected $prefix = null;
    protected $wildcard = null;
    protected $matchAll = null;
    protected $queryString = null;
    protected $bool = null;
    protected $disMax = null;
    protected $constantScore = null;
    protected $filteredQuery = null;

    public function __construct(array $options=array()) {
    }
    
    /**
     * Add a term to this query
     *
     * @return ElasticSearchDSL
     * @param string $term
     * @param string $field
     */
    public function term($term, $field=false) {
        $this->term = ($field)
            ? array($field => $term)
            : $term;
        return $this;
    }
    
    /**
     * Add a wildcard to this query
     *
     * @return ElasticSearchDSL
     * @param string $term
     * @param string $field
     */
    public function wildcard($val, $field=false) {
        $this->wildcard = ($field)
            ? array($field => $val)
            : $val;
        return $this;
    }
    
    /**
     * Add a range query
     *
     * @return ElasticSearchDSLBuilderRangeQuery
     * @param array $options
     */
    public function range(array $options=array()) {
        $this->range = new RangeQuery($options);
        return $this->range;
    }

    /**
     * Build the DSL as array
     *
     * @return array
     */
    public function build() {
        $built = array();
        if ($this->term)
            $built['term'] = $this->term;
        elseif ($this->range)
            $built['range'] = $this->range->build();
        elseif ($this->wildcard)
            $built['wildcard'] = $this->wildcard;
        return $built;
    }
}
