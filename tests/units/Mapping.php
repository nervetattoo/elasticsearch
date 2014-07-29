<?php

namespace ElasticSearch\tests\units;

require_once getcwd() . '/vendor/autoload.php';

use \mageekguy\atoum;

class Mapping extends atoum\test
{
    public function tearDown() {
        \ElasticSearch\Client::connection()->setIndex('test-index')->delete();
        \ElasticSearch\Client::connection()->delete();
    }

    public function testMapCreate() {
        $mapping = new \ElasticSearch\Mapping(array(
            'tweet' => array(
                'type' => 'string'
            ),
            'user.name' => array(
                'index' => 'not_analyzed'
            )
        ));

        $jsonBody = $mapping->export();

        $this->assert->array($jsonBody)
            ->isNotEmpty()
            ->hasSize(1)
            ->array($jsonBody['properties'])
                ->hasSize(2);

        $properties = $jsonBody['properties'];

        $this->assert->array($properties['tweet'])
            ->isNotEmpty()
            ->isEqualTo(array(
                'type' => 'string'
            ));

        $this->assert->array($properties['user.name'])
            ->isNotEmpty()
            ->isEqualTo(array(
                'index' => 'not_analyzed'
            ));
    }

    public function testAddMoreFieldsToMapping() {
        $mapping = new \ElasticSearch\Mapping;

        $exported = $mapping->field('tweet', array(
            'type' => 'string'
        ))->export();

        // TODO Does atoum have a prettier interface for this drill down?
        $this->assert->array($exported)->isNotEmpty()
            ->isEqualTo(array(
                'properties' => array(
                    'tweet' => array('type' => 'string')
                )
            ));
    }

    public function testAddFieldWithTypeLazy() {
        $mapping = new \ElasticSearch\Mapping;
        // Basic mappings:
        $exported = $mapping->field('tweet', 'string')->export();
        $this->assert->array($exported)->isNotEmpty()
            ->isEqualTo(array(
                'properties' => array(
                    'tweet' => array('type' => 'string')
                )
            ));
    }

    public function testAddTypeConstrainedMapping() {
        $mapping = new \ElasticSearch\Mapping(array(
            'tweet' => array(
                'type' => 'string'
            )
        ), array('type' => 'tweet'));

        $exported = $mapping->export();
        $this->assert->array($exported)->isNotEmpty()
            ->hasKey('properties')->notHasKey('type');
    }

    // Integrate and perform query
    public function testMapFields() {
        $client = \ElasticSearch\Client::connection(array(
            'index' => 'test-index',
            'type' => 'test-type'
        ));
        $client->index(array(
            'tweet' => 'ElasticSearch is awesome'
        ));
        $response = $client->map(array(
            'tweet' => array('type' => 'string')
        ));

        $this->assert->array($response)->isNotEmpty();
    }
}
