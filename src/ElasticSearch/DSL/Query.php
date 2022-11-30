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
 * @author  Raymond Julin <raymond.julin@gmail.com>
 * @package ElasticSearchClient
 * @since   0.1
 * Created: 2010-07-24
 */
class Query
{
    protected $term = null;
    /**
     * @var RangeQuery
     */
    protected $range;
    protected $prefix = null;
    protected $wildcard = null;
    protected $matchAll = null;
    protected $queryString = null;
    protected $bool = null;
    protected $disMax = null;
    protected $constantScore = null;
    protected $filteredQuery = null;

    public function __construct(array $options = [])
    {
    }

    /**
     * Add a term to this query
     *
     * @param string      $term
     * @param bool|string $field
     *
     * @return \ElasticSearch\DSL\Query
     */
    public function term(string $term, $field = false): self
    {
        $this->term = ($field)
            ? [ $field => $term ]
            : $term;

        return $this;
    }

    /**
     * Add a wildcard to this query
     *
     * @param             $val
     * @param bool|string $field
     *
     * @return \ElasticSearch\DSL\Query
     */
    public function wildcard($val, $field = false): self
    {
        $this->wildcard = ($field)
            ? [ $field => $val ]
            : $val;

        return $this;
    }

    /**
     * Add a range query
     *
     * @param array $options
     *
     * @return \ElasticSearch\DSL\RangeQuery
     */
    public function range(array $options = []): RangeQuery
    {
        $this->range = new RangeQuery($options);

        return $this->range;
    }

    /**
     * Build the DSL as array
     *
     * @return array
     *
     * @throws \ElasticSearch\Exception
     */
    public function build(): array
    {
        $built = [];
        if ($this->term) {
            $built['term'] = $this->term;
        } elseif ($this->range) {
            $built['range'] = $this->range->build();
        } elseif ($this->wildcard) {
            $built['wildcard'] = $this->wildcard;
        }

        return $built;
    }
}
