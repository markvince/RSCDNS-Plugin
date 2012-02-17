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
		$queryData['method'] = 'get';
		return $this->__request($queryData, $Model);
	}
	/**
	* Create a new zone entry
	*/
	public function create(&$Model, $fields = array(), $values = array()) {
		$data = array_combine($fields, $values);
		$data['method'] = 'post';
		return $this->__request($data, $Model);
	}
	/**
	* Modify zone entry
	*/
	public function update(&$Model, $fields = null, $values = null) {
		$data = array_combine($fields, $values);
		$data['method'] = 'put';
		return $this->__request($data, $Model);
	}
	/**
	* Delete zone entry
	*/
	public function delete(&$Model, $id = null) {
		$recordId = $id["$Model->alias.id"];
		$data = array('method'=>'delete');
		if (isset($id["$Model->alias.id"])) {
			$data['id'] = $id["$Model->alias.id"];	
		}
		return $this->__request($data, $Model);
	}
	
	public function prepareAPI($queryData,&$Model=null) {
		$url = '';
		$uri= '';
		$request = null;
		$method = $queryData['method'];
				
		if ($Model->table == 'rscdns_domains') {
			if ($method == 'get') {
				if (!isset($queryData['conditions']['domainId']) && isset($queryData['conditions']['domainName'])) {
					$uri = '/domains';
					$request['name'] = $queryData['conditions']['domainName']; 
				}	
			}
		}
		
		if ($Model->table == 'rscdns_records') {
			$allowed_keys = array('name','data','type','ttl', 'priority');
			//get  /domains/{domainId}/records - List all records for a domain
			//post /domains/{domainId}/records - Add record(s) for a domain
			//put  /domains/{domainId}/records - Modify the configuration for records in the domain
			
			//get /domains/{domainId}/records/{recordId} - List details for a specific record in the specified domain
			//put /domains/{domainId}/records/{recordId} - Modify the configuration for a record in the domain
			//delete /domains/{domainId}/records/{recordId} - Remove a record from the domain.
			
			//delete /domains/{domainId}/records?id={recordId1}&id={recordId2} - Remove records from the domain.
			
			$domainId = RscdnsUtil::getConfig('domainId');
			if (empty($domainId) && isset($queryData['conditions']['domainId'])) {
				$domainId = $queryData['conditions']['domainId'];
			} elseif (empty($domainId) && !isset($queryData['conditions']['domainId'])) {
				unset($domainId);
			}
			//debug($domainId);
			//debug($queryData);
			
			if (isset($queryData['conditions'][$Model->alias.'id'])) {
				$queryData['conditions']['id'] = $queryData['conditions'][$Model->alias.'id']; 
			}
			
			if ($method == 'get') {
				if (isset($domainId) && isset($queryData['conditions']['id'])) {
					$uri = '/domains/'.$domainId.'/records/'.$queryData['conditions']['id'];
					$request=array();
				} elseif (isset($domainId) && !isset($queryData['conditions']['recordId'])) {
					$uri = '/domains/'.$domainId.'/records';
					$request=array();
				}
			} elseif ($method == 'post') {
				if (isset($domainId)) {
					$uri = '/domains/'.$domainId.'/records';
					$request['records'][] = $queryData;
					foreach ($request['records'] as $reqkey => $reqval) {
						foreach ($reqval as $reckey => $recval) {
							if (!in_array($reckey, $allowed_keys)) {
								unset($request['records'][$reqkey][$reckey]);
							}	
						}
					}
				}
				
			}  elseif ($method == 'put') {
				if (isset($domainId) && isset($queryData['id'])) {
					$uri = '/domains/'.$domainId.'/records/'.$queryData['id'];
					$request = $queryData;
					foreach ($request as $reckey => $recval) {
						if (!in_array($reckey, $allowed_keys)) {
							unset($request[$reckey]);
						}	
					}
				}
			} elseif ($method == 'delete') {
				if (isset($domainId) && isset($queryData['id'])) {
					$uri = '/domains/'.$domainId.'/records/'.$queryData['id'];
					$request = array();
				}
			}
		}
		
		//Auth settings
		$auth = RscdnsUtil::getConfig('auth');
		$token = (isset($auth['token']) ? $auth['token'] : '');
		$url = RscdnsUtil::getConfig('api_url');
		$url .= $uri;
		
		return array('method'=>$method, 'url'=>$url, 'request' => $request, 'token'=>$token);
		
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
		
		$data = $this->prepareAPI($data,$Model);
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
				$response_raw = $this->Http->get($url, $request, array(
				'header'=>array(
					'X-Auth-Token' => $token,
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',)));	
			}
			
			if ($method == 'post') {
				$request = json_encode($request);
				$request = str_replace(':',' : ',$request);
				$response_raw = $this->Http->post($url, $request, array(
				'header'=>array(
					'X-Auth-Token' => $token,
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',)));
			} 
			
			if ($method == 'put') {
				$request = json_encode($request);
				$request = str_replace(':',' : ',$request);
				$response_raw = $this->Http->put($url, $request, array(
				'header'=>array(
					'X-Auth-Token' => $token,
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',)));
			}
			
			if ($method == 'delete') {
				$request = json_encode($request);
				$request = str_replace(':',' : ',$request);
				debug($url);
				debug($request);
				//die();
				$response_raw = $this->Http->delete($url, $request, array(
				'header'=>array(
					'X-Auth-Token' => $token,
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',)));
			}
			
			if ($this->Http->response['status']['code'] != 200) {
				print_r($this->Http->response['raw']);
				$errors[] = "RscdnsSource: Error: Could not connect to RSC... bad credentials?";
			}
		}
		if (empty($errors)) {
			return json_decode($response_raw,true);
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