<?php
/**
* Plugin datasource for "RSC DNS" API.
*
*
* @author Mark Vince <mvince@gmail.com>
* @link https://github.com/markvince/RSCDNS-Plugin
* @copyright (c) 2012 Mark Vince
* @license MIT License - http://www.opensource.org/licenses/mit-license.php
*
*/
class RscdnsSource extends DataSource {
	/**
	* The description of this data source
	*
	* @var string
	*/
	public $description = 'RackSpace Cloud DNS DataSource';
	/**
	* HttpSocket object
	* @var object
	*/
	public $Http;
	/**
	* Signed request string
	* @var string
	*/
	protected $_request = null;
	/**
	* Request Logs
	* @var array
	*/
	private $__requestLog = array();
	/**
	* Setup & establish HttpSocket.
	* @param config an array of configuratives to be passed to the constructor to overwrite the default
	*/
	public function __construct($config=array()) {
		parent::__construct($config);
		if (is_array($config) && !empty($config)) {
			$_config = configure::read('Rscdns');
			if (is_array($_config) && !empty($_config)) {
				$config = array_merge($_config, $config);
			}
			configure::write('Rscdns', $config);
		}
		if (!class_exists('RscdnsUtil')) {
			App::import('Lib', 'Rscdns.RscdnsUtil');
		}
		if (!class_exists('HttpSocket')) {
			App::import('Core', 'HttpSocket');
		}
		$this->Http = new HttpSocket();
		//$this->__authenticate();
	}
	
