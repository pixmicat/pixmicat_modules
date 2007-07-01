<?php
/*
mod_readonly.php
*/

class mod_readonly{
	var $READONLY,$ALLOWREPLY;

	function mod_readonly(){
		$this->READONLY = true; // 設置唯讀
		$this->ALLOWREPLY = false; // 開放回文?
	}

	function getModuleName(){
		return 'mod_readonly';
	}

	function getModuleVersionInfo(){
		return '版面唯讀 v0700701';
	}

	function autoHookRegistBegin(&$name, &$email, &$sub, &$com, $upfileInfo, $accessInfo, $POST){
		$pwd = isset($POST['pwd']) ? $POST['pwd'] : '';
		$resto = isset($POST['resto']) ? $POST['resto'] : 0;

		if($this->ALLOWREPLY && $resto) return; // 開放回文
		if($this->READONLY && $pwd != ADMIN_PASS && ($name != CAP_NAME && $pwd != CAP_PASS)){ error('唯讀模式下不能寫入新文章'); } // 檢查是否唯讀
	}
}
?>