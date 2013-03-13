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
 * Helper stuff for working with the ElasticSearch DSL
 * How to build a mildly complex query:
 * $dsl = new ElasticSearchDSL;
 * $bool = $dsl->bool(); // Return a new bool structure
 *
 * @author Raymond Julin <raymond.julin@gmail.com>
 * @package ElasticSearchClient
 * @since 0.1
 * Created: 2010-07-23
 */
class Builder {

    protected $dsl = array();

    private $explain = null;
    private $from = null;
    private $size = null;
    private $fields = null;
    private $query = null;
    private $facets = null;
    private $sort = null;

    /**
     * Construct DSL object
     *
     * @return \ElasticSearch\DSL\Builder
     * @param array $options
     */
    public function __construct(array $options=array()) {
        foreach ($options as $key => $value)
            $this->$key = $value;
    }

    /**
     * Add array clause, can only be one
     *
     * @return \ElasticSearch\DSL\Query
     * @param array $options
     */
    public function query(array $options=array()) {
        if (!($this->query instanceof Query))
            $this->query = new Query($options);
        return $this->query;
    }

    /**
     * Build the DSL as array
     *
     * @throws \ElasticSearch\Exception
     * @return array
     */
    public function build() {
        $built = array();
        if ($this->from != null)
            $built['from'] = $this->from;
        if ($this->size != null)
            $built['size'] = $this->size;
        if (!$this->query)
            throw new \ElasticSearch\Exception("Query must be specified");
        else
            $built['query'] = $this->query->build();
        return $built;
    }
}
