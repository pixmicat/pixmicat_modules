<?php
class mod_csrf_prevent{
	function getModuleName(){
		return __CLASS__.' : 防止偽造跨站請求 (CSRF)';
	}

	function getModuleVersionInfo(){
		return 'b110403';
	}

	function autoHookRegistBegin(&$name, &$email, &$sub, &$com, $upfileInfo, $accessInfo, $isReply){
		$CSRFdetectd = false;
		/* 檢查 HTTP_REFERER (防止跨站 form)
		 *  1. 無 HTTP_REFERER
		 *  2. HTTP_REFERER 不是此網域
		 */
		if(!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], fullURL()) !== 0)
			$CSRFdetectd = true;

		if($CSRFdetectd) error('CSRF detected!');
	}
}
?>