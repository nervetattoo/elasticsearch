<?php // vim:set ts=4 sw=4 et:
# Copyright 2012 Tobias Florek. All rights reserved.
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions are met:
#
# 1. Redistributions of source code must retain the above copyright notice,
# this list of conditions and the following disclaimer.
#
# 2. Redistributions in binary form must reproduce the above copyright notice,
# this list of conditions and the following disclaimer in the documentation
# and/or other materials provided with the distribution.
#
# THIS SOFTWARE IS PROVIDED BY THE FREEBSD PROJECT `AS IS'' AND ANY EXPRESS OR
# IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
# MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
# EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
# INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
# (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
# ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
# (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
# SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.


class ElasticSearchBulk {

    /**
     * @const string Indicate index operation
     */
    const ES_BULK_INDEX = 'index';

    /**
     * @const string Indicate delete operation
     */
    const ES_BULK_DELETE = 'delete';

    /**
     * @var ElasticSearchTransport The transport
     */
    protected $transport;

    /**
     * @var string The default index
     */
    protected $index;

    /**
     * @var type The default type
     */
    protected $type;

    /**
     * @var array The items to delete in the form array($doc)
     * i.e. this is an array of an array with the document to delete.
     */
    protected $to_delete = array();

    /**
     * @var array The items to index in the form array($metadata, $doc)
     */
    protected $to_index = array();

    /**
     * return ElasticSearchBulk
     * @param ElasticSearchTransport $transport The transport
     * @param string $index The default index
     * @param string $type The default type
     */
    public function __construct($transport, $index, $type) {
        $this->transport = $transport;
        $this->index = $index;
        $this->type = $type;
    }

    /**
     * @param array $doc A document to index
     * @param string $id The ID to use
     * @param string $index The index to use
     * @param string $type The type to use
     * @param array $meta The metadata to use (overwritten by $id, $index and $type, if specified)
     */
    public function index($doc, $id='', $index='', $type='', $meta=array()) {
        if ($index)
            $meta['_index'] = $index;
        if ($type)
            $meta['_type'] = $type;
        if ($id)
            $meta['_id'] = $id;

        // second overwrites first
        $meta = array_merge(array('_type'=>$this->type, '_index'=>$this->index), $meta);

        $this->to_index[] = array($meta, $doc);
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
        $this->to_delete[] = array(array('_id' => $id,
            '_type' => $type ? $type : $this->type,
            '_index'=> $index? $index: $this->index
        ));
    }

    /**
     * perform all staged operations
     * @param array $options Not used atm
     */
    public function commit($options = array()) {
        $istr = join("\n", array_map(function ($doc) {
                return $this->encode_item(ElasticSearchBulk::ES_BULK_INDEX, $doc);
            }, $this->to_index));
        $dstr = join("\n", array_map(function ($doc) {
                return $this->encode_item(ElasticSearchBulk::ES_BULK_DELETE, $doc);
            }, $this->to_delete));

        # nb: there needs to be a newline at the end.
        $str = join("\n", array($istr, $dstr))."\n";

        // clear staging arrays
        $this->to_index = $this->to_delete = array();

        $ret = $this->transport->request('/_bulk', 'POST', $str);
        return $ret;
    }

    /**
     * @param array $doc The document
     */
    protected function encode_item($type, $doc) {
        $str = json_encode(array($type => $doc[0]));

        if (count($doc) > 1)
            $str.= "\n".json_encode($doc[1]);
        return $str;
    }
}

?>
