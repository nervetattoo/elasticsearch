<?php // vim:set ts=4 sw=4 et:

namespace ElasticSearch;

/**
 * This file is part of the ElasticSearch PHP client
 *
 * (c) Raymond Julin <raymond.julin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Mapping {

    protected $properties = array();
    protected $config = array();

    /**
     * Build mapping data
     *
     * @return ElasticSearch\Mapping
     */
    public function __construct(array $properties = array(), array $config = array()) {
        $this->properties = $properties;
        $this->config = $config;
    }

    /**
     * Export mapping data as a json-ready array
     *
     * @return string
     */
    public function export() {
        return array(
            'properties' => $this->properties
        );
    }

    /**
     * Add or overwrite existing field by name
     *
     * @param string $field
     * @param string|array $config
     * @return $this
     */
    public function field($field, $config = array()) {
        if (is_string($config)) $config = array('type' => $config);
        $this->properties[$field] = $config;
        return $this;
    }

    /**
     * Get or set a config
     * 
     * @param string $key
     * @param mixed $value
     */
    public function config($key, $value = null) {
        if (is_array($key))
            $this->config = $key + $this->config;
        else {
            if ($value !== null) $this->config[$key] = $value;
            if (!isset($this->config[$key]))
                throw new \Exception("Configuration key `type` is not set");
            return $this->config[$key];
        }
    }
}
