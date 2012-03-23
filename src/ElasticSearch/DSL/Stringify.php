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
 * Parse a DSL object into a string based representation
 * Return string representation of DSL for search.
 * This will remove certain fields that are not supported
 * in a string representation
 *
 * @author Raymond Julin <raymond.julin@gmail.com>
 * @package ElasticSearch
 * @since 0.1
 * Created: 2010-07-24
 */
class Stringify {

    protected $dsl = array();
    
    public function __construct(array $dsl) {
        $this->dsl = $dsl;
    }

    public function __toString() {
        $dsl = $this->dsl;
        $query = $dsl['query'];

        $string = "";
        if (array_key_exists("term", $query))
            $string .= $this->transformDSLTermToString($query['term']);
        if (array_key_exists("wildcard", $query))
            $string .= $this->transformDSLTermToString($query['wildcard']);
        if (array_key_exists("sort", $dsl))
            $string .= $this->transformDSLSortToString($dsl['sort']);
        if (array_key_exists("fields", $dsl))
            $string .= $this->transformDSLFieldsToString($dsl['fields']);
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
            if (is_string($key))
                $string .= "$key:";
        }
        else
            $value = $dslTerm;
        /**
         * If a specific key is used as key in the array
         * this should translate to searching in a specific field (field:term)
         */
        if (strpos($value, " ") !== false)
            $string .= '"' . $value . '"';
        else
            $string .= $value;
        return $string;
    }

    /**
     * Transform search parameters to string
     *
     * @return string
     * @param mixed $dslSort
     */
    protected function transformDSLSortToString($dslSort) {
        $string = "";
        if (is_array($dslSort)) {
            foreach ($dslSort as $sort) {
                if (is_array($sort)) {
                    $field = key($sort);
                    $info = current($sort);
                }
                else
                    $field = $sort;
                $string .= "&sort=" . $field;
                if (isset($info)) {
                    if (is_string($info) && $info == "desc")
                        $string .= ":reverse";
                    elseif (is_array($info) && array_key_exists("reverse", $info) && $info['reverse'])
                        $string .= ":reverse";
                }
            }
        }
        return $string;
    }

    /**
     * Transform a selection of fields to return to string form
     *
     * @return string
     * @param mixed $dslFields
     */
    protected function transformDSLFieldsToString($dslFields) {
        $string = "";
        if (is_array($dslFields))
            $string .= "&fields=" . join(",", $dslFields);
        return $string;
    }
}
