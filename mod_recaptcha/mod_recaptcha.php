<?php
/*
mod_recaptcha.php
*/
include('recaptchalib.php'); // reCAPTCHA PHP Library

class mod_recaptcha extends ModuleHelper {
	private $KEY_PUBLIC = ''; // Public Key of this site
    private $KEY_PRIVATE = ''; // Private Key of this site

	public function __construct($PMS) {
		parent::__construct($PMS);
	}

	public function getModuleName(){
		return 'mod_recaptcha : reCAPTCHA 驗證圖像機制';
	}

	public function getModuleVersionInfo(){
		return '7th.Release (v140602)';
	}

	/* 在頁面附加 reCAPTCHA 圖像和功能 */
	public function autoHookPostForm(&$txt){
		$txt .= '<tr><th class="Form_bg">驗證碼</th><td>'.recaptcha_get_html($this->KEY_PUBLIC)."<small>(大小寫和符號需留意，兩個文字間用空白分隔)</small></td></tr>\n";
	}

	/* 在接收到送出要求後馬上檢查是否正確 */
	public function autoHookRegistBegin(&$name, &$email, &$sub, &$com, $upfileInfo, $accessInfo){
		if (adminAuthenticate('check') === true ) return; //no captcha for admin mode
		$resp = recaptcha_check_answer($this->KEY_PRIVATE, $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
		if(!$resp->is_valid){ error('reCAPTCHA 驗證碼錯誤！除大小寫須注意之外，標點符號及兩個單字都需輸入 (以空白分隔)'); } // 檢查
	}
}