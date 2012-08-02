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

### Search multiple indexes or types

```php
<?php
$es->setIndex(array("one", "two"));
$es->setType(array("mytype", "other-type"));
$es->search('title:cool');
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
    'server' => '127.0.0.1:9200',
    'protocol' => 'http',
    'index' => 'myindex',
    'type' => 'mytype'
));
```
