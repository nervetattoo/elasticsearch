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
 * @author Raymond Julin <raymond.julin@gmail.com>
 * @package ElasticSearchClient
 * @since 0.1
 * Created: 2010-07-24
 */
class RangeQuery {
    protected $fieldname = null;
    protected $from = null;
    protected $to = null;
    protected $includeLower = null;
    protected $includeUpper = null;
    protected $boost = null;

    
    /**
     * Construct new RangeQuery component
     *
     * @return ElasticSearchDSLBuilderRangeQuery
     * @param array $options
     */
    public function __construct(array $options=array()) {
        $this->fieldname = key($options);
        $values = current($options);
        if (is_array($values)) {
            foreach ($values as $key => $val)
                $this->$key = $val;
        }
    }
    
    /**
     * Setters
     *
     * @return ElasticSearchDSLBuilderRangeQuery 
     * @param mixed $value
     */
    public function fieldname($value) {
        $this->fieldname = $value;
        return $this;
    }
    public function from($value) {
        $this->from = $value;
        return $this;
    }
    public function to($value) {
        $this->to = $value;
        return $this;
    }
    public function includeLower($value) {
        $this->includeLower = $value;
        return $this;
    }
    public function includeUpper($value) {
        $this->includeUpper = $value;
        return $this;
    }
    public function boost($value) {
        $this->boost = $value;
        return $this;
    }
    
    /**
     * Build to array
     *
     * @return array
     */
    public function build() {
        $built = array();
        if ($this->fieldname) {
            $built[$this->fieldname] = array();
            foreach (array("from","to","includeLower","includeUpper", "boost") as $opt) {
                if ($this->$opt !== null)
                    $built[$this->fieldname][$opt] = $this->$opt;
            }
            if (count($built[$this->fieldname]) == 0)
                throw new Exception("Empty RangeQuery cant be created");
        }
        return $built;
    }
}
