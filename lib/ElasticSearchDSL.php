<?php // vim:set ts=4 sw=4 et:

/**
 * Helper stuff for working with the ElasticSearch DSL
 *
 * @author Raymond Julin <raymond.julin@gmail.com>
 * @package ElasticSearchClient
 * @since 0.1
 * Created: 2010-07-23
 */
class ElasticSearchDSL {

    protected $dsl = array();
    
    /**
     * Construct DSL object
     *
     * @return ElasticSearchDSL
     * @param array $dsl
     */
    public function __construct(array $dsl=array()) {
        $this->dsl = $dsl;
    }

    /**
     * Return string representation of DSL for seach.
     * This will remove certain fields that are not supported
     * in a string representation
     *
     * @return string
     */
    public function __toString() {
        if (count($this->dsl) > 0) {
            $stringify = new ElasticSearchDSLStringify($this);
            return $stringify->convert();
        }
    }

    public function toArray() {
        return $this->dsl;
    }
}
