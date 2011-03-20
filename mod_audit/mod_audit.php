<?php
class mod_audit{
	var $logname, $logLevel;

	function mod_audit($PMS){
		$this->logname = '.audit'; // 稽核紀錄檔檔名
		$this->logLevel = array( // 監察等級
			'Add' => false, // 記錄發文
			'Login' => true, // 記錄後端登入
			'Delete' => true, // 記錄刪除文章
			'AdminFunc' => true // 記錄後端操作
		);
		$PMS->addCHP('mod_audit_logcat', array(&$this, '_log'));
	}

	function getModuleName(){
		return 'mod_audit : 稽核記錄功能';
	}

	function getModuleVersionInfo(){
		return '6th.Release-pre (b110320)';
	}

	function _log($info){
		$t = time() + TIME_ZONE * 3600;
		error_log(getREMOTE_ADDR().' ['.gmdate('y/m/d H:i:s', $t).'] '."$info\n", 3, $this->logname);
	}

	// 記錄登入資訊
	function autoHookAuthenticate($passwordField, $action, &$result){
		if(!$this->logLevel['Login']) return;
		switch($action){
			case 'admin':
				$this->_log('Login: '.($result ? 'Successfully' : 'Denied, Pwd guessed: '.$_POST['pass']));
				break;
			case 'userdel':
				$this->_log('UserDelete: '.($result ? 'by Admin' : 'by User'));
				break;
		}
	}

	// 記錄刪除資訊
	function autoHookPostOnDeletion($delnoArray, $cond){
		if(!$this->logLevel['Delete']) return;
		$this->_log('Deleting: No.'.implode(', ', $delnoArray)." [$cond]");
	}

	// 記錄發文資訊
	function autoHookRegistAfterCommit($lastno, $resto, $name, $email, $sub, $com){
		if(!$this->logLevel['Add']) return;
		$this->_log("Adding: No.$lastno (Res:$resto) [Name: $name, Mail: $email, Subject: $sub, Comment: $com]");
	}

	// 記錄後端操作
	function autoHookAdminFunction($action, &$param, $funcLabel, &$message){
		if(!$this->logLevel['AdminFunc']) return;
		if($action != 'run') return;
		$this->_log("AdminFunc: $funcLabel [No.".implode(', ', $param).']');
	}
}
?>