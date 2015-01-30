<?php
/*
mod_recaptcha_v2.php
*/
require_once('recaptchalib.php'); // reCAPTCHA PHP Library

class mod_recaptcha_v2 extends ModuleHelper {
	private $KEY_PUBLIC = '';
	private $KEY_PRIVATE = '';
	private $reCaptcha;

	public function __construct($PMS) {
		parent::__construct($PMS);
		$this->reCaptcha = new ReCaptcha($this->KEY_PRIVATE);
	}

	public function getModuleName(){
		return 'mod_recaptcha_v2 : reCAPTCHA v2';
	}

	public function getModuleVersionInfo(){
		return '7th.Alpha.0 (v150127)';
	}

	public function autoHookHead(&$head, $isReply){
		$head.="<script src='https://www.google.com/recaptcha/api.js?hl=zh-TW'></script>";
	}

	/* 在頁面附加 reCAPTCHA 功能 */
	public function autoHookPostForm(&$txt){
		$txt .= '<tr><th class="Form_bg">驗證</th><td>'.'<div class="g-recaptcha" data-sitekey="'.$this->KEY_PUBLIC.'"></div>'.'</td></tr>\n';
	}

	/* 在接收到送出要求後馬上檢查是否正確 */
	public function autoHookRegistBegin(&$name, &$email, &$sub, &$com, $upfileInfo, $accessInfo){
		if (adminAuthenticate('check') === true ) return; //no captcha for admin mode
		$resp = $this->reCaptcha->verifyResponse($_SERVER['REMOTE_ADDR'], $_POST['g-recaptcha-response']);
		if($resp == null || !$resp->success){ error('reCAPTCHA failed！You are not acting like a human!'); } // 檢查
	}
}

