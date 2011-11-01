# ElasticSearch PHP client using Thrift Transport
### Install Thrift
Details on installation available from thrift wiki http://wiki.apache.org/thrift/ThriftInstallation

### Install Thrift libs 
copy from the thrift src directory the php library, lib/php/src/* into your <php libs dir>/Thrift

### Add thrift support to ElasticSearch
	Install plugin: 
		bin/plugin -install transport-thrift
	
	Configure: conf/elasticsearch.json
		{
			"thrift" : {
				"port" : "9521"
			}
		}		
		
### Gen Thrift package
	Get elasticsearch.thrift from elasticsearch @ github (currently at elasticsearch/plugins/transport/thrift/elasticsearch.thrift). Then generate the thrift files and place generated gen-php files in Thrift/packages/elasticsearch
	
	> wget http://github.com/elasticsearch/elasticsearch/raw/master/plugins/transport/thrift/elasticsearch.thrift
	> thrift --gen php elasticsearch.thrift
	> mv gen-php/elasticsearch <php libs dir>/Thrift/packages/
	> ls <php libs dir>/Thrift/packages/elasticsearch/
	elasticsearch_types.php  Rest.php
	
		
### Client usage
	// location of your thrift root
	$GLOBALS['THRIFT_ROOT'] = dirname(__FILE__) . 'libs/Thrift';
    require_once "ElasticSearchClient.php";
    $transport = new ElasticSearchTransportThrift("localhost", 9520);
	// Usage is the same as basic http client from here on out
