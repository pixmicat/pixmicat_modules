<?php
class mod_audit extends ModuleHelper {
	private $logname = '.audit'; // 稽核紀錄檔檔名
	private $logLevel= array( // 監察等級
			'Add' => false, // 記錄發文
			'Login' => true, // 記錄後端登入
			'Delete' => true, // 記錄刪除文章
			'AdminFunc' => true // 記錄後端操作
		);

	public function __construct($PMS) {
		parent::__construct($PMS);
		$PMS->addCHP('mod_audit_logcat', array(&$this, 'auditlog'));
	}
 
	public function getModuleName(){
		return 'mod_audit : 稽核記錄功能';
	}

	public function getModuleVersionInfo(){
		return '7h.Release-dev (v140607)';
	}

	public function auditlog($info){
		$t = time() + TIME_ZONE * 3600;
		error_log(getREMOTE_ADDR().' ['.gmdate('y/m/d H:i:s', $t).'] '."$info\n", 3, $this->logname);
	}

	// 記錄登入資訊
	public function autoHookAuthenticate($passwordField, $action, &$result){
		if(!$this->logLevel['Login']) return;
		switch($action){
			case 'admin':
				$this->auditlog('Login: '.($result ? 'Successfully' : 'Denied, Pwd guessed: '.$_POST['pass']));
				break;
			case 'userdel':
				$this->auditlog('UserDelete: '.($result ? 'by Admin' : 'by User'));
				break;
		}
	}

	// 記錄刪除資訊
	public function autoHookPostOnDeletion($delnoArray, $cond){
		if(!$this->logLevel['Delete']) return;
		$this->auditlog('Deleting: No.'.implode(', ', $delnoArray)." [$cond]");
	}

	// 記錄發文資訊
	public function autoHookRegistAfterCommit($lastno, $resto, $name, $email, $sub, $com){
		if(!$this->logLevel['Add']) return;
		$this->auditlog("Adding: No.$lastno (Res:$resto) [Name: $name, Mail: $email, Subject: $sub, Comment: $com]");
	}

	// 記錄後端操作
	public function autoHookAdminFunction($action, &$param, $funcLabel, &$message){
		if(!$this->logLevel['AdminFunc']) return;
		if($action != 'run') return;
		$this->auditlog("AdminFunc: $funcLabel [No.".implode(', ', $param).']');
	}
}//End-Of-Module

