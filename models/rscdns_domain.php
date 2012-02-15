<?php
class RscdnsDomain extends RscdnsAppModel {
	/*
	Special conditions fields you can search/filter by
	---
	domainName - returns list of all domains matching this name
	domainId - returns single domain if Id is found
	---
	showRecords - 'true'/'false'
	showSubdomains - 'true'/'false'
	---
	deleteSubdomains - 'true'/'false'
	---
	limit - number of results to return; default to 100
	page - which page of results to return
	*/
	
	
	/*
	*
	*/
	function test() {
		$auth = RscdnsUtil::getConfig('auth');
		debug($auth);
		return true;
	}
	
	
	/*
	* Get list of all domains
	*/
	function getDomains() {
		$domains = $this->find('all');
		return $domains;
	}
	
	/**
	* Get this domain details
	*/
	function getDomain($domain_name) {
		$conditions = array('domainName'=>$domain_name);
		$domain = $this->find('all',array('conditions'=>$conditions));
		return $domain;
	}
	
	/**
	* Get domain records
	*/
	function getDomainRecords($zone='') {
		$id = $this->getDomainId($zone);
		$conditions = array('domainId'=>$id,'showRecords'=>'true');
		$records = $this->find('all',array('conditions'=>$conditions));
	}
	
	/**
	* Get domain records and subs
	*/
	function getDomainRecordsSubs() {
		$id = 2105515;
		$conditions = array('domainId'=>$id,'showRecords'=>'true','showSubdomains'=>'true');
		$records = $this->find('all',array('conditions'=>$conditions));
	}
	
	/**
	* Get subdomains
	*/
	function getSubDomains($zone='', $limit=100) {
		$id = $this->getDomainId($zone);
		$conditions = array('domainId'=>$id,'showSubdomains'=>'true','limit'=>$limit);
		$results = $this->find('all',array('conditions'=>$conditions));
		if (isset($results['recordsList']['records'])) {
			foreach ($results['recordsList']['records'] as $subdomain) {
				echo $subdomain['name']." - ".$subdomain['id']." - ".$subdomain['data']."\r\n";
				
			}	
		} else {
			debug($results);
		}
		
	}
	
	// ---------------------
	function getDomainId($domainName) {
		$domains = $this->getDomain($domainName);
		if (isset($domains['totalEntries']) && $domains['totalEntries'] == 1) {
			return $domains['domains'][0]['id'];
		} else {
			//search for it
			foreach ($domains['domains'] as $domain) {
				if ($domain['name'] == $domainName) {
					return $domain['id']; 
				}
			}
		}
		
		return false;
	}
	
	
	
}


?>
