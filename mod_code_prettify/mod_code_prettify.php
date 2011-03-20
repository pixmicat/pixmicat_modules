<?php
class mod_code_prettify{
	// 是否硬轉換 (直接儲存轉換後的結果，減少系統日後輸出時的多次軟轉換負擔)
	// 優點是只需轉換一次，以後不再需要轉換
	var $HARD_TRANSLATE = false;
	// 硬轉換後是否相容舊 mod_code 繼續軟轉換板上已有之 [code] (因硬轉換啟動後預設不再軟轉換處理)
	// 簡單說就是硬/軟轉換並行，這樣硬轉換的好處沒有辦法完全表現出來
	var $MOD_CODE_COMPATIBLE = true;

	function mod_code_prettify() {
		global $PMS;
		if(method_exists($PMS,'addCHP')) {
			$PMS->addCHP('mod_bbbutton_addButtons',array($this,'_addButtons'));
		}
	}

	function _addButtons($txt) {
		$txt .= 'bbbuttons.tags = $.extend({
			 code:{desc:"Insert Code block"},
			},bbbuttons.tags);';
	}

	function getModuleName(){
		return 'mod_code_prettify : google-code-prettify Syntax Highlighting';
	}

	function getModuleVersionInfo(){
		return '5th.Release (v100727)';
	}

	function autoHookHead(&$dat){
		$dat .= '<link rel="stylesheet" type="text/css" href="module/prettify/prettify.css" />'."\n";
	}

	function autoHookPostInfo(&$postinfo){
		$postinfo .= '<li>程式碼可使用 [code][/code] 以 google-code-prettify 標亮 (程式自動判斷語言類別)</li>'."\n";
	}

	// 發文儲存前即進行轉換 (硬轉換)
	function autoHookRegistBeforeCommit(&$name, &$email, &$sub, &$com, &$category, &$age, $dest, $isReply, $imgWH, &$status){
		if(!$this->HARD_TRANSLATE) return; // 選擇不作硬轉換
		if(strpos($com, '[/code]') !== false){
			$com = preg_replace('/\[code(?:=(\S*?))?](?:<br \/>)?(.*?)(?:<br \/>)?\[\/code\]/us',
				'<pre class="prettyprint linenums">$2</pre>', $com);
		}
	}

	// 輸出時才即時轉換 (軟轉換)
	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		if($this->HARD_TRANSLATE && !$this->MOD_CODE_COMPATIBLE) return; // 不需再做軟轉換 (除非啟動相容模式)
		if(strpos($arrLabels['{$COM}'], '[/code]')===false) return;

		$arrLabels['{$COM}'] = preg_replace('/\[code(?:=(\S*?))?](?:<br \/>)?(.*?)(?:<br \/>)?\[\/code\]/us',
			'<pre class="prettyprint linenums">$2</pre>', $arrLabels['{$COM}']);
	}

	function autoHookThreadReply(&$arrLabels, $post, $isReply){
		$this->autoHookThreadPost($arrLabels, $post, $isReply);
	}

	function autoHookFoot(&$dat){
		$dat .= '<script type="text/javascript" src="module/prettify/prettify.js"></script>
<script type="text/javascript">prettyPrint();</script>'."\n";
	}
}
?>