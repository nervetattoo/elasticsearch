[![Build Status](https://secure.travis-ci.org/nervetattoo/elasticsearch.png?branch=master)](http://travis-ci.org/nervetattoo/elasticsearch)
# ElasticSearch PHP client
ElasticSearch is a distributed lucene powered search indexing, this is a PHP client for it

## Usage

### Initial setup 

1. Install composer. `curl -s http://getcomposer.org/installer | php`
2. Create `composer.json` containing:

    ```js
    {
        "require" : {
            "nervetattoo/elasticsearch" : ">=2.0"
        }
    }
    ```
3. Run `./composer.phar install`
4. Keep up-to-date: `./composer.phar update`

### Indexing and searching

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use \ElasticSearch\Client;
// The recommended way to go about things is to use an environment variable called ELASTICSEARCH_URL
$es = Client::connection();

// Alternatively you can use dsn string
$es = Client::connection('http://127.0.0.1:9200/myindex/mytype');

$es->index(array('title' => 'My cool document'), $id);
$es->get($id);
$es->search('title:cool');
```

### Creating mapping

```php
<?php
$es->map(array(
    'title' => array(
        'type' => 'string',
	'index' => 'analyzed'
    )
));
```

### Search multiple indexes or types

```php
<?php
$results = $es
    ->setIndex(array("one", "two"))
    ->setType(array("mytype", "other-type"))
    ->search('title:cool');
```

### Using the Query DSL

```php
<?php
$es->search(array(
    'query' => array(
        'term' => array('title' => 'cool')
    )
);
```

### Provide configuration as array

Using an array for configuration also works

```php
<?php
$es = Client::connection(array(
    'servers' => '127.0.0.1:9200',
    'protocol' => 'http',
    'index' => 'myindex',
    'type' => 'mytype'
));
```

### Support for Routing

```php
<?php
$document = array(
    'title' => 'My routed document',
    'user_id' => '42' 
);
$es->index($document, $id, array('routing' => $document['user_id']));
$es->search('title:routed', array('routing' => '42'));
```

### Using the bulk API
```php
<?php
$bulk = $es->bulk(array('chunk' => 100));

foreach ($items as $item)
    $bulk->index($item->indexable());
foreach ($delete as $item)
    $bulk->delete($item->id);
$bulk->commit();
```