	public function __authenticate($a=array()) {
		echo "Authenticating...";
		$auth_config = RscdnsUtil::getConfig('auth');
		$auth = array_merge($auth_config,$a);
		if (array_key_exists('token',$auth) && !empty($auth['token'])) {
			$auth_config['token'] = $auth['token'];
			RscdnsUtil::setConfig('auth',$auth_config);
			return true;
		} else {
			$url = $auth['auth-endpoint'].'auth.json';
			//Using version 2.0 for authenticating
			
			$request = json_encode(array('credentials' => array(
				'username' => $auth['username'],
				'key' => $auth['key'],
				)));
			
			//$request = array(
			//	'auth' => array(
			//		'RAX-KSKEY:apiKeyCredentials' => array(
			//			'username' => $auth['username'],
			//			'apikey' => $auth['key'],
			//			)
			//		),
			//	);
			$request = '{"credentials" : {"username" : "'.$auth['username'].'","key" : "'.$auth['key'].'"}}';
			print_r($url);
			print_r($request);
			$this->Http->reset();
			$response = $this->Http->post($url,$request,array(
				'header'=>array(
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',)));
			//print_r($this->Http->response['raw']);
			if (!empty($response)) {
				$response = json_decode($response);
				$token = $response->auth->token->id;
				if (!empty($token)) {
					$auth_config['token'] = $token;
					RscdnsUtil::setConfig('auth',$auth_config);
					return true;	
				}
			}
		}
		
		return false;
	}
	
	/**
	* Override of the basic describe() function 
	* @param object $model
	* @return array $_schema
	*/
	public function describe($model) {
		if (isset($model->_schema)) {
			return $model->_schema;
		} elseif (isset($model->alias) && isset($this->_schema) && isset($this->_schema[$model->alias])) {
			return $this->_schema[$model->alias];
		}
		return array();
	}
	/**
	* Unsupported methods other CakePHP model and related classes require.
	*/
	public function listSources() {
		return array('RSCDNS');
	}
	/**
	* Not currently possible to read data. Method not implemented.
	*/
	public function read(&$Model, $queryData = array()) {
		return false;
	}
	/**
	* Create a new zone entry
	*/
	public function create(&$Model, $fields = array(), $values = array()) {
		$data = array_combine($fields, $values);
		return $this->__request($data, $Model);
	}
	/**
	* Modify zone entry
	*/
	public function update(&$Model, $fields = null, $values = null) {
		$data = array_combine($fields, $values);
		return $this->__request($data, $Model);
	}
	/**
	* Delete zone entry
	*/
	public function delete(&$Model, $id = null) {
		return false;
	}
	/**
	* Place data into proper form for tranmission to RSC
	* @param mixed $data
	* @return string $xml
	*/
	public function prepareApiData($data = null, &$Model=null) {
		/*
		if (empty($data)) {
			return false;
		}
		if (is_string($data)) {
			// assume it's a XML body already
			return $data;
		}
		// litleOnlineRequestKey wrapper
		if (array_key_exists('litleOnlineRequest', $data)) {
			$litleOnlineRequestKey = $data['litleOnlineRequest'];
			unset($data['litleOnlineRequest']);
		} else {
			$attrib = array(
				'version' => LitleUtil::getConfig('version'),
				'url_xmlns' => LitleUtil::getConfig('url_xmlns'),
				'merchantId' => LitleUtil::getConfig('merchantId'),
				);
			$litleOnlineRequestKey = 'litleOnlineRequest|'.json_encode($attrib);
		}
		// authentication
		if (array_key_exists('authentication', $data)) {
			$authentication = $data['authentication'];
			unset($data['authentication']);
		} else {
			$authentication = array('authentication' => array(
				'user' => LitleUtil::getConfig('user'),
				'password' => LitleUtil::getConfig('password'),
				));
		}
		// root wrapper
		if (array_key_exists('root', $data)) {
			$root = $data['root'];
			unset($data['root']);
		} else {
			$root = (isset($Model->alias) ? $Model->alias : null);
		}
		// re-order and nest
		if (is_string($root) && !empty($root)) {
			$data = array($root => $data);
		}
		$requestArray = array($litleOnlineRequestKey => array_merge($authentication, $data));
		$xml = ArrayToXml::build($requestArray);
		$xml = str_replace('url_xmlns', 'xmlns', $xml); // special replacement
		$xml = str_replace('><', ">\n<", $xml); // formatting with linebreaks
		#$xml = preg_replace('#(<[^/>]*>)(<[^/>]*>)#', "\$1\n\$2", $xml);
		#$xml = preg_replace('#(</[a-zA-Z0-9]*>)(</[a-zA-Z0-9]*>)#', "\$1\n\$2", $xml);
		$function = __function__;
		$this->log[] =compact('func', 'data', 'requestArray', 'xml');
		
		*/
		return $xml;
	}
	/**
	* Parse the response data from a post to authorize.net
	* @param string $response
	* @param object $Model
	* @return array
	*/
	public function parseResponse($response, &$Model=null) {
		/*
		$errors = array();
		$transaction_id = null;
		$response_raw = '';
		$response_array = array();
		if (is_string($response)) {
			$response_raw = $response;
			if (!class_exists('Xml')) {
				App::import("Core", "Xml");
			}
			$Xml = new Xml($response);
			$response_array = $Xml->toArray();
		} elseif (is_array($response_array)) {
			$response_array = $response;
			if (array_key_exists('response_raw', $response_array)) {
				$response_raw = $response_array['response_raw'];
				unset($response_array['response_raw']);
			}
		} else {
			$errors[] = 'Response is in invalid format';
		}
		// boil down to just the response we are interested in
		if (array_key_exists('litleOnlineResponse', $response_array)) {
			$response_array = $response_array['litleOnlineResponse'];
		} elseif (array_key_exists('LitleOnlineResponse', $response_array)) {
			$response_array = $response_array['LitleOnlineResponse'];
		}
		// verify response_array
		if (!is_array($response_array)) {
			$errors[] = 'Response is not formatted as an Array';
		} elseif (!array_key_exists('response', $response_array)) {
			$errors[] = 'Response.response missing (request xml validity)';
		}
		if (array_key_exists('message', $response_array) && $response_array['message']!='Valid Format') {
			$errors[] = $response_array['message'];
		} elseif (intval($response_array['response'])!==0) {
			$errors[] = 'Response.response indicates request xml is in-valid, unknown Message';
		}
		if (empty($errors)) {
			$status = 'good';
		} else {
			$status = 'error';
		}
		return compact('status', 'transaction_id', 'errors', 'response_array', 'response_raw');
		*/
	}
		
	/**
	*
	* Post data to authorize.net. Returns false if there is an error,
	* or an array of the parsed response from authorize.net if valid
	*
	* @param array $request
	* @param object $Model optional
	* @return mixed $response
	*/
	public function __request($data, &$Model=null) {
		$errors = array();
		if (empty($data)) {
			$errors[] = "Missing input data";
			$request_raw = '';
		} elseif (is_array($data)) {
			$request_raw = $this->prepareApiData($data, $Model);
		} elseif (is_string($data)) {
			$request_raw = $data;
		} else {
			$errors[] = "Unknown input data type";
			$request_raw = '';
		}
		if (empty($errors)) {
			$this->Http->reset();
			$url = RscdnsUtil::getConfig('api_url');
			$response_raw = $this->Http->post($url, $request_raw, array(
				'header' => array(
					'Connection' => 'close',
					'User-Agent' => 'CakePHP RSC DNS Plugin',
					)
				));
			if ($this->Http->response['status']['code'] != 200) {
				$errors[] = "RscdnsSource: Error: Could not connect to RSC... bad credentials?";
			}
		}
		if (empty($errors)) {
			$response = $this->parseResponse($response_raw);
			extract($response);
		}
		// look for special values
		/*
		$transaction_id = $response_array['transaction_id'] = $this->array_find("litleTxnId", $response_array);
		$litleToken = $response_array['litleToken'] = $this->array_find("litleToken", $response_array);
		if (is_object($Model)) {
			$type = $response_array['type'] = str_replace('litle', '', strtolower($Model->alias));
		} else {
			$type = $response_array['type'] = "unkown";
		}
		// compact response array
		$return = compact('type', 'status', 'transaction_id', 'litleToken', 'errors', 'data', 'request_raw', 'response_array', 'response_raw', 'url');
		// assign to model if set
		if (is_object($Model)) {
			$Model->lastRequest = $return;
			// log to an array on the model
			if (isset($Model->log) && is_array($Model->log)) {
				$Model->log[] = $return;
			}
		}
		*/
		return $return;
	}
	/**
	* Recursivly look through an array to find a specific key
	* @param string $needle key to find in the array
	* @param array $haystack array to search through
	* @return mixed $output
	*/
	function array_find($needle=null, $haystack=null) {
		if (array_key_exists($needle, $haystack)) {
			return $haystack[$needle];
		}
		foreach ( $haystack as $value ) {
			if (is_array($value)) {
				$found = $this->array_find($needle, $value);
				if ($found!==null) {
					return $found;
				}
			}
		}
		return null;
	}
}
?>