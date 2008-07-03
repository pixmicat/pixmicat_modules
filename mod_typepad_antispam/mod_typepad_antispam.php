<?php
/* mod_typepad_antispam : TypePad AntiSpam Protection BETA
 * $Id$
 * $Date$
 */

class mod_typepad_antispam{
	var $THISPAGE, $api_key, $blog, $service_host, $api_host, $plugin_ver, $protocal_ver, $api_port, $recordfile;

	function mod_typepad_antispam(){
		global $PMS;
		$this->THISPAGE = $PMS->getModulePageURL(__CLASS__);
		$PMS->hookModuleMethod('ModulePage', __CLASS__); // Register ModulePage

		// TypePad AntiSpam API key 輸入位置 (申請 http://antispam.typepad.com/info/get-api-key.html)
		$this->api_key = '1234567890ab';

		// 下列若無必要請勿修改
		# Index page location (http:// included)
		$this->blog = fullURL().PHP_SELF2;
		# Base hostname for API requests (API key is always prepended to this)
		$this->service_host = 'api.antispam.typepad.com';
		$this->api_host = $this->api_key.'.'.$this->service_host;
		# Plugin version
		$this->plugin_ver = '1.0';
		# API Protocol version
		$this->protocol_ver = '1.1';
		# Port for API requests to service host
		$this->api_port = 80;
		# Spam count file
		$this->recordfile = 'mod_typepad_antispam.tmp';
	}

	function getModuleName(){
		return 'mod_typepad_antispam : TypePad AntiSpam Protection BETA';
	}

	function getModuleVersionInfo(){
		return '4th.Release.3 (v080703)';
	}

	function ModulePage(){
		global $PMS;
		$dat = '';

		$PMS->hookModuleMethod('Head', array(&$this, 'hookHeadCSS'));
		head($dat);
		$dat .= '
<div id="linkbar">
[<a href="'.PHP_SELF2.'">回到版面</a>]
<div class="bar_reply">TypePad AntiSpam</div>
</div>
<div id="container">
	<ul>
		<li>金鑰狀態: '.($this->_typepadantispam_verify_key() ? 'OK' : 'NG').'</li>
		<li>攔截狀態:
		<div id="typepadantispamwrap">
			<div id="typepadantispamstats">
				<a id="tpaa" href="http://antispam.typepad.com/">
					<div id="typepadantispam1">
						<span id="typepadantispamcount">'.$this->_typepadantispam_spam_count().'</span>
						<span id="typepadantispamsc">spam comments</span>
					</div>
					<div id="typepadantispam2">
						<span id="typepadantispambb"></span>
						<span id="typepadantispama"></span>
					</div>
					<div id="typepadantispam2">
						<span id="typepadantispambb">blocked by</span><br />
						<span id="typepadantispama"><img src="module/typepadantispam-logo.gif" style="border: 0;" /></span>
					</div>
				</a>
			</div>
		</div></li>
	</ul>
</div>
<hr />';
		foot($dat);
		echo $dat;
	}

	/* 掛載樣式表 */
	function hookHeadCSS(&$style, $isReply){
		$style .= '<style type="text/css">
#typepadantispamwrap #tpaa,#tpaa:link,#tpaa:hover,#tpaa:visited,#tpaa:active{text-decoration:none}
#tpaa:hover{border:none;text-decoration:none}
#tpaa:hover #typepadantispam1{display:none}
#tpaa:hover #typepadantispam2,#typepadantispam1{display:block}
#typepadantispam1{padding-top:5px;}
#typepadantispam2{display:none;padding-top:0px;color:#333;}
#typepadantispama{font-size:16px;font-weight:bold;line-height:18px;text-decoration:none;}
#typepadantispamcount{display:block;font:15px Verdana,Arial,Sans-Serif;font-weight:bold;text-decoration:none}
#typepadantispamwrap #typepadantispamstats{background:url(module/typepadantispam.gif) no-repeat top left;border:none;font:11px "Trebuchet MS","Myriad Pro",sans-serif;height:40px;line-height:100%;overflow:hidden;padding:3px 0 8px;text-align:center;width:120px}
</style>
';
	}

