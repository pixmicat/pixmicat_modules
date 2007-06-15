<?php
/*
mod_captcha.php

原始概念來自於各技術討論區，將一大堆想法混合實做而成。
暫時需要使用者開啟JavaScript才可以啟動。

最主要基礎是此頁內容：
http://jmhoule314.blogspot.com/2006/05/easy-php-captcha-tutorial-today-im.html
加上回應的Kris Knigga修改的成果。

實作法：
利用<script src="xxx.php"></script>來嵌入CAPTCHA，內容有驗證表格、隱藏表格和圖像連結
讀取時先生成CAPTCHA文字並產生MD5碼 (ex: 5beac5b0f75b633d732c1617f42f0590)
利用即時生成的CAPTCHA文字造出暫存的png檔案，並以md5為檔名。
(必要時生成的文字可以加入偽資訊以防止OCR程式)

回傳：
<input type="text" name="5beac5b0f75b633d732c1617f42f0590" />
<input type="hidden" name="pmd5" value="5beac5b0f75b633d732c1617f42f0590" />
<img src="mod_captcha.php?5beac5b0f75b633d732c1617f42f0590.png" />

當mod_captcha.php被要求到圖片時，印出圖片同時刪除圖片，生存週期極短。

使用者看圖片，在5beac5b0f75b633d732c1617f42f0590此格填入在圖片上看到的字。
送出表單後，依["pmd5"]的內容"5beac5b0f75b633d732c1617f42f0590"得知有此變數，
讀取["5beac5b0f75b633d732c1617f42f0590"]變數值並加以產生MD5對照，
假如一樣，便是成功。 (基本上變成MD5後誰也不知道明碼是啥，包括程式本身，明碼只寫在圖片上)


TODO:
- Script 每次讀取就製作圖檔，即使在不必要時候 (ex: 搜尋、後端、系統資訊)，應修正
- 回應時無法插入 <tr>，因為 XHTML 模式沒有 tbody (忠實呈現原始碼內容，但一般模式則會自動增加)
*/

class mod_captcha{
	var $SELF, $CAPTCHA_TMPDIR, $CAPTCHA_WIDTH, $CAPTCHA_HEIGHT, $CAPTCHA_LENGTH, $CAPTCHA_GAP, $CAPTCHA_TEXTY, $CAPTCHA_FONTFACE, $CAPTCHA_ECOUNT;
	var $LANG_CAPTCHA, $LANG_ENTERWORD, $LANG_CAPTCHA_ALT, $LANG_WORDERROR;

	function mod_captcha(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', 'mod_captcha'); // Register ModulePage
		$this->SELF = $PMS->getModulePageURL('mod_captcha'); // Self Location
		$this->CAPTCHA_TMPDIR = './tmp/'; // 圖片暫存資料夾
		$this->CAPTCHA_WIDTH = 150; // 圖片寬
		$this->CAPTCHA_HEIGHT = 25; // 圖片高
		$this->CAPTCHA_LENGTH = 6; // 明碼字數
		$this->CAPTCHA_GAP = 20; // 明碼字元間隔
		$this->CAPTCHA_TEXTY = 20; // 字元直向位置
		$this->CAPTCHA_FONTFACE = array('arial.ttf'); // 使用之 TrueType 字型 (可隨機挑選)
		$this->CAPTCHA_ECOUNT = 2; // 圖片混淆用橢圓個數

		$this->LANG_CAPTCHA = '發文驗證碼'; // Captcha:
		$this->LANG_ENTERWORD = '<small>(請輸入你在圖中看到的文字)</small>'; // Please enter the words you saw
		$this->LANG_CAPTCHA_ALT = 'CAPTCHA 驗證碼圖像'; // CAPTCHA Image
		$this->LANG_WORDERROR = '您輸入的驗證碼錯誤！'; // The words you sent is error.
	}

	function getModuleName(){
		return 'mod_captcha';
	}

	function getModuleVersionInfo(){
		return 'CAPTCHA 驗證圖像機制 v070608';
	}

	/* 在頁面附加 CAPTCHA 圖像和功能 */
	function autoHookHead(&$head){
		$head .= '<!--script type="text/javascript" src="module/jquery-latest.pack.js"></script-->
<script type="text/javascript" src="'.$this->SELF.'"></script>'."\n";
	}

