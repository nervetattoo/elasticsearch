# ElasticSearch PHP client
---
[![Latest Stable Version](https://poser.pugx.org/xhinliang/elasticsearch/v/stable)](https://packagist.org/packages/xhinliang/elasticsearch)
[![Total Downloads](https://poser.pugx.org/xhinliang/elasticsearch/downloads)](https://packagist.org/packages/xhinliang/elasticsearch)
[![Latest Unstable Version](https://poser.pugx.org/xhinliang/elasticsearch/v/unstable)](https://packagist.org/packages/xhinliang/elasticsearch)
[![License](https://poser.pugx.org/xhinliang/elasticsearch/license)](https://packagist.org/packages/xhinliang/elasticsearch)
[![composer.lock](https://poser.pugx.org/xhinliang/elasticsearch/composerlock)](https://packagist.org/packages/xhinliang/elasticsearch)

ElasticSearch is a distributed lucene powered search indexing, this is a PHP client for it

## Usage

### Initial setup

1. Install composer. `curl -s http://getcomposer.org/installer | php`
2. Create `composer.json` containing:

    ```js
    {
        "require" : {
            "xhinliang/elasticsearch" : ">=2.0"
        }
    }
    ```
3. Run `./composer.phar install`
4. Keep up-to-date: `./composer.phar update`

### Indexing and searching

```php
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
$es->map(array(
    'title' => array(
        'type' => 'string',
	'index' => 'analyzed'
    )
));
```

### Search multiple indexes or types

```php
$results = $es
    ->setIndex(array("one", "two"))
    ->setType(array("mytype", "other-type"))
    ->search('title:cool');
```

### Using the Query DSL

```php
$es->search(array(
    'query' => array(
        'term' => array('title' => 'cool')
    )
);
```

### Provide configuration as array

Using an array for configuration also works

```php
$es = Client::connection(array(
    'servers' => '127.0.0.1:9200',
    'protocol' => 'http',
    'index' => 'myindex',
    'type' => 'mytype'
));
```

### Support for Routing

```php
$document = array(
    'title' => 'My routed document',
    'user_id' => '42'
);
$es->index($document, $id, array('routing' => $document['user_id']));
$es->search('title:routed', array('routing' => '42'));
```


### Support for Bulking

```php
$document = array(
    'title' => 'My bulked entry',
    'user_id' => '43'
);
$es->beginBulk();
$es->index($document, $id, array('routing' => $document['user_id']));
$es->delete(2);
$es->delete(3);
$es->commitBulk();


$es->createBulk()
    ->delete(4)
    ->index($document, $id, 'myIndex', 'myType', array('parent' => $parentId));
    ->delete(5)
    ->delete(6)
    ->commit();

```

### Usage as a service in Symfony2

In order to use the Dependency Injection to inject the client as a service, you'll have to define it before.
So in your bundle's services.yml file you can put something like this :
```yml
    your_bundle.elastic_transport:
        class: ElasticSearch\Transport\HTTP
        arguments:
            - localhost
            - 9200
            - 60

    your_bundle.elastic_client:
        class: ElasticSearch\Client
        arguments:
            - @your_bundle.elastic_transport
```
To make Symfony2 recognize the `ElasticSearch` namespace, you'll have to register it. So in your `app/autoload.php` make sure your have :
```php
// ...

$loader->registerNamespaces(array(
    // ...
    'ElasticSearch' => __DIR__.'/path/to/your/vendor/nervetattoo/elasticsearch/src',
));
```
Then, you can get your client via the service container and use it like usual. For example, in your controller you can do this :
```php
class FooController extends Controller
{
    // ...

    public function barAction()
    {
        // ...
        $es = $this->get('your_bundle.elastic_client');
        $results = $es
            ->setIndex(array("one", "two"))
            ->setType(array("mytype", "other-type"))
            ->search('title:cool');
    }
}
```



