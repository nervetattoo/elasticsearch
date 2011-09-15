<?php

abstract class ElasticSearchTransport {
    protected $index, $type;

    abstract public function index($document, $id=false, array $options = array());
    abstract public function bulk($bulks, array $options = array());
    abstract public function request($path, $method="GET", $payload=false);
    abstract public function delete($id=false);
    abstract public function search($query);

    public function setIndex($index) {
        $this->index = $index;
    }
    public function setType($type) {
        $this->type = $type;
    }
    /*
     * Bulk index new documents or update them if existing.
     * Only supports documents of the type set before.
     *
     * @return array
     * @param array $documents The documents to index.
     * @param array $ids Optionally: the IDs of the documents in the same order.
     *                   Use NULL for not specifying the ID for only one document.
     * @param array $types the types of the document
     */
    public function index_bulk($documents, array $ids=array(), array $types = array(), array $options = array()) {
      $bulks = array();
      if (is_array($ids) && count($documents) == count($ids))
        $use_ids = true;
      else {
        $use_ids = false;
        $id = false;
      }
      if (is_array($types) && count($documents) == count($types))
        $use_types = true;
      else {
        $use_types = false;
        $type = false;
      }

      foreach ($documents as $i=>$doc) {
        if ($use_ids)
          $id = $ids[$i];
        if ($use_types)
          $type = $types[$i];

        $bulks[] = new BulkItem ("index", $this->index, $id, $type, $doc);
      }
      return $this->bulk ($bulks);
    }

    /**
     * Build a callable url
     *
     * @return string
     * @param array $path
     * @param array $options Query parameter options to pass
     */
    protected function buildUrl($path=false, array $options = array()) {
        $url = "/" . $this->index;
        if ($path && count($path) > 0)
            $url .= "/" . implode("/", array_filter($path));
        if (substr($url, -1) == "/")
            $url = substr($url, 0, -1);
        if (count($options) > 0)
            $url .= "?" . http_build_query($options);
        return $url;
    }
}

class BulkItem {
  protected $method;
  protected $index;
  protected $id;
  protected $type;
  protected $payload;

  public function __construct($method, $index, $id, $type, array $payload=array()) {
    $this->method = $method;
    $this->index = $index;
    $this->id = $id;
    $this->type = $type;
    $this->payload = $payload;
  }

  public function encode () {
    $str = json_encode(
      array($this->method=>array(
        "_index"=>$this->index,
        "_type"=>$this->type,
        "_id"=>$this->id)));
    if ($this->payload)
      $str .= "\n".json_encode($this->payload);
    return $str;
  }
}
