<?php
class mod_code extends ModuleHelper {
	private $brushes;

	public function __construct($PMS) {
		parent::__construct($PMS);
	}
	
	public function getModuleName(){
		return 'mod_code : dp.SyntaxHighlighter Embedded';
	}

	public function getModuleVersionInfo(){
		return '7th.Release.dev (v140607)';
	}

	public function autoHookHead(&$dat){
		$dat .= <<< _EOF_
<link rel="stylesheet" type="text/css" href="module/SyntaxHighlighter.css" />

_EOF_;
	}

	public function autoHookPostInfo(&$postinfo){
		$postinfo .= '<li>程式碼可使用 [code=類型][/code] 以 dp.SyntaxHighlighter 標亮 (<a href="http://code.google.com/p/syntaxhighlighter/wiki/Languages" rel="nofollow noreferrer" target="_blank">類型列表</a>)</li>'."\n";
	}

	public function _textarea($s){
		return '<textarea name="code" cols="48" rows="6" class="'.$s[1].'">'.str_replace('<br />', "\n", $s[2]).'</textarea>';
	}

	public function autoHookThreadPost(&$arrLabels, $post, $isReply){
		extract($post);
		if(strpos($arrLabels['{$COM}'], '[/code]')===false) return;

		$arrLabels['{$COM}'] = preg_replace_callback('/\[code=(\S*?)\](.*?)\[\/code\]/us', array(&$this, '_textarea'), $arrLabels['{$COM}']);
	}

	public function autoHookThreadReply(&$arrLabels, $post, $isReply){
		$this->autoHookThreadPost($arrLabels, $post, $isReply);
	}

	public function autoHookFoot(&$dat){
		$dat .= '<script type="text/javascript" src="module/mod_code.js"></script>
<script type="text/javascript" src="module/shCore.js"></script>
<script type="text/javascript">Tdp.SyntaxHighlighter(\'code\');</script>'."\n";
	}
}//End-Of-Module

