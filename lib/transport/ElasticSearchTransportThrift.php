<?php
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift.php';
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';

require_once $GLOBALS['THRIFT_ROOT'].'/packages/elasticsearch/Rest.php';	

class ElasticSearchTransportThriftException extends ElasticSearchException {
	protected $data = array(
							'payload' => null,
							'port' => null,
							'host' => null,
							'url' => null,
							'method' => null,
							);
	public function __set($key, $value) {
		if (array_key_exists($key, $this->data))
			$this->data[$key] = $value;
	}
	public function __get($key) {
		if (array_key_exists($key, $this->data))
			return $this->data[$key];
		else
			return false;
	}
	
	public function getCLICommand() {
		$postData = json_encode($this->payload);
		$curlCall = "curl -X{$method} 'http://{$this->host}:{$this->port}$this->url' -d '$postData'";
		return $curlCall;
	}
}

class ElasticSearchTransportThrift extends ElasticSearchTransport 
{
	protected $host = "";
	protected $port = 0;
	
	protected $socket = NULL;
	protected $transport = NULL;
	protected $protocol = NULL;
	protected $client = NULL;
	
	
	public function __construct($host, $port) {
		$this->host = $host;
		$this->port = $port;
		
		$this->socket = new TSocket($host, $port);
		$this->transport = new TBufferedTransport($this->socket);
		$this->protocol = new TBinaryProtocol($this->transport);
		$this->client = new RestClient($this->protocol);
		$this->transport->open();
	}
	
	public function __destruct() {
		$this->transport->close();
	}
	
	/**
	 * Index a new document or update it if existing
	 *
	 * @return array
	 * @param array $document
	 * @param mixed $id Optional
	 */
	public function index($document, $id=false) {
		$url = $this->buildUrl(array($this->type, $id));
		$method = ($id == false) ? "POST" : "PUT";
		try {
			$response = $this->call($url, $method, $document);
		}
		catch (Exception $e) {
			throw $e;
		}
		
		return $response;
	}
	
	/**
	 * Search
	 *
	 * @return array
	 * @param mixed $id Optional
	 */
	public function search($query) {
		if (is_array($query)) {
			/**
			 * Array implies using the JSON query DSL
			 */
			$url = $this->buildUrl(array($this->type, "_search"));
			try {
				$result = $this->call($url, "GET", $query);
			}
			catch (Exception $e) {
				throw $e;
			}
		}
		elseif (is_string($query)) {
			/**
			 * String based search means http query string search
			 */
			$url = $this->buildUrl(array($this->type, "_search?q=" . $query));
			$result = $this->call($url, "GET");
			try {
				$result = $this->call($url, "GET");
			}
			catch (Exception $e) {
				throw $e;
			}
		}
		return $result;
	}
	
	/**
	 * Search
	 *
	 * @return array
	 * @param mixed $id Optional
	 */
	public function deleteByQuery($query) {
		if (is_array($query)) {
			/**
			 * Array implies using the JSON query DSL
			 */
			$url = $this->buildUrl(array($this->type, "_query"));
			try {
				$result = $this->call($url, "DELETE", $query);
			}
			catch (Exception $e) {
				throw $e;
			}
		}
		elseif (is_string($query)) {
			/**
			 * String based search means http query string search
			 */
			$url = $this->buildUrl(array($this->type, "_query?q=" . $query));
			try {
				$result = $this->call($url, "DELETE");
			}
			catch (Exception $e) {
				throw $e;
			}
		}
		return $result['ok'];
	}
	
	/**
	 * Basic http call
	 *
	 * @return array
	 * @param mixed $id Optional
	 */
	public function request($path, $method="GET") {
		$url = $this->buildUrl($path);
		try {
			$result = $this->call($url, $method);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $result;
	}
	
	/**
	 * Flush this index/type combination
	 *
	 * @return array
	 */
	public function delete($id=false) {
		if ($id)
			return $this->request(array($this->type, $id), "DELETE");
		else
			return $this->request(false, "DELETE");
	}
	
	/**
	 * Perform a http call against an url with an optional payload
	 *
	 * @return array
	 * @param string $url
	 * @param string $method (GET/POST/PUT/DELETE)
	 * @param array $payload The document/instructions to pass along
	 */
	protected function call($url, $method="GET", $payload=false) {
		$req = array("method" => $GLOBALS['E_Method'][$method], 
					 "uri" => $url);
		
		if (is_array($payload) && count($payload) > 0) {
			$req["body"] = json_encode($payload);
		}
		
		$result = $this->client->execute(new RestRequest($req));
		
		if (!isset($result->status)){
			$exception = new ElasticSearchTransportThriftException();
			$exception->payload = $payload;
			$exception->port = $this->port;
			$exception->host = $this->host;
			$exception->method = $method;
			throw $exception;
		}		
		
		$data = json_decode($result->body, true);
		
		if (array_key_exists('error', $data)) {
			$this->handleError($url, $method, $payload, $data);
		}
		
		return $data;
	}
	
	protected function handleError($url, $method, $payload, $response) {
		$err = "Request: \n";
		$err .= "curl -X$method http://{$this->host}:{$this->port}$url";
		if ($payload) $err .=  " -d '" . json_encode($payload) . "'";
		$err .= "\n";
		$err .= "Triggered some error: \n";
		$err .= $response['error'] . "\n";
		//echo $err;
	}
	
	/**
	 * Build a callable url
	 *
	 * @return string
	 * @param array $path
	 */
	protected function buildUrl($path=false) {
		$url = "/" . $this->index;
		if ($path && count($path) > 0)
			$url .= "/" . implode("/", array_filter($path));
		if (substr($url, -1) == "/")
			$url = substr($url, 0, -1);
		return $url;
	}
}
