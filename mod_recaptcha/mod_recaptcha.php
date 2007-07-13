<?php
/*
mod_recaptcha.php
*/
include('recaptchalib.php'); // reCAPTCHA PHP Library

class mod_recaptcha{
	var $KEY_PUBLIC, $KEY_PRIVATE;

	function mod_recaptcha(){
		$this->KEY_PUBLIC = ''; // Public Key of this site
		$this->KEY_PRIVATE = ''; // Private Key of this site
	}

	function getModuleName(){
		return 'mod_recaptcha';
	}

	function getModuleVersionInfo(){
		return 'reCAPTCHA 驗證圖像機制 v070713';
	}

	/* 在頁面附加 reCAPTCHA 圖像和功能 */
	function autoHookPostForm(&$txt){
		global $recaptcha_api_server;
		$recaptcha_api_server = 'http://api.recaptcha.net';
		$txt .= '<tr><th class="Form_bg">驗證碼</th><td>'.recaptcha_get_html($this->KEY_PUBLIC)."<small>(大小寫和符號需留意，兩個文字間用空白分隔)</small></td></tr>\n";
	}

	/* 在接收到送出要求後馬上檢查是否正確 */
	function autoHookRegistBegin(&$name, &$email, &$sub, &$com, $upfileInfo, $accessInfo){
		global $recaptcha_verify_server;
		$recaptcha_verify_server = 'api-verify.recaptcha.net';
		$resp = recaptcha_check_answer($this->KEY_PRIVATE, $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
		if(!$resp->is_valid){ error('reCAPTCHA 驗證碼錯誤！除大小寫須注意之外，標點符號及兩個單字都需輸入 (以空白分隔)'); } // 檢查
	}
}
?>