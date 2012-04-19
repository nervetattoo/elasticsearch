[![Build Status](https://secure.travis-ci.org/nervetattoo/elasticsearch.png?branch=dev)](http://travis-ci.org/nervetattoo/elasticsearch)
# ElasticSearch PHP client
ElasticSearch is a distributed lucene powered search indexing, this is a PHP client for it

## Basic usage

```php
<?php
use \ElasticSearch\Client;
// Use dsn string or configuration array specifying keys
$es = Client::connection('http://127.0.0.1:9200/myindex/mytype');
$es = Client::connection(array(
    'server' => '127.0.0.1:9200',
    'protocol' => 'http',
    'index' => 'myindex',
    'type' => 'mytype'
));
$es->index(array('title' => 'My cool document'), $id);
$es->get($id);
$es->search('title:cool');
```

## Search multiple indexes or types

```php
<?php
$es->setIndex(array("one", "two"));
$es->setType(array("mytype", "other-type"));
$es->search('title:cool');
```

## Using the Query DSL

```php
<?php
$es->search(array(
    'query' => array(
        'term' => array('title' => 'cool')
    )
);
```
