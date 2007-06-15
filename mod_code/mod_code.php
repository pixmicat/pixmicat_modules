<?php
class mod_code{
	var $brushes;

	function mod_code(){
		$this->brushes = array('Php', 'JScript', 'Ruby'); // 選擇載入支援程式刷
	}

	function getModuleName(){
		return 'mod_code';
	}

	function getModuleVersionInfo(){
		return 'mod_code : dp.SyntaxHighlighter Embedded v070615';
	}

	function autoHookHead(&$dat){
		$dat .= <<< _EOF_
<link rel="stylesheet" type="text/css" href="module/SyntaxHighlighter.css" />
<script type="text/javascript" src="module/shCore.js"></script>

_EOF_;
		foreach($this->brushes as $b){ $dat .= '<script type="text/javascript" src="module/shBrush'.$b.'.js"></script>'."\n"; } // 載入刷子檔
	}
	
	function autoHookPostInfo(&$postinfo){
		$postinfo .= '<li>程式碼可使用 [code=類型][/code] 以 dp.SyntaxHighlighter 標亮 (<a href="http://code.google.com/p/syntaxhighlighter/wiki/Languages" rel="_blank">類型列表</a>)</li>'."\n";
	}

	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		extract($post);
		$arrLabels['{$COM}'] = preg_replace('/\[code=(\w*?)\](.*?)\[\/code\]/ue', "'<textarea name=\"code\" cols=\"10\" rows=\"10\" class=\"$1\">'.str_replace('<br />', \"\n\", '$2').'</textarea>'", $arrLabels['{$COM}']);
	}

	function autoHookThreadReply(&$arrLabels, $post, $isReply){
		extract($post);
		$arrLabels['{$COM}'] = preg_replace('/\[code=(\w*?)\](.*?)\[\/code\]/ue', "'<textarea name=\"code\" cols=\"10\" rows=\"10\" class=\"$1\">'.str_replace('<br />', \"\n\", '$2').'</textarea>'", $arrLabels['{$COM}']);
	}

	function autoHookFoot(&$dat){
		$dat .= '<script type="text/javascript">dp.SyntaxHighlighter.HighlightAll(\'code\');</script>'."\n";
	}
}
?>