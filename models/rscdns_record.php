<?php

class RscdnsRecord extends RscdnsAppModel {
	public $name = 'RscdnsRecord';
	public $useTable = false;
	
	public $_schema = array(
		'id' => array('type' => 'string', 'length' => '25', 'comment' => 'RSC identifier'),
		'domainId' => array('type' => 'integer', 'length' => '11', 'comment' => 'required to associate record with correct zone'),
		'name' => array('type' => 'string', 'length' => '50', 'comment' => 'subdomain or record name'),
		'type' => array('type' => 'string', 'length' => '15', 'comment' => 'A, CNAME, MX, TXT, etc'),
		'data' => array('type' => 'string', 'length' => '100', 'comment' => 'The records value'),
		'ttl' => array('type' => 'integer', 'length' => '8', 'comment' => 'Time To Live'),
		);
	
	
	
	//Get Record
	function getRecord ($zone, $name, $type='A') {
		$records = $this->getRecords($zone);
		foreach ($records['records'] as $record) {
			if ($record['name'] == $name && $record['type'] == $type) {
				return $record;
			}
		}
		return array();
	}
	
	//Get Records For Domain
	function getRecords($zone) {
		$domainId = $this->getDomainId($zone);
		$conditions = array('domainId'=>$domainId);
		$records = $this->find('all',array('conditions'=>$conditions));
		return $records;
	}
	
	//Add Single Record
	function addRecord($zone, $name, $type, $data, $ttl=3600) {
		$domainId = $this->getDomainId($zone);
		$recordData = array(
			'id'=>null,
			'domainId' => $domainId,
			'name'=> $name,
			'type'=>$type,
			'data' => $data,
			'ttl' => $ttl
			);
		$result = $this->save($recordData);
		return $result;
	}
	
	//Update single record
	function updateRecord($zone, $name, $type, $data, $ttl=3600) {
		$recordId = $this->getRecordId($zone, $name, $type);
		$domainId = $this->getDomainId($zone);
		$recordData = array(
			'id'=>$recordId,
			'domainId' => $domainId,
			'name'=> $name,
			'type'=>$type,
			'data' => $data,
			'ttl' => $ttl
			); 
		return $this->save($recordData);		
	}
	
	//Delete single record
	function deleteRecord($zone, $name, $type) {
		$recordId = $this->getRecordId($zone, $name, $type);
		return $this->delete($recordId);
	}
	
	//Get RecordId
	function getRecordId($zone, $name, $type) {
		$record = $this->getRecord($zone, $name, $type);
		return (isset($record['id']) ? $record['id'] : '');
	}
	
	
	//Get DomainId
	function getDomainId($zone, $refresh=false) {
		$id = RscdnsUtil::getConfig('domainId');
		if (empty($id) || $refresh) {
			$id = ClassRegistry::init('Rscdns.RscdnsDomain')->getDomainId($zone);
			RscdnsUtil::setConfig('domainId',$id);
			return $id;
		} else {
			return $id;
		}
		
	}
	
		
}

?>