	/* 在接收到送出要求後馬上檢查明暗碼是否符合 */
	function autoHookRegistBegin(&$name, &$email, &$sub, &$com, $upfileInfo, $accessInfo, $POST){
		$MD5code = $POST['pmd5'];
		if(md5(strtoupper($POST[$MD5code])) !== $MD5code) error($this->LANG_WORDERROR); // 大小寫不分檢查
	}

	function ModulePage(){
		$imgFile = isset($_GET['f']) ? $_GET['f'] : ''; // 驗證圖像名稱
		if($imgFile){
			if(file_exists($this->CAPTCHA_TMPDIR.$imgFile)) $this->OutputImage($imgFile); // 如果是要求圖檔便輸出圖檔
		}else{
			$this->OutputCAPTCHA(); // 生成CAPTCHA圖像並輸出Script文字
		}
	}

	/* 生成CAPTCHA圖像、明碼、暗碼及內嵌用Script */
	function OutputCAPTCHA(){
		// 隨機生成明碼、暗碼
		$byteTable = Array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'); // 明碼定義陣列
		$LCode = ''; // 明碼
		for($i = 0; $i < $this->CAPTCHA_LENGTH; $i++) $LCode .= $byteTable[rand(0, count($byteTable) - 1)]; // 隨機抽碼
		$DCode = md5($LCode); // 暗碼 (明碼的MD5)

		// 生成暫存圖像
		$captcha = ImageCreateTrueColor($this->CAPTCHA_WIDTH, $this->CAPTCHA_HEIGHT);
		$randcolR = rand(100, 230); $randcolG = rand(100, 230); $randcolB = rand(100, 230); // 隨機色碼值
		$backColor = ImageColorAllocate($captcha, $randcolR, $randcolG, $randcolB); // 背景色
		ImageFill($captcha, 0, 0, $backColor); // 填入背景色
		$txtColor = ImageColorAllocate($captcha, $randcolR - 40, $randcolG - 40, $randcolB - 40); // 文字色
		$rndFontCount = count($this->CAPTCHA_FONTFACE); // 隨機字型數目

		// 打入文字
		for($p = 0; $p < $this->CAPTCHA_LENGTH; $p++){
			// 設定旋轉角度 (左旋或右旋)
	    	if(rand(1, 2)==1) $degree = rand(0, 25);
	    	else $degree = rand(335, 360);
	    	// 圖層, 字型大小, 旋轉角度, X軸, Y軸, 字色, 字型, 印出文字
			ImageTTFText($captcha, rand(14, 16), $degree, ($p + 1) * $this->CAPTCHA_GAP, $this->CAPTCHA_TEXTY, $txtColor, $this->CAPTCHA_FONTFACE[rand(0, $rndFontCount - 1)], substr($LCode, $p, 1)); // 印出單個字元
		}

		// 混淆用 (畫橢圓)
		for($n = 0; $n < $this->CAPTCHA_ECOUNT; $n++){
	    	ImageEllipse($captcha, rand(1, $this->CAPTCHA_WIDTH), rand(1, $this->CAPTCHA_HEIGHT), rand(50, 100), rand(12, 25), $txtColor);
	    	ImageEllipse($captcha, rand(1, $this->CAPTCHA_WIDTH), rand(1, $this->CAPTCHA_HEIGHT), rand(50, 100), rand(12, 25), $backColor);
		}

		// 輸出圖像
		ImagePNG($captcha, $this->CAPTCHA_TMPDIR.$DCode.'.png');
		ImageDestroy($captcha);

		// 輸出Script
		echo 'jQuery(function($){ $("td.Form_bg:last").parent().after("<tr><td class=\"Form_bg\"><b>'.$this->LANG_CAPTCHA.'</b></td><td><input type=\"hidden\" name=\"pmd5\" value=\"'.$DCode.'\" /><img src=\"'.$this->SELF.'&amp;f='.$DCode.'.png\" alt=\"'.$this->LANG_CAPTCHA_ALT.'\" /><br /><input type=\"text\" name=\"'.$DCode.'\" />'.$this->LANG_ENTERWORD.'</td></tr>") });';
	}

	/* 應要求取出圖檔印出，同時刪除暫存圖檔 */
	function OutputImage($imgsrc){
		$imgsrc = $this->CAPTCHA_TMPDIR.$imgsrc; // 圖檔完整路徑
		header('Content-Type: image/png'); // 設定檔頭
		readfile($imgsrc);
		unlink($imgsrc);
	}
}
?>