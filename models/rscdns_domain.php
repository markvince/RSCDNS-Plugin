<?php
class RscdnsDomain extends RscdnsAppModel {
	
	
	
	/*
	*
	*/
	function test() {
		$this->__authenticate();
		$auth = RscdnsUtil::getConfig('auth');
		debug($auth);
		return true;
	}
	
	
	/*
	* Get list of domains
	*/
	function getlist($zone='') {
		$this->find();
		
		
	}
	
}


?>
