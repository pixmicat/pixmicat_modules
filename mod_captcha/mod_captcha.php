<?php
/*
mod_captcha.php

原始概念來自於各技術討論區，將各種想法混合實做而成。

最主要基礎是此頁內容：
http://jmhoule314.blogspot.com/2006/05/easy-php-captcha-tutorial-today-im.html
加上回應的Kris Knigga修改的成果。
*/

class mod_captcha{
	var $SELF, $CAPTCHA_TMPDIR, $CAPTCHA_WIDTH, $CAPTCHA_HEIGHT, $CAPTCHA_LENGTH, $CAPTCHA_GAP, $CAPTCHA_TEXTY, $CAPTCHA_FONTMETHOD, $CAPTCHA_FONTFACE, $CAPTCHA_ECOUNT;

	function mod_captcha(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', 'mod_captcha'); // Register ModulePage
		$this->SELF = $PMS->getModulePageURL('mod_captcha'); // Self Location

		$this->CAPTCHA_WIDTH = 150; // 圖片寬
		$this->CAPTCHA_HEIGHT = 25; // 圖片高
		$this->CAPTCHA_LENGTH = 6; // 明碼字數
		$this->CAPTCHA_GAP = 20; // 明碼字元間隔
		$this->CAPTCHA_TEXTY = 20; // 字元直向位置
		$this->CAPTCHA_FONTMETHOD = 0; // 字體使用種類 (0: GDF (*.gdf) 1: TrueType Font (*.ttf))
		$this->CAPTCHA_FONTFACE = array('./module/font1.gdf'); // 使用之字型 (可隨機挑選，惟字型種類需要相同不可混用)
		$this->CAPTCHA_ECOUNT = 2; // 圖片混淆用橢圓個數

		AttachLanguage(array($this, '_loadLanguage')); // 載入語言檔
	}

	function getModuleName(){
		return 'mod_captcha : CAPTCHA 驗證圖像機制';
	}

	function getModuleVersionInfo(){
		return '4th.Release.4-dev (v090524)';
	}

	/* 在頁面附加 CAPTCHA 圖像和功能 */
	function autoHookPostForm(&$form){
		global $languages;
		$form .= '<tr><td class="Form_bg"><b>'._T('modcaptcha_captcha').'</b></td><td><img src="'.$this->SELF.'" alt="'._T('modcaptcha_captcha_alt').'" id="chaimg" /><small>(<a href="#" onclick="(function(){var i=document.getElementById(\'chaimg\'),s=i.src;i.src=s+\'&amp;\';})();">'._T('modcaptcha_reload').'</a>)</small><br /><input type="text" name="captchacode" />'._T('modcaptcha_enterword').'</td></tr>'."\n";
	}

	/* 在接收到送出要求後馬上檢查明暗碼是否符合 */
	function autoHookRegistBegin(&$name, &$email, &$sub, &$com, $upfileInfo, $accessInfo){
		global $languages;
		@session_start();
		$MD5code = isset($_SESSION['captcha_dcode']) ? $_SESSION['captcha_dcode'] : false;
		if($MD5code===false || !isset($_POST['captchacode']) || md5(strtoupper($_POST['captchacode'])) !== $MD5code){ // 大小寫不分檢查
			unset($_SESSION['captcha_dcode']);
			error(_T('modcaptcha_worderror'));
		}
	}

	function ModulePage(){
		$this->OutputCAPTCHA(); // 生成暗碼、CAPTCHA圖像
	}

