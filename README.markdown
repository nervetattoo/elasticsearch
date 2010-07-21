# ElasticSearch PHP client
ElasticSearch is a distributed lucene powered search indexing, this is a PHP client for it
## Warning: API WILL CHANGE
## Basic usage
    require_once "ElasticSearch.php";
    $transport = new ElasticSearchTransportHTTP("localhost", 9200);
    $search = new ElasticSearch($transport, "myindex", "mytype");
    $search->index(array('title' => 'My cool document'), $id);
    $search->get($id);
    $search->search('title:cool');

## Multiple indexes or types
    $search->useIndex(array("one", "two"));
    $search->useType(array("mytype", "other-type"));
    $search->search('title:cool');

## Using the Query DSL
    $search->search(array(
        'term' => array('title' => 'cool')
    );
