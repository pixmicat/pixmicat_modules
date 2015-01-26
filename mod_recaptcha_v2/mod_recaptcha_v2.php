<?php
/*
mod_recaptcha_v2.php
*/
require_once('recaptchalib.php'); // reCAPTCHA PHP Library
 
class mod_recaptcha_v2{
        var $KEY_PUBLIC, $KEY_PRIVATE;
        var $reCaptcha;
 
        function mod_recaptcha_v2(){
                $this->KEY_PUBLIC = ''; // Public Key of this site
                $this->KEY_PRIVATE = ''; // Private Key of this site
                $this->reCaptcha = new ReCaptcha($this->KEY_PRIVATE);
        }
 
        function getModuleName(){
                return 'mod_recaptcha_v2 : reCAPTCHA v2';
        }
 
        function getModuleVersionInfo(){
                return '0.Alpha.0 (v150126)';
        }
 
        function autoHookHead(string &$head, int $isReply){
                $head.="<script src='https://www.google.com/recaptcha/api.js?hl=zh-TW'></script>";
        }
 
        /* 在頁面附加 reCAPTCHA 功能 */
        function autoHookPostForm(&$txt){
                $txt .= '<tr><th class="Form_bg">驗證</th><td>'.'<div class="g-recaptcha" data-sitekey="'.$this->KEY_PUBLIC.'"></div>'.'</td></tr>\n';
        }
 
        /* 在接收到送出要求後馬上檢查是否正確 */
        function autoHookRegistBegin(&$name, &$email, &$sub, &$com, $upfileInfo, $accessInfo){
                $resp = $this->reCaptcha->verifyResponse($_SERVER['REMOTE_ADDR'], $_POST['g-recaptcha-response']);
                if($resp == null || !$resp->success){ error('reCAPTCHA failed！You are not acting like a human!'); } // 檢查
        }
}
?>
