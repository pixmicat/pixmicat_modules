<?php
/*
mod_readonly.php
*/

class mod_readonly{
	var $READONLY, $ALLOWREPLY;

	function mod_readonly(){
		$this->READONLY = true; // 設置唯讀 (無法發文及回應)
		$this->ALLOWREPLY = false; // 開放回應
	}

	function getModuleName(){
		return 'mod_readonly : 版面唯讀';
	}

	function getModuleVersionInfo(){
		return '4th.Release.2 (v071111)';
	}

	function autoHookRegistBegin(&$name, &$email, &$sub, &$com, $upfileInfo, $accessInfo){
		$pwd = isset($_POST['pwd']) ? $_POST['pwd'] : '';
		$resto = isset($_POST['resto']) ? $_POST['resto'] : 0;

		if($this->ALLOWREPLY && $resto) return; // 開放回應
		if($this->READONLY && $pwd != ADMIN_PASS && ($name != CAP_NAME && $pwd != CAP_PASS)){ error('唯讀模式下不能寫入新文章'); } // 檢查是否唯讀
	}
}
?>