	/* 生成CAPTCHA圖像、明碼、暗碼及內嵌用Script */
	function OutputCAPTCHA(){
		@session_start();

		// 隨機生成明碼、暗碼
		$byteTable = Array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'); // 明碼定義陣列
		$LCode = ''; // 明碼
		for($i = 0; $i < $this->CAPTCHA_LENGTH; $i++) $LCode .= $byteTable[rand(0, count($byteTable) - 1)]; // 隨機抽碼
		$DCode = md5($LCode); // 暗碼 (明碼的MD5)
		$_SESSION['captcha_dcode'] = $DCode; // 暗碼存入 Session

		// 生成暫存圖像
		$captcha = ImageCreateTrueColor($this->CAPTCHA_WIDTH, $this->CAPTCHA_HEIGHT);
		$randcolR = rand(100, 230); $randcolG = rand(100, 230); $randcolB = rand(100, 230); // 隨機色碼值
		$backColor = ImageColorAllocate($captcha, $randcolR, $randcolG, $randcolB); // 背景色
		ImageFill($captcha, 0, 0, $backColor); // 填入背景色
		$txtColor = ImageColorAllocate($captcha, $randcolR - 40, $randcolG - 40, $randcolB - 40); // 文字色
		$rndFontCount = count($this->CAPTCHA_FONTFACE); // 隨機字型數目

		// 打入文字
		for($p = 0; $p < $this->CAPTCHA_LENGTH; $p++){
			if($this->CAPTCHA_FONTMETHOD){ // TrueType 字型
				// 設定旋轉角度 (左旋或右旋)
		    	if(rand(1, 2)==1) $degree = rand(0, 25);
		    	else $degree = rand(335, 360);
				// 圖層, 字型大小, 旋轉角度, X軸, Y軸 (字左下方起算), 字色, 字型, 印出文字
				ImageTTFText($captcha, rand(14, 16), $degree, ($p + 1) * $this->CAPTCHA_GAP, $this->CAPTCHA_TEXTY, $txtColor, $this->CAPTCHA_FONTFACE[rand(0, $rndFontCount - 1)], substr($LCode, $p, 1));
			}else{ // GDF 字型
				$font = ImageLoadFont($this->CAPTCHA_FONTFACE[rand(0, $rndFontCount - 1)]);
				// 圖層, 字型, X軸, Y軸 (字左上方起算), 印出文字, 字色
				ImageString($captcha, $font, ($p + 1) * $this->CAPTCHA_GAP, $this->CAPTCHA_TEXTY - 18, substr($LCode, $p, 1), $txtColor);
			}
		}

		// 混淆用 (畫橢圓)
		for($n = 0; $n < $this->CAPTCHA_ECOUNT; $n++){
	    	ImageEllipse($captcha, rand(1, $this->CAPTCHA_WIDTH), rand(1, $this->CAPTCHA_HEIGHT), rand(50, 100), rand(12, 25), $txtColor);
	    	ImageEllipse($captcha, rand(1, $this->CAPTCHA_WIDTH), rand(1, $this->CAPTCHA_HEIGHT), rand(50, 100), rand(12, 25), $backColor);
		}

		// 輸出圖像
		header('Content-Type: image/png');
		header('Cache-Control: no-cache');
		ImagePNG($captcha);
		ImageDestroy($captcha);
	}

	function _loadLanguage(){
		global $language;
		if(PIXMICAT_LANGUAGE != 'zh_TW' && PIXMICAT_LANGUAGE != 'ja_JP' && PIXMICAT_LANGUAGE != 'en_US') $lang = 'en_US';
		else $lang = PIXMICAT_LANGUAGE;

		if($lang=='zh_TW'){
			$language['modcaptcha_captcha'] = '發文驗證碼';
			$language['modcaptcha_reload'] = '看不懂？重讀';
			$language['modcaptcha_enterword'] = '<small>(請輸入你在圖中看到的文字 大小寫不分)</small>';
			$language['modcaptcha_captcha_alt'] = 'CAPTCHA 驗證碼圖像';
			$language['modcaptcha_worderror'] = '您輸入的驗證碼錯誤！';
		}elseif($lang=='ja_JP'){
			$language['modcaptcha_captcha'] = '画像認証';
			$language['modcaptcha_reload'] = 'リロード';
			$language['modcaptcha_enterword'] = '<br /><small>(画像に表示されている文字を入力してください。大文字と小文字は区別されません。)</small>';
			$language['modcaptcha_captcha_alt'] = 'CAPTCHA画像';
			$language['modcaptcha_worderror'] = '画像認証に失敗しました';
		}elseif($lang=='en_US'){
			$language['modcaptcha_captcha'] = 'Captcha';
			$language['modcaptcha_reload'] = 'Can\'t read? Reload';
			$language['modcaptcha_enterword'] = '<small>(Please enter the words you saw. Case-insensitive.)</small>';
			$language['modcaptcha_captcha_alt'] = 'CAPTCHA Image';
			$language['modcaptcha_worderror'] = 'The words you sent are error!'; 
		}
	}
}
?>