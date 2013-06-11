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

/**
 * @author Tobias Florek <me@ibotty.net>
 * @package ElasticSearchClient
 * @since 0.2
 * Created: 2012
 */
class Bulk {

    /**
     * @const string Indicate index operation
     */
    const INDEX = 'index';

    /**
     * @const string Indicate delete operation
     */
    const DELETE = 'delete';

    /**
     * @var ElasticSearchTransport The transport
     */
    protected $transport;

    /**
     * @var string The default index
     */
    protected $index;

    /**
     * @var string The default type
     */
    protected $type;

    /**
     * @var int the chunksize
     */
    protected $chunksize;

    /**
     * @var array The encoded operations as array of strings
     */
    protected $chunks = array();

    /**
     * return ElasticSearchBulk
     * @param ElasticSearchTransport $transport The transport
     * @param string $index The default index
     * @param string $type The default type
     * @param int $chunksize
     */
    public function __construct($transport, $index, $type, $chunksize=0) {
        $this->transport = $transport;
        $this->index = $index;
        $this->type = $type;
        $this->chunksize = $chunksize;
    }

    /**
     * add a document to index later.
     *
     * @param array $doc A document to index
     * @param array $meta The metadata to use if it is an array, id otherwise
     * @param array $options unused
     */
    public function index($doc, $meta = array(), $options = array()) {
        if (!is_array($meta))
            $meta = array("_id" => $meta);

        if (!isset($meta["_index"]))
            $meta['_index'] = $this->index;
        if (!isset($meta["_type"]))
            $meta['_type'] = $this->type;

        $this->chunks[] = $this->encode_operation(self::INDEX, $meta, $doc);
    }

    /**
     * delete items from index according to given specification.
     * nb: contrary to deleteByQuery, this does not accept a query string
     *
     * @param string $id
     * @param string type
     * @param string index
     */
    public function delete($id, $type='', $index='') {
        $this->chunks[] = $this->encode_operation(self::DELETE,
            array('_id' => $id,
                '_type' => $type ? $type : $this->type,
                '_index'=> $index? $index: $this->index
        ));
    }

    /**
     * perform all staged operations
     * @param array $options Not used atm
     */
    public function commit($options = array()) {
        $chunksize = $this->chunksize? $this->chunksize: count($this->chunks);

        foreach (array_chunk($this->chunks, $chunksize) as $chunks)
            $ret += $this->transport->request('/_bulk', 'POST', join("\n", $chunks) . "\n");

        return $ret;
    }

    /**
     * @param array $metadata
     * @param array $payload
     * @return string the encoded string
     */
    protected function encode_operation($type, $metadata, $payload=false) {
        $str = json_encode(array($type => $metadata));

        if ($payload)
            $str .= "\n".json_encode($payload);
        return $str;
    }
}

?>
