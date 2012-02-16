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
		$this->__authenticate();
	}
	
	public function __authenticate($a=array()) {
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
			$request = '{"credentials" : {"username" : "'.$auth['username'].'","key" : "'.$auth['key'].'"}}';
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
		return $this->__request($queryData, $Model);
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
	
	public function prepareAPI($queryData) {
		debug($queryData);
		$url = '';
		$uri= '';
		$request = null;
		$method='';
		if (isset($queryData['method']) && $queryData['method'] == 'get') {
			$method = $queryData['method'];
			/*list all domain info*/
			if (!isset($queryData['conditions']['domainId']) && !isset($queryData['conditions']['domainName'])) {
				$uri = '/domains'; 
			}
			/*list single domain info for domainId */
			if (isset($queryData['conditions']['domainId']) && !isset($queryData['conditions']['domainName'])) {
				$uri = '/domains/'.$queryData['conditions']['domainId']; 
			}
			/*list all domain info where matches domainName */
			if (!isset($queryData['conditions']['domainId']) && isset($queryData['conditions']['domainName'])) {
				$uri = '/domains';
				$request['name'] = $queryData['conditions']['domainName']; 
			}
			/*list domain records and or subdomains for domainId*/
			if (isset($queryData['conditions']['domainId'])	&& (isset($queryData['conditions']['showRecords']) || isset($queryData['conditions']['showSubdomains']))) {
				$uri = '/domains/'.$queryData['conditions']['domainId'];
				$request = $queryData['conditions'];
				unset($request['domainId']);
			}
			
		
		} elseif (isset($queryData['method']) && $queryData['method'] == 'post') {
			//add new records
			$method = $queryData['method'];
			die('need to implement saving');
			
			
		} elseif (isset($queryData['method']) && $queryData['method'] == 'put') {
			//update new records
			$method = $queryData['method'];
			
			
		} else {}
		
		
		
		//Auth settings
		$auth = RscdnsUtil::getConfig('auth');
		$token = (isset($auth['token']) ? $auth['token'] : '');
		$url = RscdnsUtil::getConfig('api_url');
		$url .= $uri;
		
		return array('method'=>$method, 'url'=>$url, 'request' => $request, 'token'=>$token);
		
	}
	
	public function addRecord($data) {
		return $this->__request($data);
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
		$uri = '';
		$request = null;
		$method = '';
		
		$data = $this->prepareAPI($data);
		$request = $data['request'];
		$url = $data['url'];
		$method = $data['method'];
		$token = $data['token']; 
		
		if (empty($method)) {
			$errors[] = "Method Unknown";
		}
		
		if (empty($errors)) {
			$this->Http->reset();
			if ($method == 'get') {
				debug($url);
				debug($request);
				//die();
				$response_raw = $this->Http->get($url, $request, array(
				'header'=>array(
					'X-Auth-Token' => $token,
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',)));	
			}
			
			if ($this->Http->response['status']['code'] != 200) {
				debug($this->Http->response['raw']);
				$errors[] = "RscdnsSource: Error: Could not connect to RSC... bad credentials?";
			}
		}
		if (empty($errors)) {
			return json_decode($response_raw,true);
			//$response = $this->parseResponse($response_raw);
			//extract($response);
		} else {
			return $errors;
		}
		return false;
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