	/**
	 * 對遠端服務伺服器送出要求
	 * @return array $response[0]: 檔頭, [1]: 內容
	 */
	function _typepadantispam_http_post($request, $host, $path, $port = 80){
		$http_request  = "POST $path HTTP/1.0\r\n";
		$http_request .= "Host: $host\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=UTF-8\r\n";
		$http_request .= "Content-Length: ".strlen($request)."\r\n";
		$http_request .= "User-Agent: Pixmicat!/".$this->getModuleVersionInfo()." | TypePadAntiSpam/$this->plugin_ver\r\n";
		$http_request .= "\r\n";
		$http_request .= $request;

		$response = '';
		if(false != ($fs = @fsockopen($host, $port, $errno, $errstr, 10))){
			fwrite($fs, $http_request);
			while(!feof($fs))
				$response .= fgets($fs, 1160); // One TCP-IP packet
			fclose($fs);
			$response = explode("\r\n\r\n", $response, 2);
		}
		return $response;
	}

	/**
	 * 檢查 API key 正確性
	 * @return boolean API key 正確性
	 */
	function _typepadantispam_verify_key(){
		$blog = $this->blog;
		$key = $this->api_key;
		$response = $this->_typepadantispam_http_post("key=$key&blog=$blog", $this->service_host, "/$this->protocol_ver/verify-key", $this->api_port);
		return (is_array($response) && isset($response[1]) && $response[1] == 'valid');
	}

	/**
	 * 回傳目前阻擋 Spam 數量
	 * @return int Spam 數量
	 */
	function _typepadantispam_spam_count(){
		return file_exists($this->recordfile) ? intval(file_get_contents($this->recordfile)) : 0;
	}

	/**
	 * 更新 Spam 阻擋數量
	 */
	function _typepadantispam_spam_count_update($newInt){
		$fp = fopen($this->recordfile, 'w');
		flock($fp, LOCK_EX);
		fwrite($fp, $newInt);
		flock($fp, LOCK_UN);
		fclose($fp);
		@chmod($this->recordfile, 0666);
	}

	function autoHookLinksAboveBar(&$link, $pageId, $addinfo=false){
		if($pageId=='status') $link .= ' [<a href="'.$this->THISPAGE.'">Spam 統計</a>]';
	}

	/**
	 * 將文章傳送至遠端服務伺服器檢查是否為 Spam
	 */
	function autoHookRegistBegin(&$name, &$email, &$sub, &$com, $upfileInfo, $accessInfo){
		$comment = array();
		$comment['blog'] = $this->blog;
		$comment['user_ip'] = preg_replace( '/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR']);
		$comment['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
		$comment['referrer'] = $_SERVER['HTTP_REFERER'];
		$comment['comment_type'] = '';
		$comment['comment_author'] = $name;
		$comment['comment_author_email'] = $email;
		$comment['comment_author_url'] = '';
		$comment['comment_content'] = $com;

		// 附加其他 SERVER 環境變數
		$ignore = array('HTTP_COOKIE');
		foreach($_SERVER as $key => $value)
			if(!in_array($key, $ignore)) $comment["$key"] = $value;

		// URL 編碼
		$query_string = '';
		foreach($comment as $key => $data)
			$query_string .= $key.'='.urlencode(stripslashes($data)).'&';

		$response = $this->_typepadantispam_http_post($query_string, $this->api_host, "/$this->protocol_ver/comment-check", $this->api_port);
		if (isset($response[1]) && 'true' == $response[1]){ // 判斷為 Spam
			$this->_typepadantispam_spam_count_update($this->_typepadantispam_spam_count() + 1);
			error('經判斷此發言為 Spam');
		}
	}
}
?>