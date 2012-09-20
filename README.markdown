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

### Selecting index & type

```php
<?php
# setIndex( $index, $type = false ) sets index & type.
# setType( $type ) sets only type.
# FALSE value - use all indices/types

$es->setIndex( 'index', 'type' );
# path: /index/type

$es->setType( 'type2' );
# path: /index/type2

$es->setType( false );
# same as $es->setIndex( 'index' );
# path: /index

$es->setIndex( false );
# path: /

$es->setType( 'type' );
# in this case same as $this->setIndex( false, 'type' )
# path: /_all/type

$es->setIndex( 'index', true );
# TRUE - will avoid type changing
# path: /index/type

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

### Using params passed through request string

```php
<?php
$results = $es->search($request_body, ['routing' => 'value']);
$results = $es->search($request_body, ['search_type' => 'count']);
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
