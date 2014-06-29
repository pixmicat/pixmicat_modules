<?php
/*
mod_readonly.php
*/
class mod_readonly extends ModuleHelper {
	private $READONLY  = true; // 設置唯讀 (無法發文及回應)
	private $ALLOWREPLY = false; // 開放回應

	public function __construct($PMS) {
		parent::__construct($PMS);
	}

	public function getModuleName(){
		return 'mod_readonly : 版面唯讀';
	}

	public function getModuleVersionInfo(){
		return '7th.Release.dev (v140606)';
	}

	public function autoHookRegistBegin(&$name, &$email, &$sub, &$com, $upfileInfo, $accessInfo){
		$pwd = isset($_POST['pwd']) ? $_POST['pwd'] : '';
		$resto = isset($_POST['resto']) ? $_POST['resto'] : 0;

		if($this->ALLOWREPLY && $resto) return; // 開放回應
		if($this->READONLY && !adminAuthenticate('check') && ($name != CAP_NAME && $pwd != CAP_PASS)){ error('唯讀模式下不能寫入新文章'); } // 檢查是否唯讀
	}
}//End-Of-Module
