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
class Mapping
{

    protected $properties = [];
    protected $config = [];

    /**
     * Build mapping data
     *
     * @param array $properties
     * @param array $config
     *
     * @return \ElasticSearch\Mapping
     */
    public function __construct(array $properties = [], array $config = [])
    {
        $this->properties = $properties;
        $this->config = $config;
    }

    /**
     * Export mapping data as a json-ready array
     *
     * @return array
     */
    public function export(): array
    {
        return [
            'properties' => $this->properties,
        ];
    }

    /**
     * Add or overwrite existing field by name
     *
     * @param string       $field
     * @param string|array $config
     *
     * @return $this
     */
    public function field(string $field, $config = []): self
    {
        if (is_string($config)) {
            $config = [ 'type' => $config ];
        }
        $this->properties[$field] = $config;

        return $this;
    }

    /**
     * Get or set a config
     *
     * @param array|string $key
     * @param mixed        $value
     *
     * @return array|void
     * @throws \Exception
     *
     */
    public function config($key, $value = null)
    {
        if (is_array($key)) {
            $this->config = array_merge($this->config, $key);
        } else {
            if ($value !== null) {
                $this->config[$key] = $value;
            }

            if (!isset($this->config[$key])) {
                throw new \Exception("Configuration key `{$key}` is not set");
            }

            return $this->config[$key];
        }
    }
}
