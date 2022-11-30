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
 * Range queries
 *
 * @author  Raymond Julin <raymond.julin@gmail.com>
 * @package ElasticSearchClient
 * @since   0.1
 * Created: 2010-07-24
 */
class RangeQuery
{
    protected $fieldName = null;
    protected $from = null;
    protected $to = null;
    protected $includeLower = null;
    protected $includeUpper = null;
    protected $boost = null;


    /**
     * Construct new RangeQuery component
     *
     * @param array $options
     *
     * @return \ElasticSearch\DSL\RangeQuery
     */
    public function __construct(array $options = [])
    {
        $this->fieldName = key($options);
        $values = current($options);
        if (is_array($values)) {
            foreach ($values as $key => $val) {
                $this->$key = $val;
            }
        }
    }

    /**
     * Setters
     *
     * @param mixed $value
     *
     * @return \ElasticSearch\DSL\RangeQuery
     */
    public function fieldName($value): self
    {
        $this->fieldName = $value;

        return $this;
    }

    /**
     * @param $value
     *
     * @return \ElasticSearch\DSL\RangeQuery $this
     */
    public function from($value): self
    {
        $this->from = $value;

        return $this;
    }

    /**
     * @param $value
     *
     * @return \ElasticSearch\DSL\RangeQuery $this
     */
    public function to($value): self
    {
        $this->to = $value;

        return $this;
    }

    /**
     * @param $value
     *
     * @return \ElasticSearch\DSL\RangeQuery $this
     */
    public function includeLower($value): self
    {
        $this->includeLower = $value;

        return $this;
    }

    /**
     * @param $value
     *
     * @return \ElasticSearch\DSL\RangeQuery $this
     */
    public function includeUpper($value): self
    {
        $this->includeUpper = $value;

        return $this;
    }

    /**
     * @param $value
     *
     * @return \ElasticSearch\DSL\RangeQuery $this
     */
    public function boost($value): self
    {
        $this->boost = $value;

        return $this;
    }

    /**
     * Build to array
     *
     * @return array
     * @throws \ElasticSearch\Exception
     */
    public function build(): self
    {
        $built = [];
        if ($this->fieldName) {
            $built[$this->fieldName] = [];
            foreach ([ "from", "to", "includeLower", "includeUpper", "boost" ] as $opt) {
                if ($this->$opt !== null) {
                    $built[$this->fieldName][$opt] = $this->$opt;
                }
            }
            if (count($built[$this->fieldName]) == 0) {
                throw new \ElasticSearch\Exception("Empty RangeQuery cant be created");
            }
        }

        return $built;
    }
}
