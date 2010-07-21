# ElasticSearch PHP client
## Basic usage
> $search = new ElasticSearch("localhost:9200", "myindex", "mytype");
> $search->index(array('title' => 'My cool document'), $id);
> $search->get($id);
> $search->search('title:cool');

## Multiple indexes or types
> $search->useIndex(array("one", "two"));
> $search->useType(array("mytype", "other-type"));
> $search->search('title:cool');

## Using the EQL
> $search->search(array(
>   'term' => array('title' => 'cool')
> );
