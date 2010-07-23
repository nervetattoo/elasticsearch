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
    public function __construct(array $dsl) {
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
        $dsl = $this->dsl['query'];
        $string = "";
        if (array_key_exists("term", $dsl))
            $string .= $this->transformDSLTermToString($dsl['term']);
        if (array_key_exists("wildcard", $dsl))
            $string .= $this->transformDSLTermToString($dsl['wildcard']);
        return $string;
    }

    /**
     * A naive transformation of possible term and wildcard arrays in a DSL
     * query
     *
     * @return string
     * @param mixed $dslTerm
     */
    protected function transformDSLTermToString($dslTerm) {
        $string = "";
        if (is_array($dslTerm)) {
            $key = key($dslTerm);
            $value = $dslTerm[$key];

            /**
             * If a specific key is used as key in the array
             * this should translate to searching in a specific field (field:term)
             */
            if (is_string($key))
                $string .= "$key:";
            if (strpos(" ", $value) !== false)
                $string .= '"' . $value . '"';
            else
                $string .= $value;
        }
        else
            $string .= $dslTerm;
        return $string;
    }
}
